<?php
/**
 * Access and MBO v5 tab actions and methods to create the admin dashboard sections
 *
 * This file contains all the actions and functions to create the admin dashboard sections.
 * for the Access and MBO v5 tabs in MZ MBO Settings page.
 *
 * @since 1.1.0
 * @package MZMBOACCESS
 */

namespace MZoo\MzMboAccess\Backend;

use MZoo\MzMindbody as MZ;
use MZoo\MzMboAccess as NS;
use MZoo\MzMboAccess\Core as Core;
use MZoo\MzMboAccess\Common as Common;
use MZoo\MzMindbody\Libraries as Libraries;
use MZoo\MzMboAccess\Schedule as Schedule;

/**
 * Actions/Filters
 *
 * Related to all settings API.
 *
 * @since 1.0.0
 */
class SettingsPage {

	/**
	 * WPOSA Object
	 *
	 * @access protected
	 * @var $wposa_obj contains the methods which do all the work.
	 */
	protected static $wposa_obj;

	/**
	 * Construct
	 *
	 * Instanciate the WPOSA object.
	 */
	public function __construct() {
		self::$wposa_obj = MZ\Core\MzMindbodyApi::$settings_page::$wposa_obj;
	}

	/**
	 * Add Sections
	 *
	 * Add sections and fields.
	 */
    // @codingStandardsIgnoreStart
	public function addSections() {
    // @codingStandardsIgnoreEnd

		// Section: Basic Settings.
		self::$wposa_obj->add_section(
			array(
				'id'    => 'mz_mbo_access',
				'title' => __( 'MZ MBO Access Settings', 'mz-mindbody-api' ),
			)
		);

		// Section: Depreciated.
		self::$wposa_obj->add_section(
			array(
				'id'    => 'mz_mbo_v_five',
				'title' => __( 'API Version 5', 'mz-mindbody-api' ),
			)
		);

		// Field: Title.
		self::$wposa_obj->add_field(
			'mz_mbo_v_five',
			array(
				'id'      => 'credentials_test',
				'type'    => 'title',
				'name'    => '<h1>API V5 Credentials Test</h1>',
				'default' => '',
			)
		);

		// Field: Title.
		self::$wposa_obj->add_field(
			'mz_mbo_v_five',
			array(
				'id'   => 'api_5_description',
				'type' => 'html',
				'name' => 'Note',
				'desc' => 'New MBO Dev accounts will not have access to the MBO V5 API.',
			)
		);

		// Field: Textarea.
		self::$wposa_obj->add_field(
			'mz_mbo_v_five',
			array(
				'id'   => 'credentials_test',
				'type' => 'html',
				'name' => __( 'Debug Output', 'mz-mindbody-api' ),
				'desc' => $this->mz_mindbody_v5_debug_text(),
			)
		);

		self::$wposa_obj->add_field(
			'mz_mbo_access',
			array(
				'id'   => 'mbo_access_shortcodes',
				'type' => 'html',
				'name' => __( 'Shortcodes and Atts', 'mz-mindbody-api' ),
				'desc' => $this->access_codes(),
			)
		);

		// Field: Server Check HTML.
		self::$wposa_obj->add_field(
			'mz_mbo_access',
			array(
				'id'   => 'server_check',
				'type' => 'html',
				'name' => __( 'Server Check', 'mz-mindbody-api' ),
				'desc' => $this->server_check(),
			)
		);
	}


