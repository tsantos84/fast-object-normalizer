<?php

declare(strict_types=1);

namespace Tsantos\Symfony\Serializer\Normalizer;

use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

final class FastObjectNormalizer extends AbstractNormalizer implements NormalizerInterface, SerializerAwareInterface
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
        private readonly NormalizerClassGenerator $classGenerator,
        private readonly NormalizerClassDumper    $classDumper,
        ClassMetadataFactoryInterface             $classMetadataFactory = null,
        NameConverterInterface                    $nameConverter = null,
        array                                     $defaultContext = []
    )
    {
        parent::__construct($classMetadataFactory, $nameConverter, $defaultContext);
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
        if ($this->isCircularReference($object, $context)) {
            return $this->handleCircularReference($object, $format, $context);
        }

        $normalizer = $this->getNormalizer($object);

        return $normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return is_object($data) && !$data instanceof \Iterator;
    }

    public function getNormalizer(string|object $classOrObject): NormalizerInterface
    {
        $class = is_object($classOrObject) ? get_class($classOrObject) : $classOrObject;

        if (isset($this->loaded[$class])) {
            return $this->loaded[$class];
        }

        $config = new NormalizerClassConfig($class, $this->classDumper->outputDir);

        if (!$config->fileExists()) {
            $phpFile = $this->classGenerator->generate($config);
            $this->classDumper->dump($config, $phpFile);
        } elseif (!$config->isLoaded()) {
            $config->load();
        }

        return $this->loaded[$class] = $config->newInstance([
            'serializer' => $this->serializer,
            'normalizer' => $this
        ]);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
