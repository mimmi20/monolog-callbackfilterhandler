<?php

/**
 * This file is part of the mimmi20/monolog-callbackfilterhandler package.
 *
 * Copyright (c) 2022-2026, Thomas Mueller <mimmi20@live.de>
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
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[DataProvider(methodName: 'provideSuiteRecords')]
    public function testIsHandling(LogRecord $logRecord): void
    {
        $filters = [];

        $test = $this->createMock(HandlerInterface::class);
        $test->expects(self::never())
            ->method('isHandling');
        $test->expects(self::never())
            ->method('handle');
        $test->expects(self::never())
            ->method('handleBatch');
        $test->expects(self::never())
            ->method('close');

        $callbackFilterHandler = new CallbackFilterHandler($test, $filters);

        self::assertTrue($callbackFilterHandler->isHandling($logRecord));
        self::assertTrue($callbackFilterHandler->getBubble());
        self::assertSame(Level::Debug, $callbackFilterHandler->getLevel());
    }

    /**
     * Filter events on standard log level (greater or equal than WARNING).
     *
     * @throws Exception
     * @throws RuntimeException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[DataProvider(methodName: 'provideSuiteRecords')]
    public function testIsHandlingLevel(LogRecord $logRecord): void
    {
        $filters = [];
        $testlvl = Level::Warning;

        $test = $this->createMock(HandlerInterface::class);
        $test->expects(self::never())
            ->method('isHandling');
        $test->expects(self::never())
            ->method('handle');
        $test->expects(self::never())
            ->method('handleBatch');
        $test->expects(self::never())
            ->method('close');

        $callbackFilterHandler = new CallbackFilterHandler($test, $filters, $testlvl, bubble: false);

        if ($logRecord->level->value >= $testlvl->value) {
            self::assertTrue($callbackFilterHandler->isHandling($logRecord));
        } else {
            self::assertFalse($callbackFilterHandler->isHandling($logRecord));
        }

        self::assertSame($testlvl, $callbackFilterHandler->getLevel());
        self::assertFalse($callbackFilterHandler->getBubble());
    }

    /**
     * Filter events on standard log level (greater or equal than WARNING).
     *
     * @throws Exception
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[DataProvider(methodName: 'provideSuiteRecords')]
    public function testIsHandlingLevelWithLoglevel(LogRecord $logRecord): void
    {
        $filters = [];
        $testlvl = LogLevel::WARNING;

        $test = $this->createMock(HandlerInterface::class);
        $test->expects(self::never())
            ->method('isHandling');
        $test->expects(self::never())
            ->method('handle');
        $test->expects(self::never())
            ->method('handleBatch');
        $test->expects(self::never())
            ->method('close');

        $callbackFilterHandler = new CallbackFilterHandler($test, $filters, $testlvl);

        $levelToCompare = Logger::toMonologLevel($testlvl);

        if ($logRecord->level->value >= $levelToCompare->value) {
            self::assertTrue($callbackFilterHandler->isHandling($logRecord));
        } else {
            self::assertFalse($callbackFilterHandler->isHandling($logRecord));
        }
    }

    /**
     * Filter events on standard log level (greater or equal than WARNING).
     *
     * @throws Exception
     * @throws RuntimeException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[DataProvider(methodName: 'provideSuiteRecords')]
    public function testIsHandlingLevelAndCallback(LogRecord $logRecord): void
    {
        $filters = [
            static fn (LogRecord $logRecord): bool => in_array(
                $logRecord->level->value,
                [Level::Info->value, Level::Notice->value],
                strict: true,
            ),
        ];
        $testlvl = Level::Info;

        $test = $this->createMock(HandlerInterface::class);
        $test->expects(self::never())
            ->method('isHandling');
        $test->expects(self::never())
            ->method('handle');
        $test->expects(self::never())
            ->method('handleBatch');
        $test->expects(self::never())
            ->method('close');

        $callbackFilterHandler = new CallbackFilterHandler($test, $filters, $testlvl);

        if (
            in_array(
                $logRecord->level->value,
                [Level::Info->value, Level::Notice->value],
                strict: true,
            )
        ) {
            self::assertTrue($callbackFilterHandler->isHandling($logRecord));
        } else {
            self::assertFalse($callbackFilterHandler->isHandling($logRecord));
        }
    }

    /**
     * Filter events on standard log level (greater or equal than WARNING).
     *
     * @throws Exception
     * @throws RuntimeException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[DataProvider(methodName: 'provideSuiteRecords')]
    public function testIsHandlingLevelAndCallbackWithLoglevel(LogRecord $logRecord): void
    {
        $filters = [
            static fn (LogRecord $logRecord): bool => in_array(
                $logRecord->level->value,
                [Level::Info->value, Level::Notice->value],
                strict: true,
            ),
        ];
        $testlvl = LogLevel::INFO;

        $test = $this->createMock(HandlerInterface::class);
        $test->expects(self::never())
            ->method('isHandling');
        $test->expects(self::never())
            ->method('handle');
        $test->expects(self::never())
            ->method('handleBatch');
        $test->expects(self::never())
            ->method('close');

        $callbackFilterHandler = new CallbackFilterHandler($test, $filters, $testlvl);

        if (
            in_array(
                $logRecord->level->value,
                [Level::Info->value, Level::Notice->value],
                strict: true,
            )
        ) {
            self::assertTrue($callbackFilterHandler->isHandling($logRecord));
        } else {
            self::assertFalse($callbackFilterHandler->isHandling($logRecord));
        }
    }

    /**
     * Filter events only on levels needed (INFO and NOTICE).
     *
     * @throws Exception
     * @throws RuntimeException
     */
    #[DataProvider(methodName: 'provideSuiteRecords')]
    public function testHandleProcessOnlyNeededLevels(LogRecord $logRecord): void
    {
        $filters = [
            static fn (LogRecord $logRecord): bool => in_array(
                $logRecord->level->value,
                [Level::Info->value, Level::Notice->value],
                strict: true,
            ),
        ];

        $testHandler           = new TestHandler();
        $callbackFilterHandler = new CallbackFilterHandler($testHandler, $filters);
        $callbackFilterHandler->handle($logRecord);

        $levelName = Level::fromValue($logRecord->level->value)->getName();
        $hasMethod = 'has' . ucfirst(mb_strtolower($levelName));
        $result    = $testHandler->{$hasMethod}(sprintf('sample of %s message', $levelName));

        if (
            in_array(
                $logRecord->level->value,
                [Level::Info->value, Level::Notice->value],
                strict: true,
            )
        ) {
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
    #[DataProvider(methodName: 'provideSuiteRecords')]
    public function testHandleProcessAllMatchingRules(LogRecord $logRecord): void
    {
        $filters = [
            static fn (LogRecord $logRecord): bool => $logRecord->level->value === Level::Notice->value,
            static fn (LogRecord $logRecord): bool => preg_match(
                '/^sample of/',
                $logRecord->message,
            ) === 1,
        ];

        $testHandler = new TestHandler();

        $callbackFilterHandler = new CallbackFilterHandler($testHandler, $filters);
        $callbackFilterHandler->handle($logRecord);

        if ($logRecord->level->value === Level::Notice->value) {
            self::assertTrue($testHandler->hasNoticeThatContains($logRecord->message));
        } else {
            self::assertFalse($testHandler->hasNoticeThatContains($logRecord->message));
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
            static fn (LogRecord $logRecord): bool => $logRecord->level->value === Level::Info->value,
            static fn (LogRecord $logRecord): bool => preg_match(
                '/information/',
                $logRecord->message,
            ) === 1,
        ];

        $records     = $this->getMultipleRecords();
        $testHandler = new TestHandler();

        $callbackFilterHandler = new CallbackFilterHandler($testHandler, $filters);
        $callbackFilterHandler->handleBatch($records);

        self::assertTrue(
            $testHandler->hasOnlyRecordsThatContains('information', Level::Info),
        );
    }

    /**
     * Filter events on batch mode.
     *
     * @throws Exception
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testHandleBatch2(): void
    {
        $filters = [
            static fn (LogRecord $logRecord): bool => $logRecord->level->value === Level::Info->value,
            static fn (LogRecord $logRecord): bool => preg_match(
                '/information/',
                $logRecord->message,
            ) === false,
        ];

        $records     = $this->getMultipleRecords();
        $testHandler = new TestHandler();

        $callbackFilterHandler = new CallbackFilterHandler($testHandler, $filters);
        $callbackFilterHandler->handleBatch($records);

        self::assertSame([], $testHandler->getRecords());
    }

    /**
     * @throws Exception
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testHandleUsesProcessors(): void
    {
        $filters = [
            static fn (LogRecord $logRecord): bool => in_array(
                $logRecord->level->value,
                [Level::Debug->value, Level::Warning->value],
                strict: true,
            ),
        ];

        $testHandler = new TestHandler();

        $logRecord = $this->getRecord();
        $record2   = $this->getRecord(Level::Error);

        $callback = static function (LogRecord $logRecord): LogRecord {
            $logRecord->extra['foo'] = true;

            return $logRecord;
        };

        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects(self::once())
            ->method('__invoke')
            ->with($logRecord)
            ->willReturnCallback($callback);

        $callbackFilterHandler = new CallbackFilterHandler($testHandler, $filters);
        $callbackFilterHandler->pushProcessor($processor);

        $callbackFilterHandler->handle($logRecord);
        $callbackFilterHandler->handle($record2);

        self::assertTrue(
            $testHandler->hasOnlyRecordsMatching(
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
    #[DataProvider(methodName: 'provideSuiteBubbleRecords')]
    public function testHandleRespectsBubble(LogRecord $logRecord): void
    {
        $filters = [
            static fn (LogRecord $logRecord): bool => in_array(
                $logRecord->level->value,
                [Level::Info->value, Level::Notice->value],
                strict: true,
            ),
        ];
        $testlvl = Level::Info;

        $testHandler = new TestHandler();

        foreach ([false, true] as $bubble) {
            $handler = new CallbackFilterHandler($testHandler, $filters, $testlvl, $bubble);

            if ($logRecord->level->value === Level::Notice->value && $bubble === false) {
                self::assertTrue($handler->handle($logRecord));
            } else {
                self::assertFalse($handler->handle($logRecord));
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
    #[DataProvider(methodName: 'provideSuiteBubbleRecords')]
    public function testHandleRespectsBubbleWithLoglevel(LogRecord $logRecord): void
    {
        $filters     = [
            static fn (LogRecord $logRecord): bool => in_array(
                $logRecord->level->value,
                [Level::Info->value, Level::Notice->value],
                strict: true,
            ),
        ];
        $testlvl     = LogLevel::INFO;
        $testHandler = new TestHandler();

        foreach ([false, true] as $bubble) {
            $handler = new CallbackFilterHandler($testHandler, $filters, $testlvl, $bubble);

            if ($logRecord->level->value === Level::Notice->value && $bubble === false) {
                self::assertTrue($handler->handle($logRecord));
            } else {
                self::assertFalse($handler->handle($logRecord));
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

        $callbackFilterHandler = new CallbackFilterHandler($test, $filters);

        self::assertSame($test, $callbackFilterHandler->getHandler());
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

        $logRecord = self::createStub(LogRecord::class);

        $callbackFilterHandler = new CallbackFilterHandler(
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

        $callbackFilterHandler->getHandler($logRecord);
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

        $logRecord   = self::createStub(LogRecord::class);
        $testHandler = self::createStub(HandlerInterface::class);

        $callbackFilterHandler = new CallbackFilterHandler(
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

        self::assertSame($testHandler, $callbackFilterHandler->getHandler($logRecord));
    }

    /**
     * @throws Exception
     * @throws RuntimeException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testReset(): void
    {
        $filters = [
            static fn (LogRecord $logRecord): bool => in_array(
                $logRecord->level->value,
                [Level::Debug->value, Level::Warning->value],
                strict: true,
            ),
        ];

        $test = $this->createMock(GroupHandler::class);
        $test->expects(self::once())
            ->method('reset');

        $processor = $this->createMock(UidProcessor::class);
        $processor->expects(self::once())
            ->method('reset');

        $callbackFilterHandler = new CallbackFilterHandler($test, $filters);
        $callbackFilterHandler->pushProcessor($processor);

        $callbackFilterHandler->reset();
    }

    /**
     * @throws Exception
     * @throws RuntimeException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testReset2(): void
    {
        $filters = [
            static fn (LogRecord $logRecord): bool => in_array(
                $logRecord->level->value,
                [Level::Debug->value, Level::Warning->value],
                strict: true,
            ),
        ];

        $test = $this->createMock(AbstractTestHandler::class);
        $test->expects(self::never())
            ->method('reset');

        $processor = $this->createMock(UidProcessor::class);
        $processor->expects(self::once())
            ->method('reset');

        $callbackFilterHandler = new CallbackFilterHandler($test, $filters);
        $callbackFilterHandler->pushProcessor($processor);

        $callbackFilterHandler->reset();
    }
}
