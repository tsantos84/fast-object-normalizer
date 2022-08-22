<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
use Tsantos\Symfony\Serializer\Normalizer\GeneratedNormalizer;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerGenerator;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithConstructor;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\Php80WithoutAccessors;

class NormalizeTest extends TestCase
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
        $subject = $this->createDummyObject();

        $result = $this->serializer->normalize($subject);

        $this->assertSame('foo1', $result['string']);
        $this->assertSame(1.1, $result['float']);
        $this->assertSame(['foo' => 'bar'], $result['array']);
        $this->assertSame([['foo' => 'bar1']], $result['objectCollection']);
        $this->assertSame([1, 2, 3], $result['intCollection']);
    }

    public function testNormalizeCircularReference(): void
    {
        $this->expectException(CircularReferenceException::class);
        $subject = $this->createDummyObject();
        $subject->nested = $subject;
        $this->serializer->normalize($subject);
    }

    private function createDummyObject(): Php80WithoutAccessors
    {
        $object = new Php80WithoutAccessors();
        $object->string = 'foo1';
        $object->stringWithDocBlock = 'foo2';
        $object->float = 1.1;
        $object->int = 1;
        $object->array = ['foo' => 'bar'];
        $object->intCollection = [1, 2, 3];
        $object->objectCollection = [new DummyWithConstructor('bar1')];

        return $object;
    }
}