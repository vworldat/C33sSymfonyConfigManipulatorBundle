<?php

namespace C33s\SymfonyConfigManipulatorBundle\Tests\Command;

use C33s\SymfonyConfigManipulatorBundle\Command\RefreshConfigFilesCommand;
use Symfony\Component\DependencyInjection\Container;

class UpdateConfigFilesCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testCommandExecutes()
    {
        $command = new RefreshConfigFilesCommand();

        $manipulator = $this
            ->getMockBuilder('C33s\SymfonyConfigManipulatorBundle\Manipulator\ConfigManipulator')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $manipulator
            ->expects($this->once())
            ->method('refreshConfigFiles')
        ;

        $container = new Container();
        $container->set('c33s_symfony_config_manipulator.config_manipulator', $manipulator);

        $command->setContainer($container);

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command->run($input, $output);
    }
}
