<?php
/**
 * @license GPL-2.0-only
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\ImageDownloader;

use InvalidArgumentException;
use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\ImageDownloader\Exceptions\ImageDownloadException;
use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\ImageDownloader\Models\ResponseAdapter;

final class FileNameProcessor
{
	/**
	 * @var mixed[]
	 */
	private $allowed_extensions;

	public const SCALED_SIZE = 'scaled';

	public function __construct(
		array $allowed_extensions
	) {
		/** @var array<string, bool> */
		$this->allowed_extensions = $allowed_extensions;
		$this->allowed_extensions = array_change_key_case($this->allowed_extensions);
	}

	/**
	 * Construct an image file name from a Prophecy Image Service URI.
	 *
	 * @param string $src       The fully qualified URI to the image.
	 * @param string $extension The file extension/image type.
	 * @param string $name      An optional name for the file, without extension. Original src will be used otherwise.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function build_image_file_name(string $src, string $extension, string $name = ''): string {
		if (! $this->is_valid_image_extension($extension)) {
			throw new InvalidArgumentException(sprintf('Invalid file extension: %s', $extension));
		}

		$filename = $name ?: pathinfo(rawurldecode($src), PATHINFO_FILENAME);

		return sprintf('%s.%s', trim($filename), strtolower($extension));
	}

	/**
	 * Format the file name to save it as WordPress would.
	 *
	 * @param string $file_path The full server path to the saved image file.
	 *
	 * @throws ImageDownloadException
	 */
	public function format_file_path_for_wordpress(string $file_path, ResponseAdapter $response): string {
		$info    = pathinfo($file_path);
		$dirname = $info['dirname'] ?? '';
		$ext     = $info['extension'] ?? '';

		if (! $dirname || ! $ext) {
			throw new ImageDownloadException('Missing dirname or extension');
		}

		// WordPress scaled images do not have width/height in their names.
		if ($response->size === self::SCALED_SIZE) {
			return str_replace(".$ext", sprintf('-%s.%s', self::SCALED_SIZE, $ext), "$dirname/$response->filename");
		}

		// @phpstan-ignore-next-line
		[ $width, $height ] = getimagesize($file_path);

		return str_replace(".$ext", "-{$width}x$height.$ext", "$dirname/$response->filename");
	}

	/**
	 * Check if the file extension is in the allowed types.
	 */
	private function is_valid_image_extension(string $extension): bool {
		return  $this->allowed_extensions[strtolower($extension)] ?? false;
	}
}
