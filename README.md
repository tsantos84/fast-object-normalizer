# Symfony Fast Object Normalizer

Symfony Serializer is one of the Symfony's components aimed serialize and deserialize data in PHP
applications. The serialization process is a common practice when we need to expose and receive data
to internet in some format like JSON or XML and developers normally do this through an API. Symfony 
Serializer has many built-in normalizers to (de-)normalize data, and it is good to use it when starting
new projects. However, when application performance starts to make difference to your project, those
normalizers can not be the best choice because the runtime overhead they bring to the application. That's

## Fast Object Normalizer

Fast Object Normalizer is a normalizer that generates dedicated normalizes to your data, so you don't need
to worry about runtime overhead like reflections or code relying on metadata. By using this package in your project
you can **improve in 5x** the serializer process performance! See benchmark results here:

### Instalation

    composer require tsantos/fast-object-normalizer

#### Symfony Applications

This package ships with a Symfony Bundle that autmatically adds the normalizer to the normalizer stack. 
It means that you need to do nothing in your code to improve the performance of your application.

#### Standalone Applications

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

### Configuration

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

#### Black box

This approach is more restritive and is the best choice for many applications. You know exactly what classes will be 
normalized by `Fast Object Normalizer`

#### White box

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

### You must be aware

This package *IS NOT* a full replacement to Symfony's `AbstractObjectNormalizer`. Please, read the table bellow to see
what features this package can delivery to you.

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