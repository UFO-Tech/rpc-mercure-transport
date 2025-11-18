🚀 RpcMercureTransport

![Ukraine](https://img.shields.io/badge/Glory-Ukraine-yellow?labelColor=blue)

**Mercure Transport Layer for [JSON-RPC-BUNDLE](https://github.com/ufo-tech/json-rpc-bundle)**

Adds support for cross-domain events between services via [Mercure Hub](https://mercure.rocks/).

---

## 🧬 Idea

This extension for [JSON-RPC-BUNDLE](https://github.com/ufo-tech/json-rpc-bundle) allows
publishing events from the RPC server to the **Mercure Hub** and receiving them by other services
that are subscribed to the corresponding **topics**.

This enables building **asynchronous event-driven interaction between microservices**,
without the need for RabbitMQ or other brokers.

---
![License](https://img.shields.io/badge/license-MIT-green?labelColor=7b8185)
![Size](https://img.shields.io/github/repo-size/ufo-tech/rpc-mercure-transport?label=Size%20of%20the%20repository)
![package_version](https://img.shields.io/github/v/tag/ufo-tech/rpc-mercure-transport?color=blue&label=Latest%20Version&logo=Packagist&logoColor=white&labelColor=7b8185)
![fork](https://img.shields.io/github/forks/ufo-tech/rpc-mercure-transport?color=green&logo=github&style=flat)

### Environment Requirements
![php_version](https://img.shields.io/packagist/dependency-v/ufo-tech/rpc-mercure-transport/php?logo=PHP&logoColor=white)
![symfony mercure](https://img.shields.io/packagist/dependency-v/ufo-tech/rpc-mercure-transport/symfony/mercure-bundle?label=Mercure&logo=Symfony&logoColor=white)
![ufo-tech/rpc-bundle](https://img.shields.io/packagist/dependency-v/ufo-tech/rpc-mercure-transport/ufo-tech/json-rpc-bundle?label=JsonRpcBundle&logo=ufo&logoColor=white)

## ⚙️ Installation

```bash
composer require ufo-tech/rpc-mercure-transport
```

---

## 🔧 Configuration

`config/packages/ufo_json_rpc.yaml`:

```yaml
ufo_json_rpc:
  #  ...
  transports:
    async:
      #  ...
      - type: 'mercure'
        config:
          name: 'rpc_socket'
          dsn: '%env(resolve:MERCURE_PUBLIC_URL)%'
          topics_prefix: 'rpc.event.'
```

---

## 🚀 Send a message

If an `RpcSocketRequestEvent` event is dispatched in the `EventDispatcher`, the service automatically publishes it to the Mercure Hub.

The library includes syntactic sugar `Ufo\RpcMercure\Services\RpcSocketTransport::fireEvent`

```php
use Ufo\RpcMercure\Services\RpcSocketTransport;

class SomeService
{
    public function __construct(
        protected RpcSocketTransport $rpcSocketTransport,
    ) {}
    
    public function someMethod(): void
    {
        $this->rpcSocketTransport->fireEvent(
                eventName: 'event.test',
                eventData: [
                    'someKey' => 'some value',
                ]
            ),
        );
    }
}
```

→ on the Mercure side, this event will be sent to the topic `[topics_prefix].request`.

Other services in your SOA subscribed to this topic will instantly receive messages via SSE.

---

## 📡 Example of subscribing to requests

```bash
php bin/console ufo:rpc:socket:consume --topic=request -v
```
```stdout
Connect to socket: request                                                                                               


2025-11-19 16:09:30:
>>> {"id":"691debba4a97b","method":"event.test","jsonrpc":"2.0","params":{"$rpc":{"timeout":10,"rayId":"691debba4a97b"}}}

Service "event.test" is not found on RPC Service Map
====================================================================================================

2025-11-19 16:09:30:
>>> {"id":"691debba83412","method":"ping","jsonrpc":"2.0","params":{"$rpc":{"timeout":10,"rayId":"691debba83412"}}}
<<< {"id":"691debba83412","result":"PONG","jsonrpc":"2.0"}


====================================================================================================

2025-11-19 16:09:30:
>>> {"id":"691debba8ef62","method":"Messenger.send","jsonrpc":"2.0","params":{"$rpc":{"timeout":10,"rayId":"691debba8ef62"}}}

Required parameter "message" not passed

```
## 📡 Example of subscribing to responses

```bash
php bin/console ufo:rpc:socket:consume --topic=response -v
```
```stdout
Connect to socket: response                                                                                               

2025-11-19 16:09:30:
{"id":"691debba83412","result":"PONG","jsonrpc":"2.0"}
====================================================================================================
2025-11-19 16:09:30:
{"id":"691debba8ef62","error":"Required parameter "message" not passed","jsonrpc":"2.0"}

```
---

## 🧠 Main idea

* each RPC service has its own events (domain events)
* `rpc-mercure-transport` broadcasts them to the Mercure Hub
* other services can react to them without direct dependency, just by implementing a method named after the event

---

## 🦠 License

MIT © [UFO-Tech](https://github.com/ufo-tech)
</file>
