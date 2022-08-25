<?php

declare(strict_types=1);

namespace Tsantos\Symfony\Serializer\Normalizer;

use Nette\PhpGenerator\PhpFile;

final class NormalizerClassPersister
{
    public function __construct(
        private readonly string $outputDir
    )
    {
    }

    public function persist(PhpFile $phpFile, bool $overwrite = false, bool $andLoad = true): string
    {
        if (count($phpFile->getClasses()) === 0) {
            throw new \Exception('There is no class defined on PhpFile provided');
        }

        $class = current($phpFile->getClasses());
        $shortName = $longName = $class->getName();

        if (count($phpFile->getNamespaces()) > 0) {
            $longName = current($phpFile->getNamespaces())->getName() . '\\' . $shortName;
        }

        $filename = $this->outputDir . DIRECTORY_SEPARATOR . $shortName . '.php';

        if (file_exists($filename) && !$overwrite) {
            if ($andLoad && !class_exists($longName, false)) {
                self::load($filename);
            }
            return $longName;
        }

        file_put_contents($filename, (string) $phpFile);

        if ($andLoad) {
            self::load($filename);
        }

        return $longName;
    }

    private static function load(string $filename): bool
    {
        include_once $filename;
        return true;
    }
}
