<?php

declare(strict_types=1);

/*
 * This file is part of the TSantos Fast Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TSantos\Test\FastObjectNormalizer\Fixtures;

final class DummyWithConstructor
{
    public function __construct(
        public string $foo,
        public string $bar
    ) {
    }

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function setFoo(string $foo): void
    {
        $this->foo = $foo;
    }

    public function getBar(): string
    {
        return $this->bar;
    }

    public function setBar(string $bar): void
    {
        $this->bar = $bar;
    }
}
