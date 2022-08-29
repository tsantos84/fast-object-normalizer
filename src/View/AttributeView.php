<?php

declare(strict_types=1);

/*
 * This file is part of the TSantos Fast Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TSantos\FastObjectNormalizer\View;

class AttributeView
{
    public string $name;
    public string $serializedName;
    public string $valueReader;
    public string $valueWriter;
    public bool $isScalarType;
    public string $type;
    public bool $isNullable;
}
