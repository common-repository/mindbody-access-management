<?php
/**
 * Activator class
 *
 * Methods to run on plugin activation.
 *
 * @package MZMBOACCESS
 */

namespace MZoo\MzMboAccess\Core;

use MZoo\MzMindbody\Common;

/**
 * Fired during plugin activation
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link  http://mzoo.org
 * @since 1.0.0
 *
 * @author Mike iLL/mZoo.org
 **/
class Activator {
	/**
	 * Check php version and that MZMBO is active.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {

		// Get token right now.
		$token_object = new Common\TokenManagement();
		$token_object->serve_token();

	}
}
