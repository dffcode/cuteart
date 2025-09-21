<?php
/**
 * @license GPL-2.0-only
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\ImageDownloader\Sanitization;

use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\ImageDownloader\Sanitization\Contracts\Sanitizer;

/**
 * Sanitizes a filename using a callable/Closure strategy.
 */
final class FileNameSanitizer
{
	/**
	 * @var Sanitizer
	 *
	 * @readonly
	 */
	private $sanitizer;
	/**
	 * @param Sanitizer $sanitizer The filename sanitizer Closure strategy.
	 */
	public function __construct(Sanitizer $sanitizer) {
		$this->sanitizer = $sanitizer;
	}

	/**
	 * Sanitize a filename.
	 */
	public function sanitize(string $filename): string {
		return ($this->sanitizer)($filename);
	}
}
