<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

class Php80WithoutAccessors
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
}
