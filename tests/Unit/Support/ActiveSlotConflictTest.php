<?php

namespace Tests\Unit\Support;

use App\Support\ActiveSlotConflict;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ActiveSlotConflictTest extends TestCase
{
    /** @return array<string, array{string, string, bool}> */
    public static function conflictMessages(): array
    {
        return [
            'mysql constraint' => [
                'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry for key academic_terms_single_active',
                '23000',
                true,
            ],
            'sqlite column' => [
                'SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: academic_terms.active_slot',
                '23000',
                true,
            ],
            'unrelated unique key' => [
                'SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: academic_terms.code',
                '23000',
                false,
            ],
        ];
    }

    #[DataProvider('conflictMessages')]
    public function test_it_only_matches_the_named_active_slot_constraint(
        string $message,
        string $code,
        bool $expected,
    ): void {
        $previous = new PDOException($message, (int) $code);
        $exception = new QueryException('sqlite', 'insert into academic_terms', [], $previous);

        $this->assertSame(
            $expected,
            ActiveSlotConflict::matches(
                $exception,
                'academic_terms_single_active',
                'academic_terms.active_slot',
            ),
        );
    }
}
