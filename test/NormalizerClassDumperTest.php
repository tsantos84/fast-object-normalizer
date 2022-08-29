<?php

declare(strict_types=1);

/*
 * This file is part of the TSantos Fast Object Normalizer package.
 * (c) Tales Santos <tales.augusto.santos@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TSantos\Test\FastObjectNormalizer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use TSantos\FastObjectNormalizer\NormalizerClassConfig;
use TSantos\FastObjectNormalizer\NormalizerClassDumper;
use TSantos\Test\FastObjectNormalizer\Fixtures\DummyA;
use TSantos\Test\FastObjectNormalizer\Fixtures\DummyB;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class NormalizerClassDumperTest extends TestCase
{
    private Filesystem $filesystem;
    private ?string $outputDir = null;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->outputDir = sys_get_temp_dir().'/generated_classes';
        $this->filesystem->mkdir($this->outputDir);
    }

    protected function tearDown(): void
    {
        if (\is_string($this->outputDir)) {
            $this->filesystem->remove($this->outputDir);
        }
    }

    public function testDumpClassWithoutLoadingIt(): void
    {
        $persister = new NormalizerClassDumper($this->outputDir);
        $config = new NormalizerClassConfig(DummyA::class, $this->outputDir);
        $this->assertFileDoesNotExist($config->getFilename());
        $this->assertFalse(class_exists($config->normalizerClassName, false));
        $persister->dump($config, sprintf('<?php class %s {}', $config->normalizerClassShortName), false);
        $this->assertFileExists($config->getFilename());
        $this->assertFalse(class_exists($config->normalizerClassName, false));
    }

    public function testDumpClassLoadingIt(): void
    {
        $persister = new NormalizerClassDumper($this->outputDir);
        $config = new NormalizerClassConfig(DummyB::class, $this->outputDir);
        $this->assertFileDoesNotExist($config->getFilename());
        $this->assertFalse(class_exists($config->normalizerClassName, false));
        $persister->dump($config, sprintf('<?php class %s {}', $config->normalizerClassShortName));
        $this->assertFileExists($config->getFilename());
        $this->assertTrue(class_exists($config->normalizerClassName, false));
    }
}
