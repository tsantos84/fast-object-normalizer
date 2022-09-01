# [WIP] Symfony Fast Object Normalizer 
[![Build status][main image]][main] [![Coverage Status][main coverage image]][main coverage]

Symfony Object Normalizer that improves the serialization process [up to 5x (e.g. 400%)][benchmark] compared to built-in normalizers.
This boost of performance can be accomplished because this normalizer generates dedicated normalizes to your 
data classes, so you don't need to worry about runtime overhead like reflections or code relying on metadata 
(e.g. ClassMetadata).

## Instalation

    composer require tsantos/fast-object-normalizer

### Symfony Applications

This package ships with a Symfony Bundle that autmatically adds the normalizer to the normalizer stack. 

#### Applications using Flex

Just install the package and the Symfony Flex automatically register the bundle into your application.

#### Applications not using Flex

You need to register the bundle manually:

```php
<?php
// config/bundles.php
return [
    // ..
    TSantos\FastObjectNormalizer\Bridge\Symfony\FastObjectNormalizerBundle::class => ['all' => true]
];
```

### Standalone Applications

For applications using Symfony Serializer as a standalone component, you'll need to register the normalizer
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
the built-in normalizer working because `Fast Object Normalizer` may not support the object that is being serialized.

## Configuration

`Fast Object Normalizer` is aimed to serialize data class (e.g: DTO), but in somecases it is possible that your
application tries to serialize an object which holds a resource or a connection and normally there is an exclusive
normalizer that deals with that type. To ensure that `Fast Object Normalizer` skip such types, you can configure it
to serialize only types that matchs some pattern:

```php
<?php
$normalizer = new FastObjectNormalizer(
    includedTypes: ['^App\\\Entity']
    //... other args here
)
```
```yaml
# config/packages/fast_object_normalizer.yaml
fast_object_normalizer:
  includedTypes: ["^App\\Entity"]
```

Configuring the normalizer with `includedTypes` you know exactly what types the normalizer will support. In the major of
times, you'll opt to this configuration to avoid weird behaviors.

There is also the oposite of `includedTypes` which now will accepts all types except those defined in the `excludedList`:

```php
<?php
$normalizer = new FastObjectNormalizer(
    includedTypes: ['^App\\\Entity']
    //... other args here
)
```
```yaml
# config/packages/fast_object_normalizer.yaml
fast_object_normalizer:
  excludedTypes: ["^Some\\Vendor\\Object"]
```

Finally, you can combine both options:

```php
<?php
$normalizer = new FastObjectNormalizer(
    includedTypes: ['^App\\\Entity'],
    excludedTypes: ['^App\\\Entity\\\\ExcludedType'],
    //... other args here
)
```
```yaml
# config/packages/fast_object_normalizer.yaml
fast_object_normalizer:
  excludedTypes: ['^App\\\Entity']
  includedTypes: ["^App\\\Entity\\\\ExcludedType"]
```

With this configuration, you are allowing all types starting with `App\Entity` but excluding `App\Entity\ExcludedType`.

## YOU MUST BE AWARE

This packages tries to be the more transparent as possible to allow you to use it without break your application.
As explained previously, this package creates dedicated normalizers to serialize your data as fast as possible, which
means that the built-in normalizer `AbstractObjectNormalizer` will not be reached. It means that features like attribute
grouping, null value skipping, attribute callback etc. were reimplemented here to boost performance, that is, 
**_it is possible_** to have some different behaviors compared to `AbstractObjectNormalizer`. Please, open an issue if you
find some divergent behavior.

The bellow table list all the features currently supported by built-in normalizer and `Fast Object Normalizer`.

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

If you want to use `Fast Object Normalizer` but need a specific feature to be implemented, I'd be very happy to
point you in how we can implement it.

[main image]: https://github.com/tsantos84/fast-object-normalizer/actions/workflows/ci.yml/badge.svg?branch=main
[main]: https://github.com/tsantos84/fast-object-normalizer/tree/main
[main coverage image]: https://codecov.io/gh/tsantos84/fast-object-normalizer/branch/main/graph/badge.svg
[main coverage]: https://codecov.io/gh/tsantos84/fast-object-normalizer/branch/main

[benchmark]: https://github.com/tsantos84/fast-object-normalizer/actions/workflows/benchmark.yml
