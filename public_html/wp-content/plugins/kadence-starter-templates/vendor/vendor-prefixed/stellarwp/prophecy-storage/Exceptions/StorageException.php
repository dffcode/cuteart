<?php
/**
 * @license GPL-2.0-only
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Storage\Exceptions;

use Exception;
use RuntimeException;

class StorageException extends RuntimeException
{
	public static function putError(string $path, ?Exception $previous = null): StorageException {
		return new self(sprintf('Could not put the given object at "%s".', $path), 0, $previous);
	}

	public static function appendError(string $path, ?Exception $previous = null): StorageException {
		return new self(sprintf('Could not append the given object at "%s".', $path), 0, $previous);
	}

	public static function deleteError(string $path, ?Exception $previous = null): StorageException {
		return new self(sprintf('Could not remove the given object at "%s".', $path), 0, $previous);
	}
}
