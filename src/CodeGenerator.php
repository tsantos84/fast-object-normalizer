<?php

declare(strict_types=1);

namespace Tsantos\Symfony\Serializer\Normalizer;

use Symfony\Component\PropertyInfo\Type;

final class CodeGenerator
{
    public static function getSetterMethodName(\ReflectionClass $refClass, string $property): ?string
    {
        foreach (['set', 'with'] as $prefix) {
            $method = $prefix . ucfirst($property);
            if ($refClass->hasMethod($method)) {
                return $method;
            }
        }

        return null;
    }

    public static function generateSetter(\ReflectionClass $refClass, string $property): string
    {
        if (null !== $method = self::getSetterMethodName($refClass, $property)) {
            return '%s->' . $method . '(%s)';
        }

        return '%s->'.$property . ' = %s';
    }

    public static function getGetterMethodName(\ReflectionClass $refClass, string $property): ?string
    {
        foreach (['get', 'is', 'can', 'has'] as $prefix) {
            $method = $prefix . ucfirst($property);
            if ($refClass->hasMethod($method)) {
                return $method;
            }
        }

        return null;
    }

    public static function generateGetter(\ReflectionClass $refClass, string $property): string
    {
        if (null !== $method = self::getGetterMethodName($refClass, $property)) {
            return '%s->' . $method . '()';
        }

        return '%s->'.$property;
    }

    public static function wrapIf(string $condition, string $blockTrue, ?string $elseBlock = null): string
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
        return '//' . PHP_EOL . '// '. $content . PHP_EOL . '//';
    }

    public static function dumpCode(array $codeLines, int $breakLines = 2): string
    {
        return implode(str_repeat(PHP_EOL, $breakLines), $codeLines);
    }

    public static function castVar(string $content, string $type): string
    {
        return match ($type) {
            Type::BUILTIN_TYPE_BOOL,
            Type::BUILTIN_TYPE_FLOAT,
            Type::BUILTIN_TYPE_INT,
            Type::BUILTIN_TYPE_STRING => sprintf('(%s) %s', $type, $content),
            Type::BUILTIN_TYPE_TRUE,
            Type::BUILTIN_TYPE_FALSE => '(bool)' . $content,
            default => $content
        };
    }
}
