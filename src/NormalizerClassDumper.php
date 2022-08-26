<?php

declare(strict_types=1);

/*
 * This file is part of the Tsantos Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tsantos\Symfony\Serializer\Normalizer;

use Nette\PhpGenerator\PhpFile;

final class NormalizerClassDumper
{
    public function __construct(
        public readonly string $outputDir,
        private readonly bool $overwrite = false
    ) {
    }

    public function dump(NormalizerClassConfig $classConfig, PhpFile $phpFile, bool $andLoad = true): bool
    {
        if (0 === \count($phpFile->getClasses())) {
            throw new \Exception('There is no class defined on PhpFile provided');
        }

        $shortName = $classConfig->normalizerClassShortName;
        $filename = $this->outputDir.\DIRECTORY_SEPARATOR.$shortName.'.php';

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
