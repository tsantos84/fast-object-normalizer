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
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;
use TSantos\FastObjectNormalizer\FastObjectNormalizer;
use TSantos\FastObjectNormalizer\NormalizerClassDumper;
use TSantos\FastObjectNormalizer\NormalizerClassGenerator;
use TSantos\Test\FastObjectNormalizer\Fixtures\DummyA;
use TSantos\Test\FastObjectNormalizer\Fixtures\DummyWithConstructor;
use TSantos\Test\FastObjectNormalizer\Fixtures\DummyWithPrivateAttribute;
use TSantos\Test\FastObjectNormalizer\Fixtures\Php80WithoutAccessors;

class NormalizationTest extends TestCase
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

    public function testNormalize(): void
    {
        $subject = $this->createDummyObject();

        $result = $this->serializer->normalize($subject, 'json');

        $this->assertSame('foo1', $result['string']);
        $this->assertSame(1.1, $result['float']);
        $this->assertSame(['foo' => 'bar'], $result['array']);
        $this->assertSame([['foo' => 'foo', 'bar' => 'bar']], $result['objectCollection']);
        $this->assertSame([1, 2, 3], $result['intCollection']);
        $this->assertNull($result['nullable']);
    }

    public function testNormalizeSkipingNullValues(): void
    {
        $subject = $this->createDummyObject();
        $result = $this->serializer->normalize($subject, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);
        $this->assertArrayNotHasKey('nullable', $result);
    }

    public function testNormalizePrivateAttributes(): void
    {
        $subject = new DummyWithPrivateAttribute('private', 'public');
        $result = $this->serializer->normalize($subject);
        $this->assertSame('private', $result['private']);
        $this->assertSame('public', $result['public']);
    }

    public function testNormalizeWithGroup(): void
    {
        $subject = $this->createDummyObject();

        $result = $this->serializer->normalize($subject, 'json', [
            AbstractNormalizer::GROUPS => ['foo-group', 'bar-group'],
        ]);

        $this->assertSame('foo1', $result['string']);
        $this->assertSame(1, $result['int']);
        $this->assertSame(1.1, $result['float']);
        $this->assertArrayNotHasKey('stringWithDocBlock', $result);
    }

    public function testNormalizeProvidingAttributesInContext(): void
    {
        $subject = $this->createDummyObject();

        $result = $this->serializer->normalize($subject, 'json', [
            AbstractNormalizer::ATTRIBUTES => ['string', 'int', 'objectCollection' => ['foo']],
        ]);

        $this->assertSame('foo1', $result['string']);
        $this->assertSame(1, $result['int']);
        $this->assertArrayNotHasKey('stringWithDocBlock', $result);

        // assert keys of nested objects
        $this->assertArrayHasKey('objectCollection', $result);
        $this->assertArrayHasKey('foo', $result['objectCollection'][0]);
        $this->assertArrayNotHasKey('bar', $result['objectCollection'][0]);
    }

    public function testNormalizeWithIgnoreAttribute(): void
    {
        $subject = $this->createDummyObject();
        $result = $this->serializer->normalize($subject);
        $this->assertArrayNotHasKey('ignored', $result);
    }

    public function testNormalizeWithIgnoreAttributeOnContext(): void
    {
        $subject = $this->createDummyObject();
        $result = $this->serializer->normalize($subject, 'json', [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['string', 'objectCollection' => ['foo']],
        ]);
        $this->assertArrayNotHasKey('string', $result);

        // assert nested attributes
        $this->assertArrayHasKey('objectCollection', $result);
        $this->assertArrayNotHasKey('foo', $result['objectCollection'][0]);
        $this->assertArrayHasKey('bar', $result['objectCollection'][0]);
    }

    public function testNormalizeCircularReference(): void
    {
        $this->expectException(CircularReferenceException::class);
        $subject = $this->createDummyObject();
        $subject->nested = $subject;
        $this->serializer->normalize($subject);
    }

    public function testNormalizeWithSerializedNameAttribute(): void
    {
        $subject = $this->createDummyObject();
        $result = $this->serializer->normalize($subject);
        $this->assertArrayHasKey('barName', $result);
        $this->assertArrayNotHasKey('fooName', $result);
    }

    public function testNormalizeWithClassDiscriminator(): void
    {
        $subject = new DummyA();
        $result = $this->serializer->normalize($subject);
        $this->assertArrayHasKey('type', $result);
        $this->assertSame('dummyA', $result['type']);
    }

    public function testNormalizeAttributeWithCallback(): void
    {
        $toUpper = fn (string $value): string => strtoupper($value);
        $subject = $this->createDummyObject();
        $result = $this->serializer->normalize($subject, null, [
            AbstractNormalizer::CALLBACKS => [
                'string' => $toUpper,
                'objectCollection' => [
                    'foo' => $toUpper,
                ],
            ],
        ]);
        $this->assertArrayHasKey('string', $result);
        $this->assertSame('FOO1', $result['string']);

        $this->assertArrayHasKey('objectCollection', $result);
        $this->assertArrayHasKey('foo', $result['objectCollection'][0]);
        $this->assertSame('FOO', $result['objectCollection'][0]['foo']);
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
        $object->objectCollection = [new DummyWithConstructor('foo', 'bar')];
        $object->ignored = 'ignored';
        $object->fooName = 'foo';

        return $object;
    }
}
