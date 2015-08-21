<?php

namespace C33s\SymfonyConfigManipulatorBundle\Manipulator;

use C33s\SymfonyConfigManipulatorBundle\Exception\MissingModuleConfigException;
use C33s\SymfonyConfigManipulatorBundle\Exception\ModuleExistsException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * This class is used to handle the Symfony config files in a more structured way.
 */
class ConfigManipulator
{
    /**
     * @var string
     */
    protected $kernelRootDir;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * List of environments to scan for config files.
     *
     * @var string[]
     */
    protected $environments;

    /**
     * This will be true after all the config files have been refreshed once.
     *
     * @var bool
     */
    protected $isInitialized = false;

    /**
     * @var YamlManipulator
     */
    protected $yamlManipulator;

    /**
     * @param string $rootDir Kernel root dir
     */
    public function __construct(array $environments, $kernelRootDir, LoggerInterface $logger)
    {
        $this->environments = $environments;
        $this->kernelRootDir = $kernelRootDir;
        $this->logger = $logger;
    }

    /**
     * Refresh Symfony config files, moving all config sections inside the main config*.yml files into separated sub files.
     * e.g.: "framework:" inside app/config/config_dev.yml will be moved into app/config/config.dev/framework.yml.
     */
    public function refreshConfigFiles()
    {
        $this->initAllConfigs();
    }

    /**
     * @return YamlManipulator
     */
    public function getYamlManipulator()
    {
        if (null === $this->yamlManipulator) {
            $this->yamlManipulator = new YamlManipulator();
        }

        return $this->yamlManipulator;
    }

    /**
     * Get the base folder holding the main config files (app/config by default).
     *
     * @return string
     */
    protected function getBaseConfigFolder()
    {
        return $this->kernelRootDir.'/config/';
    }

    /**
     * Get the path to the main config file for the given environment.
     * e.g.:
     *  * app/config/config.yml
     *  * app/config/config_dev.yml
     *  * app/config/config_prod.yml
     *  * app/config/config_test.yml.
     *
     * @param string $environment
     *
     * @return string
     */
    protected function getConfigFile($environment)
    {
        return $this->getBaseConfigFolder().rtrim('config_'.$environment, '_').'.yml';
    }

    /**
     * Get the name of the sub folder to place in app/config.
     * e.g.:
     *  * config
     *  * config.dev
     *  * config.prod
     *  * config.test.
     *
     * @param string $environment
     *
     * @return string
     */
    protected function getImporterFolderName($environment)
    {
        return rtrim('config.'.$environment, '.').'/';
    }

    /**
     * Get the path to the module importer file for the given environment.
     * e.g.:
     *  * app/config/config/_importer.yml
     *  * app/config/config.dev/_importer.yml
     *  * app/config/config.prod/_importer.yml
     *  * app/config/config.test/_importer.yml.
     *
     * @param string $environment
     * @param string $filename    You may supply another importer file for custom use
     *
     * @return string
     */
    public function getImporterFile($environment, $filename = '_importer.yml')
    {
        return $this->getBaseConfigFolder().$this->getImporterFolderName($environment).$filename;
    }

    /**
     * Get the file path for the given config module and environment.
     * e.g.:
     *  * 'framework'           => app/config/config/framework.yml
     *  * 'swiftmailer', 'dev'  => app/config/config.dev/swiftmailer.yml.
     *
     * @param string $module
     * @param string $environment
     *
     * @return string
     */
    public function getModuleFile($module, $environment = '')
    {
        return $this->getBaseConfigFolder().$this->getImporterFolderName($environment).$module.'.yml';
    }

    /**
     * Create the given folder inside the base config folder.
     *
     * @param string $folderName
     *
     * @return string The path to the created folder
     */
    protected function createConfigFolder($folderName)
    {
        $folder = $this->getBaseConfigFolder().$folderName;
        if (!is_dir($folder)) {
            $this->logger->info("Creating folder config/{$folderName}");
            mkdir($folder);
        }

        return $folder;
    }

    /**
     * Refresh config files for all environments.
     */
    protected function initAllConfigs()
    {
        if ($this->isInitialized) {
            return;
        }

        $this->logger->info('Checking and initializing config files');
        foreach ($this->environments as $environment) {
            $this->initConfig($environment);
        }

        $this->isInitialized = true;
    }

