<?php

declare(strict_types=1);

/*
 * This file is part of the TSantos Fast Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    public function getAttributeMutator(AttributeView $attributeView, array $replacements = []): string
    {
        if (null !== $method = self::getMutatorMethodName($attributeView->classView->targetRefClass, $attributeView->name)) {
            return strtr(':object->'.$method.'(:value)', $replacements);
        }

        $refProperty = $attributeView->classView->targetRefClass->getProperty($attributeView->name);

        if ($refProperty->isPublic()) {
            return strtr(':object->'.$attributeView->name.' = :value', $replacements);
        }

        return strtr(':refClass->getProperty(\''.$attributeView->name.'\')->setValue(:object, :value)', $replacements);
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

    public function getAttributeAccessor(AttributeView $attributeView, array $replacements = []): string
    {
        if (null !== $method = self::getAccessorMethodName($attributeView->classView->targetRefClass, $attributeView->name)) {
            return strtr(':object->'.$method.'()', $replacements);
        }

        $refProperty = $attributeView->classView->targetRefClass->getProperty($attributeView->name);

        if ($refProperty->isPublic()) {
            return strtr(':object->'.$attributeView->name, $replacements);
        }

        return strtr(':refClass->getProperty(\''.$attributeView->name.'\')->getValue(:object)', $replacements);
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
