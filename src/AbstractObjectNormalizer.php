<?php

declare(strict_types=1);

namespace Tsantos\Symfony\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;

abstract class AbstractObjectNormalizer implements NormalizerInterface, ObjectFactoryInterface
{
    protected static string $targetType;
    protected static array $allowedAttributes = [];
    protected readonly \ReflectionClass $refClass;

    public function __construct(
        protected readonly Serializer           $serializer,
        protected readonly FastObjectNormalizer $normalizer,
    ) {
        $this->refClass = new \ReflectionClass(static::$targetType);
    }

    protected function getAllowedAttributes(array $context): array
    {
        if (!isset($context[AbstractNormalizer::GROUPS])) {
            return static::$allowedAttributes['*'] ?? [];
        }

        $groupsKey = implode('-', (array) $context[AbstractNormalizer::GROUPS]);

        if (isset(static::$allowedAttributes[$groupsKey])) {
            return static::$allowedAttributes[$groupsKey];
        }

        $attributes = [];

        foreach ($context[AbstractNormalizer::GROUPS] as $group) {
            $attributes = array_merge($attributes, static::$allowedAttributes[$group] ?? []);
        }

        return static::$allowedAttributes[$groupsKey] = $attributes;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null)
    {
        return $type === static::$targetType;
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return $data instanceof static::$targetType;
    }
}
