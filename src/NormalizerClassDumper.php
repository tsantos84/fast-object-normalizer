<?php

declare(strict_types=1);

namespace Tsantos\Symfony\Serializer\Normalizer;

use Nette\PhpGenerator\PhpFile;

final class NormalizerClassDumper
{
    public function __construct(
        public readonly string $outputDir,
        private readonly bool $overwrite = false
    )
    {
    }

    public function dump(NormalizerClassConfig $classConfig, PhpFile $phpFile, bool $andLoad = true): bool
    {
        if (count($phpFile->getClasses()) === 0) {
            throw new \Exception('There is no class defined on PhpFile provided');
        }

        $shortName = $classConfig->normalizerClassShortName;
        $filename = $this->outputDir . DIRECTORY_SEPARATOR . $shortName . '.php';

        if ($classConfig->fileExists() && !$this->overwrite) {
            if ($andLoad && !$classConfig->isLoaded()) {
                $classConfig->load();
            }
            return true;
        }

        file_put_contents($filename, (string) $phpFile);

        if ($andLoad) {
            $classConfig->load();
        }

        return true;
    }
}
