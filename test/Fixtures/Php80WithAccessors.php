<?php

declare(strict_types=1);

/*
 * This file is part of the Tsantos Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

class Php80WithAccessors
{
    #[Groups('foo-group')]
    public string $string;

    /** @var string */
    public $stringWithDocBlock;

    #[Groups('foo-group')]
    public int $int;
    public float $float;
    public array $array;
    public ?self $nested = null;

    /** @var DummyWithConstructor[] */
    public array $objectCollection;

    /** @var int[] */
    public array $intCollection;

    public ?int $nullable = null;

    #[Ignore]
    public ?string $ignored = null;

    #[SerializedName('barName')]
    public ?string $fooName = null;

    public function getString(): string
    {
        return $this->string;
    }

    public function setString(string $string): void
    {
        $this->string = $string;
    }

    public function getStringWithDocBlock(): string
    {
        return $this->stringWithDocBlock;
    }

    public function setStringWithDocBlock(string $stringWithDocBlock): void
    {
        $this->stringWithDocBlock = $stringWithDocBlock;
    }

    public function getInt(): int
    {
        return $this->int;
    }

    public function setInt(int $int): void
    {
        $this->int = $int;
    }

    public function getFloat(): float
    {
        return $this->float;
    }

    public function setFloat(float $float): void
    {
        $this->float = $float;
    }

    public function getArray(): array
    {
        return $this->array;
    }

    public function setArray(array $array): void
    {
        $this->array = $array;
    }

    public function getNested(): ?self
    {
        return $this->nested;
    }

    public function setNested(?self $nested): void
    {
        $this->nested = $nested;
    }

    public function getObjectCollection(): array
    {
        return $this->objectCollection;
    }

    public function setObjectCollection(array $objectCollection): void
    {
        $this->objectCollection = $objectCollection;
    }

    public function getIntCollection(): array
    {
        return $this->intCollection;
    }

    public function setIntCollection(array $intCollection): void
    {
        $this->intCollection = $intCollection;
    }

    public function getNullable(): ?int
    {
        return $this->nullable;
    }

    public function setNullable(?int $nullable): void
    {
        $this->nullable = $nullable;
    }

    public function getIgnored(): ?string
    {
        return $this->ignored;
    }

    public function setIgnored(?string $ignored): void
    {
        $this->ignored = $ignored;
    }

    public function getFooName(): ?string
    {
        return $this->fooName;
    }

    public function setFooName(?string $fooName): void
    {
        $this->fooName = $fooName;
    }
}
