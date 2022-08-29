<?php

declare(strict_types=1);

/*
 * This file is part of the TSantos Fast Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TSantos\FastObjectNormalizer;

use Nette\PhpGenerator\PhpFile;

final class NormalizerClassDumper
{
    public function __construct(
        public readonly string $outputDir,
        public readonly bool $overwrite = false
    ) {
    }

    public function dump(NormalizerClassConfig $classConfig, string $content, bool $andLoad = true): bool
    {
        $shortName = $classConfig->normalizerClassShortName;
        $filename = $this->outputDir.\DIRECTORY_SEPARATOR.$shortName.'.php';

        if ($classConfig->fileExists() && !$this->overwrite) {
            if ($andLoad && !$classConfig->isLoaded()) {
                $classConfig->load();
            }

            return true;
        }

        file_put_contents($filename, $content);

        if ($andLoad) {
            $classConfig->load();
        }

        return true;
    }
}
