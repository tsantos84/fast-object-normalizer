<?php

declare(strict_types=1);

/*
 * This file is part of the Tsantos Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tsantos\Symfony\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;

abstract class AbstractObjectNormalizer implements NormalizerInterface, ObjectFactoryInterface
{
    protected static string $targetType;
    protected static array $allowedAttributes = [];
    protected readonly \ReflectionClass $refClass;

    public function __construct(
        protected readonly Serializer $serializer,
        protected readonly FastObjectNormalizer $normalizer,
    ) {
        $this->refClass = new \ReflectionClass(static::$targetType);
    }

    protected function getAllowedAttributes(array $context): array
    {
        if (!isset($context[AbstractNormalizer::GROUPS])) {
            $attributes = static::$allowedAttributes['*'] ?? [];
        } else {
            $groupsKey = implode('-', (array) $context[AbstractNormalizer::GROUPS]);
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
            foreach ((array) $context[AbstractNormalizer::ATTRIBUTES] as $key => $attribute) {
                $filtered[] = \is_array($attribute) ? $key : $attribute;
            }
            $attributes = array_intersect_key($attributes, array_flip($filtered));
        }

        if (isset($context[AbstractNormalizer::IGNORED_ATTRIBUTES])) {
            $filtered = [];
            foreach ((array) $context[AbstractNormalizer::IGNORED_ATTRIBUTES] as $key => $attribute) {
                // allowing nested object to be de-normalized
                if (\is_array($attribute) && !empty($attribute)) {
                    continue;
                }
                $filtered[] = \is_array($attribute) && !empty($attribute) ? $key : $attribute;
            }
            $attributes = array_diff_key($attributes, array_flip($filtered));
        }

        return $attributes;
    }

    protected function createChildContext(string $property, array $context = []): array
    {
        $newContext = $context;

        if (isset($context[AbstractNormalizer::ATTRIBUTES]) && isset($context[AbstractNormalizer::ATTRIBUTES][$property])) {
            $newContext[AbstractNormalizer::ATTRIBUTES] = $context[AbstractNormalizer::ATTRIBUTES][$property];
        }

        if (isset($context[AbstractNormalizer::IGNORED_ATTRIBUTES]) && isset($context[AbstractNormalizer::IGNORED_ATTRIBUTES][$property])) {
            $newContext[AbstractNormalizer::IGNORED_ATTRIBUTES] = $context[AbstractNormalizer::IGNORED_ATTRIBUTES][$property];
        }

        if (isset($context[AbstractNormalizer::CALLBACKS]) && isset($context[AbstractNormalizer::CALLBACKS][$property])) {
            $newContext[AbstractNormalizer::CALLBACKS] = $context[AbstractNormalizer::CALLBACKS][$property];
        }

        return $newContext;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        return $this->doDenormalize($data, $type, $format, $context);
    }

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        $data = $this->doNormalize($object, $format, $context);

        foreach ($context[AbstractNormalizer::CALLBACKS] ?? [] as $attribute => $callback) {
            if (is_callable($callback) && array_key_exists($attribute, $data)) {
                $data[$attribute] = call_user_func($callback, $data[$attribute], $data, $attribute, $format, $context);
            }
        }

        return $data;
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

    protected abstract function doNormalize(mixed $object, ?string $format = null, array $context = []): array;

    protected abstract function doDenormalize(array $data, string $type, string $format = null, array $context = []): object;
}
