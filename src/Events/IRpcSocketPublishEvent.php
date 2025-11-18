<?php

namespace Ufo\RpcMercure\Events;

use Ufo\DTO\Interfaces\IArrayConvertible;

interface IRpcSocketPublishEvent extends IArrayConvertible
{
    public function getId(): string;

    public function getTopicName(): string;
}