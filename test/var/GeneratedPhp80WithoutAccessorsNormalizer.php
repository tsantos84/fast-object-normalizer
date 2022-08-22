<?php

namespace App\Serializer\Normalizer;

/**
 * Auto-generated class! Do not change it by yourself.
 */
class GeneratedPhp80WithoutAccessorsNormalizer implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface, \Symfony\Component\Serializer\Normalizer\DenormalizerInterface, \Tsantos\Symfony\Serializer\Normalizer\ObjectFactoryInterface
{
	public function __construct(private readonly \Symfony\Component\Serializer\Serializer $serializer)
	{
	}


	public function normalize(mixed $object, string $format = null, array $context = [])
	{
		$data = [];
		$data['string'] = $object->string;
		$data['stringWithDocBlock'] = $object->stringWithDocBlock;
		$data['int'] = $object->int;
		$data['float'] = $object->float;
		$data['array'] = $this->serializer->normalize($object->array, $format, $context);;
		$data['nested'] = $this->serializer->normalize($object->nested, $format, $context);;
		$data['objectCollection'] = $this->serializer->normalize($object->objectCollection, $format, $context);;
		$data['intCollection'] = $this->serializer->normalize($object->intCollection, $format, $context);;
		$data['nullable'] = $object->nullable;
		return $data;
	}


	public function supportsNormalization(mixed $data, string $format = null)
	{
		return $data instanceof \Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\Php80WithoutAccessors;
	}


	public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
	{
		$object = $context['object_to_populate'] ?? $this->newInstance($data, $context);
		if (isset($data['string'])) {
		    $object->string = $data['string'];
		}

		if (isset($data['stringWithDocBlock'])) {
		    $object->stringWithDocBlock = $data['stringWithDocBlock'];
		}

		if (isset($data['int'])) {
		    $object->int = $data['int'];
		}

		if (isset($data['float'])) {
		    $object->float = $data['float'];
		}

		if (isset($data['array'])) {
		    $object->array = $data['array'];
		}

		if (isset($data['nested'])) {
		    $object->nested = $this->serializer->denormalize($data['nested'], 'Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithConstructor', $format, $context);
		}

		if (isset($data['objectCollection'])) {
		    $object->objectCollection = $this->serializer->denormalize($data['objectCollection'], 'Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyWithConstructor[]', $format, $context);
		}

		if (isset($data['intCollection'])) {
		    $object->intCollection = $this->serializer->denormalize($data['intCollection'], 'int[]', $format, $context);
		}

		$object->nullable = $data['nullable'] ?? null;
		return $object;
	}


	public function supportsDenormalization(mixed $data, string $type, string $format = null)
	{
		return $type === '\Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\Php80WithoutAccessors';
	}


	public function newInstance(array $data = [], array $context = []): object
	{
		return new \Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\Php80WithoutAccessors();
	}
}
