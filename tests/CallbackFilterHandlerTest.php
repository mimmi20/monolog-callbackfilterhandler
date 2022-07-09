<?php declare(strict_types=1);

namespace Bartlett\Monolog\Handler\Tests;

use Bartlett\Monolog\Handler\CallbackFilterHandler;

use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\Logger;

use Monolog\LogRecord;
use Psr\Log\LogLevel;
use RuntimeException;
use function func_get_args;
use function in_array;
use function preg_match;

class CallbackFilterHandlerTest extends TestCase
{
    /**
     * Filter events on standard log level (without restriction).
     *
     * @covers CallbackFilterHandler::isHandling
     * @dataProvider provideSuiteRecords
     */
    public function testIsHandling(LogRecord $record)
    {
        $filters = [];

        $test    = $this->getMockBuilder(HandlerInterface::class)
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

        $this->assertTrue($handler->isHandling($record));
    }

    /**
     * Filter events on standard log level (greater or equal than WARNING).
     *
     * @covers CallbackFilterHandler::isHandling
     * @dataProvider provideSuiteRecords
     */
    public function testIsHandlingLevel(LogRecord $record)
    {
        $filters = [];
        $testlvl = Level::Warning;

        $test    = $this->getMockBuilder(HandlerInterface::class)
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

        if ($record->level->value >= $testlvl->value) {
            $this->assertTrue($handler->isHandling($record));
        } else {
            $this->assertFalse($handler->isHandling($record));
        }
    }