	/**
	 * Access Codes
	 *
	 * Explanatory strings for access settings fields.
	 */
	private function access_codes() {
		$return = '';
		/* translators: Basic shortcode example. */
		$return .= '<p>' . sprintf( '[%1$s] %2$s [%3$s]', 'mbo-client-access', __( 'Restricted content here between both tags', 'mz-mindbody-api' ), '/mbo-client-access' ) . '</p>';
		$return .= '<ul>';
		$return .= '<li><strong>denied_redirect</strong>: ' . __( "((url string) URL to redirect users to who are logged in but don't have access.", 'mz-mindbody-api' ) . '</li>';
		$return .= '<li><strong>user_login_redirect</strong>: ' . __( '((bool) Set to 1 to use as redirect login to take user to an Access Level designated page.', 'mz-mindbody-api' ) . '</li>';
		$return .= '<li><strong>form_heading</strong>: ' . sprintf(
			/* translators: Notify of default message. */
			__( '(string) Default: %2$s%1$s%3$s', 'mz-mindbody-api' ),
			__( 'Login with your Mindbody account to access this content.', 'mz-mbo-access' ),
			'&quot;',
			'&quot;'
		) . '</li>';
		$return .= '<li><strong>password_reset_request</strong>: ' . __( '(string) Password Reset button text. Blank string to remove button.', 'mz-mindbody-api' ) . '</li>';
		$return .= '<li><strong>manage_on_mbo</strong>: ' . __( '(string) Link to MBO Site button text. Blank string to remove button.', 'mz-mindbody-api' ) . '</li>';
		$return .= '<li><strong>denied_message</strong>: ' . __( '(string) Message preceding list of items required for access.', 'mz-mindbody-api' ) . '</li>';
		$return .= '<li><strong>access_expired</strong>: ' . __( '(string) Message alerting client that access has expired.', 'mz-mindbody-api' ) . '</li>';
		/* translators: Explain how to use levels, which are 1, 2 or 1 and 2. */
		$return .= '<li><strong>access_levels</strong>: ' . sprintf( __( '(int/list) (Default %1$d) Levels of access required to access content %1$d, %2$d or %3$s', 'mz-mindbody-api' ), 1, 2, '"1, 4, 7"' ) . '</li>';
		$return .= '</ul>';
		/* translators: This is an example of a working shortcode with access denied message. */
		$return .= sprintf( '[%1$s %2$s]%3$s[%4$s]', 'mbo-client-access', 'access_levels="1,2,4" denied_message="Not so fast, bub."', 'Restricted Content', '/mbo-client-access' );
		$return .= '<h3>' . __( 'Note', 'mz-mindbody-api' ) . '</h3>';
		$return .= sprintf(
			/* translators: Explain how to use and set redirect URLs. */
			__(
				'If %1$s is set to %2$s, 
                                user will be redirected to the page configured for the FIRST access level included in the %3$s shortcode attribute.',
				'mz-mindbody-api'
			),
			'<code>user_login_redirect</code>',
			'<code>1</code>',
			'<code>access_levels</code>'
		);
		return $return;
	}

	/**
	 * Server Check
	 *
	 * Unreliable checks for presence of PEAR/SOAP.
	 */
	private function server_check() {

		$return          = '';
		$mz_requirements = 0;

		if ( extension_loaded( 'soap' ) ) {
			$return .= __( 'SOAP installed! ', 'mz-mindbody-api' );
		} else {
			$return         .= __( 'SOAP is not installed. ', 'mz-mindbody-api' );
			$mz_requirements = 1;
		}
		$return .= '&nbsp;';

		if ( 1 === $mz_requirements ) {
			$return .= '<div class="settings-error notice notice-warning is-dismissible" style="padding:1.5em;"><p>';
			$return .= __(
				'MZ Mindbody API requires SOAP. 
                        Please contact your hosting provider or 
                        enable via your CPANEL of php.ini file.',
				'mz-mindbody-api'
			);
			$return .= '</p></div>';
		} else {
			$return .= '<div class="" ><p>';
			$return .= __(
				'Congratulations. Your server appears to be configured to integrate with mindbodyonline.',
				'mz-mindbody-api'
			);
			$return .= '</p></div>';
		}
		return $return;
	}

	/**
	 * MZ Mindbody v5 Debug Text
	 *
	 * @return html string
	 */
	private function mz_mindbody_v5_debug_text() {
		return '<a href="#" class="button" id="mzTestCredentialsV5">' . __( 'Test Credentials', 'mz-mindbody-api' ) . '</a><div id="displayTestV5"></div>';
	}
}
