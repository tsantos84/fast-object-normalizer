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
    )
    {
        $this->refClass = new \ReflectionClass(static::$targetType);
    }

    protected function getAllowedAttributes(array $context): array
    {
        if (!isset($context[AbstractNormalizer::GROUPS])) {
            $attributes = static::$allowedAttributes['*'] ?? [];
        } else {
            $groupsKey = implode('-', (array)$context[AbstractNormalizer::GROUPS]);
            if (isset(static::$allowedAttributes[$groupsKey])) {
                $attributes = static::$allowedAttributes[$groupsKey];
            } else {
                $attributes = [];
                foreach ($context[AbstractNormalizer::GROUPS] as $group) {
                    $attributes = array_merge($attributes, static::$allowedAttributes[$group] ?? []);
                }
                static::$allowedAttributes[$groupsKey] = $attributes;
            }
        }

        if (isset($context[AbstractNormalizer::ATTRIBUTES])) {
            $filtered = [];
            foreach ((array)$context[AbstractNormalizer::ATTRIBUTES] as $key => $attribute) {
                $filtered[] = is_array($attribute) ? $key : $attribute;
            }
            $attributes = array_intersect_key($attributes, array_flip($filtered));
        }

        if (isset($context[AbstractNormalizer::IGNORED_ATTRIBUTES])) {
            $filtered = [];
            foreach ((array)$context[AbstractNormalizer::IGNORED_ATTRIBUTES] as $key => $attribute) {
                // allowing nested object to be de-normalized
                if (is_array($attribute) && !empty($attribute)) {
                    continue;
                }
                $filtered[] = is_array($attribute) && !empty($attribute) ? $key : $attribute;
            }
            $attributes = array_diff_key($attributes, array_flip($filtered));
        }

        return $attributes;
    }

    protected function createContextForProperty(string $property, array $context = []): array
    {
        $newContext = $context;

        if (isset($context[AbstractNormalizer::ATTRIBUTES]) && isset($context[AbstractNormalizer::ATTRIBUTES][$property])) {
            $newContext[AbstractNormalizer::ATTRIBUTES] = $context[AbstractNormalizer::ATTRIBUTES][$property];
        }

        if (isset($context[AbstractNormalizer::IGNORED_ATTRIBUTES]) && isset($context[AbstractNormalizer::IGNORED_ATTRIBUTES][$property])) {
            $newContext[AbstractNormalizer::IGNORED_ATTRIBUTES] = $context[AbstractNormalizer::IGNORED_ATTRIBUTES][$property];
        }

        return $newContext;
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
