<?php declare(strict_types=1);

namespace GraphQL\Executor\Promise;

use Amp\Promise as AmpPromise;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Utils\Utils;
use React\Promise\PromiseInterface as ReactPromise;

/**
 * Convenience wrapper for promises represented by Promise Adapter.
 */
class Promise
{
    /** @var SyncPromise|ReactPromise|AmpPromise<mixed> */
    public $adoptedPromise;

    private PromiseAdapter $adapter;

    /**
     * @param mixed $adoptedPromise
     */
    public function __construct($adoptedPromise, PromiseAdapter $adapter)
    {
        Utils::invariant(! $adoptedPromise instanceof self, 'Expecting promise from adapted system, got ' . self::class);

        $this->adoptedPromise = $adoptedPromise;
        $this->adapter = $adapter;
    }

    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): Promise
    {
        return $this->adapter->then($this, $onFulfilled, $onRejected);
    }
}
