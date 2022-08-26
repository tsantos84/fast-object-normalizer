<?php

declare(strict_types=1);

/*
 * This file is part of the Tsantos Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures;

use Symfony\Component\Serializer\Annotation\DiscriminatorMap;

#[DiscriminatorMap(typeProperty: 'type', mapping: [
    'dummyA' => DummyA::class,
    'dummyB' => DummyB::class,
    'dummyC' => DummyC::class,
])]
interface DummyInterface
{
}
