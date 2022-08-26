<?php

declare(strict_types=1);

/*
 * This file is part of the Tsantos Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tsantos\Test\Symfony\Serializer\Normalizer\Benchmark;

use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Warmup;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyInterface;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithConstructor;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithPrivateAttribute;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\Php80WithAccessors;

abstract class AbstractBench
{
    protected Serializer $serializer;

    public function __construct()
    {
        $this->serializer = new Serializer($this->getNormalizers(), ['json' => new JsonEncoder()]);
    }

    abstract public function getNormalizers(): array;

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
            'groups' => ['foo-group'],
        ]);
    }

    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('generateNormalizedData')]
    public function benchDenormalize(array $data): void
    {
        $this->serializer->denormalize($data, Php80WithAccessors::class);
    }

    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('generateDenormalizedDataWithPrivateProperties')]
    public function benchNormalizePrivateProperties(array $data): void
    {
        $this->serializer->normalize($data, 'json');
    }

    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('generateDataForDummyInterface')]
    public function benchDenormalizeInterface(array $data): void
    {
        foreach ($data as $row) {
            $this->serializer->denormalize($row, DummyInterface::class, 'json');
        }
    }

    public function generateObjects(): array
    {
        $data = [];
        for ($i = 1; $i <= 1000; ++$i) {
            $data[] = $subject = new Php80WithAccessors();
            $subject->string = 'foo1';
            $subject->stringWithDocBlock = 'foo2';
            $subject->float = 1.1;
            $subject->int = 1;
            $subject->array = ['foo' => 'bar'];
            $subject->intCollection = [1, 2, 3];
            $subject->objectCollection = [new DummyWithConstructor('foo', 'bar')];
        }

        return [
            'all' => $data,
        ];
    }

    public function generateNormalizedData(): array
    {
        $data = [];
        for ($i = 1; $i <= 1000; ++$i) {
            $data[] = [
                'string' => 'foo1',
                'stringWithDocBlock' => 'foo2',
                'float' => 1.1,
                'int' => 1,
                'array' => ['foo' => 'bar'],
                'intCollection' => [1, 2, 3, 4],
                'objectCollection' => [
                    ['foo' => 'bar1'],
                    ['foo' => 'bar1'],
                    ['foo' => 'bar1'],
                    ['foo' => 'bar1'],
                ],
            ];
        }

        return [
            'all' => $data,
        ];
    }

    public function generateDenormalizedDataWithPrivateProperties(): array
    {
        $data = [];
        for ($i = 1; $i <= 1000; ++$i) {
            $data[] = new DummyWithPrivateAttribute('private', 'public');
        }

        return [
            'all' => $data,
        ];
    }

    public function generateDataForDummyInterface(): array
    {
        $data = [];
        for ($i = 1; $i <= 2; ++$i) {
            $data[] = [
                'type' => 'dummyA',
            ];
        }

        return [
            'all' => $data,
        ];
    }
}
