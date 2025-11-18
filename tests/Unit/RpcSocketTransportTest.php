<?php

namespace Ufo\RpcMercureTest\Unit;

use Symfony\Component\HttpFoundation\RequestStack;
use Ufo\JsonRpcBundle\ConfigService\RpcAsyncConfig;
use Ufo\JsonRpcBundle\ConfigService\RpcMainConfig;
use PHPUnit\Framework\TestCase;

class RpcSocketTransportTest extends TestCase
{
    protected RpcAsyncConfig $asyncConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->asyncConfig = new RpcAsyncConfig([
            [
                'type' => 'mercure',
                'config' => [
                    'name' => 'rpc_socket',
                    'dsn' => 'https://127.0.0.1:6379',
                    'topics_prefix' => 'rpc_mercure'
                ]
            ],
            [
                'type' => 'mercure',
                'config' => [
                    'name' => 'rpc_socket',
                    'dsn' => 'https://127.0.0.1:6379',
                    'topics_prefix' => 'rpc_mercure'
                ]
            ],
            [
                'type' => 'amqp',
                'config' => [
                    'name' => 'rpc_async',
                    'dsn' => 'amqp://guest:guest:5672/internal/queue',
                ]
            ]
        ],
            new RpcMainConfig(
                [],
                'dev',
                $this->createMock(RequestStack::class),
                []
            )
        );

    }

//    public function test__construct() {}
//
//    public function testSend() {}
//
//    public function testFetch() {}
}
