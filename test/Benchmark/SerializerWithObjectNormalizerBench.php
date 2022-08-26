<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer\Benchmark;

use Doctrine\Common\Annotations\AnnotationReader;
use PhpBench\Attributes\Skip;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;

final class SerializerWithObjectNormalizerBench extends AbstractBench
{
    public function getNormalizers(): array
    {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $discriminator = new ClassDiscriminatorFromClassMetadata($classMetadataFactory);

        return [
            new DateTimeNormalizer(),
            new UidNormalizer(),
            new DateTimeZoneNormalizer(),
            new ArrayDenormalizer(),
            new ObjectNormalizer(
                classMetadataFactory: $classMetadataFactory,
                classDiscriminatorResolver: $discriminator
            )
        ];
    }
}
