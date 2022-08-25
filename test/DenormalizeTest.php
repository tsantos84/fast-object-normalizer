<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer;

use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerClassPersister;
use Tsantos\Symfony\Serializer\Normalizer\SuperFastObjectNormalizer;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerClassGenerator;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyA;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyInterface;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithComplexAttributeInConstructor;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithConstructor;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithPrivateAttribute;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\Php80WithoutAccessors;

final class DenormalizeTest extends TestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $discriminator = new ClassDiscriminatorFromClassMetadata($classMetadataFactory);

        $this->serializer = new Serializer([
            new DateTimeNormalizer(),
            new UidNormalizer(),
            new DateTimeZoneNormalizer(),
            new ArrayDenormalizer(),
            new SuperFastObjectNormalizer(
                classGenerator: new NormalizerClassGenerator($classMetadataFactory, $discriminator),
                classPersister: new NormalizerClassPersister(__DIR__ . '/var'),
                classMetadataFactory: $classMetadataFactory,
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

    public function testDenormalizePrivateAttributes(): void
    {
        $data = [
            'private' => 'private',
            'public' => 'public'
        ];

        $result = $this->serializer->denormalize($data, DummyWithPrivateAttribute::class);
        $this->assertInstanceOf(DummyWithPrivateAttribute::class, $result);

        $ref = new \ReflectionObject($result);

        $this->assertSame('private', $ref->getProperty('private')->getValue($result));
        $this->assertSame('public', $result->public);
    }

    public function testDenormalizeWithDiscriminator(): void
    {
        $data = ['type' => 'dummyA'];
        $result = $this->serializer->denormalize($data, DummyInterface::class);
        $this->assertInstanceOf(DummyA::class, $result);
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

    public function testDenormalizeWithIgnoredAttribute(): void
    {
        $data = [
            'string' => 'foo',
            'int' => 10,
            'ignored' => 'ignored'
        ];

        $result = $this->serializer->denormalize($data, Php80WithoutAccessors::class);

        $this->assertInstanceOf(Php80WithoutAccessors::class, $result);
        $this->assertSame('foo', $result->string);
        $this->assertSame(10, $result->int);
        $this->assertNull($result->ignored);
    }

    public function testDenormalizeWithWithAttributesOnConstructor(): void
    {
        $data = [
            'foo' => ['foo' => 'bar'],
        ];

        $result = $this->serializer->denormalize($data, DummyWithComplexAttributeInConstructor::class);

        $this->assertInstanceOf(DummyWithComplexAttributeInConstructor::class, $result);
        $this->assertInstanceOf(DummyWithConstructor::class, $result->foo);
        $this->assertSame('bar', $result->foo->foo);
    }
}
