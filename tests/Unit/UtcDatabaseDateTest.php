<?php

namespace Tests\Unit;

use App\Support\UtcDatabaseDate;
use PHPUnit\Framework\TestCase;

class UtcDatabaseDateTest extends TestCase
{
    public function test_it_labels_database_timestamp_as_utc(): void
    {
        $date = UtcDatabaseDate::parseRaw('2026-09-30 23:46:28');

        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('2026-09-30T23:46:28+00:00', UtcDatabaseDate::toIso8601('2026-09-30 23:46:28'));
    }

    public function test_it_returns_null_for_missing_timestamp(): void
    {
        $this->assertNull(UtcDatabaseDate::parseRaw(null));
        $this->assertNull(UtcDatabaseDate::toIso8601(''));
    }
}
