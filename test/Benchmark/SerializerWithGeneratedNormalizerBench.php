<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer\Benchmark;

use Doctrine\Common\Annotations\AnnotationReader;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Warmup;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerClassDumper;
use Tsantos\Symfony\Serializer\Normalizer\SuperFastObjectNormalizer;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerClassGenerator;

final class SerializerWithGeneratedNormalizerBench extends AbstractBench
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
            new SuperFastObjectNormalizer(
                classGenerator: new NormalizerClassGenerator($classMetadataFactory, $discriminator),
                classDumper: new NormalizerClassDumper(__DIR__ . '/../var'),
                classMetadataFactory: $classMetadataFactory,
            )
        ];
    }
}
