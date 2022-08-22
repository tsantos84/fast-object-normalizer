<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer\Benchmark;

use PhpBench\Attributes\Groups;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Warmup;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Tsantos\Symfony\Serializer\Normalizer\GeneratedNormalizer;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerGenerator;

final class SerializerWithGeneratedNormalizerBench extends AbstractBench
{
    public function getNormalizers(): array
    {
        return [
            new DateTimeNormalizer(),
            new UidNormalizer(),
            new DateTimeZoneNormalizer(),
            new ArrayDenormalizer(),
            new GeneratedNormalizer(new NormalizerGenerator(__DIR__ . '/../var'))
        ];
    }
}
