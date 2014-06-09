Installation
============

```
cli@host # php composer.phar require "docdigital/cdn-bundle:dev-master"
```

Configuration
=============

Enable CdnBundle in AppKernel

```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            ::
            new DocDigital\Bundle\CdnBundle\DdCdnBundle(),
        );
        ::
        return $bundles;
    }
  ::
}
```
Add     dd_cdn.upload.dir: parameter to parameters.yml

```yml
# app/config/parameters.yml

parameters:
    dd_cdn.upload.dir: "%kernel.root_dir%/../media/" # or any directory with 
                                                     # write permissions for www-data (apache) user.
```
