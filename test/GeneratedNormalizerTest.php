<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
use Tsantos\Symfony\Serializer\Normalizer\GeneratedNormalizer;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerGenerator;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithConstructor;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\Php80WithoutAccessors;

class GeneratedNormalizerTest extends TestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new Serializer([
            new DateTimeNormalizer(),
            new UidNormalizer(),
            new DateTimeZoneNormalizer(),
            new ArrayDenormalizer(),
            new GeneratedNormalizer(new NormalizerGenerator(__DIR__ . '/var'))
        ], ['json' => new JsonEncoder()]);
    }

    public function testNormalize(): void
    {
        $subject = new Php80WithoutAccessors();
        $subject->string = 'foo1';
        $subject->stringWithDocBlock = 'foo2';
        $subject->float = 1.1;
        $subject->int = 1;
        $subject->array = ['foo' => 'bar'];
        $subject->intCollection = [1, 2, 3];
        $subject->nested = new DummyWithConstructor('foo');
        $subject->objectCollection = [new DummyWithConstructor('bar1')];

        $result = $this->serializer->normalize($subject);

        $this->assertSame('foo1', $result['string']);
        $this->assertSame(1.1, $result['float']);
        $this->assertSame(['foo' => 'bar'], $result['array']);
        $this->assertSame(['foo' => 'foo'], $result['nested']);
        $this->assertSame([['foo' => 'bar1']], $result['objectCollection']);
        $this->assertSame([1, 2, 3], $result['intCollection']);
    }

    public function testDenormalize(): void
    {
        $data = [
            'string' => 'foo',
            'float' => 1.1,
            'int' => 10,
            'array' => ['foo' => 'bar'],
            'objectCollection' => [
                ['foo' => 'bar'],
                ['foo' => 'baz'],
            ],
            'intCollection' => [1, 2, 3, 4]
        ];

        $result = $this->serializer->denormalize($data, Php80WithoutAccessors::class);

        $this->assertInstanceOf(Php80WithoutAccessors::class, $result);
        $this->assertSame('foo', $result->string);
        $this->assertSame(1.1, $result->float);
        $this->assertSame(['foo' => 'bar'], $result->array);
        $this->assertCount(2, $result->objectCollection);
        $this->assertSame('bar', $result->objectCollection[0]->foo);
        $this->assertSame('baz', $result->objectCollection[1]->foo);
    }
}
