<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare( strict_types=1 );

namespace KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\Notice;

use KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\Contracts\Abstract_Provider;

final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton( Notice_Controller::class, Notice_Controller::class );
		$this->container->singleton( Notice_Handler::class, static function ( $c ): Notice_Handler {
			return new Notice_Handler( $c->get( Notice_Controller::class ) );
		} );

		add_action( 'admin_notices', [ $this->container->get( Notice_Handler::class ), 'display' ], 12, 0 );
	}

}
