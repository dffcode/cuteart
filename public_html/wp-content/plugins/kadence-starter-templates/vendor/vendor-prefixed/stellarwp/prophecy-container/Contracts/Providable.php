<?php
/**
 * @license GPL-2.0-only
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Container\Contracts;

interface Providable
{
	/**
	 * Registers bindings in the container.
	 */
	public function register(): void;

	/**
	 * Whether the service provider will be a deferred one or not.
	 */
	public function isDeferred(): bool;

	/**
	 * Returns an array of the class or interfaces bound and provided by the service provider.
	 *
	 * @return string[] A list of fully-qualified implementations provided by the service provider.
	 */
	public function provides(): array;

	/**
	 * Binds and sets up implementations at boot time.
	 */
	public function boot(): void;
}
