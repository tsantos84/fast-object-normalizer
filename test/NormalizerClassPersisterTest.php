<?php

declare(strict_types=1);

namespace Tsantos\Test\Symfony\Serializer\Normalizer;

use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tsantos\Symfony\Serializer\Normalizer\NormalizerClassPersister;

class NormalizerClassPersisterTest extends TestCase
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

    public function testPersistClassWithoutLoadingIt(): void
    {
        $persister = new NormalizerClassPersister($this->outputDir);
        $phpFile = new PhpFile();
        $phpFile->addClass('Foo\\Bar\\BazClass1');
        $persister->persist($phpFile, false, false);
        $this->assertFileExists($this->outputDir . '/BazClass1.php');
        $this->assertFalse(class_exists('Foo\\Bar\\BazClass1', false));
    }

    public function testPersistClassLoadingIt(): void
    {
        $persister = new NormalizerClassPersister($this->outputDir);
        $phpFile = new PhpFile();
        $phpFile->addClass('Foo\\Bar\\BazClass2');
        $persister->persist($phpFile);
        $this->assertFileExists($this->outputDir . '/BazClass2.php');
        $this->assertTrue(class_exists('Foo\\Bar\\BazClass2', false));
    }
}
