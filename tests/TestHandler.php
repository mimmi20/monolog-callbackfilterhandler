<?php declare(strict_types=1);

namespace Bartlett\Monolog\Handler\Tests;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\TestHandler as BaseTestHandler;

use Monolog\LogRecord;
use function array_key_exists;
use function array_keys;
use function count;
use function strpos;

/**
 * Features included in dev-master branch but not yet released as a stable version
 * @see https://github.com/Seldaek/monolog/pull/529
 *
 * And 2 new features not yet proposed
 * @see hasOnlyRecordsThatContains()
 * @see hasOnlyRecordsMatching()
 */
class TestHandler extends BaseTestHandler
{
    public function hasEmergencyThatContains($message): bool
    {
        return $this->hasRecordThatContains($message, Level::Emergency);
    }

    public function hasAlertThatContains($message): bool
    {
        return $this->hasRecordThatContains($message, Level::Alert);
    }

    public function hasCriticalThatContains($message): bool
    {
        return $this->hasRecordThatContains($message, Level::Critical);
    }

    public function hasErrorThatContains($message): bool
    {
        return $this->hasRecordThatContains($message, Level::Error);
    }

    public function hasWarningThatContains($message): bool
    {
        return $this->hasRecordThatContains($message, Level::Warning);
    }

    public function hasNoticeThatContains($message): bool
    {
        return $this->hasRecordThatContains($message, Level::Notice);
    }

    public function hasInfoThatContains($message): bool
    {
        return $this->hasRecordThatContains($message, Level::Info);
    }

    public function hasDebugThatContains($message): bool
    {
        return $this->hasRecordThatContains($message, Level::Debug);
    }

    public function hasRecordThatContains(string $message, Level $level): bool
    {
        if (!isset($this->recordsByLevel[$level->value])) {
            return false;
        }

        foreach ($this->recordsByLevel[$level->value] as $rec) {
            if (str_contains($rec['message'], $message)) {
                return true;
            }
        }

        return false;
    }

    // new feature not yet proposed
    public function hasOnlyRecordsThatContains(string $message, Level $level): bool
    {
        $levels = array_keys($this->recordsByLevel);

        if (count($levels) !== 1) {
            return false;
        }

        return $this->hasRecordThatContains($message, $level);
    }

    // new feature not yet proposed
    public function hasOnlyRecordsMatching(array $pattern): bool
    {
        foreach ($this->records as $record) {
            foreach (array_keys($pattern) as $key) {
                if (!property_exists($record, $key)) {
                    return false;
                }

                if ($record->$key !== $pattern[$key]) {
                    return false;
                }
            }
        }

        return true;
    }
}
