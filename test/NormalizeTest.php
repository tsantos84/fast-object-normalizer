<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer;

use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
use Tsantos\Symfony\Serializer\Normalizer\SuperFastObjectNormalizer;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerClassGenerator;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerClassPersister;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerLoader;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyA;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyInterface;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithConstructor;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithPrivateAttribute;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\Php80WithoutAccessors;

class NormalizeTest extends TestCase
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

    public function testNormalize(): void
    {
        $subject = $this->createDummyObject();

        $result = $this->serializer->normalize($subject, 'json');

        $this->assertSame('foo1', $result['string']);
        $this->assertSame(1.1, $result['float']);
        $this->assertSame(['foo' => 'bar'], $result['array']);
        $this->assertSame([['foo' => 'bar1']], $result['objectCollection']);
        $this->assertSame([1, 2, 3], $result['intCollection']);
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
            AbstractNormalizer::GROUPS => 'foo-group'
        ]);

        $this->assertSame('foo1', $result['string']);
        $this->assertSame(1, $result['int']);
        $this->assertArrayNotHasKey('stringWithDocBlock', $result);
    }

    public function testNormalizeProvidingAttributesInContext(): void
    {
        $subject = $this->createDummyObject();

        $result = $this->serializer->normalize($subject, 'json', [
            AbstractNormalizer::ATTRIBUTES => ['string', 'int']
        ]);

        $this->assertSame('foo1', $result['string']);
        $this->assertSame(1, $result['int']);
        $this->assertArrayNotHasKey('stringWithDocBlock', $result);
    }

    public function testNormalizeWithIgnoreAttribute(): void
    {
        $subject = $this->createDummyObject();
        $result = $this->serializer->normalize($subject, );
        $this->assertArrayNotHasKey('ignored', $result);
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
        $object->ignored = 'ignored';
        $object->fooName = 'foo';

        return $object;
    }
}
