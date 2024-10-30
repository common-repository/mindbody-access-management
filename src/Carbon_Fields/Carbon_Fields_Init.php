<?php
/**
 * The core plugin class.
 *
 * Define internationalization, admin-specific hooks,
 * and public-facing site hooks.
 *
 * @package MZMBOACCESS
 */

namespace MZoo\MzMboAccess\Carbon_Fields;

use MZoo\MzMboAccess as NS;
use MZoo\MzMindbody as MZ;
use Carbon_Fields;
use Carbon_Fields\Container;
use Carbon_Fields\Field;
use MZoo\MzMindbody\Sale;
use MZoo\MzMindbody\Site;

/**
 * Carbon Fields Extension Class.
 *
 * Hook in the Carbon Fields actions, filters, etc.
 *
 * @link  http://mzoo.org
 * @since 1.0.0
 *
 * @author Mike iLL/mZoo.org
 */
class Carbon_Fields_Init {

	/**
	 * Post Listings Array
	 *
	 * @since 2.1.1
	 * @access private
	 * @var array $posts_for_options get posts to use for redirect selection.
	 */
	private $posts_for_options;

	/**
	 * Load Carbon Fields
	 *
	 * @since 2.1.1
	 *
	 * Call the Carbon Fields boot method.
	 */
	public function crb_load() {
		Carbon_Fields\Carbon_Fields::boot();
	}

	/**
	 * Access Levels Page via Carbon Fields
	 *
	 * @since 2.1.1
	 * @return void
	 */
	public function access_levels_page() {

		Container\Container::make( 'theme_options', __( 'MBO Access Levels' ) )
			->add_fields(
				array(
					Field::make( 'complex', 'mbo_access_access_levels', __( 'Access Level' ) )
					->add_fields(
						'access_level',
						array(
							Field::make( 'text', 'access_level_name', __( 'Name' ) ),
							Field::make( 'multiselect', 'access_level_contracts', __( 'Mindbody Contracts' ) )
								->add_options( self::get_mbo_contracts() ),
							Field::make( 'multiselect', 'access_level_memberships', __( 'Mindbody Memberships' ) )
								->add_options( self::get_mbo_memberships() ),
							Field::make( 'multiselect', 'access_level_services', __( 'Mindbody Services' ) )
								->add_options( self::get_mbo_services() ),
							Field::make( 'select', 'access_level_redirect_post', __( 'Redirect Post' ) )
								->add_options( self::get_posts_for_redirects() )
								->set_help_text( __( 'Home page for this access level.', 'mz-mbo-access' ) )
								->set_default_value( 0 ),
						)
					)->set_help_text( __( 'Generate Access Levels by Mindbody Subscriptions, Memberships and/or Services.', 'mz-mbo-access' ) ),
				)
			);

	}

	/**
	 * Get Mindbody Contracts
	 *
	 * @since 2.1.1
	 *
	 * @return dictionary of MBO subscriptions by Id.
	 */
	public static function get_mbo_contracts() {
		$sale_object = new Sale\RetrieveSale();
		$contracts   = $sale_object->get_contracts( true );
		return is_array( $contracts ) ? $contracts : array();
	}

	/**
	 * Get Mindbody Memberships
	 *
	 * @since 2.1.1
	 *
	 * @return dictionary (Active) of MBO site memberships by MembershipId.
	 */
	public static function get_mbo_memberships() {
		$site_object = new Site\RetrieveSite();
		$memberships = $site_object->get_site_memberships( true );
		return is_array( $memberships ) ? $memberships : array();
	}

	/**
	 * Get Mindbody Memberships
	 *
	 * @since 2.1.1
	 *
	 * @return dictionary (Active) of MBO site memberships by MembershipId.
	 */
	public static function get_mbo_services() {
		$sale_object = new Sale\RetrieveSale();
		$services    = $sale_object->get_services( true );
		return is_array( $services ) ? $services : array();
	}

	/**
	 * Get Posts for Redirects
	 *
	 * Listing of WP Pages which can be selected for
	 * redirect for access level.
	 */
	private function get_posts_for_redirects() {
		if ( ! empty( $this->posts_for_options ) ) {
			return $this->posts_for_options; // Already did this.
		}
		// Make sure there's an empty entry for default selection.
		$this->posts_for_options = array( __( 'No Redirect', 'mz-mbo-access' ) );

		$posts = get_posts(
			array(
				'post_type'   => array( 'page', 'post' ),
				'numberposts' => -1,
			)
		);
		foreach ( $posts as $k => $post ) {
			$this->posts_for_options[ get_post_permalink( $post->ID ) ] = $post->post_title;
		}
		return $this->posts_for_options;
	}

}
