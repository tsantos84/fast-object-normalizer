<?php

declare(strict_types=1);

namespace TSantos\FastObjectNormalizer\View;

class ClassView
{
    public ?string $namespace = null;
    public array $allowedAttributes = [];
    public array $attributes = [];
    public bool $isAbstract = false;
    public array $discriminatorMapping = [];
    public ?string $discriminatorProperty;
    public array $constructorArgs = [];

    public function __construct(public string $className, public string $targetClassName, public string $targetClassShortName)
    {
        $namespace = explode('\\', ltrim($this->className, '\\'));
        if (count($namespace) > 1) {
            array_shift($namespace);
            $this->namespace = join('\\', $namespace);
        }
    }

    public function add(AttributeView $propertyView): void
    {
        $this->attributes[] = $propertyView;
    }

    public function dumpAllowedAttributes(): string
    {
        return var_export($this->allowedAttributes, true);
    }

    public function dumpDiscriminatorMapping(): string
    {
        return var_export($this->discriminatorMapping, true);
    }
}
