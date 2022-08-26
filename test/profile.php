<?php

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerClassGenerator;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerClassDumper;
use Tsantos\Symfony\Serializer\Normalizer\SuperFastObjectNormalizer;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithConstructor;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\Php80WithoutAccessors;

require __DIR__ . '/../vendor/autoload.php';

$classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
$discriminator = new ClassDiscriminatorFromClassMetadata($classMetadataFactory);

$serializer = new Serializer([
    new ArrayDenormalizer(),
    new SuperFastObjectNormalizer(
        classGenerator: new NormalizerClassGenerator($classMetadataFactory, $discriminator),
        classDumper: new NormalizerClassDumper(__DIR__ . '/var', false),
        classMetadataFactory: $classMetadataFactory,
    )
], ['json' => new JsonEncoder()]);

function createDummyObject(): Php80WithoutAccessors {
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

$object = createDummyObject();
$result = $serializer->normalize($object);