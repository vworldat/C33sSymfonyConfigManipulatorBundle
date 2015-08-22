Keep your Symfony2 YAML config files organized
==============================================

[![Build Status](https://travis-ci.org/vworldat/C33sSymfonyConfigManipulatorBundle.svg)](https://travis-ci.org/vworldat/C33sSymfonyConfigManipulatorBundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/716c4317-aaf1-466c-90bf-48c3d98bf8c0/mini.png)](https://insight.sensiolabs.com/projects/716c4317-aaf1-466c-90bf-48c3d98bf8c0)

Do you hate stuffing tons of config into a single `config.yml` file, losing track of all the sections inside the file? Then this is for you!

This bundle provides some general-purpose YAML and Symfony config manipulation tasks. The most important one
is to split the Symfony `app/config/config*.yml` files into separate sections, leading to a structure like this:

```
# Symfony Standard Edition 2.7.3

app/config
├── config
│   ├── assetic.yml
│   ├── doctrine.yml
│   ├── framework.yml
│   ├── parameters.yml
│   ├── swiftmailer.yml
│   └── twig.yml
│
├── config_dev
│   ├── assetic.yml
│   ├── framework.yml
│   ├── monolog.yml
│   ├── swiftmailer.yml
│   └── web_profiler.yml
│
├── config_prod
│   ├── doctrine.yml
│   ├── framework.yml
│   └── monolog.yml
│
├── config_test
│   ├── framework.yml
│   ├── swiftmailer.yml
│   └── web_profiler.yml
│
├── config_dev.yml
├── config_prod.yml
├── config_test.yml
├── config.yml
│
│   # parameters.yml, routing.yml, security.yml etc. will never be touched
├── parameters.yml
├── parameters.yml.dist
├── routing_dev.yml
├── routing.yml
├── security.yml
└── services.yml
```

The cleaned up `config.yml` looks like this:

```yml
imports:
    - { resource: parameters.yml }
    - { resource: security.yml }
    - { resource: services.yml }
    - { resource: config/assetic.yml }
    - { resource: config/doctrine.yml }
    - { resource: config/framework.yml }
    - { resource: config/parameters.yml }
    - { resource: config/swiftmailer.yml }
    - { resource: config/twig.yml }
```

`config_dev.yml` content:

```yml
imports:
    - { resource: config.yml }
    - { resource: config_dev/assetic.yml }
    - { resource: config_dev/framework.yml }
    - { resource: config_dev/monolog.yml }
    - { resource: config_dev/swiftmailer.yml }
    - { resource: config_dev/web_profiler.yml }
```

### Advantages:

* Keep an overview which config modules are present by just looking at the sub folders
* By keeping separate files in your git repository, you may easily follow changes for specific config sections
* Working in larger teams becomes a little easier when the main `config.yml` isn't edited by several people at once
* *The configuration sections are copied as YAML text, not array data, so all your comments and formatting are preserved!*
* Manipulating specific config sections programmatically becomes a little easier

Installation
------------

Require [`c33s/symfony-config-manipulator-bundle`](https://packagist.org/packages/c33s/symfony-config-manipulator-bundle) in your `composer.json` file:

```js
{
    "require": {
        "c33s/symfony-config-manipulator-bundle": "@stable",
    }
}
```

or, if you are using ['composer-yaml'](https://packagist.org/packages/igorw/composer-yaml):

```yml
require:
    c33s/symfony-config-manipulator-bundle:     '@stable'
```

Register the bundle in `app/AppKernel.php`:

```php

    // app/AppKernel.php

    public function registerBundles()
    {
        return array(
            // ... existing bundles
            new C33s\SymfonyConfigManipulatorBundle\C33sSymfonyConfigManipulatorBundle(),
        );
    }

```

Usage
-----

All you have to do is run a single command:

    $ php app/console config:refresh-files

You may re-run it anytime you want. This is especially helpful if you are adding new configuration sections to your project. Just paste them into your main 
`config.yml`, `config_dev.yml` or similar files and run the command to instantly move the new configuration to separate files.

If you add a config section to your `config.yml` that is already present in a separate file with the same name, the command will exit with an error message.
Merge your configurations manually and you're good again.

Safety
------

The config splitter will never overwrite any existing config files as long as they contain parseable YAML. But as Murphy's law goes, there might be bugs where nobody expects them.

**Make sure to commit your configuration files to your git repository to keep your code safe!**
