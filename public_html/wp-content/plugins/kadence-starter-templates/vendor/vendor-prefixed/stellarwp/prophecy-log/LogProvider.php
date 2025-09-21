<?php
/**
 * @license GPL-2.0-only
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Log;

use KadenceWP\KadenceStarterTemplates\lucatume\DI52\Container;
use KadenceWP\KadenceStarterTemplates\Monolog\Formatter\LineFormatter;
use KadenceWP\KadenceStarterTemplates\Monolog\Handler\ErrorLogHandler;
use KadenceWP\KadenceStarterTemplates\Monolog\Handler\StreamHandler;
use KadenceWP\KadenceStarterTemplates\Monolog\Logger;
use KadenceWP\KadenceStarterTemplates\Psr\Log\LoggerInterface;
use RuntimeException;
use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Container\Contracts\Provider;
use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Log\Formatters\ColoredLineFormatter;
use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Log\Handlers\NullHandler;

final class LogProvider extends Provider
{
	public const LOG_LEVEL = 'prophecy.log.log_level';
	public const CHANNELS  = [
		'console'  => [
			'class'     => StreamHandler::class,
			'formatter' => ColoredLineFormatter::class,
		],
		'errorlog' => [
			'class'     => ErrorLogHandler::class,
			'formatter' => LineFormatter::class,
		],
		'stack'    => [
			'console',
			'errorlog',
		],
		'null'     => [
			'class' => NullHandler::class,
		],
	];

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$this->container->singleton(self::LOG_LEVEL, LogLevel::fromName($this->config->get('log.level', 'debug')));

		$this->container->when(ColoredLineFormatter::class)
						->needs('$dateFormat')
						->give('Y-m-d H:i:s.v e');

		$channel = $this->config->get('log.channel');

		$this->container->singleton(
			StreamHandler::class,
			function ($c) use ($channel) {
				return new StreamHandler(
					$this->config->get("log.channels.$channel.with.stream", 'php://stdout'),
					$c->get(self::LOG_LEVEL)
				);
			}
		);

		$this->container->bind(
			LoggerInterface::class,
			static function (Container $c) use ($channel): LoggerInterface {
				$handler = self::CHANNELS[$channel] ?? false;

				if (! $handler) {
					throw new RuntimeException(
						sprintf(
							'Invalid log channel. Valid options are: %s',
							implode(',', array_keys(self::CHANNELS))
						)
					);
				}

				$logger = new Logger($channel);

				/**
				 * @var array{handler: \KadenceWP\KadenceStarterTemplates\Monolog\Handler\AbstractProcessingHandler, formatter: string|class-string} $handlers
				 */
				$handlers = [];

				// Single handler channel.
				if (! empty($handler['class'])) {
					$handlers[] = [
						'handler'   => $c->get($handler['class']),
						'formatter' => $handler['formatter'] ?? '',
					];
				} else {
					// We are on a stack channel, which uses multiple existing handlers.
					foreach ($handler as $stackChannel) {
						$handlers[] = [
							'handler'   => $c->get(self::CHANNELS[$stackChannel]['class']),
							'formatter' => self::CHANNELS[$stackChannel]['formatter'] ?? '',
						];
					}
				}

				/** @var array{handler: \KadenceWP\KadenceStarterTemplates\Monolog\Handler\AbstractProcessingHandler, formatter: string|class-string} $registeredHandler */
				foreach ($handlers as $registeredHandler) {
					if (! empty($registeredHandler['formatter'])) {
						$registeredHandler['handler']->setFormatter($c->get($registeredHandler['formatter']));
					}

					// Set the configured log level for each handler.
					$registeredHandler['handler']->setLevel($c->get(self::LOG_LEVEL));

					$logger->pushHandler($registeredHandler['handler']);
				}

				return $logger;
			}
		);
	}
}