    /**
     * Refresh config files for the given environment
     * e.g.:
     *  * ''    => splitting config.yml into config/{module}.yml sections
     *  * 'dev' => splitting config_dev.yml into config.dev/{module}.yml sections.
     *
     *  @param string $environment
     */
    protected function initConfig($environment)
    {
        $this->logger->debug("Initializing '$environment' config");

        $configFile = $this->getConfigFile($environment);
        if (!file_exists($configFile)) {
            $this->logger->warning("Could not find $configFile");

            return;
        }

        $folderName = $this->getImporterFolderName($environment);
        $this->createConfigFolder($folderName);

        $modules = $this->getYamlManipulator()->parseYamlModules($configFile);

        $this->logger->debug('Checking modules');
        // check if we can safely move all the config. This has to be done first to make sure the Symfony config does not break.
        foreach ($modules as $module => $data) {
            if ('imports' === $module) {
                continue;
            }

            if (!$this->checkCanCreateModuleConfig($module, $environment)) {
                throw new \RuntimeException("Cannot move config module '{$module}' from file config/config_{$environment}.yml to file config/{$folderName}{$module}.yml because it already exists and contains YAML data. Please clean up manually and retry.");
            }
        }

        $this->logger->debug('Found '.count($modules).' modules inside config file: "'.implode(', "', array_keys($modules)).'"');

        $this->logger->debug('Adding modules to separated config files');
        foreach ($modules as $module => $data) {
            if ('imports' === $module) {
                continue;
            }

            $this->addModuleConfig($module, $data['content'], $environment);
        }

        $data = array(
            'imports' => isset($modules['imports']) ? $modules['imports']['data']['imports'] : array(),
        );

        $filename = $folderName.'_importer.yml';
        if (!$this->getYamlManipulator()->dataContainsImportFile($data, $filename)) {
            $data['imports'][] = array('resource' => $filename);
        }

        $this->logger->debug("Re-writing $configFile");
        file_put_contents($configFile, Yaml::dump($data));
    }

    /**
     * Check if the config for the given module name and environment can safely be created.
     *
     * @param string $module
     * @param string $environment
     * @param bool   $allowOverwriteEmpty Set to false to disallow replacing files without readable YAML content
     *
     * @return bool
     */
    public function checkCanCreateModuleConfig($module, $environment = '', $allowOverwriteEmpty = true)
    {
        $targetFile = $this->getModuleFile($module, $environment);
        if (file_exists($targetFile)) {
            $content = Yaml::parse(file_get_contents($targetFile));
            if (null !== $content || !$allowOverwriteEmpty) {
                // there is something inside this file that parses as yaml
                return false;
            }
        }

        return true;
    }

    /**
     * Add the given content as {$module}.yml into the config folder for the given environment.
     *
     * @throws ModuleExistsException if there is an existing config file with the same name and $overwriteExisting is false
     *
     * @param string $module
     * @param string $yamlContent
     * @param string $environment
     * @param bool   $overwriteExisting If set to true, existing YAML files will be overwritten
     */
    public function addModuleConfig($module, $yamlContent, $environment = '', $overwriteExisting = false)
    {
        if (!$overwriteExisting && !$this->checkCanCreateModuleConfig($module, $environment)) {
            throw new ModuleExistsException("Cannot add config module '{$module}' for environment $environment because the target file already exists and contains YAML data. Please clean up manually and retry.");
        }

        $targetFile = $this->getModuleFile($module, $environment);
        if (!$overwriteExisting && file_exists($targetFile)) {
            $this->logger->debug("File $targetFile exists, appending existing content");
            $yamlContent .= "\n".file_get_contents($targetFile);
        }

        $yamlContent = trim($yamlContent)."\n";

        $this->logger->debug("Writing $targetFile");
        file_put_contents($targetFile, $yamlContent);

        $this->enableModuleConfig($module, $environment);
    }

    /**
     * Enable the module config inside the _importer.yml file for the given environment.
     * For this to work the specified config{.environment}/{module}.yml file has to exist.
     *
     * @throws MissingModuleConfigException if the file to enable does not exist.
     *
     * @param string $module
     * @param string $environment
     */
    public function enableModuleConfig($module, $environment = '')
    {
        $targetFile = $this->getModuleFile($module, $environment);
        if (!file_exists($targetFile)) {
            throw new MissingModuleConfigException("Cannot enable importer for {$module}.yml while file does not exist.");
        }

        if ($this->getYamlManipulator()->addImportFilenameToImporterFile($this->getImporterFile($environment), $module.'.yml')) {
            $this->logger->info("Added module '$module' to '$environment' config importer");
        } else {
            $this->logger->debug("Module '$module' for '$environment' already exists in config importer");
        }
    }

    /**
     * Add a parameter to parameters.yml and parameters.yml.dist.
     *
     * @param string $name
     * @param mixed  $defaultValue
     * @param string $addComment
     */
    public function addParameter($name, $defaultValue, $addComment = null)
    {
        $this->logger->info("Setting parameter $name in parameters.yml");
        $this->getYamlManipulator()->addParameterToFile($this->getBaseConfigFolder().'parameters.yml', $name, $defaultValue, false);
        $this->getYamlManipulator()->addParameterToFile($this->getBaseConfigFolder().'parameters.yml.dist', $name, $defaultValue, true, $addComment);
    }
}
