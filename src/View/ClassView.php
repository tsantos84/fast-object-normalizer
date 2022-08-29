<?php

declare(strict_types=1);

/*
 * This file is part of the TSantos Fast Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        if (\count($namespace) > 1) {
            array_shift($namespace);
            $this->namespace = implode('\\', $namespace);
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
