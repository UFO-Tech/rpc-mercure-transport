<?php
namespace Ufo\RpcMercure\DTO;

use Ufo\JsonRpcBundle\ConfigService\RpcAsyncConfig;
use Ufo\RpcError\RpcAsyncRequestException;
use Ufo\RpcMercure\Exceptions\RpcMercureConfigException;

readonly class MercureConfig
{
    public const string REQUEST = 'request';
    public const string RESPONSE = 'response';
    public const string QUERY_PARAM_TOPIC = '?topic=';

    public function __construct(
        public string $name,
        public string $dsn,
        public string $topicsPrefix,
    ) {}

    /**
     * @throws RpcMercureConfigException
     */
    public static function fromRpcAsyncConfig(RpcAsyncConfig $asyncConfig): static
    {
        try {
            $configInfo = $asyncConfig->getConfig('mercure');
        } catch (RpcAsyncRequestException $e) {
            static::configError($e->getMessage());
        }
        return new static(
            $configInfo->name,
            $configInfo->config['dsn'] ?? static::configError('Mercure dsn is not configured'),
            $configInfo->config['topics_prefix'] ?? static::configError('Mercure topics_prefix is not configured'),
        );
    }

    /**
     * @throws RpcMercureConfigException
     */
    protected static function configError(string $error): never
    {
        throw new RpcMercureConfigException($error);
    }

    public function getTopic(string $name = self::REQUEST, bool $withQueryParam = true): string
    {
        $prefix = '';
        if ($withQueryParam) $prefix = static::QUERY_PARAM_TOPIC;
        return $prefix . $this->topicsPrefix . $name;
    }

}