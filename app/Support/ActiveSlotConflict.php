<?php

namespace App\Support;

use Illuminate\Database\QueryException;

final class ActiveSlotConflict
{
    public static function matches(
        QueryException $exception,
        string $constraint,
        string $qualifiedColumn,
    ): bool {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) $exception->getCode();

        return in_array($sqlState, ['19', '23000', '23505'], true)
            && (
                str_contains($message, strtolower($constraint))
                || str_contains($message, strtolower($qualifiedColumn))
            );
    }
}
