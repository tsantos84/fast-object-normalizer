<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures;

final class DummyWithComplexAttributeInConstructor
{
    public function __construct(
        public DummyWithConstructor $foo
    )
    {
    }
}
