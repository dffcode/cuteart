<?php
/**
 * @license GPL-2.0-only
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\ImageDownloader\Models;

/**
 * Represents the results of a downloaded image.
 */
final class DownloadedImage
{
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var int
	 */
	public $width;
	/**
	 * @var int
	 */
	public $height;
	/**
	 * @var string
	 */
	public $file;
	/**
	 * @var string
	 */
	public $size;
	/**
	 * @var string
	 */
	public $alt;
	/**
	 * @var string
	 */
	public $url;
	/**
	 * @var string
	 */
	public $photographer;
	/**
	 * @var string
	 */
	public $photographer_url;
	/**
	 * @param int    $id               The unique Pexels ID.
	 * @param int    $width            The image's original max width.
	 * @param int    $height           The image's original max height.
	 * @param string $file             The server path to the file.
	 * @param string $size             The WordPress size name, e.g. thumbnail.
	 * @param string $alt              The alt description for the image.
	 * @param string $url              The Pexels attachment URL.
	 * @param string $photographer     The photographer's name.
	 * @param string $photographer_url The photographer's Pexels URL.
	 */
	public function __construct(int $id, int $width, int $height, string $file, string $size, string $alt, string $url, string $photographer, string $photographer_url) {
		$this->id               = $id;
		$this->width            = $width;
		$this->height           = $height;
		$this->file             = $file;
		$this->size             = $size;
		$this->alt              = $alt;
		$this->url              = $url;
		$this->photographer     = $photographer;
		$this->photographer_url = $photographer_url;
	}

	/**
	 * @return array{id: int, width: int, height: int, file: string, size: string, alt: string, url: string, photographer: string, photographer_url: string}
	 */
	public function toArray(): array {
		return [
			'id'               => $this->id,
			'width'            => $this->width,
			'height'           => $this->height,
			'file'             => $this->file,
			'size'             => $this->size,
			'alt'              => $this->alt,
			'url'              => $this->url,
			'photographer'     => $this->photographer,
			'photographer_url' => $this->photographer_url,
		];
	}
}
