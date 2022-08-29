<?php

declare(strict_types=1);

namespace TSantos\FastObjectNormalizer\Twig\Extension;

use TSantos\FastObjectNormalizer\View\AttributeView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class NormalizerClassExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('attr_mutator', [$this, 'getAttributeMutator']),
            new TwigFunction('attr_accessor', [$this, 'getAttributeAccessor']),
        ];
    }

    public function getAttributeMutator(AttributeView $attributeView, array $replacements = [], bool $allowRefl = true): ?string
    {
        if (null !== $method = self::getMutatorMethodName($attributeView->classView->targetRefClass, $attributeView->name)) {
            return strtr(':object->'.$method.'(:value)', $replacements);
        }

        if (self::isMutable($attributeView->classView->targetRefClass, $attributeView->name)) {
            return strtr(':object->'.$attributeView->name.' = :value', $replacements);
        }

        if ($allowRefl) {
            return strtr(':refClass->getProperty(\''.$attributeView->name.'\')->setValue(:object, :value)', $replacements);
        }

        return null;
    }

    public function getAttributeAccessor(AttributeView $attributeView, array $replacements = [], bool $allowRef = true): ?string
    {
        if (null !== $method = self::getAccessorMethodName($attributeView->classView->targetRefClass, $attributeView->name)) {
            return strtr(':object->'.$method.'()', $replacements);
        }

        if (self::isAccessible($attributeView->classView->targetRefClass, $attributeView->name)) {
            return strtr(':object->'.$attributeView->name, $replacements);
        }

        if ($allowRef) {
            return strtr(':refClass->getProperty(\''.$attributeView->name.'\')->getValue(:object)', $replacements);
        }

        return null;
    }

    private static function isMutable(\ReflectionClass $refClass, string $property): bool
    {
        if (null !== self::getMutatorMethodName($refClass, $property)) {
            return true;
        }

        $refProperty = $refClass->getProperty($property);

        return $refProperty->isPublic();
    }

    private static function getMutatorMethodName(\ReflectionClass $refClass, string $property): ?string
    {
        foreach (['set', 'with', 'add'] as $prefix) {
            $method = $prefix.ucfirst($property);
            if ($refClass->hasMethod($method)) {
                return $method;
            }
        }

        return null;
    }

    private static function isAccessible(\ReflectionClass $refClass, string $property): bool
    {
        if (null !== self::getAccessorMethodName($refClass, $property)) {
            return true;
        }

        $refProperty = $refClass->getProperty($property);

        return $refProperty->isPublic();
    }

    private static function getAccessorMethodName(\ReflectionClass $refClass, string $property): ?string
    {
        foreach (['get', 'is', 'can', 'has'] as $prefix) {
            $method = $prefix.ucfirst($property);
            if ($refClass->hasMethod($method)) {
                return $method;
            }
        }

        return null;
    }
}
