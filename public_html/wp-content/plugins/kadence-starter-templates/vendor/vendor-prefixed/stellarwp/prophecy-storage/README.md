# Prophecy Storage

> ⚠️ **This is a read-only repository!**
> For pull requests or issues, see [stellarwp/prophecy-monorepo](https://github.com/stellarwp/prophecy-monorepo).

A Storage interface, allowing you to modify files utilizing different drivers.

## Installation

Update your composer.json and add the following to your `repositories` object:

```json
{
    "type": "vcs",
    "url": "git@github.com:stellarwp/prophecy-storage.git"
}
```

Then, install:

```shell
composer require stellarwp/prophecy-storage
```

Currently, there is just a [LocalStorage](./Drivers/LocalStorage.php) driver.

## Local Storage Driver Configuration

If using [di52](https://github.com/lucatume/di52), bind and configure the driver in a Provider.

```php
final class StorageProvider extends Provider
{
	public function register(): void {
		$this->container->bind( Storage::class, LocalStorage::class );
		$this->container->when( LocalStorage::class )
						->needs( '$storagePath' )
						->give( '/path/where/you/store/files' );
	}
}
```

Then, inject or call the Storage interface and begin manipulating files:

```php
$storage = $this->container->get( Storage::class );

// Create a new file in the configured storage path with the contents of "Hello World!".
$storage->put('hello-world.txt', 'Hello World!');

// Get the content of a file.
$storage->get('hello-world.txt');

// Append content to the end of a text file.
$storage->append('hello-world.txt', ' Goodbye World!');

// Delete a file.
$storage->delete('hello-world.txt');
```
> 💡 You can also store binary files like images.
