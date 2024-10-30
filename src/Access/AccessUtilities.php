<?php
/**
 * Access Utilities
 *
 * Class that retrieves client to expose access ulities.
 *
 * @package MZMBOACCESS
 */

namespace MZoo\MzMboAccess\Access;

use MZoo\MzMboAccess as NS;
use MZoo\MzMindbody as MZ;
use MZoo\MzMboAccess\Core;
use MZoo\MzMboAccess\Client;
use MZoo\MzMindbody\Common;
use MZoo\MzMindbody\Common\Interfaces;

/**
 * Access Utilities Class
 *
 * Class that extends MZ MBO retrieve Client class to expose access ulities.
 */
class AccessUtilities extends Client\RetrieveClient {

	/**
	 * Access Levels
	 *
	 * @since 1.0.5
	 *
	 * @var array $access_level integers indicating which levels client has access to.
	 */
	public $client_access_levels = array();

	/**
	 * Contract IDs
	 *
	 * @since 1.0.5
	 *
	 * @access private
	 * @var array $client_contract_ids integers indicating valid MBO contracts client has.
	 */
	private $client_contract_ids = array();

	/**
	 * Service IDs
	 *
	 * @since 1.0.5
	 *
	 * @access private
	 * @var array $client_service_ids integers indicating valid MBO services client has.
	 */
	private $client_service_ids = array();

	/**
	 * Membership IDs
	 *
	 * @since 1.0.5
	 *
	 * @access private
	 * @var array $membership_ids integers indicating valid MBO memberships client has.
	 */
	private $client_membership_ids = array();

	/**
	 * Purchase IDs
	 *
	 * @since 1.0.5
	 *
	 * @access private
	 * @var array $membership_ids integers indicating valid MBO memberships client has.
	 */
	private $client_purchase_ids = array();

	/**
	 * Mindbody Access Levels
	 *
	 * @since 1.0.5
	 *
	 * @access public
	 * @var array $mindbody_access_levels mbo_access_access_levels option array.
	 */
	public $mindbody_access_levels = array();

	/**
	 * Check Access Permissions
	 *
	 * @since 1.0.0
	 *
	 * @param int $client_id from MBO.
	 * @return array of levels from mbo_access_access_levels option to which client has access.
	 */
	public function check_access_permissions( $client_id ) {

		// If client isn't logged in, return empty array.
		$client_object = new Client\RetrieveClient;
		$logged_in     = $client_object->check_client_logged();
		if ( false === $logged_in ) {
			return array();
		}

		try {
			return $this->set_client_access_level( $client_id );
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}

	}

	/**
	 * Compare Client Service Status
	 *
	 * @since 2.5.8
	 *
	 * return true if active membership matches one in received array (or string)
	 *
	 * @param int $client_id from MBO.
	 *
	 * @return bool
	 */
	public function set_client_access_level( $client_id ) {

		$this->mindbody_access_levels = carbon_get_theme_option( 'mbo_access_access_levels' );

		/*
		 * (
		 *     [_type] => access_level
		 *     [access_level_name] => Monthly Unlimited, 10 and 5 Class Cards
		 *     [access_level_subscriptions] => Array
		 *         (
		 *         )
		 *
		 *     [access_level_memberships] => Array
		 *         (
		 *         )
		 *
		 *     [access_level_services] => Array
		 *         (
		 *             [0] => 123456789
		 *             [1] => 1364
		 *             [2] => 1300
		 *         )
		 *
		 * )
		 */
		try {
			$this->client_contract_ids   = $this->get_client_contract_ids( $client_id );
			$this->client_membership_ids = $this->get_client_active_membership_ids( $client_id );
			$this->client_service_ids    = $this->get_client_valid_service_ids( $client_id );
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}

		// Populate client access levels with levels client has access to.
		foreach ( $this->mindbody_access_levels as $k => $level ) {
			if ( true === $this->check_client_access_to_level( $client_id, $level ) ) {
				$this->client_access_levels[] = $k + 1;
			}
		}

		$this->update_client_session(
			array(
				'access_levels'  => $this->client_access_levels,
				'contract_ids'   => $this->client_contract_ids,
				'service_ids'    => $this->client_service_ids,
				'membership_ids' => $this->client_membership_ids,
			)
		);

		return $this->client_access_levels;
	}

