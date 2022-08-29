<?php

declare(strict_types=1);

/*
 * This file is part of the TSantos Fast Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TSantos\FastObjectNormalizer;

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
        private readonly NormalizerClassDumper $classDumper,
        private readonly array $includedTypes = [],
        private readonly array $excludedTypes = [],
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        array $defaultContext = []
    ) {
        parent::__construct($classMetadataFactory, $nameConverter, $defaultContext);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        $denormalizer = $this->getNormalizer($type);

        return $denormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null)
    {
        if (!$this->supportType($type)) {
            return false;
        }

        return $this->getNormalizer($type)->supportsDenormalization($data, $type, $format);
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
        if (!\is_object($data) || $data instanceof \Iterator) {
            return false;
        }

        if (!$this->supportType(\get_class($data))) {
            return false;
        }

        return $this->getNormalizer(\get_class($data))->supportsNormalization($data, $format);
    }

    private function supportType(string $type): bool
    {
        if (isset(self::SCALAR_TYPES[$type]) || str_ends_with($type, '[]')) {
            return false;
        }

        foreach ($this->includedTypes as $include) {
            if (preg_match('/'.$include.'/', $type) > 0) {
                return true;
            }
        }

        foreach ($this->excludedTypes as $exclude) {
            if (preg_match('/'.$exclude.'/', $type) > 0) {
                return false;
            }
        }

        return true;
    }

    public function getNormalizer(string|object $classOrObject): NormalizerInterface
    {
        $class = \is_object($classOrObject) ? \get_class($classOrObject) : $classOrObject;

        if (isset($this->loaded[$class])) {
            return $this->loaded[$class];
        }

        $config = new NormalizerClassConfig($class, $this->classDumper->outputDir);

        if (!$config->fileExists() || $this->classDumper->overwrite) {
            $phpFile = $this->classGenerator->generate($config);
            $this->classDumper->dump($config, $phpFile);
        } elseif (!$config->isLoaded()) {
            $config->load();
        }

        return $this->loaded[$class] = $config->newInstance([
            'serializer' => $this->serializer,
            'normalizer' => $this,
        ]);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
