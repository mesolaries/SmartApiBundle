
[![Latest Stable Version](https://poser.pugx.org/mesolaries/smart-api-bundle/v)](https://packagist.org/packages/mesolaries/smart-api-bundle) 
[![Total Downloads](https://poser.pugx.org/mesolaries/smart-api-bundle/downloads)](https://packagist.org/packages/mesolaries/smart-api-bundle)
[![GitHub Issues](https://img.shields.io/github/issues/mesolaries/SmartApiBundle)](https://github.com/mesolaries/SmartApiBundle/issues)
[![License](https://img.shields.io/github/license/mesolaries/SmartApiBundle)](https://github.com/mesolaries/SmartApiBundle/blob/main/LICENSE)

Smart API Bundle
============
This package provides a RESTful development helper services for Symfony. 

There are two main services for now. Read detailed docs for each service below:
- [SmartRequest](Request/README.md)
- [SmartProblem](Problem/README.md) 

Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require mesolaries/smart-api-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require mesolaries/smart-api-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Mesolaries\SmartApiBundle\MesolariesSmartApiBundle::class => ['all' => true],
];
```