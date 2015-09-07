<?php

namespace C33s\SymfonyConfigManipulatorBundle\Tests\DependencyInjection;

use C33s\SymfonyConfigManipulatorBundle\DependencyInjection\C33sSymfonyConfigManipulatorExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class C33sSymfonyConfigManipulatorExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testExtensionLoadsSomething()
    {
        $container = new ContainerBuilder();
        $extension = new C33sSymfonyConfigManipulatorExtension();
        $extension->load(array(), $container);
        $this->assertNotCount(0, $container->getDefinitions(), 'Extension contains at least one service definition');
    }
}