	/**
	 * Is Service Valid
	 *
	 * @since  1.0.0
	 * @param  array $service as returned from mbo.
	 * @return bool true if there are remaining and date not expired.
	 */
	private function is_service_valid( $service ) {

		if ( $service['Remaining'] < 1 ) {
			return false;
		}

		$service_expiration = new \DateTime(
			$service['ExpirationDate'],
			wp_timezone()
		);

		$now = new \DateTimeImmutable( 'now', wp_timezone() );

		if ( $service_expiration->format( 'Y-m-d\TH:i:s.v' ) < $now->format( 'Y-m-d\TH:i:s.v' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Compare Client Contract Status
	 *
	 * @since 1.0.0
	 *
	 * DEPRECIATED: Not in use @since 2.1.1 (maybe earlier).
	 *
	 * @param string|array $contract_types from MBO.
	 *
	 * @return false|int based on client access level.
	 */
	public function compare_client_contract_status( $contract_types = array() ) {

		$contract_types = is_array( $contract_types ) ? $contract_types : array( $contract_types );

		$contracts = $this->get_client_contracts();

		if ( false === (bool) $contracts[0]['ContractName'] ) {
			return 0;
		}

		foreach ( $contracts as $contract ) {
			if ( in_array( $contract['ContractName'], $contract_types, true ) ) {
				return 2;
			}
		}

		return 0;
	}

	/**
	 * Get Client Valid Membership IDs
	 *
	 * @since 2.1.1
	 * @param int $client_id MBO client Id.
	 * @return array of IDs of memberships client has.
	 */
	private function get_client_active_membership_ids( $client_id ) {
		$memberships    = $this->get_client_active_memberships( $client_id );
		$membership_ids = array();
		foreach ( $memberships as $k => $v ) {
			$membership_ids[] = $v['MembershipId'];
		}
		return $membership_ids;
	}

	/**
	 * Get Client Contract IDs
	 *
	 * @since 2.1.1
	 * @param int $client_id MBO client Id.
	 * @return array of IDs of contracts client has.
	 */
	private function get_client_contract_ids( $client_id ) {
		$contracts    = $this->get_client_contracts( $client_id );
		$contract_ids = array();
		foreach ( $contracts as $k => $v ) {
			$contract_ids[] = $v['Id'];
		}
		return $contract_ids;
	}

	/**
	 * Get Client Service IDs
	 *
	 * @since 2.1.1
	 * @param int $client_id MBO client Id.
	 * @return array of IDs of services client has.
	 */
	private function get_client_valid_service_ids( $client_id ) {
		$services    = $this->get_client_services( $client_id );
		$service_ids = array();
		foreach ( $services as $k => $service ) {
			if ( true === $this->is_service_valid( $service ) ) {
				$service_ids[] = $service['ProductId'];
			}
		}
		return $service_ids;
	}

	/**
	 * Get Client Purchase IDs
	 *
	 * @since 2.1.1
	 * @access protected
	 * @param int $client_id MBO client Id.
	 * @return array of IDs of services client has.
	 */
	protected function get_client_purchase_ids( $client_id ) {
		$purchases    = $this->get_client_purchases( $client_id );
		$purchase_ids = array();
		foreach ( $purchases as $k => $purchase ) {
			foreach ( $purchase['Sale']['PurchasedItems'] as $k => $item ) {
				$purchase_ids[] = $item['BarcodeId'];
			}
		}
		return $purchase_ids;
	}

	/**
	 * Check Client Access to Level
	 *
	 * Return true if client has access to any of the subscriptions in this level.
	 *
	 * @since 2.1.1
	 *
	 * @param int $client_id MBO client Id.
	 * @param int $level level index plus one from mbo_access_access_levels:
	 *
	 *    [_type] => access_level
	 *    [access_level_name] => Corporate Member
	 *    [access_level_contracts] => Array
	 *        (
	 *        )
	 *
	 *  [access_level_memberships] => Array
	 *      (
	 *          [0] => 30
	 *      )
	 *
	 *  [access_level_services] => Array
	 *      (
	 *      )
	 *
	 *   [access_level_redirect_post] => http://project.test/?post_type=post&p=20030.
	 *
	 * @return bool has or does not have access to level.
	 */
	private function check_client_access_to_level( $client_id, $level ) {
		foreach ( $this->client_contract_ids as $k => $v ) {
			if ( in_array( $v, $level['access_level_contracts'], true ) ) {
				return true;
			}
		}
		foreach ( $this->client_membership_ids as $k => $v ) {
			if ( in_array( $v, $level['access_level_memberships'], true ) ) {
				return true;
			}
		}
		foreach ( $this->client_service_ids as $k => $v ) {
			if ( in_array( $v, $level['access_level_services'], true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Compare Client Purchase Status
	 *
	 * @since 2.1.1
	 *
	 * return true if purchased items matches one in received array (or string).
	 *
	 * @param int          $client_id from MBO.
	 * @param string|array $purchase_types  of purchased items.
	 *
	 * @return bool
	 */
	public function compare_client_purchase_status( $client_id, $purchase_types = array() ) {

		$purchase_types = is_array( $purchase_types ) ? $purchase_types : array( $purchase_types );

		$purchases = $this->get_client_purchases( $client_id );

		if ( false === (bool) $purchases[0]['Sale'] ) {
			return 0;
		}

		foreach ( $purchases as $purchase ) {
			if ( in_array( $purchase['Description'], $purchase_types, true ) ) {
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Get Client Access Level
	 *
	 * @since 2.1.1
	 *
	 * @return int indicating access level of currently logged in client.
	 */
	public function get_client_access_levels() {

		return $this->access_levels;
	}
}
