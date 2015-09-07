<?php

namespace C33s\SymfonyConfigManipulatorBundle\Tests\Manipulator;

use C33s\SymfonyConfigManipulatorBundle\Manipulator\YamlManipulator;
use C33s\SymfonyConfigManipulatorBundle\Tests\BaseTestCase;

class YamlManipulatorTest extends BaseTestCase
{
    /**
     * @dataProvider provideSortImports
     *
     * @param array  $input
     * @param string $prefix
     * @param array  $output
     */
    public function testSortImports(array $input, $prefix, array $expectedOutput)
    {
        $manipulator = new YamlManipulator();
        $output = $manipulator->sortImports($input, $prefix);

        $this->assertEquals($expectedOutput, $output);
    }

    public function provideSortImports()
    {
        return array(
            // sort everything, no prefix
            array(
                array('imports' => array(
                    array('resource' => 'b.yml'),
                    array('resource' => 'c.yml'),
                    array('resource' => 'a.yml'),
                )),
                '',
                array('imports' => array(
                    array('resource' => 'a.yml'),
                    array('resource' => 'b.yml'),
                    array('resource' => 'c.yml'),
                )),
            ),
            // prefix and files before
            array(
                array('imports' => array(
                    array('resource' => 'c.yml'),
                    array('resource' => 'b.yml'),
                    array('resource' => 'config/c.yml'),
                    array('resource' => 'config/a.yml'),
                    array('resource' => 'config/b.yml'),
                )),
                'config/',
                array('imports' => array(
                    array('resource' => 'c.yml'),
                    array('resource' => 'b.yml'),
                    array('resource' => 'config/a.yml'),
                    array('resource' => 'config/b.yml'),
                    array('resource' => 'config/c.yml'),
                )),
            ),
            // prefix and files after
            array(
                array('imports' => array(
                    array('resource' => 'config/c.yml'),
                    array('resource' => 'config/a.yml'),
                    array('resource' => 'config/b.yml'),
                    array('resource' => 'c.yml'),
                    array('resource' => 'b.yml'),
                )),
                'config/',
                array('imports' => array(
                    array('resource' => 'config/a.yml'),
                    array('resource' => 'config/b.yml'),
                    array('resource' => 'config/c.yml'),
                    array('resource' => 'c.yml'),
                    array('resource' => 'b.yml'),
                )),
            ),
            // prefix and files before and after
            array(
                array('imports' => array(
                    array('resource' => 'e.yml'),
                    array('resource' => 'a.yml'),
                    array('resource' => 'config/c.yml'),
                    array('resource' => 'config/a.yml'),
                    array('resource' => 'config/b.yml'),
                    array('resource' => 'c.yml'),
                    array('resource' => 'b.yml'),
                )),
                'config/',
                array('imports' => array(
                    array('resource' => 'e.yml'),
                    array('resource' => 'a.yml'),
                    array('resource' => 'config/a.yml'),
                    array('resource' => 'config/b.yml'),
                    array('resource' => 'config/c.yml'),
                    array('resource' => 'c.yml'),
                    array('resource' => 'b.yml'),
                )),
            ),
            // no prefix but folders
            array(
                array('imports' => array(
                    array('resource' => 'e.yml'),
                    array('resource' => 'a.yml'),
                    array('resource' => 'config/c.yml'),
                    array('resource' => 'config/a.yml'),
                    array('resource' => 'config/b.yml'),
                    array('resource' => 'c.yml'),
                    array('resource' => 'b.yml'),
                )),
                '',
                array('imports' => array(
                    array('resource' => 'a.yml'),
                    array('resource' => 'b.yml'),
                    array('resource' => 'c.yml'),
                    array('resource' => 'config/a.yml'),
                    array('resource' => 'config/b.yml'),
                    array('resource' => 'config/c.yml'),
                    array('resource' => 'e.yml'),
                )),
            ),
        );
    }

    /**
     * @dataProvider provideDataContainsImportFile
     *
     * @param array  $data
     * @param string $filename
     * @param bool   $expectedResult
     */
    public function testDataContainsImportFile($data, $filename, $expectedResult)
    {
        $manipulator = new YamlManipulator();
        $result = $manipulator->dataContainsImportFile($data, $filename);
        $this->assertEquals($expectedResult, $result);
    }

    public function provideDataContainsImportFile()
    {
        return array(
            array(
                array('imports' => array(
                    array('resource' => 'foo.yml'),
                    array('resource' => 'bar.yml'),
                )),
                'foo.yml',
                true,
            ),
            array(
                array('imports' => array(
                    array('resource' => 'foo.yml'),
                    array('resource' => 'bar.yml'),
                )),
                'baz.yml',
                false,
            ),
            array(
                array('imports' => array(
                    array('resource' => 'foo.yml'),
                    array('resource' => 'bar.yml'),
                )),
                'foo.ym',
                false,
            ),
            array(
                array('imports' => array(
                    array('resource' => 'foo.yml'),
                    array('resource' => 'bar.yml'),
                )),
                'oo.yml',
                false,
            ),
            array(
                array(),
                'foo.yml',
                false,
            ),
            array(
                array('imports' => null),
                'foo.yml',
                false,
            ),
        );
    }

    /**
     * @dataProvider provideImporterHasFilename
     *
     * @param string $importerFile
     * @param string $filename
     * @param bool   $expectedResult
     */
    public function testImporterHasFilename($importerFile, $filename, $expectedResult)
    {
        $manipulator = new YamlManipulator();
        $result = $manipulator->importerFileHasFilename($importerFile, $filename);
        $this->assertEquals($expectedResult, $result);
    }

    public function provideImporterHasFilename()
    {
        return array(
            array(
                __DIR__.'/../Fixtures/symfony-standard-2.7.3/expected/config/config.yml',
                'config/assetic.yml',
                true,
            ),
            array(
                __DIR__.'/../Fixtures/symfony-standard-2.7.3/expected/config/config.yml',
                'config/does-not-exist.yml',
                false,
            ),
            array(
                __DIR__.'/../Fixtures/symfony-standard-2.7.3/expected/config/does-not-exist.yml',
                'config/assetic.yml',
                false,
            ),
        );
    }
}
