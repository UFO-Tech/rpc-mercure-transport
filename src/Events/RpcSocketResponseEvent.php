<?php

namespace Ufo\RpcMercure\Events;

use Ufo\RpcMercure\DTO\MercureConfig;
use Ufo\RpcObject\RpcRequest;
use Ufo\RpcObject\RpcResponse;
use Ufo\RpcObject\Transformer\Transformer;

class RpcSocketResponseEvent extends BaseRpcSocketPublishEvent
{
    const string NAME = 'rpc.socket.response';

    public function __construct(
        RpcRequest $request,
    )
    {
        parent::__construct(
            $request->getId(),
            $request
        );
    }

    public function toArray(): array
    {
        $response = $this->request->getResponseObject();

        return Transformer::getDefault()->normalize(
            $response,
            context: [
                'groups' => [$response->getError() ? RpcResponse::IS_ERROR : RpcResponse::IS_RESULT],
            ]
        );
    }

    public function getTopicName(): string
    {
        return MercureConfig::RESPONSE;
    }

}