<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer;

use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
use Tsantos\Symfony\Serializer\Normalizer\GeneratedNormalizer;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerGenerator;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\Php80WithoutAccessors;

final class DenormalizeTest extends TestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new Serializer([
            new DateTimeNormalizer(),
            new UidNormalizer(),
            new DateTimeZoneNormalizer(),
            new ArrayDenormalizer(),
            new GeneratedNormalizer(
                generator: new NormalizerGenerator(outputDir: __DIR__ . '/var', overwrite: true),
                classMetadataFactory: new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()))
            )
        ], ['json' => new JsonEncoder()]);
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

    public function testDenormalizeWithGroups(): void
    {
        $data = [
            'string' => 'foo',
            'int' => 10,
            'nullable' => 1
        ];

        $result = $this->serializer->denormalize($data, Php80WithoutAccessors::class, 'json', [
            'groups' => ['foo-group']
        ]);

        $this->assertInstanceOf(Php80WithoutAccessors::class, $result);
        $this->assertSame('foo', $result->string);
        $this->assertSame(10, $result->int);
        $this->assertNull($result->nullable);
    }
}
