<?php
/**
 * @license GPL-2.0-only
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Log\Formatters;

use KadenceWP\KadenceStarterTemplates\Monolog\Formatter\LineFormatter;
use KadenceWP\KadenceStarterTemplates\Psr\Log\LogLevel;

class ColoredLineFormatter extends LineFormatter
{
	public const MODE_COLOR_LEVEL_ALL   = -1;
	public const MODE_COLOR_LEVEL_FIRST = 1;
	private const RESET                 = "\033[0m";

	/**
	 * Color scheme - use ANSI colour sequences
	 *
	 * @var array<string, string>
	 */
	private $colorScheme = [
		LogLevel::DEBUG     => "\033[0;37m",
		LogLevel::INFO      => "\033[1;32m",
		LogLevel::NOTICE    => "\033[1;34m",
		LogLevel::WARNING   => "\033[1;33m",
		LogLevel::ERROR     => "\033[0;33m",
		LogLevel::CRITICAL  => "\033[1;31m",
		LogLevel::ALERT     => "\033[0;31m",
		LogLevel::EMERGENCY => "\033[1;35m",
	];

	/**
	 * ColoredLineFormatter constructor.
	 *
	 * @param string|null        $format                The format of the message
	 * @param string|null        $dateFormat            The format of the timestamp: one supported by DateTime::format
	 * @param bool               $allowInlineLineBreaks Whether to allow inline line breaks in log entries
	 * @param array<string>|null $colorScheme           @see ColoredLineFormatter::$colorScheme
	 * @param int                $colorMode             Whether we want to replace all '%level_name%' occurrences or only the first.
	 *                                                  Only useful if no %color_start%/%color_end% specified in $format
	 */
	public function __construct(
		?string $format = '[%datetime%] %level_name%: %message% %context% %extra%' . PHP_EOL,
		?string $dateFormat = null,
		bool $allowInlineLineBreaks = false,
		bool $ignoreEmptyContextAndExtra = true,
		?array $colorScheme = null,
		int $colorMode = self::MODE_COLOR_LEVEL_ALL
	) {
		parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);

		if (strpos($this->format, '%color_start%') === false && strpos($this->format, '%color_end%') === false) {
			$updatedFormat = preg_replace(
				'/%level_name%/',
				'%color_start%%level_name%%color_end%',
				$this->format,
				$colorMode
			);

			if (! is_null($updatedFormat)) {
				$this->format = $updatedFormat;
			}
		}

		if (! is_null($colorScheme)) {
			$this->colorScheme = $colorScheme;
		}
	}

	/**
	 * Formats a log record, with color.
	 *
	 * @param mixed[] $record A log record to format.
	 *
	 * @return string The formatted and colored record
	 */
	public function format(array $record): string {
		$formatted = parent::format($record);
		$formatted = str_replace('%color_start%', $this->colorScheme[\KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Log\LogLevel::toPsrLogLevel($record['level'])], $formatted);

		return str_replace('%color_end%', self::RESET, $formatted);
	}
}
