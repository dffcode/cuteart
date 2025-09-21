<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare( strict_types=1 );

namespace KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\Auth;

use KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\Config;

/**
 * Determines if the current site will allow the user to use the authorize button.
 */
final class Authorizer {

	/**
	 * Checks if the current user can perform an action.
	 *
	 * @throws \RuntimeException
	 *
	 * @return bool
	 */
	public function can_auth(): bool {
		return (bool) apply_filters(
			'stellarwp/uplink/' . Config::get_hook_prefix() . '/auth/user_check',
			is_super_admin()
		);
	}

}
