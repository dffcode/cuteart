<?php declare( strict_types=1 );

namespace KadenceWP\KadenceStarterTemplates\Shutdown;

use KadenceWP\KadenceStarterTemplates\Cache\Ai_Cache;
use KadenceWP\KadenceStarterTemplates\Cache\Block_Library_Cache;
use KadenceWP\KadenceStarterTemplates\Image_Downloader\Cache_Primer;
use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Container\Contracts\Provider;

final class Shutdown_Provider extends Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->when( Shutdown_Collection::class )
		                ->needs( '$tasks' )
		                ->give( static function ( $c ): array {
			                // Add any terminable tasks to the collection to run on shutdown.
			                // Important: these will run in the order provided!
			                return [
				                $c->get( Cache_Primer::class ),
				                $c->get( Block_Library_Cache::class ),
				                $c->get( Ai_Cache::class ),
			                ];
		                } );

		add_action( 'shutdown', $this->container->callback( Shutdown_Handler::class, 'handle' ), 1 );
	}

}
