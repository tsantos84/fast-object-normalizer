<?php

declare(strict_types=1);

/*
 * This file is part of the TSantos Fast Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TSantos\Test\FastObjectNormalizer;

use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;
use TSantos\FastObjectNormalizer\FastObjectNormalizer;
use TSantos\FastObjectNormalizer\NormalizerClassDumper;
use TSantos\FastObjectNormalizer\NormalizerClassGenerator;
use TSantos\Test\FastObjectNormalizer\Fixtures\DummyA;
use TSantos\Test\FastObjectNormalizer\Fixtures\DummyInterface;
use TSantos\Test\FastObjectNormalizer\Fixtures\DummyWithComplexAttributeInConstructor;
use TSantos\Test\FastObjectNormalizer\Fixtures\DummyWithConstructor;
use TSantos\Test\FastObjectNormalizer\Fixtures\DummyWithPrivateAttribute;
use TSantos\Test\FastObjectNormalizer\Fixtures\Php80WithoutAccessors;

final class DenormalizationTest extends TestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $discriminator = new ClassDiscriminatorFromClassMetadata($classMetadataFactory);

        $this->serializer = new Serializer([
            new ArrayDenormalizer(),
            new FastObjectNormalizer(
                classGenerator: new NormalizerClassGenerator($classMetadataFactory, $discriminator),
                classDumper: new NormalizerClassDumper(__DIR__.'/var', true),
                classMetadataFactory: $classMetadataFactory,
            ),
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
                ['foo' => 'foo', 'bar' => 'bar'],
                ['foo' => 'foo', 'bar' => 'bar'],
            ],
            'intCollection' => [1, 2, 3, 4],
        ];

        $result = $this->serializer->denormalize($data, Php80WithoutAccessors::class);

        $this->assertInstanceOf(Php80WithoutAccessors::class, $result);
        $this->assertSame('foo', $result->string);
        $this->assertSame(1.1, $result->float);
        $this->assertSame(['foo' => 'bar'], $result->array);
        $this->assertCount(2, $result->objectCollection);
        $this->assertInstanceOf(DummyWithConstructor::class, $result->objectCollection[0]);
        $this->assertSame('foo', $result->objectCollection[0]->foo);
        $this->assertSame('foo', $result->objectCollection[1]->foo);
    }

    public function testDenormalizePrivateAttributes(): void
    {
        $data = [
            'private' => 'private',
            'public' => 'public',
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
            'nullable' => 1,
        ];

        $result = $this->serializer->denormalize($data, Php80WithoutAccessors::class, 'json', [
            'groups' => ['foo-group'],
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
            'ignored' => 'ignored',
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
            'foo' => ['foo' => 'foo', 'bar' => 'bar'],
        ];

        $result = $this->serializer->denormalize($data, DummyWithComplexAttributeInConstructor::class);

        $this->assertInstanceOf(DummyWithComplexAttributeInConstructor::class, $result);
        $this->assertInstanceOf(DummyWithConstructor::class, $result->foo);
        $this->assertSame('foo', $result->foo->foo);
        $this->assertSame('bar', $result->foo->bar);
    }

    public function testDenormalizeWithCallbacks(): void
    {
        $data = [
            'string' => 'foo',
            'objectCollection' => [
                ['foo' => 'foo', 'bar' => 'bar'],
            ],
        ];

        $toUpper = fn (string $value): string => strtoupper($value);

        $result = $this->serializer->denormalize($data, Php80WithoutAccessors::class, null, [
            AbstractNormalizer::CALLBACKS => [
                'string' => $toUpper,
                'objectCollection' => [
                    'foo' => $toUpper,
                ],
            ],
        ]);

        $this->assertInstanceOf(Php80WithoutAccessors::class, $result);
        $this->assertSame('FOO', $result->string);
        $this->assertSame('FOO', $result->objectCollection[0]->foo);
    }
}
