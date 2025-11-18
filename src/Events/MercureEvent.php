<?php

namespace Ufo\RpcMercure\Events;

use Closure;
use Ufo\JsonRpcBundle\EventDrivenModel\Events\BaseRpcEvent;

class MercureEvent extends BaseRpcEvent
{
    const string NAME = 'rpc.socket.read';

    /**
     * @param string $topicName
     * @param string $data
     * @param Closure(string, bool, bool): void $callback
    */
    public function __construct(
        readonly public string $topicName,
        readonly public string $data,
        readonly public Closure $callback,
    ) {}
}