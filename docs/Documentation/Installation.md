Installation
============

The plugin should be installed using Composer.

Use the inline `require` for composer:
```
composer require cakedc/search:3.0.*-dev
```

or add this to your composer.json configuration:
```
{
        "require" : {
                "cakedc/search": "3.0.*-dev"
        }
}
```

Then you will need to load the plugin in your `config/bootstrap.php` with `Plugin::load('Search');`
