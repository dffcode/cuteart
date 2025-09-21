<?php declare( strict_types=1 );

namespace KadenceWP\KadenceStarterTemplates\Shutdown\Contracts;

use KadenceWP\KadenceStarterTemplates\Shutdown\Shutdown_Provider;

/**
 * Any class that utilizes the Shutdown Handler to perform
 * a task should implement this contract and be added to
 * the collection.
 *
 * @see Shutdown_Provider::register()
 */
interface Terminable {

	/**
	 * Perform a task on shutdown.
	 *
	 * @action shutdown
	 *
	 * @return void
	 */
	public function terminate(): void;

}