    /**
     * Filter events on standard log level (greater or equal than WARNING).
     *
     * @covers CallbackFilterHandler::isHandling
     * @dataProvider provideSuiteRecords
     */
    public function testIsHandlingLevelWithLoglevel(LogRecord $record)
    {
        $filters = [];
        $testlvl = LogLevel::WARNING;

        $test    = $this->getMockBuilder(HandlerInterface::class)
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
            $this->assertTrue($handler->isHandling($record));
        } else {
            $this->assertFalse($handler->isHandling($record));
        }
    }

    /**
     * Filter events on standard log level (greater or equal than WARNING).
     *
     * @covers CallbackFilterHandler::isHandling
     * @dataProvider provideSuiteRecords
     */
    public function testIsHandlingLevelAndCallback(LogRecord $record)
    {
        $filters = [
            function (LogRecord $record) {
                return in_array($record->level->value, [Level::Info->value, Level::Notice->value], true);
            }
        ];
        $testlvl = Level::Info;

        $test    = $this->getMockBuilder(HandlerInterface::class)
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
            $this->assertTrue($handler->isHandling($record));
        } else {
            $this->assertFalse($handler->isHandling($record));
        }
    }

    /**
     * Filter events on standard log level (greater or equal than WARNING).
     *
     * @covers CallbackFilterHandler::isHandling
     * @dataProvider provideSuiteRecords
     */
    public function testIsHandlingLevelAndCallbackWithLoglevel(LogRecord $record)
    {
        $filters = [
            function (LogRecord $record) {
                return in_array($record->level->value, [Level::Info->value, Level::Notice->value], true);
            }
        ];
        $testlvl = LogLevel::INFO;

        $test    = $this->getMockBuilder(HandlerInterface::class)
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
            $this->assertTrue($handler->isHandling($record));
        } else {
            $this->assertFalse($handler->isHandling($record));
        }
    }

    /**
     * Filter events only on levels needed (INFO and NOTICE).
     *
     * @covers CallbackFilterHandler::handle
     * @dataProvider provideSuiteRecords
     */
    public function testHandleProcessOnlyNeededLevels(LogRecord $record)
    {
        $filters = [
            function (LogRecord $record) {
                return in_array($record->level->value, [Level::Info->value, Level::Notice->value], true);
            }
        ];

        $test    = new TestHandler();
        $handler = new CallbackFilterHandler($test, $filters);
        $handler->handle($record);

        $levelName = Level::fromValue($record->level->value)->getName();
        $hasMethod = 'has' . ucfirst(strtolower($levelName));
        $result = $test->{$hasMethod}(sprintf('sample of %s message', $levelName), $record->level);

        if (in_array($record->level->value, [Level::Info->value, Level::Notice->value])) {
            $this->assertTrue($result);
        } else {
            $this->assertFalse($result);
        }
    }

    /**
     * Filter events that matches all rules defined in filters.
     *
     * @covers CallbackFilterHandler::handle
     * @dataProvider provideSuiteRecords
     */
    public function testHandleProcessAllMatchingRules(LogRecord $record)
    {
        $filters = [
            function (LogRecord $record) {
                return ($record->level->value == Level::Notice->value);
            },
            function (LogRecord $record) {
                return (preg_match('/^sample of/', $record->message) === 1);
            }
        ];

        $test    = new TestHandler();

        $handler = new CallbackFilterHandler($test, $filters);
        $handler->handle($record);

        if ($record->level->value === Level::Notice->value) {
            $this->assertTrue($test->hasNoticeThatContains($record->message));
        } else {
            $this->assertFalse($test->hasNoticeThatContains($record->message));
        }
    }

    /**
     * Filter events on batch mode.
     *
     * @covers CallbackFilterHandler::handleBatch
     */
    public function testHandleBatch()
    {
        $filters = [
            function (LogRecord $record) {
                return ($record->level->value == Level::Info->value);
            },
            function (LogRecord $record) {
                return (preg_match('/information/', $record->message) === 1);
            }
        ];

        $records = $this->getMultipleRecords();
        $test    = new TestHandler();

        $handler = new CallbackFilterHandler($test, $filters);
        $handler->handleBatch($records);

        $this->assertTrue($test->hasOnlyRecordsThatContains('information', Level::Info));
    }

    /**
     * @covers CallbackFilterHandler::handle
     * @covers CallbackFilterHandler::pushProcessor
     */
    public function testHandleUsesProcessors()
    {
        $filters = [
            function (LogRecord $record) {
                return in_array($record->level->value, [Level::Debug->value, Level::Warning->value], true);
            }
        ];

        $test    = new TestHandler();

        $handler = new CallbackFilterHandler($test, $filters);
        $handler->pushProcessor(
            function (LogRecord $record) {
                $record->extra['foo'] = true;

                return $record;
            }
        );
        $handler->handle($this->getRecord());
        $handler->handle($this->getRecord(Level::Error));

        $this->assertTrue(
            $test->hasOnlyRecordsMatching(
                [
                    'extra' => ['foo' => true],
                    'level' => Level::Warning
                ]
            )
        );
    }

    /**
     * Filter events matching bubble feature.
     *
     * Note: only the levels notice and warning are tested
     *
     * @covers CallbackFilterHandler::handle
     * @dataProvider provideSuiteBubbleRecords
     */
    public function testHandleRespectsBubble(LogRecord $record)
    {
        $filters = [
            function (LogRecord $record) {
                return in_array($record->level->value, [Level::Info->value, Level::Notice->value], true);
            }
        ];
        $testlvl = Level::Info;

        $test    = new TestHandler();

        foreach ([false, true] as $bubble) {
            $handler = new CallbackFilterHandler($test, $filters, $testlvl, $bubble);

            if ($record->level->value == Level::Notice->value && $bubble === false) {
                $this->assertTrue($handler->handle($record));
            } else {
                $this->assertFalse($handler->handle($record));
            }
        }
    }

    /**
     * Filter events matching bubble feature.
     *
     * Note: only the levels notice and warning are tested
     *
     * @covers CallbackFilterHandler::handle
     * @dataProvider provideSuiteBubbleRecords
     */
    public function testHandleRespectsBubbleWithLoglevel(LogRecord $record)
    {
        $filters = [
            function (LogRecord $record) {
                return in_array($record->level->value, [Level::Info->value, Level::Notice->value], true);
            }
        ];
        $testlvl = LogLevel::INFO;
        $test    = new TestHandler();

        foreach ([false, true] as $bubble) {
            $handler = new CallbackFilterHandler($test, $filters, $testlvl, $bubble);

            if ($record->level->value == Level::Notice->value && $bubble === false) {
                $this->assertTrue($handler->handle($record));
            } else {
                $this->assertFalse($handler->handle($record));
            }
        }
    }

    /**
     * Bad filter configuration.
     */
    public function testHandleWithBadFilterThrowsException()
    {
        $filters = [false];

        $test    = $this->getMockBuilder(HandlerInterface::class)
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

        $this->expectException(RuntimeException::class);

        new CallbackFilterHandler($test, $filters);
    }
}
