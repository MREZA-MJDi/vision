<?php

namespace Tests\Unit;

use App\Support\JalaliDate;
use PHPUnit\Framework\TestCase;

class JalaliDateTest extends TestCase
{
    public function test_converts_the_nowruz_boundary_to_solar_hijri(): void
    {
        $this->assertSame('۱۴۰۳/۰۱/۰۱', JalaliDate::format('2024-03-20 12:30:00', 'Y/m/d'));
    }

    public function test_formats_timestamps_and_null_values(): void
    {
        $this->assertSame('۱۴۰۲/۱۲/۲۹ ۰۸:۰۵', JalaliDate::format('2024-03-19 08:05:00', 'Y/m/d H:i'));
        $this->assertSame('—', JalaliDate::format(null));
    }
}
