<?php

namespace Ufo\RpcMercure\Events;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Ufo\JsonRpcBundle\ConfigService\RpcMainConfig;
use Ufo\JsonRpcBundle\Server\Async\RpcAsyncProcessor;
use Ufo\RpcError\RpcAsyncRequestException;
use Ufo\RpcError\RpcMethodNotFoundExceptionRpc;
use Ufo\RpcMercure\DTO\MercureConfig;
use Ufo\RpcMercure\Exceptions\RpcMercureRequestException;
use Ufo\RpcMercure\Services\RpcSocketTransport;
use Ufo\RpcObject\RpcError;
use Ufo\RpcObject\RpcRequest;
use Ufo\RpcObject\RpcResponse;
use Ufo\RpcObject\Transformer\ResponseCreator;

use function json_encode;
use function ob_get_clean;
use function ob_start;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_UNICODE;
use const PHP_EOL;

#[AsEventListener(MercureEvent::NAME, method: 'processRequest', priority: 100)]
#[AsEventListener(MercureEvent::NAME, method: 'processResponse', priority: 100)]

#[AsEventListener(RpcSocketRequestEvent::NAME, method: 'pushToSocket', priority: 100)]
#[AsEventListener(RpcSocketResponseEvent::NAME, method: 'pushToSocket', priority: 100)]
class MercureEventProcessor
{
    protected MercureConfig $mercureConfig;

    public function __construct(
        protected RpcMainConfig $mainConfig,
        protected HubInterface $hub,
        protected RpcAsyncProcessor $asyncProcessor,
        protected RpcSocketTransport $rpcSocketTransport,
    )
    {
        $this->mercureConfig = MercureConfig::fromRpcAsyncConfig($this->mainConfig->asyncConfig);
    }

    /**
     * @throws RpcAsyncRequestException
     */
    public function pushToSocket(BaseRpcSocketPublishEvent $event): void
    {
        try {
            $update = new Update(
                $this->mercureConfig->getTopic($event->getTopicName(), withQueryParam: false),
                json_encode($event->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                id: $event->getId()
            );

            $this->hub->publish($update);
        } catch (\Throwable $e) {
            throw new RpcMercureRequestException('RPC Error: ' . $e->getMessage(), previous: $e);
        }
    }

    public function processResponse(MercureEvent $event): void
    {
        if ($event->topicName !== MercureConfig::RESPONSE) return;
        $callback = $event->callback;
        $response = ResponseCreator::fromJson($event->data);
        $callback($event->data, (bool)$response->getError());
    }

    public function processRequest(MercureEvent $event): void
    {
        if ($event->topicName !== MercureConfig::REQUEST) return;
        $callback = $event->callback;
        $request = RpcRequest::fromJson($event->data);
        $output = '';
        try {
            $this->handleRequest($request, $output);
            $callback($output);

        } catch (RpcMethodNotFoundExceptionRpc $e) {
            $callback($output . PHP_EOL . $e->getMessage(), true, true);
            return;
        } catch (\Throwable $e) {
            $request->setError($e);
            $request->setResponse(new RpcResponse(
                $request->getId(),
                error: RpcError::fromThrowable($e)
            ));
            $callback($output . PHP_EOL . $e->getMessage(), true);
        }
        $this->rpcSocketTransport->response($request);
    }

    protected function handleRequest(RpcRequest $request, string &$output): void
    {
        ob_start();
        try {
            $this->asyncProcessor->processAsync($request);
        } catch (RpcMethodNotFoundExceptionRpc $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RpcMercureRequestException($e->getMessage(), previous: $e);
        } finally {
            $output = (string) ob_get_clean();
        }
    }
}