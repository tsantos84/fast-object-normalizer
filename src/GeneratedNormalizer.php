<?php

declare(strict_types=1);

namespace Tsantos\Symfony\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

final class GeneratedNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    private const SCALAR_TYPES = [
        'int' => true,
        'bool' => true,
        'float' => true,
        'string' => true,
    ];

    private static array $loaded = [];

    public function __construct(
        private readonly NormalizerGenerator $generator
    )
    {
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        $denormalizer = $this->getNormalizer($type);

        return $denormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null)
    {
        return !array_key_exists($type, self::SCALAR_TYPES);
    }

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        $normalizer = $this->getNormalizer($object);

        return $normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return is_object($data);
    }

    private function getNormalizer(string|object $classOrObject): NormalizerInterface & DenormalizerInterface & ObjectFactoryInterface
    {
        $class = is_object($classOrObject) ? get_class($classOrObject) : $classOrObject;

        if (isset(self::$loaded[$class])) {
            return self::$loaded[$class];
        }

        $result = $this->generator->generate($classOrObject);

        if (!class_exists($result['className'], false)) {
            include_once $result['filename'];
        }

        return self::$loaded[$class] = new $result['className']($this->serializer);
    }
}
