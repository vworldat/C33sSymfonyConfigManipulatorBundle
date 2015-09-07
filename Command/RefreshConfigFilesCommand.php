<?php

namespace C33s\SymfonyConfigManipulatorBundle\Command;

use C33s\SymfonyConfigManipulatorBundle\Manipulator\ConfigManipulator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshConfigFilesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('config:refresh-files')
            ->setDescription('Refresh config files, splitting config sections into separate files as needed')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getConfigManipulator()->refreshConfigFiles();
    }

    /**
     * @return ConfigManipulator
     */
    protected function getConfigManipulator()
    {
        return $this->getContainer()->get('c33s_symfony_config_manipulator.config_manipulator');
    }
}
