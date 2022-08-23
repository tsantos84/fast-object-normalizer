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

    public function getPublic(): string
    {
        return $this->public;
    }

    public function setPublic(string $public): void
    {
        $this->public = $public;
    }
}
