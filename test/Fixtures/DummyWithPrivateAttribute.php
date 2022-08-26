<?php

declare(strict_types=1);

/*
 * This file is part of the TSantos Fast Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TSantos\Test\FastObjectNormalizer\Fixtures;

final class DummyWithPrivateAttribute
{
    public function __construct(
        private string $private,
        public string $public
    ) {
    }

    public function getPublic(): string
    {
        return $this->public;
    }

    public function setPublic(string $public): void
    {
        $this->public = $public;
    }
}
