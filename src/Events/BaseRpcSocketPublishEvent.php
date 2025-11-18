<?php

namespace Ufo\RpcMercure\Events;

use Ufo\JsonRpcBundle\EventDrivenModel\Events\BaseRpcEvent;
use Ufo\RpcObject\RpcRequest;

abstract class BaseRpcSocketPublishEvent extends BaseRpcEvent implements IRpcSocketPublishEvent
{
    public function __construct(
        readonly public string $id,
        readonly public RpcRequest $request
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

}