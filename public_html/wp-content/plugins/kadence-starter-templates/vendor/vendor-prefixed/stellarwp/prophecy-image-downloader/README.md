# Prophecy Image Downloader

> ⚠️ **This is a read-only repository!**
> For pull requests or issues, see [stellarwp/prophecy-monorepo](https://github.com/stellarwp/prophecy-monorepo).

A very fast, asynchronous concurrent image downloader built on [symfony/http-client](https://symfony.com/doc/current/http_client.html).

## Installation

Update your composer.json and add the following to your `repositories` object:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:stellarwp/prophecy-container.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:stellarwp/prophecy-log.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:stellarwp/prophecy-image-downloader.git"
        }        
    ]
}
```

Then, install:

```shell
composer require stellarwp/prophecy-image-downloader
```

If using the [di52 container](https://github.com/lucatume/di52), create a `config.php` and register
it in the container with:

```php
$this->container->bind(Dot::class, new Dot(require_once dirname(__FILE__) . '/config.php'));
```

The config.php file map environment variables, either from an `.env` file or manually set, e.g.

```php
<?php declare(strict_types=1);

return [
    'image_batch_size' => $_ENV['APP_IMAGE_BATCH_SIZE'] ?? 10,
	'storage_path'     => $_ENV['APP_STORAGE_PATH'] ?? '/tmp', // This could be the path to your wp-content/uploads folder.
];
```


Then, include the [ImageProvider.php](./ImageProvider.php), or create a custom/modified version 
of it and call the `register` method.

The downloader is meant to work with the [Prophecy Laravel Service](https://github.com/stellarwp/prophecy)'s API responses, however
if you format your array in the same way that [ImageDownloader::download()](./ImageDownloader.php) accepts, it will
fetch the files to your configured location:


## File Name Sanitization

The system provides some basic file name sanitization, but you can also use your framework's strategy or
make a custom strategy, as long as it implements the [Sanitizer Contract](./Sanitization/Contracts/Sanitizer.php).

For example, to use WordPress's file name sanitization, simply bind the interface to the provided WPFileNameStrategy, e.g. using
di52's container:

```php
$this->container->bind(Sanitizer::class, \StellarWP\ProphecyMonorepo\ImageDownloader\Sanitization\Sanitizers\WPFileNameSanitizer::class);
```
