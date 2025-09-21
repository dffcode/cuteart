<?php
/**
 * @license GPL-2.0-only
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\ImageDownloader\Sanitization\Sanitizers;

use RuntimeException;
use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\ImageDownloader\Sanitization\Contracts\Sanitizer;
use KadenceWP\KadenceStarterTemplates\Symfony\Component\String\Slugger\SluggerInterface;

/**
 * The default filename sanitizer.
 */
final class DefaultFileNameSanitizer implements Sanitizer
{
	/**
	 * @readonly
	 *
	 * @var \KadenceWP\KadenceStarterTemplates\Symfony\Component\String\Slugger\SluggerInterface
	 */
	private $slugger;
	public function __construct(SluggerInterface $slugger) {
		$this->slugger = $slugger;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws RuntimeException
	 */
	public function __invoke(string $filename): string {
		// Dot files are renamed regardless of security settings.
		$filename = trim($filename, '.');

		// Remove any null bytes. See
		// http://php.net/manual/security.filesystem.nullbytes.php
		$filename = str_replace(chr(0), '', $filename);

		$ext = pathinfo($filename, PATHINFO_EXTENSION);

		if (empty($ext)) {
			throw new RuntimeException('Cannot sanitize dot files');
		}

		$original = pathinfo($filename, PATHINFO_FILENAME);

		return $this->slugger->slug($original)->lower() . ".$ext";
	}
}
