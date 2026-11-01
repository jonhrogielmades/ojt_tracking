<?php

namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;

class DtrTimeCalculator
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function calculate(array $data): int
    {
        $timeIn = $this->time((string) $data['time_in']);
        $timeOut = $this->time((string) $data['time_out']);
        $totalMinutes = (int) (($timeOut->getTimestamp() - $timeIn->getTimestamp()) / 60);

        if ($totalMinutes <= 0) {
            throw new InvalidArgumentException('Time out must be later than time in.');
        }

        if (! empty($data['break_start']) && ! empty($data['break_end'])) {
            $breakStart = $this->time((string) $data['break_start']);
            $breakEnd = $this->time((string) $data['break_end']);
            $breakMinutes = (int) (($breakEnd->getTimestamp() - $breakStart->getTimestamp()) / 60);

            if ($breakStart <= $timeIn || $breakEnd >= $timeOut || $breakMinutes <= 0) {
                throw new InvalidArgumentException('The break must be within the work period.');
            }

            $totalMinutes -= $breakMinutes;
        }

        if ($totalMinutes <= 0) {
            throw new InvalidArgumentException('The calculated working time must be greater than zero.');
        }

        return $totalMinutes;
    }

    private function time(string $value): DateTimeImmutable
    {
        $time = DateTimeImmutable::createFromFormat('!H:i', substr($value, 0, 5));

        if ($time === false) {
            throw new InvalidArgumentException('A supplied time is invalid.');
        }

        return $time;
    }
}
