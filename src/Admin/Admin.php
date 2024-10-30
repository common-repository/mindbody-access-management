<?php
/**
 * Admin Class
 *
 * For some admin functionality. Backend contains the settings page.
 *
 * @package MZMBOACCESS
 */

namespace MZoo\MzMboAccess\Admin;

use MZoo\MzMboAccess as NS;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link  http://mzoo.org
 * @since 1.0.0
 *
 * @author Mike iLL/mZoo.org
 */
class Admin {

	/**
	 * Notify Admin when plugin deactivated.
	 *
	 * TODO: abstract, maybe
	 *
	 * @since 2.5.7
	 */
	public function admin_notice() { ?>
	<div class="error">
		<p>
			<?php
			esc_html_e(
				'Missing required plugin: MZ Mindbody API.',
				'mz-mbo-access'
			);
			?>
		</p>
	</div>
		<?php
	}
}
