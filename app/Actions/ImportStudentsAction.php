<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use App\Rules\StudentId;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class ImportStudentsAction
{
    private const REQUIRED_HEADERS = ['name', 'email', 'student_id', 'program', 'section'];

    /**
     * @return array{headers: list<string>, rows: list<array{line: int, data: array<string, string>, errors: list<string>, valid: bool}>}
     */
    public function preview(UploadedFile $file): array
    {
        $stream = fopen($file->getRealPath(), 'rb');
        if ($stream === false) {
            throw new RuntimeException('The uploaded CSV could not be opened.');
        }

        try {
            $rawHeaders = fgetcsv($stream);
            if (! is_array($rawHeaders)) {
                throw new RuntimeException('The CSV file is empty.');
            }

            $headers = array_map(
                static fn (mixed $header): string => Str::of((string) $header)->replace("\xEF\xBB\xBF", '')->trim()->lower()->toString(),
                $rawHeaders,
            );
            $missingHeaders = array_values(array_diff(self::REQUIRED_HEADERS, $headers));

            if ($missingHeaders !== []) {
                throw new RuntimeException('Missing CSV columns: '.implode(', ', $missingHeaders).'.');
            }

            $rows = [];
            $seenEmails = [];
            $seenStudentIds = [];
            $line = 1;

            while (($values = fgetcsv($stream)) !== false) {
                $line++;

                if ($line > 501) {
                    throw new RuntimeException('A single import is limited to 500 student rows.');
                }

                $values = array_pad($values, count($headers), '');
                $data = [];
                foreach (self::REQUIRED_HEADERS as $requiredHeader) {
                    $index = array_search($requiredHeader, $headers, true);
                    $data[$requiredHeader] = trim((string) ($values[$index === false ? 0 : $index] ?? ''));
                }

                $data['email'] = Str::lower($data['email']);
                if (collect($data)->every(static fn (string $value): bool => $value === '')) {
                    continue;
                }

                $validator = Validator::make($data, $this->rules());
                $errors = $validator->errors()->all();

                if (isset($seenEmails[$data['email']])) {
                    $errors[] = "Email is duplicated on line {$seenEmails[$data['email']]}.";
                }

                if (isset($seenStudentIds[$data['student_id']])) {
                    $errors[] = "Student ID is duplicated on line {$seenStudentIds[$data['student_id']]}.";
                }

                $seenEmails[$data['email']] = $line;
                $seenStudentIds[$data['student_id']] = $line;
                $rows[] = [
                    'line' => $line,
                    'data' => $data,
                    'errors' => array_values(array_unique($errors)),
                    'valid' => $errors === [],
                ];
            }

            return ['headers' => self::REQUIRED_HEADERS, 'rows' => $rows];
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return array{created: int, reset_links_sent: int, reset_links_failed: int}
     */
    public function import(array $rows): array
    {
        $students = DB::transaction(function () use ($rows): array {
            $created = [];

            foreach ($rows as $row) {
                $validated = Validator::make($row, $this->rules())->validate();
                $created[] = User::query()->create([
                    ...$validated,
                    'password' => Str::password(40),
                    'offline_storage_key' => (string) Str::uuid(),
                    'role' => UserRole::Student,
                    'is_active' => true,
                ]);
            }

            return $created;
        });

        $linksSent = 0;
        foreach ($students as $student) {
            try {
                if (Password::sendResetLink(['email' => $student->email]) === Password::RESET_LINK_SENT) {
                    $linksSent++;
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return [
            'created' => count($students),
            'reset_links_sent' => $linksSent,
            'reset_links_failed' => count($students) - $linksSent,
        ];
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'student_id' => ['required', 'string', 'max:9', new StudentId, Rule::unique('users', 'student_id')],
            'program' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:100'],
        ];
    }
}
