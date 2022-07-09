<?php declare(strict_types=1);
/**
 * Callback Filter Handler for Monolog.
 *
 * @category Logging
 * @package  monolog-callbackfilterhandler
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @author   Christophe Coevoet
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 */

namespace Bartlett\Monolog\Handler;

use Closure;
use Monolog\Handler\AbstractHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\ProcessableHandlerTrait;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\ResettableInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use function array_shift;
use function array_unshift;
use function is_callable;
use function json_encode;

/**
 * Monolog handler wrapper that filters records based on a list of callback functions.
 *
 * @category Logging
 * @package  monolog-callbackfilterhandler
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @author   Christophe Coevoet
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @since    Class available since Release 1.0.0
 */
class CallbackFilterHandler extends AbstractHandler implements ProcessableHandlerInterface, ResettableInterface
{
    use ProcessableHandlerTrait;

    /**
     * Handler or factory Closure($record, $this)
     *
     * @phpstan-var (Closure(LogRecord|null, HandlerInterface): HandlerInterface)|HandlerInterface
     */
    protected Closure|HandlerInterface $handler;

    /**
     * Filters Closure to restrict log records.
     *
     * @var Closure[]
     *
     * @phpstan-var array<int|string, (Closure(LogRecord, int|string|Level): bool)>
     */
    protected array $filters;

    /**
     * @param Closure|HandlerInterface $handler Handler or factory Closure($record, $this).
     * @param Closure[]                $filters A list of filters to apply
     * @param int|string|Level         $level   The minimum logging level at which this handler will be triggered
     * @param bool                     $bubble  Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(Closure|HandlerInterface $handler, array $filters, int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);    // @phpstan-ignore-line

        $this->handler = $handler;
        $this->filters = [];

        foreach ($filters as $filter) {
            if (!$filter instanceof Closure) {
                throw new RuntimeException(
                    'The given filter (' . json_encode($filter) . ') is not a Closure'
                );
            }
            $this->filters[] = $filter;
        }
    }

    /**
     * @inheritDoc
     */
    public function isHandling(LogRecord $record): bool
    {
        if (!parent::isHandling($record)) {
            return false;
        }

        if (isset($record->message)) {
            // when record is fulfilled, try each filter
            foreach ($this->filters as $filter) {
                if (!$filter($record, $this->level)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException
     */
    public function handle(LogRecord $record): bool
    {
        // The same logic as in FilterHandler

        if (!$this->isHandling($record)) {
            return false;
        }

        if (\count($this->processors) > 0) {
            $record = $this->processRecord($record);
        }

        $this->getHandler($record)->handle($record);

        return false === $this->bubble;
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException
     */
    public function handleBatch(array $records): void
    {
        // The same logic as in FilterHandler

        $filtered = [];
        foreach ($records as $record) {
            if ($this->isHandling($record)) {
                $filtered[] = $record;
            }
        }

        if (count($filtered) > 0) {
            $this->getHandler($filtered[count($filtered) - 1])->handleBatch($filtered);
        }
    }

    /**
     * Return the nested handler
     *
     * If the handler was provided as a factory, this will trigger the handler's instantiation.
     *
     * @throws RuntimeException
     */
    public function getHandler(LogRecord $record = null): HandlerInterface
    {
        // The same logic as in FingersCrossedHandler

        if (!$this->handler instanceof HandlerInterface) {
            $handler = ($this->handler)($record, $this);
            if (!$handler instanceof HandlerInterface) {
                throw new RuntimeException("The factory Closure should return a HandlerInterface");
            }
            $this->handler = $handler;
        }

        return $this->handler;
    }

    public function reset(): void
    {
        $this->resetProcessors();

        $handler = $this->getHandler();

        if ($handler instanceof ResettableInterface) {
            $handler->reset();
        }
    }
}
