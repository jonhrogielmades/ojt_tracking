<?php

namespace App\Services;

class EntryFingerprint
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function dtr(array $data): string
    {
        return $this->hash([
            'work_date' => (string) $data['work_date'],
            'time_in' => substr((string) $data['time_in'], 0, 5),
            'break_start' => $this->timeOrNull($data['break_start'] ?? null),
            'break_end' => $this->timeOrNull($data['break_end'] ?? null),
            'time_out' => substr((string) $data['time_out'], 0, 5),
            'notes' => $this->textOrNull($data['notes'] ?? null),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function activity(array $data): string
    {
        return $this->hash([
            'activity_date' => (string) $data['activity_date'],
            'tasks_completed' => $this->textOrNull($data['tasks_completed'] ?? null),
            'skills_tools' => $this->textOrNull($data['skills_tools'] ?? null),
            'lessons_learned' => $this->textOrNull($data['lessons_learned'] ?? null),
            'challenges_notes' => $this->textOrNull($data['challenges_notes'] ?? null),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hash(array $payload): string
    {
        return hash('sha256', json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    private function timeOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 5);
    }

    private function textOrNull(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return str_replace(["\r\n", "\r"], "\n", trim((string) $value));
    }
}
