<?php

declare(strict_types=1);

/*
 * This file is part of the Tsantos Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tsantos\Symfony\Serializer\Normalizer;

final class NormalizerClassConfig
{
    public \ReflectionClass $refClass;
    public readonly string $normalizerClassName;
    public readonly string $normalizerClassShortName;

    public function __construct(
        public readonly string $subjectClassName,
        public readonly string $outputDir,
        public readonly ?string $outputNamespace = null
    ) {
        $this->refClass = new \ReflectionClass($this->subjectClassName);
        $this->normalizerClassShortName = 'Generated'.$this->refClass->getShortName().'Normalizer';
        $this->normalizerClassName = $this->outputNamespace.'\\'.$this->normalizerClassShortName;
    }

    public function isLoaded(): bool
    {
        return class_exists($this->normalizerClassName, false);
    }

    public function fileExists(): bool
    {
        return file_exists($this->getFilename());
    }

    public function getFilename(): string
    {
        return $this->outputDir.\DIRECTORY_SEPARATOR.$this->normalizerClassShortName.'.php';
    }

    public function load(): void
    {
        $filename = $this->getFilename();
        self::doLoad($filename);
    }

    private static function doLoad(string $filename): void
    {
        include_once $filename;
    }

    public function newInstance(array $args): NormalizerInterface
    {
        $refClass = new \ReflectionClass($this->normalizerClassName);

        return $refClass->newInstanceArgs($args);
    }
}
