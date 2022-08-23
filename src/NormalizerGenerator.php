<?php

declare(strict_types=1);

namespace Tsantos\Symfony\Serializer\Normalizer;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

final class NormalizerGenerator
{
    private readonly PropertyInfoExtractorInterface $propertyInfo;

    public function __construct(
        readonly private string $outputDir,
        readonly private string $namespace = 'App\\Serializer\\Normalizer',
        readonly private bool $overwrite = false,
    )
    {
        // a full list of extractors is shown further below
        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();
        $listExtractors = [$reflectionExtractor];
        $typeExtractors = [$phpDocExtractor, $reflectionExtractor];
        $descriptionExtractors = [$phpDocExtractor];
        $accessExtractors = [$reflectionExtractor];
        $propertyInitializableExtractors = [$reflectionExtractor];

        $this->propertyInfo = new PropertyInfoExtractor(
            $listExtractors,
            $typeExtractors,
            $descriptionExtractors,
            $accessExtractors,
            $propertyInitializableExtractors
        );
    }

    public function generate(object|string $target, ?ClassMetadataFactoryInterface $metadataFactory): array
    {
        if (is_object($target)) {
            $target = get_class($target);
        }

        $ref = new \ReflectionClass($target);

        $className = 'Generated' . $ref->getShortName() . 'Normalizer';
        $filename = ($this->outputDir . '/' . $className . '.php');

        if (file_exists($filename) && !$this->overwrite) {
            return [
                'className' => $this->namespace . '\\' . $className,
                'filename' => $filename
            ];
        }

        $metadata = $metadataFactory->getMetadataFor($ref->getName());

        $class = new ClassType($className);
        $class
            ->addComment('Auto-generated class! Do not change it by yourself.');

        $this->buildAllowedAttributes($class, $metadata);

        $class->addMethod('__construct')
            ->addPromotedParameter('serializer')
            ->setType(Serializer::class)
            ->setPrivate()
            ->setReadOnly();

        $this->buildNormalizeMethods($class, $metadata);
        $this->buildDenormalizeMethods($class, $ref);

        $namespace = new PhpNamespace($this->namespace);
        $namespace->add($class);

        $phpFile = new PhpFile();
        $phpFile->addNamespace($namespace);

        file_put_contents($filename, (string) $phpFile);

        return [
            'className' => $this->namespace . '\\' . $className,
            'filename' => $filename
        ];
    }

    private function buildAllowedAttributes(ClassType $classType, ClassMetadataInterface $metadata): void
    {
        $allowedAttributes = [];

        foreach ($metadata->getAttributesMetadata() as $attributeMetadata) {
            if (!$attributeMetadata->isIgnored()) {
                $allowedAttributes[$attributeMetadata->getName()] = true;
            }
        }

        $classType
            ->addProperty('allowedAttributes', $allowedAttributes)
            ->setType('array')
            ->setStatic()
            ->setPrivate();
    }

    private function buildNormalizeMethods(ClassType $class, ClassMetadataInterface $metadata): void
    {
        $class->addImplement(NormalizerInterface::class);
        $normalizeMethod = $class->addMethod('normalize');
        $normalizeMethod->addParameter('object')->setType('mixed');
        $normalizeMethod->addParameter('format', null)->setType('string');
        $normalizeMethod->addParameter('context', [])->setType('array');

        $bodyLines = ['$allowedAttributes = $context[\'allowed_attributes\'][\''.$metadata->getName().'\'] ?? self::$allowedAttributes;'];
        foreach ($metadata->getAttributesMetadata() as $property) {
            $methodSuffix = ucfirst($property->name);
            $accessor = match (true) {
                $metadata->getReflectionClass()->hasMethod('get' . $methodSuffix) => '$object->get' . $methodSuffix,
                $metadata->getReflectionClass()->hasMethod('is' . $methodSuffix) => '$object->is' . $methodSuffix,
                default => '$object->' . $property->name,
            };

            $types = (array) $this->propertyInfo->getTypes($metadata->name, $property->name);

            foreach ($types as $type) {
                if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() || Type::BUILTIN_TYPE_ARRAY === $type->getBuiltinType()) {
                    $accessor = sprintf('$this->serializer->normalize(%s, $format, $context);', $accessor);
                    break;
                }
            }

            $propertyLine = sprintf("\$data['%s'] = %s;", $property->getSerializedName() ?? $property->getName(), $accessor);
            $propertyLine = <<<STRING
if (isset(\$allowedAttributes['$property->name'])) {
    $propertyLine
}
STRING;

            $bodyLines[] = $propertyLine;
        }

        $code = join(PHP_EOL, $bodyLines);

