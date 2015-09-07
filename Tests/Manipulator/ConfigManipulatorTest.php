<?php

namespace C33s\SymfonyConfigManipulatorBundle\Tests\Manipulator;

use C33s\SymfonyConfigManipulatorBundle\Manipulator\ConfigManipulator;
use C33s\SymfonyConfigManipulatorBundle\Tests\BaseTestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

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
            array(__DIR__.'/../Fixtures/refresh-config/symfony-standard-2.7.3', array('', 'dev', 'prod', 'test')),

            // adding custom sections to the Symfony standard edition 2.7.3 config that has been refreshed beforehand
            array(__DIR__.'/../Fixtures/refresh-config/symfony-standard-2.7.3-add-sections', array('', 'dev', 'prod', 'test')),
        );
    }

    public function testInitAllConfigsRunsOnlyOnce()
    {
        $manipulator = $this
            ->getMockBuilder('C33s\SymfonyConfigManipulatorBundle\Manipulator\ConfigManipulator')
            ->setConstructorArgs(array(array('prod'), $this->tempDir, new NullLogger()))
            ->setMethods(array('initConfig'))
            ->getMock()
        ;

        $manipulator
            ->expects($this->once())
            ->method('initConfig')
            ->with('prod')
        ;

        $manipulator->refreshConfigFiles();
        $manipulator->refreshConfigFiles();
    }

    public function testEnableModuleConfigWithExistingFile()
    {
        $sourceDir = __DIR__.'/../Fixtures/enable-module-config/config-file-exists';
        $environments = array('');

        $this->mirrorDirectory($sourceDir.'/source', $this->tempDir);

        $manipulator = new ConfigManipulator($environments, $this->tempDir, new NullLogger());
        $manipulator->enableModuleConfig('new_config');

        $this->assertDirectoriesAreEqual($sourceDir.'/expected', $this->tempDir);
    }

    /**
     * @expectedException C33s\SymfonyConfigManipulatorBundle\Exception\MissingModuleConfigException
     */
    public function testEnableModuleConfigWithoutExistingFile()
    {
        $sourceDir = __DIR__.'/../Fixtures/enable-module-config/config-file-does-not-exist';
        $environments = array('');

        $this->mirrorDirectory($sourceDir.'/source', $this->tempDir);

        $manipulator = new ConfigManipulator($environments, $this->tempDir, new NullLogger());
        $manipulator->enableModuleConfig('new_config');
    }

    /**
     * @expectedException C33s\SymfonyConfigManipulatorBundle\Exception\ModuleExistsException
     */
    public function testInitConfigWithExistingModuleFile()
    {
        $sourceDir = __DIR__.'/../Fixtures/refresh-config/module-exists';
        $environments = array('');

        $this->mirrorDirectory($sourceDir.'/source', $this->tempDir);

        $manipulator = new ConfigManipulator($environments, $this->tempDir, new NullLogger());
        $manipulator->refreshConfigFiles();
    }

    public function testInitConfigIgnoresMissingEnvironments()
    {
        $sourceDir = __DIR__.'/../Fixtures/refresh-config/ignore-missing-environments';
        $environments = array('dev');

        $this->mirrorDirectory($sourceDir.'/source', $this->tempDir);

        $manipulator = new ConfigManipulator($environments, $this->tempDir, new NullLogger());
        $manipulator->refreshConfigFiles();

        $this->assertDirectoriesAreEqual($sourceDir.'/expected', $this->tempDir);
    }

    /**
     * @expectedException C33s\SymfonyConfigManipulatorBundle\Exception\ConfigManipulatorException
     */
    public function testStripBaseConfigFolderFromPath()
    {
        $manipulator = new ConfigManipulator(array(''), $this->tempDir, new NullLogger());
        $manipulatorClass = new \ReflectionClass($manipulator);
        $method = $manipulatorClass->getMethod('stripBaseConfigFolderFromPath');
        $method->setAccessible(true);

        $method->invokeArgs($manipulator, array('this/is/an/invalid/path.yml'));
    }

    /**
     * @dataProvider provideAddModuleConfig
     *
     * @param string $filename
     * @param string $name
     * @param mixed  $value
     * @param bool   $preserveFormatting
     * @param string $addComment
     */
    public function testAddModuleConfig($sourceDir, $module, $yamlContent, $overwriteExisting, $enable, $expectException)
    {
        $this->setupTempDir();

        $this->mirrorDirectory($sourceDir.'/source', $this->tempDir);
        $manipulator = new ConfigManipulator(array(''), $this->tempDir, new NullLogger());

        if ($expectException) {
            $this->setExpectedException('C33s\SymfonyConfigManipulatorBundle\Exception\ModuleExistsException');
        }

        $manipulator->addModuleConfig($module, $yamlContent, '', $overwriteExisting, $enable);

        if (!$expectException) {
            $this->assertDirectoriesAreEqual($sourceDir.'/expected', $this->tempDir);
        }
    }

    public function provideAddModuleConfig()
    {
        $sets = array();
        $finder = Finder::create()
            ->directories()
            ->depth(0)
            ->in(__DIR__.'/../Fixtures/add-module-config/')
        ;

        foreach ($finder as $dir) {
            /* @var $dir SplFileInfo */
            $vars = Yaml::parse(file_get_contents($dir->getRealPath().'/vars.yml'));
            $sets[] = array(
                $dir->getRealPath(),
                $vars['module'],
                $vars['yamlContent'],
                $vars['overwriteExisting'],
                $vars['enable'],
                $vars['expectException'],
            );
        }

        return $sets;
    }

    public function testAddParameter()
    {
        $yamlManipulator = $this->getMock('C33s\SymfonyConfigManipulatorBundle\Manipulator\YamlManipulator');

        $yamlManipulator
            ->expects($this->exactly(2))
            ->method('addParameterToFile')
            ->withConsecutive(
                array(
                    $this->equalTo($this->tempDir.'/config/parameters.yml'),
                    $this->equalTo('foo'),
                    $this->equalTo('default value'),
                    $this->equalTo(false),
                    $this->equalTo(null),
                ),
                array(
                    $this->equalTo($this->tempDir.'/config/parameters.yml.dist'),
                    $this->equalTo('foo'),
                    $this->equalTo('default value'),
                    $this->equalTo(true),
                    $this->equalTo('no comment'),
                )
            )
        ;

        $manipulator = new ConfigManipulator(array(''), $this->tempDir, new NullLogger());
        $manipulator->setYamlManipulator($yamlManipulator);
        $manipulator->addParameter('foo', 'default value', 'no comment');
    }
}
