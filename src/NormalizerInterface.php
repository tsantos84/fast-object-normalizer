<?php

declare(strict_types=1);

namespace Tsantos\Symfony\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface as BaseNormalizerInterface;

interface NormalizerInterface extends BaseNormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{

}