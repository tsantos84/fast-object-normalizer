<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures;

final class DummyWithConstructor
{
    public function __construct(
        public string $foo
    )
    {
    }
}
