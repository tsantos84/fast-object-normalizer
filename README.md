# Symfony Fast Object Normalizer

A Symfony Object Normalizer that improves the serialization process up to 5x. This boost of performance can be 
accomplished because this normalizer generates dedicated normalizes to your data classes, so you don't need
to worry about runtime overhead like reflections or code relying on metadata.

## Instalation

    composer require tsantos/fast-object-normalizer

### Symfony Applications

This package ships with a Symfony Bundle that autmatically adds the normalizer to the normalizer stack. 
It means that you need to do nothing in your code to improve the performance of your application.

### Standalone Applications

For applications using Symfony Serializer as a standalone component, you need to register the normalizer
manually:

```php
<?php

require 'vendor/autoload.php';

use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectDenormalizer;

$classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
$discriminator = new ClassDiscriminatorFromClassMetadata($classMetadataFactory);

$serializer = new Serializer([
    new ArrayDenormalizer(),
    new FastObjectNormalizer(
        classGenerator: new NormalizerClassGenerator($classMetadataFactory, $discriminator),
        classDumper: new NormalizerClassDumper('./var/cache/serializer'),
        classMetadataFactory: $classMetadataFactory,
    ),
    new ObjectNormalizer($classMetadataFactory)
], ['json' => new JsonEncoder()]);
```

Note that `ObjectNormalizer` still in the normalizer stack. This is not a requirement, but it is a good idea to have
the built-in normalizer working if `Fast Object Normalizer` does not have support to (de-)normalizer the given data.

## Configuration

Fast Object Normalizer normalize and denormalize all kind of objects by default. For very small applications it is ok
to go with this behavior, but for more complex applications, like API Platform that (de-)normalize many kind of data class,
it is a good idea to limit what classes you want to serialize with `Fast Object Normalize`:

```php
<?php
$normalizer = new FastObjectNormalizer(
    includedTypes: ['^App\\\Entity'], // black-box
    excludedTypes: ['^App\\\Repository'], // white-box
    //... other args here
)
```

You can work in two main approaches: Blackbox and Whitebox

### Black box

This approach is more restritive and is the best choice for many applications. You know exactly what classes will be 
normalized by `Fast Object Normalizer`

### White box

Is the opposite of Black Bbox. You allow all classes except those defined in `excludedTypes`.

The `includedTypes` option has precedence over `excludedTypes`. It means that you can exclude from the `Fast Object
Normalizer` a subset of those classes defined in `includedTypes` by providing both options when creating the normalizer:

```php
<?php
$normalizer = new FastObjectNormalizer(
    includedTypes: ['^App\\\Entity'], // black-box
    excludedTypes: ['^App\\\Entity\\\ShouldNotBeNormalized\\\\WithFastObjectNormalizer'], // white-box
    //... other args here
)
```

## YOU MUST BE AWARE

This package *IS NOT* a full replacement to Symfony's `AbstractObjectNormalizer`. Please, read the table bellow to see
what features this package currently implements.

| **Feature**                                      | **Builtin Normalizer** | **Fast Object Normalizer** |
|--------------------------------------------------|:----------------------:|:--------------------------:|
| Groups                                           |          Yes           |            Yes             |
| Ignore attributes                                |          Yes           |            Yes             |
| Skip null values                                 |          Yes           |            Yes             |
| Serialization of Interfaces and Abstract classes |          Yes           |            Yes             |
| Attribute callbacks                              |          Yes           |            Yes             |
| Circular Reference Handle                        |          Yes           |            Yes             |
| Object to populate                               |          Yes           |            Yes             |
| Max depth handle                                 |          Yes           |          Not yet           |
| Avoid extra attributes                           |          Yes           |          Not yet           |
| Default constructor args                         |          Yes           |          Not yet           |
| Type enforcement                                 |          Yes           |          Not yet           |
| Deep object to populate                          |          Yes           |          Not yet           |
| Preserve empty objects                           |          Yes           |          Not yet           |
| Skip uninitialized values                        |          Yes           |          Not yet           |

An important aspect you should know also is that almost all these features were re-implemented and tested in this package. 
It means that some different behaviors can be experienced in some specific scenarios. If you experience some weird behavior,
please don't hesitate to open a new issue. 

