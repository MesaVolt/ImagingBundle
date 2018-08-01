# Mesavolt/ImagingBundle


## Installation

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
composer require mesavolt/imaging-bundle
```

That's it. Flex automagically enables the bundle for you. Go to the **Configuration**
section of this README to see how you can customize the bundle's behavior.

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
composer require mesavolt/imaging-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

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

## Usage

Inject the `Mesavolt\SimpleCache` service into your services and controllers
(or get the `mesavolt.simple_cache` service from the container) :

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
