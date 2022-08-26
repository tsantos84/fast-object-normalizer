<?php

declare(strict_types=1);

/*
 * This file is part of the TSantos Fast Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;
use TSantos\FastObjectNormalizer\FastObjectNormalizer;
use TSantos\FastObjectNormalizer\NormalizerClassDumper;
use TSantos\FastObjectNormalizer\NormalizerClassGenerator;
use TSantos\Test\FastObjectNormalizer\Fixtures\DummyWithConstructor;
use TSantos\Test\FastObjectNormalizer\Fixtures\Php80WithoutAccessors;

require __DIR__.'/../vendor/autoload.php';

$classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
$discriminator = new ClassDiscriminatorFromClassMetadata($classMetadataFactory);

$serializer = new Serializer([
    new ArrayDenormalizer(),
    new FastObjectNormalizer(
        classGenerator: new NormalizerClassGenerator($classMetadataFactory, $discriminator),
        classDumper: new NormalizerClassDumper(__DIR__.'/var', false),
        classMetadataFactory: $classMetadataFactory,
    ),
], ['json' => new JsonEncoder()]);

function createDummyObject(): Php80WithoutAccessors
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

$object = createDummyObject();
$result = $serializer->normalize($object);
