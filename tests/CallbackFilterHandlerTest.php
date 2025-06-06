<?php

/**
 * This file is part of the mimmi20/monolog-callbackfilterhandler package.
 *
 * Copyright (c) 2022-2025, Thomas Mueller <mimmi20@live.de>
 * Copyright (c) 2015-2021, Laurent Laville <pear@laurent-laville.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Mimmi20\Monolog\Handler\Tests;

use Mimmi20\Monolog\Handler\CallbackFilterHandler;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Monolog\Processor\UidProcessor;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Exception;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use RuntimeException;

use function in_array;
use function mb_strtolower;
use function preg_match;
use function sprintf;
use function ucfirst;

final class CallbackFilterHandlerTest extends AbstractTestCase
{
    /**
     * Filter events on standard log level (without restriction).
     *
     * @throws Exception
     * @throws RuntimeException
     */
    #[DataProvider('provideSuiteRecords')]
    public function testIsHandling(LogRecord $record): void
    {
        $filters = [];

        $test = $this->getMockBuilder(HandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $test->expects(self::never())
            ->method('isHandling');
        $test->expects(self::never())
            ->method('handle');
        $test->expects(self::never())
            ->method('handleBatch');
        $test->expects(self::never())
            ->method('close');

        $handler = new CallbackFilterHandler($test, $filters);

        self::assertTrue($handler->isHandling($record));
        self::assertTrue($handler->getBubble());
        self::assertSame(Level::Debug, $handler->getLevel());
    }

    /**
     * Filter events on standard log level (greater or equal than WARNING).
     *
     * @throws Exception
     * @throws RuntimeException
     */
    #[DataProvider('provideSuiteRecords')]
    public function testIsHandlingLevel(LogRecord $record): void
    {
        $filters = [];
        $testlvl = Level::Warning;

        $test = $this->getMockBuilder(HandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $test->expects(self::never())
            ->method('isHandling');
        $test->expects(self::never())
            ->method('handle');
        $test->expects(self::never())
            ->method('handleBatch');
        $test->expects(self::never())
            ->method('close');

        $handler = new CallbackFilterHandler($test, $filters, $testlvl, false);

        if ($record->level->value >= $testlvl->value) {
            self::assertTrue($handler->isHandling($record));
        } else {
            self::assertFalse($handler->isHandling($record));
        }

        self::assertSame($testlvl, $handler->getLevel());
        self::assertFalse($handler->getBubble());
    }

    /**
     * Filter events on standard log level (greater or equal than WARNING).
     *
     * @throws Exception
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    #[DataProvider('provideSuiteRecords')]
    public function testIsHandlingLevelWithLoglevel(LogRecord $record): void
    {
        $filters = [];
        $testlvl = LogLevel::WARNING;

        $test = $this->getMockBuilder(HandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $test->expects(self::never())
            ->method('isHandling');
        $test->expects(self::never())
            ->method('handle');
        $test->expects(self::never())
            ->method('handleBatch');
        $test->expects(self::never())
            ->method('close');

        $handler = new CallbackFilterHandler($test, $filters, $testlvl);

        $levelToCompare = Logger::toMonologLevel($testlvl);

        if ($record->level->value >= $levelToCompare->value) {
            self::assertTrue($handler->isHandling($record));
        } else {
            self::assertFalse($handler->isHandling($record));
        }
    }

    /**
     * Filter events on standard log level (greater or equal than WARNING).
     *
     * @throws Exception
     * @throws RuntimeException
     */
    #[DataProvider('provideSuiteRecords')]
    public function testIsHandlingLevelAndCallback(LogRecord $record): void
    {
        $filters = [
            static fn (LogRecord $record) => in_array(
                $record->level->value,
                [Level::Info->value, Level::Notice->value],
                true,
            ),
        ];
        $testlvl = Level::Info;

        $test = $this->getMockBuilder(HandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $test->expects(self::never())
            ->method('isHandling');
        $test->expects(self::never())
            ->method('handle');
        $test->expects(self::never())
            ->method('handleBatch');
        $test->expects(self::never())
            ->method('close');

        $handler = new CallbackFilterHandler($test, $filters, $testlvl);

        if (in_array($record->level->value, [Level::Info->value, Level::Notice->value], true)) {
            self::assertTrue($handler->isHandling($record));
        } else {
            self::assertFalse($handler->isHandling($record));
        }
    }

    /**
     * Filter events on standard log level (greater or equal than WARNING).
     *
     * @throws Exception
     * @throws RuntimeException
     */
    #[DataProvider('provideSuiteRecords')]
    public function testIsHandlingLevelAndCallbackWithLoglevel(LogRecord $record): void
    {
        $filters = [
            static fn (LogRecord $record) => in_array(
                $record->level->value,
                [Level::Info->value, Level::Notice->value],
                true,
            ),
        ];
        $testlvl = LogLevel::INFO;

        $test = $this->getMockBuilder(HandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $test->expects(self::never())
            ->method('isHandling');
        $test->expects(self::never())
            ->method('handle');
        $test->expects(self::never())
            ->method('handleBatch');
        $test->expects(self::never())
            ->method('close');

        $handler = new CallbackFilterHandler($test, $filters, $testlvl);

        if (in_array($record->level->value, [Level::Info->value, Level::Notice->value], true)) {
            self::assertTrue($handler->isHandling($record));
        } else {
            self::assertFalse($handler->isHandling($record));
        }
    }

    /**
     * Filter events only on levels needed (INFO and NOTICE).
     *
     * @throws Exception
     * @throws RuntimeException
     */
    #[DataProvider('provideSuiteRecords')]
    public function testHandleProcessOnlyNeededLevels(LogRecord $record): void
    {
        $filters = [
            static fn (LogRecord $record) => in_array(
                $record->level->value,
                [Level::Info->value, Level::Notice->value],
                true,
            ),
        ];

        $test    = new TestHandler();
        $handler = new CallbackFilterHandler($test, $filters);
        $handler->handle($record);

        $levelName = Level::fromValue($record->level->value)->getName();
        $hasMethod = 'has' . ucfirst(mb_strtolower($levelName));
        $result    = $test->{$hasMethod}(sprintf('sample of %s message', $levelName));

        if (in_array($record->level->value, [Level::Info->value, Level::Notice->value], true)) {
            self::assertTrue($result);
        } else {
            self::assertFalse($result);
        }
    }

    /**
     * Filter events that matches all rules defined in filters.
     *
     * @throws Exception
     * @throws RuntimeException
     */
    #[DataProvider('provideSuiteRecords')]
    public function testHandleProcessAllMatchingRules(LogRecord $record): void
    {
        $filters = [
            static fn (LogRecord $record) => $record->level->value === Level::Notice->value,
            static fn (LogRecord $record) => preg_match('/^sample of/', $record->message) === 1,
        ];

        $test = new TestHandler();

        $handler = new CallbackFilterHandler($test, $filters);
        $handler->handle($record);

        if ($record->level->value === Level::Notice->value) {
            self::assertTrue($test->hasNoticeThatContains($record->message));
        } else {
            self::assertFalse($test->hasNoticeThatContains($record->message));
        }
    }

    /**
     * Filter events on batch mode.
     *
     * @throws Exception
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function testHandleBatch(): void
    {
        $filters = [
            static fn (LogRecord $record) => $record->level->value === Level::Info->value,
            static fn (LogRecord $record) => preg_match('/information/', $record->message) === 1,
        ];

        $records = $this->getMultipleRecords();
        $test    = new TestHandler();

        $handler = new CallbackFilterHandler($test, $filters);
        $handler->handleBatch($records);

        self::assertTrue(
            $test->hasOnlyRecordsThatContains('information', Level::Info),
        );
    }

    /**
     * Filter events on batch mode.
     *
     * @throws Exception
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function testHandleBatch2(): void
    {
        $filters = [
            static fn (LogRecord $record) => $record->level->value === Level::Info->value,
            static fn (LogRecord $record) => preg_match('/information/', $record->message) === false,
        ];

        $records = $this->getMultipleRecords();
        $test    = new TestHandler();

        $handler = new CallbackFilterHandler($test, $filters);
        $handler->handleBatch($records);

        self::assertSame([], $test->getRecords());
    }

    /**
     * @throws Exception
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function testHandleUsesProcessors(): void
    {
        $filters = [
            static fn (LogRecord $record) => in_array(
                $record->level->value,
                [Level::Debug->value, Level::Warning->value],
                true,
            ),
        ];

        $test = new TestHandler();

        $record1 = $this->getRecord();
        $record2 = $this->getRecord(Level::Error);

        $callback = static function (LogRecord $record): LogRecord {
            $record->extra['foo'] = true;

            return $record;
        };

        $processor = $this->getMockBuilder(ProcessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processor->expects(self::once())
            ->method('__invoke')
            ->with($record1)
            ->willReturnCallback($callback);

        $handler = new CallbackFilterHandler($test, $filters);
        $handler->pushProcessor($processor);

        $handler->handle($record1);
        $handler->handle($record2);

        self::assertTrue(
            $test->hasOnlyRecordsMatching(
                [
                    'extra' => ['foo' => true],
                    'level' => Level::Warning,
                ],
            ),
        );
    }

    /**
     * Filter events matching bubble feature.
     *
     * Note: only the levels notice and warning are tested
     *
     * @throws Exception
     * @throws RuntimeException
     */
    #[DataProvider('provideSuiteBubbleRecords')]
    public function testHandleRespectsBubble(LogRecord $record): void
    {
        $filters = [
            static fn (LogRecord $record) => in_array(
                $record->level->value,
                [Level::Info->value, Level::Notice->value],
                true,
            ),
        ];
        $testlvl = Level::Info;

        $test = new TestHandler();

        foreach ([false, true] as $bubble) {
            $handler = new CallbackFilterHandler($test, $filters, $testlvl, $bubble);

            if ($record->level->value === Level::Notice->value && $bubble === false) {
                self::assertTrue($handler->handle($record));
            } else {
                self::assertFalse($handler->handle($record));
            }
        }
    }

    /**
     * Filter events matching bubble feature.
     *
     * Note: only the levels notice and warning are tested
     *
     * @throws Exception
     * @throws RuntimeException
     */
    #[DataProvider('provideSuiteBubbleRecords')]
    public function testHandleRespectsBubbleWithLoglevel(LogRecord $record): void
    {
        $filters = [
            static fn (LogRecord $record) => in_array(
                $record->level->value,
                [Level::Info->value, Level::Notice->value],
                true,
            ),
        ];
        $testlvl = LogLevel::INFO;
        $test    = new TestHandler();

        foreach ([false, true] as $bubble) {
            $handler = new CallbackFilterHandler($test, $filters, $testlvl, $bubble);

            if ($record->level->value === Level::Notice->value && $bubble === false) {
                self::assertTrue($handler->handle($record));
            } else {
                self::assertFalse($handler->handle($record));
            }
        }
    }

    /**
     * Bad filter configuration.
     *
     * @throws RuntimeException
     */
    public function testHandleWithBadFilterThrowsException(): void
    {
        $filters = [false];

        $test = new class () implements HandlerInterface {
            /**
             * @throws \Exception
             *
             * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
             */
            #[Override]
            public function isHandling(LogRecord $record): bool
            {
                throw new \Exception();
            }

            /**
             * @throws \Exception
             *
             * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
             */
            #[Override]
            public function handle(LogRecord $record): bool
            {
                throw new \Exception();
            }

            /**
             * @param array<LogRecord> $records
             *
             * @throws \Exception
             *
             * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
             */
            #[Override]
            public function handleBatch(array $records): void
            {
                throw new \Exception();
            }

            /** @throws \Exception */
            #[Override]
            public function close(): void
            {
                throw new \Exception();
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The given filter (false) is not a Closure');
        $this->expectExceptionCode(0);

        new CallbackFilterHandler($test, $filters);
    }

    /**
     * Bad filter configuration.
     *
     * @throws RuntimeException
     */
    public function testGetHandler(): void
    {
        $filters = [];

        $test = new class () implements HandlerInterface {
            /**
             * @throws \Exception
             *
             * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
             */
            #[Override]
            public function isHandling(LogRecord $record): bool
            {
                throw new \Exception();
            }

            /**
             * @throws \Exception
             *
             * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
             */
            #[Override]
            public function handle(LogRecord $record): bool
            {
                throw new \Exception();
            }

            /**
             * @param array<LogRecord> $records
             *
             * @throws \Exception
             *
             * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
             */
            #[Override]
            public function handleBatch(array $records): void
            {
                throw new \Exception();
            }

            /** @throws \Exception */
            #[Override]
            public function close(): void
            {
                throw new \Exception();
            }
        };

        $handler = new CallbackFilterHandler($test, $filters);

        self::assertSame($test, $handler->getHandler());
    }

    /**
     * Bad filter configuration.
     *
     * @throws RuntimeException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetHandlerWithClosureFailure(): void
    {
        $filters = [];

        $logRecord = $this->createMock(LogRecord::class);

        $handler = new CallbackFilterHandler(
            /**
             * @throws void
             *
             * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
             */
            static function (LogRecord $innerRecord, HandlerInterface $innerHandler) use ($logRecord): mixed {
                self::assertSame($innerRecord, $logRecord);

                return null;
            },
            $filters,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The factory Closure should return a HandlerInterface');
        $this->expectExceptionCode(0);

        $handler->getHandler($logRecord);
    }

    /**
     * Bad filter configuration.
     *
     * @throws RuntimeException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetHandlerWithClosure(): void
    {
        $filters = [];

        $logRecord   = $this->createMock(LogRecord::class);
        $testHandler = $this->createMock(HandlerInterface::class);

        $handler = new CallbackFilterHandler(
            /**
             * @throws void
             *
             * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
             */
            static function (LogRecord $innerRecord, HandlerInterface $innerHandler) use ($logRecord, $testHandler): mixed {
                self::assertSame($innerRecord, $logRecord);

                return $testHandler;
            },
            $filters,
        );

        self::assertSame($testHandler, $handler->getHandler($logRecord));
    }

    /**
     * @throws Exception
     * @throws RuntimeException
     */
    public function testReset(): void
    {
        $filters = [
            static fn (LogRecord $record) => in_array(
                $record->level->value,
                [Level::Debug->value, Level::Warning->value],
                true,
            ),
        ];

        $test = $this->getMockBuilder(GroupHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $test->expects(self::once())
            ->method('reset');

        $processor = $this->getMockBuilder(UidProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processor->expects(self::once())
            ->method('reset');

        $handler = new CallbackFilterHandler($test, $filters);
        $handler->pushProcessor($processor);

        $handler->reset();
    }

    /**
     * @throws Exception
     * @throws RuntimeException
     */
    public function testReset2(): void
    {
        $filters = [
            static fn (LogRecord $record) => in_array(
                $record->level->value,
                [Level::Debug->value, Level::Warning->value],
                true,
            ),
        ];

        $test = $this->getMockBuilder(AbstractTestHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $test->expects(self::never())
            ->method('reset');

        $processor = $this->getMockBuilder(UidProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processor->expects(self::once())
            ->method('reset');

        $handler = new CallbackFilterHandler($test, $filters);
        $handler->pushProcessor($processor);

        $handler->reset();
    }
}
