<?php
/**
 * @license GPL-2.0-only
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\ImageDownloader;

use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Container\Contracts\Provider;
use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\ImageDownloader\Sanitization\Contracts\Sanitizer;
use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\ImageDownloader\Sanitization\Sanitizers\DefaultFileNameSanitizer;
use KadenceWP\KadenceStarterTemplates\Symfony\Component\HttpClient\HttpClient;
use KadenceWP\KadenceStarterTemplates\Symfony\Component\String\Slugger\AsciiSlugger;
use KadenceWP\KadenceStarterTemplates\Symfony\Component\String\Slugger\SluggerInterface;
use KadenceWP\KadenceStarterTemplates\Symfony\Contracts\HttpClient\HttpClientInterface;

final class ImageProvider extends Provider
{
	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$this->container->bind(SluggerInterface::class, AsciiSlugger::class);
		$this->container->bind(Sanitizer::class, DefaultFileNameSanitizer::class);

		$this->container->when(FileNameProcessor::class)
						->needs('$allowed_extensions')
						->give([
							'jpg'  => true,
							'jpeg' => true,
							'webp' => true,
							'png'  => true,
						]);

		$this->container->when(ImageDownloader::class)
						->needs(HttpClientInterface::class)
						->give(HttpClient::create());

		$this->container->when(ImageDownloader::class)
						->needs('$batch_size')
						->give((int) $this->config->get('image_batch_size'));
	}
}
