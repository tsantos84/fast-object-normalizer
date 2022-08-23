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
            ->addImplement(NormalizerInterface::class)
            ->addImplement(DenormalizerInterface::class)
            ->addImplement(ObjectFactoryInterface::class)
            ->addComment('Auto-generated class! Do not change it by yourself.');

        $class
            ->addProperty('refClass', null)
            ->setType(\ReflectionClass::class)
            ->setNullable()
            ->setPrivate()
            ->setStatic();

        $this->buildAllowedAttributes($class, $metadata);

        $constructor = $class->addMethod('__construct');
        $constructor->setBody(CodeGenerator::wrapIf('null === self::$refClass', 'self::$refClass = new \ReflectionClass(\''.$ref->getName().'\');'));
        $constructor
            ->addPromotedParameter('serializer')
            ->setType(Serializer::class)
            ->setPrivate()
            ->setReadOnly();

        $this->buildNormalizeMethod($class, $metadata);
        $this->buildSupportsNormalizationMethod($class, $metadata);

        $this->buildDenormalizeMethod($class, $metadata);
        $this->buildSupportsDenormalizationMethod($class, $metadata);

        $this->buildNewInstanceMethod($class, $metadata);

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

    private function buildNormalizeMethod(ClassType $class, ClassMetadataInterface $metadata): void
    {
        $normalizeMethod = $class->addMethod('normalize');
        $normalizeMethod->addParameter('object')->setType('mixed');
        $normalizeMethod->addParameter('format', null)->setType('string');
        $normalizeMethod->addParameter('context', [])->setType('array');

        $bodyLines = ['$allowedAttributes = $context[\'allowed_attributes\'][\''.$metadata->getName().'\'] ?? self::$allowedAttributes;'];
        $bodyLines[] = '$data = [];';
        foreach ($metadata->getAttributesMetadata() as $property) {
            if ($property->isIgnored()) {
                $bodyLines[] = CodeGenerator::comment('skiping property "' . $property->getName() . '" as it was ignored');
                continue;
            }

            $getter = CodeGenerator::generateGetter($metadata->getReflectionClass(), $property->getName(), [
                ':object' => '$object',
                ':refClass' => 'self::$refClass'
            ]);

            $types = (array) $this->propertyInfo->getTypes($metadata->name, $property->name);

            foreach ($types as $type) {
                if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() || Type::BUILTIN_TYPE_ARRAY === $type->getBuiltinType()) {
                    $getter = sprintf('$this->serializer->normalize(%s, $format, $context);', $getter);
                    break;
                }
            }

            $propertyLine = sprintf("\$data['%s'] = %s;", $property->getSerializedName() ?? $property->getName(), $getter);
            $propertyLine = CodeGenerator::wrapIf(
                CodeGenerator::isset("\$allowedAttributes['$property->name']"),
                $propertyLine
            );
            $bodyLines[] = $propertyLine;
        }

        $bodyLines[] = 'return $data;';
        $normalizeMethod->setBody(CodeGenerator::dumpCode($bodyLines));
    }

    private function buildSupportsNormalizationMethod(ClassType $class, ClassMetadataInterface $metadata): void
    {
        $supportsNormalizeMethod = $class->addMethod('supportsNormalization');
        $supportsNormalizeMethod->addParameter('data')->setType('mixed');
        $supportsNormalizeMethod->addParameter('format', null)->setType('string');
        $supportsNormalizeMethod->setBody(<<<STRING
return \$data instanceof \\$metadata->name;
STRING
        );
    }

    private function buildDenormalizeMethod(ClassType $class, ClassMetadataInterface $metadata): void
    {
        $denormalize = $class->addMethod('denormalize');
        $denormalize->addParameter('data')->setType('mixed');
        $denormalize->addParameter('type')->setType('string');
        $denormalize->addParameter('format', null)->setType('string');
        $denormalize->addParameter('context', [])->setType('array');

        $bodyLines = ['$object = $context[\''.AbstractNormalizer::OBJECT_TO_POPULATE.'\'] ?? $this->newInstance($data, $format, $context);'];
        $bodyLines[] = '$allowedAttributes = $context[\'allowed_attributes\'][\''.$metadata->getName().'\'] ?? self::$allowedAttributes;';

        foreach ($metadata->getAttributesMetadata() as $property) {
            if ($property->isIgnored()) {
                $bodyLines[] = CodeGenerator::comment('skiping property "' . $property->getName() . '" as it was ignored');
                continue;
            }
            $serializedName = $property->getSerializedName() ?? $property->getName();

            $setter = CodeGenerator::generateSetter($metadata->getReflectionClass(), $property->getName(), [
                ':object' => '$object',
                ':refClass' => 'self::$refClass',
            ]);

            $rawData = $denormalizedValue = sprintf('$data[\'%s\']', $serializedName);
            $nullable = true;
            $dataType = null;
            $needsDenormalization = false;

            if (null !== $types = $this->propertyInfo->getTypes($metadata->name, $property->name)) {
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
                    $denormalizedValue = sprintf('isset(%s) ? %s : null', $rawData, $denormalizedValue);
                }
            }

            $propertyCode = strtr($setter . ';', [':value' => $denormalizedValue]);
            $propertyCode = CodeGenerator::wrapIf("isset(\$allowedAttributes['$property->name']) && array_key_exists('$serializedName', \$data)", $propertyCode);

            $bodyLines[] = $propertyCode;
        }

        $bodyLines[] = 'return $object;';

        $denormalize->setBody(CodeGenerator::dumpCode($bodyLines));
    }

    private function buildSupportsDenormalizationMethod(ClassType $class, ClassMetadataInterface $metadata): void
    {
        $supportsNormalizeMethod = $class->addMethod('supportsDenormalization');
        $supportsNormalizeMethod->addParameter('data')->setType('mixed');
        $supportsNormalizeMethod->addParameter('type')->setType('string');
        $supportsNormalizeMethod->addParameter('format', null)->setType('string');
        $supportsNormalizeMethod->setBody(<<<STRING
return \$type === '\\$metadata->name';
STRING
        );
    }

    private function buildNewInstanceMethod(ClassType $class, ClassMetadataInterface $metadata): void
    {
        $newInstanceMethod = $class->addMethod('newInstance')->setReturnType('object');
        $newInstanceMethod->addParameter('data', [])->setType('array');
        $newInstanceMethod->addParameter('format', null)->setType('string');
        $newInstanceMethod->addParameter('context', [])->setType('array');

        if ((null === $constructor = $metadata->getReflectionClass()->getConstructor()) || $constructor->getNumberOfParameters() === 0) {
            $newInstanceMethod->setBody(sprintf('return new \%s();', $metadata->getName()));
            return;
        }

        $params = [];
        $bodyLines = ['$args = [];'];
        $bodyLines[] = '$allowedAttributes = $context[\'allowed_attributes\'][\''.$metadata->getName().'\'] ?? self::$allowedAttributes;';

        $classAttributes = [];

        foreach ($metadata->getAttributesMetadata() as $attribute) {
            $classAttributes[$attribute->getName()] = $attribute;
        }

        foreach ($constructor->getParameters() as $parameter) {
            $attribute = $classAttributes[$parameter->getName()] ?? null;

            if (null === $attribute || $attribute->isIgnored()) {
                // todo what to do here?
                continue;
            }

            $serializedName = $attribute->getSerializedName() ?? $attribute->getName();
            $types = $this->propertyInfo->getTypes($metadata->getReflectionClass()->getName(), $parameter->getName());

            if (empty($types)) {
                // todo what to do here?
                continue;
            }

            $type = $types[0];

            $propertyCode = sprintf("\$args['%s'] = \$data['%s']", $parameter->getName(), $serializedName);

            $needsDenormalization = false;
            $dataType = null;
            if ($type->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT) {
                $needsDenormalization = true;
                $dataType = $type->getClassName();
            } elseif (!empty($type->getCollectionValueTypes())) {
                $needsDenormalization = true;
                $collectionType = $type->getCollectionValueTypes()[0];
                $dataType = ($collectionType->getClassName() ?? $collectionType->getBuiltinType()) . '[]';
            }

            if ($needsDenormalization) {
                $propertyCode = sprintf("\$args['%s'] = \$this->serializer->denormalize(\$data['%s'], '%s', \$format, \$context)", $parameter->getName(), $serializedName, $dataType);
            }

            $bodyLines[] = CodeGenerator::wrapIf("isset(\$allowedAttributes['$parameter->name']) && array_key_exists('$serializedName', \$data)", $propertyCode . ';');
            $params[] = sprintf("\$args['%s']", $parameter->getName());
        }

        $bodyLines[] = sprintf('return new \%s(%s);', $metadata->getName(), join(', ', $params));

        $newInstanceMethod->setBody(CodeGenerator::dumpCode($bodyLines));
    }
}
