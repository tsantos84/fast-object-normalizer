<?php

declare(strict_types=1);

namespace TSantos\FastObjectNormalizer\Bridge\Symfony;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer as BaseCacheWarmer;

final class CacheWarmer extends BaseCacheWarmer
{
    public function __construct(
        private readonly string $outputDir,
        private readonly Filesystem $filesystem
    )
    {
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
