<?php

declare(strict_types=1);

namespace Tsantos\Symfony\Serializer\Normalizer;

use PhpBench\Reflection\ReflectionClass;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

final class SuperFastObjectNormalizer extends AbstractNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    private array $loaded = [];

    private const SCALAR_TYPES = [
        'int' => true,
        'bool' => true,
        'float' => true,
        'string' => true,
    ];

    public function __construct(
        private readonly NormalizerClassGenerator                     $classGenerator,
        private readonly NormalizerClassPersister                     $classPersister,
        ClassMetadataFactoryInterface                         $classMetadataFactory = null,
        NameConverterInterface                                $nameConverter = null,
        array                                                 $defaultContext = []
    )
    {
        parent::__construct($classMetadataFactory, $nameConverter, $defaultContext);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        $denormalizer = $this->getNormalizer($type);

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

        $normalizer = $this->getNormalizer($object, $context);

        if (false !== $attributes = $this->getAllowedAttributes($object, $context, true)) {
            $context['allowed_attributes'][get_class($object)] = array_flip($attributes);
        }

        return $normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return is_object($data) && !$data instanceof \Iterator;
    }

    public function getNormalizer(string|object $classOrObject): NormalizerInterface & DenormalizerInterface & ObjectFactoryInterface
    {
        $class = is_object($classOrObject) ? get_class($classOrObject) : $classOrObject;

        if (isset($this->loaded[$class])) {
            return $this->loaded[$class];
        }

        $phpFile = $this->classGenerator->generate($class);

        $generatedClass = $this->classPersister->persist($phpFile);

        return $this->loaded[$class] = new $generatedClass($this->serializer, $this);
    }
}