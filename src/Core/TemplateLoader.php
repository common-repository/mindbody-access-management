<?php
/**
 * Template Loader
 *
 * Extend Gamajo Template Loader
 *
 * @package MZMBOACCESS
 */

namespace MZoo\MzMboAccess\Core;

use MZoo\MzMindbody\Libraries as Libraries;
use MZoo\MzMboAccess as NS;

/**
 * Template Loader
 *
 * Extend Gamajo Template Loader
 */
class TemplateLoader extends \MZMBO_Gamajo_Template_Loader {

	/**
	 * Prefix for filter names.
	 *
	 * @since 1.0.1
	 *
	 * @var string
	 */
	protected $filter_prefix = 'mz_mbo_access';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * For example: 'your-plugin-templates'.
	 *
	 * @since 1.0.1
	 *
	 * @var string
	 */
	protected $theme_template_directory = 'templates/mindbody/access';

	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * @since 1.0.1
	 *
	 * @var string
	 */
	protected $plugin_directory = NS\PLUGIN_NAME_DIR;

	/**
	 * Directory name where templates are found in this plugin.
	 *
	 * Can either be a defined constant, or a relative reference from where the subclass lives.
	 *
	 * e.g. 'templates' or 'includes/templates', etc.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $plugin_template_directory = 'src/Frontend/views';
}
