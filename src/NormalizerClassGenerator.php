<?php

declare(strict_types=1);

/*
 * This file is part of the TSantos Fast Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TSantos\FastObjectNormalizer;

use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use TSantos\FastObjectNormalizer\Twig\Extension\NormalizerClassExtension;
use TSantos\FastObjectNormalizer\View\AttributeView;
use TSantos\FastObjectNormalizer\View\ClassView;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class NormalizerClassGenerator
{
    private readonly PropertyInfoExtractorInterface $propertyInfo;
    private readonly Environment $twig;

    public function __construct(
        readonly private ClassMetadataFactoryInterface $metadataFactory,
        readonly private ?ClassDiscriminatorResolverInterface $classDiscriminatorResolver = null,
        ?Environment $twig = null,
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

        if (null === $twig) {
            $this->twig = new Environment(new FilesystemLoader(__DIR__.'/Resources/view'), [
                'strict_variables' => true,
            ]);
            $this->twig->addExtension(new NormalizerClassExtension());
        }
    }

    public function generate(NormalizerClassConfig $config): string
    {
        $classView = new ClassView(
            targetRefClass: $config->refClass,
            className: $config->normalizerClassShortName,
            targetClassName: $config->subjectClassName,
            targetClassShortName: $config->refClass->getShortName()
        );

        $metadata = $this->metadataFactory->getMetadataFor($config->subjectClassName);
        $attributes = $metadata->getAttributesMetadata();

        if ($config->refClass->hasMethod('__construct')) {
            $constructor = $config->refClass->getConstructor();
            foreach ($constructor->getParameters() as $parameter) {
                $attribute = $attributes[$parameter->getName()] ?? null;

                // parameter is a member of the target, we can reuse metadata attribute to build attribute view
                if (null !== $attribute) {
                    if ($attribute->isIgnored()) {
                        continue;
                    }
                    $attributeView = $this->createAttributeView($attribute, $metadata);

                    $types = $this->propertyInfo->getTypes($metadata->getReflectionClass()->getName(), $parameter->getName());

                    if (empty($types)) {
                        // todo what to do here?
                        continue;
                    }
                    $classView->constructorArgs[] = $attributeView;
                }
            }
        }

        if (null !== $this->classDiscriminatorResolver && null !== $mapping = $this->classDiscriminatorResolver->getMappingForClass($metadata->getName())) {
            $classView->isAbstract = true;
            $classView->discriminatorMapping = $mapping->getTypesMapping();
            $classView->discriminatorProperty = $mapping->getTypeProperty();
        }

        foreach ($metadata->getAttributesMetadata() as $attribute) {
            if ($attribute->isIgnored()) {
                continue;
            }

            $attrView = $this->createAttributeView($attribute, $metadata);

            $classView->allowedAttributes['*'][$attribute->getName()] = true;

            foreach ($attribute->getGroups() as $group) {
                $classView->allowedAttributes[$group][$attribute->getName()] = true;
            }

            $classView->add($attrView);
        }

        return $this->twig->render('class.php.twig', [
            'classView' => $classView,
        ]);
    }

    private function createAttributeView(AttributeMetadataInterface $attribute, ClassMetadataInterface $metadata): AttributeView
    {
        $attrView = new AttributeView();
        $attrView->name = $attribute->getName();
        $attrView->serializedName = $attribute->getSerializedName() ?? $attribute->getName();

        $types = (array) $this->propertyInfo->getTypes($metadata->name, $attribute->name);

        /** @var Type $type */
        foreach ($types as $type) {
            $attrView->type = $type->getBuiltinType();
            $attrView->isNullable = $type->isNullable();
            $attrView->isScalarType = \in_array($type->getBuiltinType(), [
                'int', 'string', 'bool', 'float', 'double',
            ]);

            if (!$attrView->isScalarType) {
                if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType()) {
                    $attrView->type = $type->getClassName();
                } elseif (!empty($type->getCollectionValueTypes())) {
                    $collectionType = $type->getCollectionValueTypes()[0];
                    $attrView->type = ($collectionType->getClassName() ?? $collectionType->getBuiltinType()).'[]';
                } else {
                    $attrView->isScalarType = true;
                }
            }
            break;
        }

        return $attrView;
    }
}
