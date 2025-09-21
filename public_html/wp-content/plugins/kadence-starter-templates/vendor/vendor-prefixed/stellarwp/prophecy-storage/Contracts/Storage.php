<?php
/**
 * @license GPL-2.0-only
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Storage\Contracts;

use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Storage\Exceptions;

interface Storage
{
	/**
	 * Puts an object in the storage.
	 *
	 * If the object already exists, it is overwritten.
	 *
	 * @param string $path The object path.
	 * @param mixed  $data The object data.
	 *
	 * @throws Exceptions\StorageException If an error occurs.
	 */
	public function put(string $path, $data): void;

	/**
	 * Append data to an object in the storage.
	 *
	 * @param string $path The object path.
	 * @param mixed  $data The object data.
	 *
	 * @throws Exceptions\StorageException If an error occurs.
	 */
	public function append(string $path, $data): void;

	/**
	 * Retrieves an object from the storage.
	 *
	 * @param string $path The object path.
	 *
	 * @throws Exceptions\NotFoundException If the path is not found.
	 */
	public function get(string $path): string;

	/**
	 * Returns whether an object exists for the given path.
	 */
	public function has(string $path): bool;

	/**
	 * Deletes an object from the storage.
	 *
	 * @throws Exceptions\StorageException If an error occurs.
	 */
	public function delete(string $path): void;
}
