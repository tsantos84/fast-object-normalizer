<?php

declare(strict_types=1);

/*
 * This file is part of the TSantos Fast Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TSantos\FastObjectNormalizer\Bridge\Symfony;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer as BaseCacheWarmer;

final class CacheWarmer extends BaseCacheWarmer
{
    public function __construct(
        private readonly string $outputDir,
        private readonly Filesystem $filesystem
    ) {
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir)
    {
        if (!$this->filesystem->exists($this->outputDir)) {
            $this->filesystem->mkdir($this->outputDir);
        }
    }
}
