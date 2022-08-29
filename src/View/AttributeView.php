<?php

declare(strict_types=1);

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
