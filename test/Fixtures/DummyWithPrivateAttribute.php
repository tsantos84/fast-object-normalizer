<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures;

final class DummyWithPrivateAttribute
{
    public function __construct(
        private string $private,
        public string $public
    )
    {
    }
}
