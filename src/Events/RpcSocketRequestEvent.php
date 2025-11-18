<?php

namespace Ufo\RpcMercure\Events;

use Ufo\RpcError\AbstractRpcErrorException;
use Ufo\RpcMercure\DTO\MercureConfig;
use Ufo\RpcObject\RpcRequest;

use function uniqid;

class RpcSocketRequestEvent extends BaseRpcSocketPublishEvent
{
    const string NAME = 'rpc.socket.request';

    /**
     * @throws AbstractRpcErrorException
     */
    public function __construct(
        string $method,
        array $params = [],
        ?string $id = null
    )
    {
        $id ??= uniqid();
        parent::__construct(
            $id,
            new RpcRequest($id, $method, $params)
        );
    }

    public function toArray(): array
    {
        return $this->request->toArray();
    }

    public function getTopicName(): string
    {
        return MercureConfig::REQUEST;
    }

}