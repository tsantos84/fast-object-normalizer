<?php

declare(strict_types=1);

namespace Tsantos\Symfony\Serializer\Normalizer;

interface ObjectFactoryInterface
{
    public function newInstance(array $data = [], array $context = []): object;
}
