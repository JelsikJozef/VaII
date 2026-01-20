<?php

namespace Framework\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;

// AI-GENERATED: Unified date/time formatter (GitHub Copilot / ChatGPT), 2026-01-20
class DateFormatter
{
    /**
     * Format various date/time inputs into the unified "d. m. Y, HH:MM" style.
     */
    public static function formatDateTime(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        try {
            if ($value instanceof DateTimeInterface) {
                $dt = DateTimeImmutable::createFromInterface($value);
            } else {
                $stringValue = trim((string)$value);
                if ($stringValue === '') {
                    return '—';
                }

                $dt = new DateTimeImmutable($stringValue);
            }
        } catch (Exception) {
            return is_string($value) ? $value : (string)$value;
        }

        $tzId = date_default_timezone_get();
        if (is_string($tzId) && $tzId !== '') {
            try {
                $dt = $dt->setTimezone(new DateTimeZone($tzId));
            } catch (Exception) {
                // keep original timezone if invalid
            }
        }

        return $dt->format('j. n. Y, H:i');
    }
}
