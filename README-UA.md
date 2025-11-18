# 🚀 RpcMercureTransport
![Ukraine](https://img.shields.io/badge/Glory-Ukraine-yellow?labelColor=blue)

**Mercure Transport Layer for [JSON-RPC-BUNDLE](https://github.com/ufo-tech/json-rpc-bundle)**

Додає підтримку кросдоменних подій між сервісами через [Mercure Hub](https://mercure.rocks/).

---

## 🧬 Ідея

Це розширення для [JSON-RPC-BUNDLE](https://github.com/ufo-tech/json-rpc-bundle), яке дозволяє
публікувати події з RPC-сервера у **Mercure Hub** і отримувати їх іншими сервісами,
що підписані на відповідні **topics**.

Це дає можливість побудови **асинхронної подієвої взаємодії між мікросервісами**,
без потреби в RabbitMQ чи інших брокерах.

---
![License](https://img.shields.io/badge/license-MIT-green?labelColor=7b8185)
![Size](https://img.shields.io/github/repo-size/ufo-tech/rpc-mercure-transport?label=Size%20of%20the%20repository)
![package_version](https://img.shields.io/github/v/tag/ufo-tech/rpc-mercure-transport?color=blue&label=Latest%20Version&logo=Packagist&logoColor=white&labelColor=7b8185)
![fork](https://img.shields.io/github/forks/ufo-tech/rpc-mercure-transport?color=green&logo=github&style=flat)

### Environment Requirements
![php_version](https://img.shields.io/packagist/dependency-v/ufo-tech/rpc-mercure-transport/php?logo=PHP&logoColor=white)
![symfony mercure](https://img.shields.io/packagist/dependency-v/ufo-tech/rpc-mercure-transport/symfony/mercure-bundle?label=Mercure&logo=Symfony&logoColor=white)
![ufo-tech/rpc-bundle](https://img.shields.io/packagist/dependency-v/ufo-tech/rpc-mercure-transport/ufo-tech/json-rpc-bundle?label=JsonRpcBundle&logo=ufo&logoColor=white)

## ⚙️ Встановлення

```bash
composer require ufo-tech/rpc-mercure-transport
```

---

## 🔧 Конфігурація

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

## 🚀 Відправити повідомлення

Якщо в `EventDispatcher` закинути подію `RpcSocketRequestEvent`, сервіс автоматично публікує її у Mercure Hub.

В бібліотеці є синтаксичний цукор `Ufo\RpcMercure\Services\RpcSocketTransport::fireEvent`

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

→ на боці Mercure ця подія піде в topic `[topics_prefix].request`.

Інші сервіси вашої SOA, що підписані на цей topic, миттєво отримають повідомлення через SSE.

---

## 📡 Приклад підписи на запити

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
## 📡 Приклад підписи на відповіді

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

## 🧠 Основна ідея

* кожен RPC-сервіс має власні події (domain events)
* `rpc-mercure-transport` транслює їх у Mercure Hub
* інші сервіси можуть реагувати на них без прямої залежності, для цього достатньо реалізувати метод з іменем події

---

## 🦠 Ліцензія

MIT © [UFO-Tech](https://github.com/ufo-tech)
