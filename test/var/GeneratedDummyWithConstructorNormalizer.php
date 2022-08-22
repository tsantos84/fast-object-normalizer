<?php

namespace App\Serializer\Normalizer;

/**
 * Auto-generated class! Do not change it by yourself.
 */
class GeneratedDummyWithConstructorNormalizer implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface, \Symfony\Component\Serializer\Normalizer\DenormalizerInterface, \Tsantos\Symfony\Serializer\Normalizer\ObjectFactoryInterface
{
	public function __construct(private readonly \Symfony\Component\Serializer\Serializer $serializer)
	{
	}


	public function normalize(mixed $object, string $format = null, array $context = [])
	{
		$data = [];
		$data['foo'] = $object->foo;
		return $data;
	}


	public function supportsNormalization(mixed $data, string $format = null)
	{
		return $data instanceof \Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithConstructor;
	}


	public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
	{
		$object = $context['object_to_populate'] ?? $this->newInstance($data, $context);
		if (isset($data['foo'])) {
		    $object->foo = $data['foo'];
		}

		return $object;
	}


	public function supportsDenormalization(mixed $data, string $type, string $format = null)
	{
		return $type === '\Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithConstructor';
	}


	public function newInstance(array $data = [], array $context = []): object
	{
		return new \Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithConstructor($data['foo']);
	}
}
