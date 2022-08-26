<?php

declare(strict_types=1);

/*
 * This file is part of the Tsantos Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tsantos\Symfony\Serializer\Normalizer;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class NormalizerClassGenerator
{
    private readonly PropertyInfoExtractorInterface $propertyInfo;

    public function __construct(
        readonly private ClassMetadataFactoryInterface $metadataFactory,
        readonly private ?ClassDiscriminatorResolverInterface $classDiscriminatorResolver = null,
    ) {
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

    public function generate(NormalizerClassConfig $config): PhpFile
    {
        $metadata = $this->metadataFactory->getMetadataFor($config->subjectClassName);

        $phpFile = new PhpFile();
        $phpFile
            ->setStrictTypes();

        $class = $phpFile
            ->addClass($config->normalizerClassName)
            ->setFinal()
            ->setExtends(\Tsantos\Symfony\Serializer\Normalizer\AbstractObjectNormalizer::class)
            ->addComment('Auto-generated class! Do not change it by yourself.');

        $class
            ->addProperty('targetType', $metadata->getName())
            ->setType('string')
            ->setStatic()
            ->setProtected();

        $this->buildAllowedAttributes($class, $metadata);
        $this->buildNormalizeMethod($class, $metadata);
        $this->buildDenormalizeMethod($class, $metadata);
        $this->buildNewInstanceMethod($class, $metadata);

        return $phpFile;
    }

    private function buildAllowedAttributes(ClassType $classType, ClassMetadataInterface $metadata): void
    {
        $allowedAttributes = [];

        foreach ($metadata->getAttributesMetadata() as $attributeMetadata) {
            if ($attributeMetadata->isIgnored()) {
                continue;
            }

            $allowedAttributes['*'][$attributeMetadata->getName()] = true;

            foreach ($attributeMetadata->getGroups() as $group) {
                $allowedAttributes[$group][$attributeMetadata->getName()] = true;
            }
        }

        $classType
            ->addProperty('allowedAttributes', $allowedAttributes)
            ->setType('array')
            ->setStatic()
            ->setProtected();
    }

    private function buildNormalizeMethod(ClassType $class, ClassMetadataInterface $metadata): void
    {
        list($discriminatorProperty, $discriminatorValue) = $this->getDiscriminatorData($metadata);

        $normalizeMethod = $class->addMethod('normalize');
        $normalizeMethod->addParameter('object')->setType('mixed');
        $normalizeMethod->addParameter('format', null)->setType('string');
        $normalizeMethod->addParameter('context', [])->setType('array');

        $bodyLines = ['$allowedAttributes = $this->getAllowedAttributes($context);'];
        $bodyLines[] = '$data = [];';

        if (null !== $discriminatorProperty && null !== $discriminatorValue) {
            $bodyLines[] = sprintf("\$data['%s'] = '%s';", $discriminatorProperty, $discriminatorValue);
        }

        foreach ($metadata->getAttributesMetadata() as $property) {
            if ($property->isIgnored()) {
                $bodyLines[] = CodeGenerator::comment('skiping property "'.$property->getName().'" as it was ignored');
                continue;
            }

            if ($property->getName() === $discriminatorProperty) {
                continue;
            }

            $getter = CodeGenerator::generateGetter($metadata->getReflectionClass(), $property->getName(), [
                ':object' => '$object',
                ':refClass' => '$this->refClass',
            ]);

            $types = (array) $this->propertyInfo->getTypes($metadata->name, $property->name);

            foreach ($types as $type) {
                if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() || Type::BUILTIN_TYPE_ARRAY === $type->getBuiltinType()) {
                    $getter = sprintf('$this->serializer->normalize(%s, $format, $this->createContextForProperty(\'%s\', $context));', $getter, $property->getName());
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

    private function buildDenormalizeMethod(ClassType $class, ClassMetadataInterface $metadata): void
    {
        $denormalize = $class->addMethod('denormalize');
        $denormalize->addParameter('data')->setType('mixed');
        $denormalize->addParameter('type')->setType('string');
        $denormalize->addParameter('format', null)->setType('string');
        $denormalize->addParameter('context', [])->setType('array');

        $bodyLines = ['$object = $context[\''.AbstractNormalizer::OBJECT_TO_POPULATE.'\'] ?? $this->newInstance($data, $format, $context);'];
        $bodyLines[] = '$allowedAttributes = $this->getAllowedAttributes($context);';

        foreach ($metadata->getAttributesMetadata() as $property) {
            if ($property->isIgnored()) {
                $bodyLines[] = CodeGenerator::comment('skiping property "'.$property->getName().'" as it was ignored');
                continue;
            }
            $serializedName = $property->getSerializedName() ?? $property->getName();

            $setter = CodeGenerator::generateSetter($metadata->getReflectionClass(), $property->getName(), [
                ':object' => '$object',
                ':refClass' => '$this->refClass',
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
                    $dataType = ($collectionType->getClassName() ?? $collectionType->getBuiltinType()).'[]';
                    $needsDenormalization = true;
                }
            }

            if ($needsDenormalization) {
                $denormalizedValue = sprintf('$this->serializer->denormalize(%s, \'%s\', $format, $this->createContextForProperty(\'%s\', $context))', $rawData, $dataType, $property->getName());
                if ($nullable) {
                    $denormalizedValue = sprintf('isset(%s) ? %s : null', $rawData, $denormalizedValue);
                }
            }

            $propertyCode = strtr($setter.';', [':value' => $denormalizedValue]);
            $propertyCode = CodeGenerator::wrapIf("isset(\$allowedAttributes['$property->name']) && array_key_exists('$serializedName', \$data)", $propertyCode);

            $bodyLines[] = $propertyCode;
        }

        $bodyLines[] = 'return $object;';

        $denormalize->setBody(CodeGenerator::dumpCode($bodyLines));
    }

    private function buildNewInstanceMethod(ClassType $class, ClassMetadataInterface $metadata): void
    {
        $newInstanceMethod = $class->addMethod('newInstance')->setReturnType('object');
        $newInstanceMethod->addParameter('data', [])->setType('array');
        $newInstanceMethod->addParameter('format', null)->setType('string');
        $newInstanceMethod->addParameter('context', [])->setType('array');

        if (null !== $this->classDiscriminatorResolver && null !== $mapping = $this->classDiscriminatorResolver->getMappingForClass($metadata->getName())) {
            $propertyType = $mapping->getTypeProperty();

            $bodyLines = [];
            $bodyLines[] = CodeGenerator::wrapIf("!isset(\$data['".$propertyType."'])", 'throw \\'.NotNormalizableValueException::class."::createForUnexpectedDataType(sprintf('Type property \"%s\" not found for the abstract object \"%s\".', '$propertyType', '{$metadata->getName()}'), null, ['string'], isset(\$context['deserialization_path']) ? \$context['deserialization_path'].'$propertyType' : '$propertyType', false);");
            $bodyLines[] = sprintf('$class = self::$classDiscriminator[$data["%s"]];', $propertyType);
            $bodyLines[] = 'return $this->normalizer->getNormalizer($class)->newInstance($data, $format, $context);';

            $class->addProperty('classDiscriminator', $mapping->getTypesMapping())
                ->setType('array')
                ->setStatic()
                ->setPrivate();

            $newInstanceMethod->setBody(CodeGenerator::dumpCode($bodyLines));

            return;
        }

        if ((null === $constructor = $metadata->getReflectionClass()->getConstructor()) || 0 === $constructor->getNumberOfParameters()) {
            $newInstanceMethod->setBody(sprintf('return new \%s();', $metadata->getName()));

            return;
        }

        $params = [];
        $bodyLines = ['$args = [];'];
        $bodyLines[] = '$allowedAttributes = $this->getAllowedAttributes($context);';

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
            if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType()) {
                $needsDenormalization = true;
                $dataType = $type->getClassName();
            } elseif (!empty($type->getCollectionValueTypes())) {
                $needsDenormalization = true;
                $collectionType = $type->getCollectionValueTypes()[0];
                $dataType = ($collectionType->getClassName() ?? $collectionType->getBuiltinType()).'[]';
            }

            if ($needsDenormalization) {
                $propertyCode = sprintf("\$args['%s'] = \$this->serializer->denormalize(\$data['%s'], '%s', \$format, \$this->createContextForProperty('%s', \$context))", $parameter->getName(), $serializedName, $dataType, $parameter->getName());
            }

            $bodyLines[] = CodeGenerator::wrapIf("isset(\$allowedAttributes['$parameter->name']) && array_key_exists('$serializedName', \$data)", $propertyCode.';');
            $params[] = sprintf("\$args['%s']", $parameter->getName());
        }

        $bodyLines[] = sprintf('return new \%s(%s);', $metadata->getName(), implode(', ', $params));

        $newInstanceMethod->setBody(CodeGenerator::dumpCode($bodyLines));
    }

    private function getDiscriminatorData(ClassMetadataInterface $metadata): array
    {
        if (null !== $this->classDiscriminatorResolver && null !== $mapping = $this->classDiscriminatorResolver->getMappingForMappedObject($metadata->getName())) {
            return [
                $mapping->getTypeProperty(),
                array_search($metadata->getName(), $mapping->getTypesMapping()),
            ];
        }

        return [null, null];
    }
}
