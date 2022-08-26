<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer;

use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerClassConfig;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerClassDumper;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyA;
use Tsantos\Test\Symfony\Serializer\Normalizer\Fixtures\DummyB;

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
        $this->outputDir = sys_get_temp_dir() . '/generated_classes';
        $this->filesystem->mkdir($this->outputDir);
    }

    protected function tearDown(): void
    {
        if (is_string($this->outputDir)) {
            $this->filesystem->remove($this->outputDir);
        }
    }

    public function testDumpClassWithoutLoadingIt(): void
    {
        $persister = new NormalizerClassDumper($this->outputDir);
        $config = new NormalizerClassConfig(DummyA::class, $this->outputDir);
        $phpFile = new PhpFile();
        $phpFile->addClass($config->normalizerClassName);
        $this->assertFileDoesNotExist($config->getFilename());
        $this->assertFalse(class_exists($config->normalizerClassName, false));
        $persister->dump($config, $phpFile, false);
        $this->assertFileExists($config->getFilename());
        $this->assertFalse(class_exists($config->normalizerClassName, false));
    }

    public function testDumpClassLoadingIt(): void
    {
        $persister = new NormalizerClassDumper($this->outputDir);
        $config = new NormalizerClassConfig(DummyB::class, $this->outputDir);
        $phpFile = new PhpFile();
        $phpFile->addClass($config->normalizerClassName);
        $this->assertFileDoesNotExist($config->getFilename());
        $this->assertFalse(class_exists($config->normalizerClassName, false));

        $persister->dump($config, $phpFile);

        $this->assertFileExists($config->getFilename());
        $this->assertTrue(class_exists($config->normalizerClassName, false));
    }
}
