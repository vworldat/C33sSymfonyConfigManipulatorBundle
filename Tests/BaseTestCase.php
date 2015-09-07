<?php

namespace C33s\SymfonyConfigManipulatorBundle\Tests;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    protected $tempDir;

    /**
     * Create temporary directory.
     */
    protected function setupTempDir()
    {
        $this->tempDir = tempnam(sys_get_temp_dir(), 'c33s');

        if (file_exists($this->tempDir)) {
            unlink($this->tempDir);
        }

        mkdir($this->tempDir);
    }

    /**
     * Remove temporary directory.
     */
    protected function cleanupTempDir()
    {
        if (null === $this->tempDir) {
            return;
        }

        $files = Finder::create()
            ->in($this->tempDir)
            ->files()
        ;
        foreach ($files as $file) {
            unlink($file->getPathname());
        }

        $dirs = iterator_to_array(Finder::create()
            ->in($this->tempDir)
            ->directories()
            ->sortByName()
        );
        $dirs = array_reverse($dirs);
        foreach ($dirs as $dir) {
            rmdir($dir->getPathname());
        }

        rmdir($this->tempDir);
    }

    /**
     * Assert that both the structure and the actual file contents of the 2 given paths are equal (recursively).
     *
     * @param string $expectedPath
     * @param string $actualPath
     */
    public function assertDirectoriesAreEqual($expectedPath, $actualPath)
    {
        $expectedFiles = iterator_to_array(Finder::create()
            ->in($expectedPath)
            ->files()
            ->sortByName()
        );

        $actualFiles = iterator_to_array(Finder::create()
            ->in($actualPath)
            ->files()
            ->sortByName()
        );

        $expectedFilePaths = array();
        foreach ($expectedFiles as $file) {
            $expectedFilePaths[$file->getRelativePathName()] = $file->getRelativePathName();
        }

        $actualFilePaths = array();
        foreach ($actualFiles as $file) {
            $actualFilePaths[$file->getRelativePathName()] = $file->getRelativePathName();
        }

        $this->assertEquals($expectedFilePaths, $actualFilePaths, 'Directory and file structure is matching');

        foreach ($expectedFiles as $file) {
            $this->assertEquals(file_get_contents($file->getPathname()), file_get_contents($actualPath.'/'.$file->getRelativePathname()), 'Content of file '.$file->getRelativePathname().' is matching');
        }
    }

    /**
     * Mirror the full directory and file structure of the given source directory to the given target directory.
     *
     * @param string $sourceDir
     * @param string $targetDir
     */
    protected function mirrorDirectory($sourceDir, $targetDir)
    {
        $files = Finder::create()
            ->in($sourceDir)
            ->sortByName()
        ;

        foreach ($files as $file) {
            /* @var $file SplFileInfo */
            if ($file->isDir()) {
                mkdir($targetDir.'/'.$file->getRelativePathname());
            } else {
                copy($file->getPathname(), $targetDir.'/'.$file->getRelativePathname());
            }
        }
    }
}
