<?php declare(strict_types=1);

namespace Bartlett\Monolog\Handler\Tests;

use Monolog\DateTimeImmutable;
use Monolog\Level;
use Monolog\Logger;

use DateTime;
use Monolog\LogRecord;
use function array_combine;
use function microtime;
use function sprintf;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array<mixed> $context
     */
    protected function getRecord(int|string|Level $level = Level::Warning, string|\Stringable $message = 'test', array $context = [], string $channel = 'test'): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable('now'),
            channel: $channel,
            level: $level,
            message: $message,
            context: $context,
            extra: [],
        );
    }

    /**
     * @return LogRecord[]
     */
    protected function getMultipleRecords(): array
    {
        return [
            $this->getRecord(Level::Debug, 'debug message 1'),
            $this->getRecord(Level::Debug, 'debug message 2'),
            $this->getRecord(Level::Info, 'information'),
            $this->getRecord(Level::Warning, 'warning'),
            $this->getRecord(Level::Error, 'error'),
        ];
    }

    /**
     * Data provider that produce a suite of records in level order.
     *
     * @return LogRecord[][]
     * @see CallbackFilterHandlerTest::testIsHandling()
     * @see CallbackFilterHandlerTest::testIsHandlingLevel()
     * @see CallbackFilterHandlerTest::testHandleProcessOnlyNeededLevels()
     * @see CallbackFilterHandlerTest::testHandleProcessAllMatchingRules()
     */
    public function provideSuiteRecords(): array
    {
        $dataset = [];

        foreach (Level::VALUES as $levelCode) {
            $level = Level::fromValue($levelCode);

            $dataset[] = [$this->getRecord($level, sprintf('sample of %s message', $level->getName()))];
        }

        return $dataset;
    }

    /**
     * Data provider that produce a suite of records for bubble respect.
     *
     * @return LogRecord[]
     * @see CallbackFilterHandlerTest::testHandleRespectsBubble()
     */
    public function provideSuiteBubbleRecords(): array
    {
        return [
            [$this->getRecord(Level::Notice)],
            [$this->getRecord()],
        ];
    }
}
