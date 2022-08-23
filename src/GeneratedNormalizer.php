<?php

declare(strict_types=1);

namespace Tsantos\Symfony\Serializer\Normalizer;

use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

final class GeneratedNormalizer extends AbstractNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
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
        private readonly NormalizerGenerator $generator,
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        array $defaultContext = []
    )
    {
        parent::__construct($classMetadataFactory, $nameConverter, $defaultContext);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        $denormalizer = $this->loadNormalizer($type);

        if (false !== $attributes = $this->getAllowedAttributes($type, $context, true)) {
            $context['allowed_attributes'][$type] = array_flip($attributes);
        }

        return $denormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null)
    {
        return !array_key_exists($type, self::SCALAR_TYPES);
    }

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        if ($this->isCircularReference($object, $context)) {
            return $this->handleCircularReference($object, $format, $context);
        }

        $normalizer = $this->loadNormalizer($object, $context);

        if (false !== $attributes = $this->getAllowedAttributes($object, $context, true)) {
            $context['allowed_attributes'][get_class($object)] = array_flip($attributes);
        }

        return $normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return is_object($data);
    }

    private function loadNormalizer(string|object $classOrObject, array $context = []): NormalizerInterface & DenormalizerInterface & ObjectFactoryInterface
    {
        $class = is_object($classOrObject) ? get_class($classOrObject) : $classOrObject;

        if (isset(self::$loaded[$class])) {
            return self::$loaded[$class];
        }

        $result = $this->generator->generate($classOrObject, $this->classMetadataFactory);

        if (!class_exists($result['className'], false)) {
            include_once $result['filename'];
        }

        return self::$loaded[$class] = new $result['className']($this->serializer);
    }
}
