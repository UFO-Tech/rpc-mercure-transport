<?php

namespace Ufo\RpcMercure\Services;

use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Jwt\FactoryTokenProvider;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Ufo\JsonRpcBundle\ConfigService\RpcMainConfig;
use Ufo\JsonRpcBundle\EventDrivenModel\RpcEventFactory;
use Ufo\RpcError\AbstractRpcErrorException;
use Ufo\RpcMercure\DTO\MercureConfig;
use Ufo\RpcMercure\Events\MercureEvent;
use Ufo\RpcMercure\Events\RpcSocketRequestEvent;
use Ufo\RpcMercure\Events\RpcSocketResponseEvent;
use Ufo\RpcMercure\Exceptions\RpcMercureConfigException;
use Ufo\RpcMercure\Exceptions\RpcMercureRequestException;
use Ufo\RpcObject\RpcRequest;

use function explode;
use function str_replace;
use function str_starts_with;
use function substr;
use function trim;

class RpcSocketTransport
{
    public const string HEART_BIT = ':';
    readonly public MercureConfig $mercureConfig;

    /**
     * @throws RpcMercureConfigException
     */
    public function __construct(
        protected RpcMainConfig $mainConfig,
        protected FactoryTokenProvider $tokenProvider,
        protected HttpClientInterface $http,
        protected RpcEventFactory $eventFactory,
    )
    {
        $this->mercureConfig = MercureConfig::fromRpcAsyncConfig($this->mainConfig->asyncConfig);
    }

    /**
     * @throws AbstractRpcErrorException
     */
    public function request(
        string $method,
        array $params = [],
        ?string $id = null
    ): void
    {
        $this->eventFactory->fire(
            new RpcSocketRequestEvent($method, $params, $id)
        );
    }

    /**
     * @throws AbstractRpcErrorException
     */
    public function fireEvent(
        string $eventName,
        array $eventData = []
    ): void
    {
        $this->request(
            'event.' . $eventName,
            $eventData
        );
    }

    public function response(
        RpcRequest $request,
    ): void
    {
        $this->eventFactory->fire(
            new RpcSocketResponseEvent($request)
        );
    }

    public function fetch(string $topicName, Closure $callback): void
    {
        $url = $this->mercureConfig->dsn . $this->mercureConfig->getTopic($topicName);
        $response = $this->http->request(
            Request::METHOD_GET,
            $url,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->tokenProvider->getJwt(),
                ],
            ]
        );

        foreach ($this->http->stream($response) as $chunk) {
            if (!$chunk->isLast() && $chunk->isTimeout()) {
                continue;
            }

            $buffer = trim($chunk->getContent());
            if (empty($buffer) || $buffer === static::HEART_BIT) continue;

            $lines = explode("\n", $buffer);
            $id = null;
            $payloadJson = null;
            $clearBuffer = str_replace('data:', '', $buffer);

            foreach ($lines as $line) {
                if (str_starts_with($line, 'id:')) {
                    $id = trim(substr($line, 3));
                    continue;
                } elseif (str_starts_with($line, 'data:')) {
                    $payloadJson .= trim(substr($line, 5));
                    continue;
                }
                throw new RpcMercureRequestException('Unsupported row format in ' . $clearBuffer);
            }
            if (!$id || !$payloadJson) {
                return;
            }

            $this->eventFactory->fire(
                new MercureEvent(
                    $topicName,
                    $payloadJson,
                    $callback
                )
            );
        }

    }
}