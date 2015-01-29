<?php

namespace WyriHaximus\React;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

// @codingStandardsIgnoreStart
class TickingFuturePromise
// @codingStandardsIgnoreEnd
{
    /**
     * ReactPHP event loop.
     *
     * @var LoopInterface
     */
    protected $loop;

    /**
     * Callable to run at the future tick.
     *
     * @var callable
     */
    protected $check;

    /**
     * The value to pass into $check at each tick.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Deferred to resolve once $check has returned a value.
     *
     * @var Deferred
     */
    protected $deferred;

    /**
     * Factory used by tickingFuturePromise, see there for more details.
     *
     * @param LoopInterface $loop  ReactPHP event loop.
     * @param callable      $check Callable to run at the future tick.
     * @param mixed         $value Value to pass into $check on tick.
     *
     * @return mixed
     */
    public static function create(LoopInterface $loop, callable $check, $value = null)
    {
        return (new self($loop, $check, $value))->run();
    }

    /**
     * Hidden constructor, let the factory handle it.
     *
     * @param LoopInterface $loop  ReactPHP event loop.
     * @param callable      $check Callable to run at the future tick.
     * @param mixed         $value Value to pass into $check on tick.
     */
    private function __construct(LoopInterface $loop, callable $check, $value)
    {
        $this->loop = $loop;
        $this->check = $check;
        $this->value = $value;
        $this->deferred = new Deferred();
    }

    /**
     * Run the ticking future promise.
     *
     * @return \React\Promise\Promise
     */
    protected function run()
    {
        futurePromise($this->loop)->then(function () {
            $this->check();
        });
        return $this->deferred->promise();
    }

    /**
     * Run the $check callable and resolve when needed.
     *
     * @return void
     */
    protected function check()
    {
        $check = $this->check;
        $result = $check($this->value);
        if ($result !== false) {
            $this->deferred->resolve($result);
            return;
        }

        futurePromise($this->loop)->then(function () {
            $this->check();
        });
    }
}
