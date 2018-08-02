# Mesavolt/ImagingBundle

[![Build Status](https://travis-ci.org/MesaVolt/ImagingBundle.svg?branch=master)](https://travis-ci.org/MesaVolt/ImagingBundle)
[![Coverage Status](https://coveralls.io/repos/github/MesaVolt/ImagingBundle/badge.svg?branch=master)](https://coveralls.io/github/MesaVolt/ImagingBundle?branch=master)

## Installation

Use `composer`: 

```console
composer require mesavolt/imaging-bundle
```

### Applications that don't use Symfony Flex

If you don't use Symfony Flex, you need to enable the bundle by hand.
To to so, add it to the list of registered bundles in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Mesavolt\ImagingBundle\ImagingBundle(),
        );
        // ...
    }
    // ...
}
```

## Configuration

The following options are available to customize the behavior of the bundle's imaging service :

| Option name                     | Default value        | Role                                        |
| ------------------------------- | -------------------- | --------------------------------------------|
| imaging.transparency_replacement| `#FFFFFF`            | The color used to replace transparent areas |


## Usage

Inject the `Mesavolt\ImagingBundle\ImagingService` service into your services and controllers
(or get the `mesavolt.imaging` service from the container) :

```php
<?php

namespace App;


use Mesavolt\ImagingBundle\Service\ImagingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function index(ImagingService $imagingService)
    {
        $relative = '/public/thumbnails/thumbnail.jpeg';
        $path = $this->getParameter('kernel.project_dir').$relative;
        $imagingService->shrink('/tmp/image.jpg', $path);
        
        return $this->render('home/index.html.twig', [
            'shrunk' => $relative
        ]);
    }
}

```
