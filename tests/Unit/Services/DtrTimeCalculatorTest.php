<?php

namespace Tests\Unit\Services;

use App\Services\DtrTimeCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DtrTimeCalculatorTest extends TestCase
{
    public function test_returns_work_minutes_after_subtracting_break(): void
    {
        $calculator = new DtrTimeCalculator;

        $minutes = $calculator->calculate([
            'time_in' => '08:15',
            'break_start' => '12:00',
            'break_end' => '12:45',
            'time_out' => '17:00',
        ]);

        $this->assertSame(480, $minutes);
    }

    public function test_rejects_time_out_before_time_in(): void
    {
        $calculator = new DtrTimeCalculator;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Time out must be later than time in.');

        $calculator->calculate([
            'time_in' => '17:00',
            'time_out' => '08:00',
        ]);
    }
}
