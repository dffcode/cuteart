<?php
/**
 * @license GPL-2.0-only
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Container;

use Closure;
use KadenceWP\KadenceStarterTemplates\lucatume\DI52\Container as DI52Container;
use KadenceWP\KadenceStarterTemplates\lucatume\DI52\ContainerException;
use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Container\Contracts\Container;

/**
 * @method mixed make(string $id)
 * @method mixed getVar(string $key, mixed|null $default = null)
 * @method void  singletonDecorators($id, array $decorators, ?array $afterBuildMethods = null)
 * @method void  bindDecorators($id, array $decorators, ?array $afterBuildMethods = null)
 */
final class ContainerAdapter implements Container
{
	/**
	 * @readonly
	 *
	 * @var DI52Container
	 */
	private $container;
	public function __construct(DI52Container $container) {
		$this->container = $container;
	}

	/**
	 * @param mixed         $implementation
	 * @param string[]|null $afterBuildMethods
	 *
	 * @throws \KadenceWP\KadenceStarterTemplates\lucatume\DI52\ContainerException
	 */
	public function bind(string $id, $implementation = null, array $afterBuildMethods = null): void {
		$this->container->bind($id, $implementation, $afterBuildMethods);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return mixed
	 */
	public function get(string $id) {
		return $this->container->get($id);
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function get_container(): DI52Container {
		return $this->container;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @codeCoverageIgnore
	 */
	public function has(string $id): bool {
		return $this->container->has($id);
	}

	/**
	 * @param mixed         $implementation
	 * @param string[]|null $afterBuildMethods
	 *
	 * @throws \KadenceWP\KadenceStarterTemplates\lucatume\DI52\ContainerException
	 */
	public function singleton(string $id, $implementation = null, array $afterBuildMethods = null): void {
		$this->container->singleton($id, $implementation, $afterBuildMethods);
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(string $serviceProviderClass, ...$alias): void {
		$this->container->register($serviceProviderClass, ...$alias);
	}

	/**
	 * {@inheritDoc}
	 */
	public function when(string $class): Container {
		$this->container->when($class);

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function needs(string $id): Container {
		$this->container->needs($id);

		return $this;
	}

	/**
	 * @param mixed $implementation
	 */
	public function give($implementation): void {
		$this->container->give($implementation);
	}

	/**
	 * @param mixed $id
	 */
	public function instance($id, array $buildArgs = [], ?array $afterBuildMethods = null): Closure {
		// @phpstan-ignore-next-line invalid DocBlock comments in DI52
		return $this->container->instance($id, $buildArgs, $afterBuildMethods);
	}

	/**
	 * @param class-string|string|object $id
	 *
	 * @throws ContainerException
	 */
	public function callback($id, string $method): callable {
		return $this->container->callback($id, $method);
	}

	/**
	 * Defer all other calls to the container object.
	 *
	 * @param mixed[] $args
	 *
	 * @return mixed
	 */
	public function __call(string $name, array $args) {
		return $this->container->{$name}(...$args);
	}
}
