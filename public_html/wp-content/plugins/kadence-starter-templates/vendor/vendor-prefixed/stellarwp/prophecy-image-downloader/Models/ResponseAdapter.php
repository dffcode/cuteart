<?php
/**
 * @license GPL-2.0-only
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\ImageDownloader\Models;

use KadenceWP\KadenceStarterTemplates\Symfony\Contracts\HttpClient\ResponseInterface;

final class ResponseAdapter
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
	public $filename;
	/**
	 * @var string
	 */
	public $size;
	/**
	 * @var int
	 */
	public $key;
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
	 * @var \KadenceWP\KadenceStarterTemplates\Symfony\Contracts\HttpClient\ResponseInterface
	 */
	public $response;
	/**
	 * @param int    $id               The unique Pexels ID.
	 * @param int    $width            The image's original max width.
	 * @param int    $height           The image's original max height.
	 * @param string $filename         The file name with extension.
	 * @param string $size             The WordPress size name, e.g. thumbnail.
	 * @param int    $key              The index associated with the collection as we loop through it.
	 * @param string $alt              The alt description for the image.
	 * @param string $url              The Pexels attachment URL.
	 * @param string $photographer     The photographer's name.
	 * @param string $photographer_url The photographer's Pexels URL.
	 */
	public function __construct(int $id, int $width, int $height, string $filename, string $size, int $key, string $alt, string $url, string $photographer, string $photographer_url, ResponseInterface $response) {
		$this->id               = $id;
		$this->width            = $width;
		$this->height           = $height;
		$this->filename         = $filename;
		$this->size             = $size;
		$this->key              = $key;
		$this->alt              = $alt;
		$this->url              = $url;
		$this->photographer     = $photographer;
		$this->photographer_url = $photographer_url;
		$this->response         = $response;
	}
}
