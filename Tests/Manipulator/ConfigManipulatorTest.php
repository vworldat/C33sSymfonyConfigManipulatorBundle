<?php

namespace C33s\SymfonyConfigManipulatorBundle\Tests\Manipulator;

use C33s\SymfonyConfigManipulatorBundle\Manipulator\ConfigManipulator;
use C33s\SymfonyConfigManipulatorBundle\Tests\BaseTestCase;
use Psr\Log\NullLogger;

class ConfigManipulatorTest extends BaseTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->setupTempDir();
    }

    protected function tearDown()
    {
        $this->cleanupTempDir();
        parent::tearDown();
    }

    /**
     * @dataProvider provideFullConfigFolder
     *
     * @param string $folder
     */
    public function testFullConfigRefresh($sourceDir, $environments)
    {
        $this->mirrorDirectory($sourceDir.'/source', $this->tempDir);

        $manipulator = new ConfigManipulator($environments, $this->tempDir, new NullLogger());
        $manipulator->refreshConfigFiles();

        $this->assertDirectoriesAreEqual($sourceDir.'/expected', $this->tempDir);
    }

    public function provideFullConfigFolder()
    {
        return array(
            // testing a plain Symfony standard edition 2.7.3
            array(__DIR__.'/../Fixtures/symfony-standard-2.7.3', array('', 'dev', 'prod', 'test')),

            // adding custom sections to the Symfony standard edition 2.7.3 config that has been refreshed beforehand
            array(__DIR__.'/../Fixtures/symfony-standard-2.7.3-add-sections', array('', 'dev', 'prod', 'test')),
        );
    }
}
