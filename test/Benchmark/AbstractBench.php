<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer\Benchmark;

use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
use Tsantos\Symfony\Serializer\Normalizer\GeneratedNormalizer;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerGenerator;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithConstructor;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\Php80WithAccessors;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\Php80WithoutAccessors;

abstract class AbstractBench
{
    protected Serializer $serializer;

    public function __construct()
    {
        $this->serializer = new Serializer($this->getNormalizers(), ['json' => new JsonEncoder()]);
    }

    abstract function getNormalizers(): array;

    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('generateObjects')]
    public function benchNormalize(array $data): void
    {
        $this->serializer->normalize($data);
    }

    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('generateObjects')]
    public function benchNormalizeWithGroups(array $data): void
    {
        $this->serializer->normalize($data, 'json', [
            'groups' => ['foo-group']
        ]);
    }

    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('generateNormalizedData')]
    public function benchDenormalize(array $data): void
    {
        $this->serializer->denormalize($data, Php80WithAccessors::class);
    }

    public function generateObjects(): array
    {
        $data = [];
        for ($i = 1; $i <= 1000; $i++) {
            $data[] = $subject = new Php80WithAccessors();
            $subject->string = 'foo1';
            $subject->stringWithDocBlock = 'foo2';
            $subject->float = 1.1;
            $subject->int = 1;
            $subject->array = ['foo' => 'bar'];
            $subject->intCollection = [1, 2, 3];
            $subject->objectCollection = [new DummyWithConstructor('bar1')];
        }

        return [
            'all' => $data
        ];
    }

    public function generateNormalizedData(): array
    {
        $data = [];
        for ($i = 1; $i <= 1000; $i++) {
            $data[] = [
                'string' => 'foo1',
                'stringWithDocBlock' => 'foo2',
                'float' => 1.1,
                'int' => 1,
                'array' => ['foo' => 'bar'],
                'intCollection' => [1,2,3,4],
                'objectCollection' => [
                    ['foo' => 'bar1'],
                    ['foo' => 'bar1'],
                    ['foo' => 'bar1'],
                    ['foo' => 'bar1'],
                ]
            ];
        }

        return [
            'all' => $data
        ];
    }
}