        $normalizeMethod->setBody(<<<CODE
    \$data = [];
$code
return \$data;
CODE
        );

        $supportsNormalizeMethod = $class->addMethod('supportsNormalization');
        $supportsNormalizeMethod->addParameter('data')->setType('mixed');
        $supportsNormalizeMethod->addParameter('format', null)->setType('string');
        $supportsNormalizeMethod->setBody(<<<STRING
return \$data instanceof \\$metadata->name;
STRING
        );
    }

    private function buildDenormalizeMethods(ClassType $class, \ReflectionClass $ref): void
    {
        $class
            ->addImplement(DenormalizerInterface::class)
            ->addImplement(ObjectFactoryInterface::class);

        $denormalize = $class->addMethod('denormalize');
        $denormalize->addParameter('data')->setType('mixed');
        $denormalize->addParameter('type')->setType('string');
        $denormalize->addParameter('format', null)->setType('string');
        $denormalize->addParameter('context', [])->setType('array');

        $bodyLines = ['$object = $context[\''.AbstractNormalizer::OBJECT_TO_POPULATE.'\'] ?? $this->newInstance($data, $context);'];

        foreach ($ref->getProperties() as $property) {
            $methodSuffix = ucfirst($property->name);
            $writer = match (true) {
                $ref->hasMethod('set' . $methodSuffix) => 'set' . $methodSuffix,
                $ref->hasMethod('with' . $methodSuffix) => 'with' . $methodSuffix,
                default => $property->name,
            };
            $rawData = $denormalizedValue = sprintf('$data[\'%s\']', $property->name);
            $nullable = true;
            $dataType = null;
            $needsDenormalization = false;

            if (null !== $types = $this->propertyInfo->getTypes($ref->name, $property->name)) {
                $type = $types[0];
                $nullable = $type->isNullable();
                if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType()) {
                    $dataType = $type->getClassName();
                    $needsDenormalization = true;
                } elseif (!empty($type->getCollectionValueTypes())) {
                    $collectionType = $type->getCollectionValueTypes()[0];
                    $dataType = ($collectionType->getClassName() ?? $collectionType->getBuiltinType()) . '[]';
                    $needsDenormalization = true;
                }
            }

            if ($needsDenormalization) {
                $denormalizedValue = sprintf('$this->serializer->denormalize(%s, \'%s\', $format, $context)', $rawData, $dataType);
                if ($nullable) {
                    $denormalizedValue = sprintf('isset(%s) ? %s : null;', $rawData, $denormalizedValue);
                }
            }

            $propertyCode = sprintf("\$object->%s = %s%s", $writer, $this->getCastString($dataType), $denormalizedValue);

            if (!$nullable) {
                $propertyCode = <<<STRING
if (isset($rawData)) {
    $propertyCode;
}

STRING;
            } elseif (!$needsDenormalization) {
                $propertyCode .= ' ?? null;';
            }

            $bodyLines[] = $propertyCode;
        }

        $bodyLines[] = 'return $object;';

        $denormalize->setBody(join(PHP_EOL, $bodyLines));

        $supportsNormalizeMethod = $class->addMethod('supportsDenormalization');
        $supportsNormalizeMethod->addParameter('data')->setType('mixed');
        $supportsNormalizeMethod->addParameter('type')->setType('string');
        $supportsNormalizeMethod->addParameter('format', null)->setType('string');
        $supportsNormalizeMethod->setBody(<<<STRING
return \$type === '\\$ref->name';
STRING
        );

        $newInstanceMethod = $class->addMethod('newInstance')->setReturnType('object');
        $newInstanceMethod->addParameter('data', [])->setType('array');
        $newInstanceMethod->addParameter('context', [])->setType('array');
        $params = [];
        if ((null !== $constructor = $ref->getConstructor()) && $constructor->getNumberOfParameters() > 0) {
            foreach ($constructor->getParameters() as $parameter) {
                $params[] = sprintf("\$data['%s']", $parameter->getName());
            }
        }

        $newInstanceMethod->setBody(sprintf('return new \%s(%s);', $ref->getName(), join(', ', $params)));
    }

    private function getCastString(?string $scalarType): string
    {
        return match ($scalarType) {
            Type::BUILTIN_TYPE_BOOL,
            Type::BUILTIN_TYPE_FLOAT,
            Type::BUILTIN_TYPE_INT,
            Type::BUILTIN_TYPE_STRING => sprintf('(%s)', $scalarType),
            Type::BUILTIN_TYPE_TRUE,
            Type::BUILTIN_TYPE_FALSE => '(bool)',
            default => ''
        };
    }
}
