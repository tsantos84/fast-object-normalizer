<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures;

use Symfony\Component\Serializer\Annotation\DiscriminatorMap;

#[DiscriminatorMap(typeProperty: 'type', mapping: [
    'dummyA' => DummyA::class,
    'dummyB' => DummyB::class,
])]
interface DummyInterface
{

}