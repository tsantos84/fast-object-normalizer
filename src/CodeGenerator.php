<?php

declare(strict_types=1);

/*
 * This file is part of the Tsantos Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tsantos\Symfony\Serializer\Normalizer;

use Symfony\Component\PropertyInfo\Type;

final class CodeGenerator
{
    public static function isWritable(\ReflectionClass $refClass, string $property): bool
    {
        if (null !== self::getSetterMethodName($refClass, $property)) {
            return true;
        }

        $refProperty = $refClass->getProperty($property);

        return $refProperty->isPublic();
    }

    public static function isReadable(\ReflectionClass $refClass, string $property): bool
    {
        if (null !== self::getGetterMethodName($refClass, $property)) {
            return true;
        }

        $refProperty = $refClass->getProperty($property);

        return $refProperty->isPublic();
    }

    public static function getSetterMethodName(\ReflectionClass $refClass, string $property): ?string
    {
        foreach (['set', 'with'] as $prefix) {
            $method = $prefix.ucfirst($property);
            if ($refClass->hasMethod($method)) {
                return $method;
            }
        }

        return null;
    }

    public static function generateSetter(\ReflectionClass $refClass, string $property, array $replacements = [], bool $allowRefl = true): ?string
    {
        if (null !== $method = self::getSetterMethodName($refClass, $property)) {
            return strtr(':object->'.$method.'(:value)', $replacements);
        }

        if (self::isWritable($refClass, $property)) {
            return strtr(':object->'.$property.' = :value', $replacements);
        }

        if ($allowRefl) {
            return strtr(':refClass->getProperty(\''.$property.'\')->setValue(:object, :value)', $replacements);
        }

        return null;
    }

    public static function getGetterMethodName(\ReflectionClass $refClass, string $property): ?string
    {
        foreach (['get', 'is', 'can', 'has'] as $prefix) {
            $method = $prefix.ucfirst($property);
            if ($refClass->hasMethod($method)) {
                return $method;
            }
        }

        return null;
    }

    public static function generateGetter(\ReflectionClass $refClass, string $property, array $replacements = [], bool $allowRef = true): ?string
    {
        if (null !== $method = self::getGetterMethodName($refClass, $property)) {
            return strtr(':object->'.$method.'()', $replacements);
        }

        if (self::isReadable($refClass, $property)) {
            return strtr(':object->'.$property, $replacements);
        }

        if ($allowRef) {
            return strtr(':refClass->getProperty(\''.$property.'\')->getValue(:object)', $replacements);
        }

        return null;
    }

    public static function wrapIf(string $condition, string $blockTrue, string $elseBlock = null): string
    {
        $code = <<<CODE
if ($condition) {
    $blockTrue
}
CODE;

        if (null !== $elseBlock) {
            $code .= <<<CODE
else {
    $elseBlock
}
CODE;
        }

        return $code;
    }

    public static function isset(string $subject): string
    {
        return sprintf('isset(%s)', $subject);
    }

    public static function comment(string $content): string
    {
        return '//'.\PHP_EOL.'// '.$content.\PHP_EOL.'//';
    }

    public static function dumpCode(array $codeLines, int $breakLines = 2): string
    {
        return implode(str_repeat(\PHP_EOL, $breakLines), $codeLines);
    }

    public static function castVar(string $content, string $type): string
    {
        return match ($type) {
            Type::BUILTIN_TYPE_BOOL,
            Type::BUILTIN_TYPE_FLOAT,
            Type::BUILTIN_TYPE_INT,
            Type::BUILTIN_TYPE_STRING => sprintf('(%s) %s', $type, $content),
            Type::BUILTIN_TYPE_TRUE,
            Type::BUILTIN_TYPE_FALSE => '(bool)'.$content,
            default => $content
        };
    }
}
