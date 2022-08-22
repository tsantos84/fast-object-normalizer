<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures;

class Php80WithoutAccessors
{
    public string $string;

    /** @var string */
    public $stringWithDocBlock;

    public int $int;
    public float $float;
    public array $array;
    public DummyWithConstructor $nested;

    /** @var DummyWithConstructor[] */
    public array $objectCollection;

    /** @var int[] */
    public array $intCollection;

    public ?int $nullable = null;
}
