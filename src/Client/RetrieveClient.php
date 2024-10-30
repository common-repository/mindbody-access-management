<?php
/**
 * Retrieve Client
 *
 * @package MZMBOACCESS
 */

namespace MZoo\MzMboAccess\Client;

use MZoo\MzMindbody as MZ;
use MZoo\MzMboAccess as NS;
use MZoo\MzMboAccess\Session as Session;
use MZoo\MzMindbody\Core as Core;
use MZoo\MzMindbody\Common as Common;
use MZoo\MzMindbody\Libraries as Libraries;
use MZoo\MzMindbody\Schedule as Schedule;
use MZoo\MzMindbody\Common\Interfaces as Interfaces;
use EAMann\Sessionz as Sessionz;

/**
 * Class that holds Client Interface Methods
 */
class RetrieveClient extends Interfaces\Retrieve {

	/**
	 * The Mindbody API Object
	 *
	 * @access private
	 * @var    object $mb with interface methods.
	 */
	private $mb;

	/**
	 * MBO Client
	 *
	 * GetClient result from MBO
	 *
	 * @access private
	 * @var    array $mbo_client as returned from MBO.
	 */
	private $mbo_client;

	/**
	 * Client Services
	 *
	 * Services returned from MBO
	 *
	 * @access private
	 * @var    array $services as returned from MBO.
	 */
	private $services;

	/**
	 * Format for date display, specific to MBO API Plugin.
	 *
	 * @since  1.0.1
	 * @access public
	 * @var    string $date_format WP date format option.
	 */
	public $date_format;

	/**
	 * Format for time display, specific to MBO API Plugin.
	 *
	 * @since  1.0.1
	 * @access public
	 * @var    string $time_format
	 */
	public $time_format;

	/**
	 * Instance of our $_Session.
	 *
	 * @since  1.0.1
	 * @access public
	 * @var    object $session
	 */
	public $session;

	/**
	 * Class constructor
	 *
	 * @since 1.0.1
	 */
	public function __construct() {
		$this->date_format = Core\MzMindbodyApi::$date_format;
		$this->time_format = Core\MzMindbodyApi::$time_format;
		$this->session     = Session\MzAccessSession::instance();
	}

	/**
	 * Client Login – using API VERSION 5!
	 *
	 * @since 1.0.1
	 *
	 * @param array $credentials with username and password.
	 * @param array $additional_details additional endpoints to populate from.
	 *
	 * @return array - result type and message.
	 */
	public function log_client_in( $credentials = array(
		'username' => '',
		'password' => '',
	), $additional_details = array() ) {

		$valid_credentials = $this->validate_login_fields( $this->sanitize_login_fields( $credentials ) );

		if ( 2 === $valid_credentials ) {
			return array(
				'type'    => 'error',
				'message' => __( 'Badly formed email.', 'mz-mbo-access' ),
			);
		} elseif ( 3 === $valid_credentials ) {
			return array(
				'type'    => 'error',
				'message' => __(
					'All Mindbody passwords must 
                                contain 8 to 15 characters and 
                                must include both letters and 
                                numbers.',
					'mz-mbo-access'
				),
			);
		}

		$validate_login = $this->validate_client( $valid_credentials );

		if ( ! is_array( $validate_login ) || ! array_key_exists( 'ValidateLoginResult', $validate_login ) ) {

			if ( is_array( $validate_login ) ) {
				return array(
					'type'    => 'error',
					'message' => implode( ', ', $validate_login ),
				);
			}
			return array(
				'type'    => 'error',
				'message' => $validate_login,
			);
		}

		if ( ! empty( $validate_login['ValidateLoginResult']['GUID'] ) ) {
			$client_info = $validate_login['ValidateLoginResult']['Client'];

			if ( ! empty( $additional_details ) ) {
				foreach ( $additional_details as $endpoint ) {
					switch ( $endpoint ) {
						case 'get_clients':
							$additional = $this->get_clients( array( $client_info['ID'] ) )[0];
							if ( ! is_array( $additional ) ) {
								break;
							}
							$client_info = array_merge( $additional, $client_info );
							break;
						case 'get_client_purchases':
							$additional = $this->get_client_purchases( $client_info['ID'] );
							if ( ! is_array( $additional ) ) {
								break;
							}
							$client_info = array_merge(
								array(
									'purchases' => $additional,
								),
								$client_info
							);
							break;
					}
				}
			}

			if ( $this->create_client_session( $client_info ) ) {
				return array(
					'type'           => 'success',
					'message'        => __(
						'Welcome',
						'mz-mbo-access'
					) . ', ' . $client_info['FirstName'],
					'client_id'      => $client_info['ID'],
					'client_details' => $client_info,
				);
			}
			return array(
				'type'    => 'error',
				'message' => sprintf(
					/* translators: alert referencing the user. */
					__( 'Whoops. Please try again, %1$s.', 'mz-mbo-access' ),
					$validate_login['ValidateLoginResult']['Client']['FirstName']
				),
			);
		} else {
			// Otherwise error message.
			if ( ! empty( $validate_login['ValidateLoginResult']['Message'] ) ) {
				return array(
					'type'    => 'error',
					'message' => $validate_login['ValidateLoginResult']['Message'],
				);
			} else {
				// Default fallback message.
				return array(
					'type'    => 'error',
					'message' => __( 'Invalid Login', 'mz-mbo-access' ),
				);
			}
		}
	}




	/**
	 * Validate Client - API VERSION 5!
	 *
	 * @since 1.0.1
	 *
	 * @param array $validate_login_result with result from MBO API.
	 */
	public function validate_client( $validate_login_result ) {

		// Create the MBO Object using API VERSION 5!

		$this->get_mbo_results( 5 );

		try {
			$result = $this->mb->ValidateLogin(
				array(
					'Username' => $validate_login_result['Username'],
					'Password' => $validate_login_result['Password'],
				)
			);
		} catch ( \Exception $e ) {
			return 'V5 API Error: ' . $e->getMessage();
		}

		return $result;
	}


	/**
	 * Get Client
	 *
	 * @since 2.0.6
	 * Get @array of MBO Client IDs
	 *
	 * @param int $client_id for MBO access. for MBO.
	 * @return array _single_ (first) Client from Mindbody.
	 */
	public function get_client( $client_id ) {

		$this->get_mbo_results();

		$result = $this->mb->GetClients(
			array(
				'ClientIds' => array( $client_id ),
			)
		);

		return $result['Clients'][0];
	}


	/**
	 * Create Client Session
	 *
	 * @since 1.0.0
	 *
	 * Sanitize array returned from MBO and save in $_SESSION under mbo_result key.
	 *
	 * @param array $client_info from MBO.
	 */
	public function create_client_session( $client_info ) {

		if ( ! empty( $client_info['ID'] ) ) {
			$sanitized_client_info = MZ\MZMBO()->helpers->array_map_recursive(
				'sanitize_text_field',
				$client_info
			);

			$client_info_with_access = array_merge(
				array( 'access_level' => 0 ),
				$sanitized_client_info
			);

			// If validated, create session variables and store.
			$client_details = array(
				'mbo_result' => $sanitized_client_info,
			);

			$this->session->set( 'MBO_Client', $client_details );

			return $this->session->get( 'MBO_Client' );
		}
	}


	/**
	 * Update Client Session
	 *
	 * @since 2.0.5
	 *
	 * @param array $additional_info with MBO client details to add to Session.
	 */
	public function update_client_session( $additional_info ) {

		$previous_session = (array) $this->session->get( 'MBO_Client' )->mbo_result;

		if ( ! empty( $previous_session['ID'] ) ) {
			$sanitized_additional_info = MZ\MZMBO()->helpers->array_map_recursive(
				'sanitize_text_field',
				$additional_info
			);

			$new_session = array_merge( $previous_session, $sanitized_additional_info );

			// If validated, create session variables and store.
			$client_details = array(
				'mbo_result' => $new_session,
			);

			$this->session->set( 'MBO_Client', $client_details );

			return $new_session;
		}
	}

	/**
	 * Client Log Out
	 */
	public function client_logout() {

		$this->session->set( 'MBO_Client', array() );
		setcookie( 'PHPSESSID', false );

		return true;
	}

	/**
	 * Return MBO Account config required fields with what I think
	 * are default required fields.
	 *
	 * @since: 1.0.1
	 *
	 * @return array numeric array of required fields.
	 */
	public function get_signup_form_fields() {

		// Crate the MBO Object.
		$this->get_mbo_results();

		$required_fields = $this->mb->GetRequiredClientFields();

		$default_required_fields = array(
			'Email',
			'FirstName',
			'LastName',
		);

		return array_merge(
			$default_required_fields,
			array_map(
				'sanitize_text_field',
				$required_fields['RequiredClientFields']
			)
		);
	}

	/**
	 * Create MBO Account.
	 *
	 * @param array $client_fields to send to MBO.
	 */
	public function add_client( $client_fields = array() ) {

		// Crate the MBO Object.
		$this->get_mbo_results();

		$signup_result = $this->mb->AddClient( $client_fields );

		return $signup_result;
	}

	/**
	 * Sanitize User Credentials via WP helpers.
	 *
	 * @since: 1.0.1
	 *
	 * @param array $credentials user passwd.
	 * @return array of sanitized credentials.
	 */
	public function sanitize_login_fields( $credentials = array() ) {

		$credentials['Username'] = sanitize_email( $credentials['Username'] );
		$credentials['Password'] = sanitize_text_field( $credentials['Password'] );

		return $credentials;
	}


	/**
	 * Verify User Credentials.
	 *
	 * @since: 1.0.1
	 * @param array $credentials for MBO access.
	 * @return array of verified credentials.
	 */
	public function validate_login_fields( $credentials = array() ) {
		if ( false === filter_var( $credentials['Username'], FILTER_VALIDATE_EMAIL ) ) {
			return 2;
		}

		if ( false === $this->verify_mbo_pass() ) {
			return 3;
		}

		return $credentials;
	}

	/**
	 * Check if MBO pass meets their criteria.
	 *
	 * @since: 1.0.1
	 *
	 * @param string $mbo_password submitted by user.
	 * @return bool
	 */
	public function verify_mbo_pass( $mbo_password = '' ) {

		// "All Mindbody passwords must contain 8 to 15 characters,
		// and must include both letters and numbers."
		$re = '/^[A-Z0-9a-z].{7,14}$/m';

		return preg_match( $re, $mbo_password );
	}



	/**
	 * Get client details from session
	 *
	 * @since: 1.0.1
	 *
	 * @return array of client info from MBO or require login.
	 */
	public function get_client_details_from_session() {

		$client_info = $this->session->get( 'MBO_Client' );

		if ( ! (bool) $client_info->mbo_result ) {
			return __( 'Please Login', 'mz-mindbody-api' );
		}

		return $client_info->mbo_result;
	}

	/**
	 * Get client active memberships.
	 *
	 * Memberships will be an array, each of which contain among other stuff:
	 *
	 * [Name] => Monthly Membership - Gym Access
	 *      [PaymentDate] => 2020-05-06T00:00:00
	 *      [Program] => Array
	 *          (
	 *              [Id] => 21
	 *              [Name] => Gym Membership
	 *              [ScheduleType] => Arrival
	 *              [CancelOffset] => 0
	 *          )
	 * [Remaining] => 1000, etc..
	 *
	 * @since: 1.0.1
	 * @param int $client_id for MBO access..
	 *
	 * @return array numeric array of active memberships
	 */
	public function get_client_active_memberships( $client_id ) {

		// Create the MBO Object.
		$this->get_mbo_results();

		$result = $this->mb->GetActiveClientMemberships(
			array( 'clientId' => $client_id )
		); // Think this is not UniqueID.

		if ( ! array_key_exists( 'ClientMemberships', $result ) ) {
			return array();
		}

		return $result['ClientMemberships'];
	}

	/**
	 * Get client account balance.
	 *
	 * This wraps a method for getting balances for multiple accounts, but
	 * we just get it for one.
	 *
	 * @since: 1.0.1
	 * @param int $client_id for MBO access.
	 *
	 * @return array account balances.
	 */
	public function get_client_account_balance( $client_id ) {

		// Can accept a list of client id strings.
		$result = $this->mb->GetClientAccountBalances(
			array( 'clientIds' => $client_id )
		); // Think this is not UniqueID.

		// Just return the first (and only) result.
		return $result['Clients'][0]['AccountBalance'];
	}

	/**
	 * Get client contracts.
	 *
	 * Returns an array of items that look like this:
	 *
	 * [AgreementDate] => 2020-05-06T00:00:00
	 * [AutopayStatus] => Active
	 * [ContractName] => Monthly Membership - 12 Months
	 * [EndDate] => 2021-05-06T00:00:00
	 * [Id] => 15040
	 * [OriginationLocationId] => 1
	 * [StartDate] => 2020-05-06T00:00:00
	 * [SiteId] => -99
	 * [UpcomingAutopayEvents] => Array
	 *     (
	 *         [0] => Array
	 *             (
	 *                 [ClientContractId] => 15040
	 *                 [ChargeAmount] => 75
	 *                 [PaymentMethod] => DebitAccount
	 *                 [ScheduleDate] => 2020-06-06T00:00:00
	 *             )
	 * etc...
	 * [LocationId] => 1
	 * [Payments] => Array
	 * (
	 *  [0] => Array
	 *      (
	 *          [Id] => 158015
	 *          [Amount] => 75
	 *          [Method] => 16
	 *          [Type] => Account
	 *          [Notes] =>
	 *      )
	 *
	 * )
	 *
	 * @since: 1.0.1
	 * @param int $client_id for MBO access..
	 *
	 * @return array numeric array of client contracts.
	 */
	public function get_client_contracts( $client_id ) {

		// Create the MBO Object.
		$this->get_mbo_results();

		$result = $this->mb->GetClientContracts(
			array( 'clientId' => $client_id )
		);

		if ( ! array_key_exists( 'Contracts', $result ) ) {
			return array();
		}

		return $result['Contracts'];
	}

	/**
	 * Get client purchases.
	 *
	 * Returns an array of items that look like this:
	 * [Sale] => Array
	 *     (
	 *         [Id] => 100160377
	 *         [SaleDate] => 2020-05-06T00:00:00Z
	 *         [SaleTime] => 23:46:45
	 *         [SaleDateTime] => 2020-05-06T23:46:45Z
	 *         [ClientId] => 100015683
	 *         [PurchasedItems] => Array
	 *             (
	 *                 [0] => Array
	 *                     (
	 *                         [Id] => 1198
	 *                         [IsService] => 1
	 *                         [BarcodeId] =>
	 *                     )
	 *             )
	 *         [LocationId] => 1
	 *         [Payments] => Array
	 *             (
	 *                 [0] => Array
	 *                     (
	 *                         [Id] => 158015
	 *                         [Amount] => 75
	 *                         [Method] => 16
	 *                         [Type] => Account
	 *                         [Notes] =>
	 *                     )
	 *             )
	 *     )
	 * [Description] => Monthly Membership - Gym Access
	 * [AccountPayment] =>
	 * [Price] => 75
	 * [AmountPaid] => 75
	 * [Discount] => 0
	 * [Tax] => 0
	 * [Returned] =>
	 * [Quantity] => 1
	 *
	 * @since: 1.0.1
	 * @param int $client_id for MBO access.
	 * @param int $start_date Datetime string to send to Mindbody.
	 *
	 * @return array numeric array of client purchases
	 */
	public function get_client_purchases( $client_id, $start_date = '2001-01-01T00:00:00' ) {

		// Create the MBO Object.
		$this->get_mbo_results();

		$result = $this->mb->GetClientPurchases(
			array(
				'ClientId'  => $client_id,
				'StartDate' => $start_date,
			)
		); // NOT "UniqueID".

		if ( ! array_key_exists( 'Purchases', $result ) ) {
			return array();
		}

		return $result['Purchases'];
	}

	/**
	 * Get client services.
	 *
	 * Returns an array of items that look like this:
	 *
	 * [ActiveDate] => 2021-03-31T00:00:00
	 * [Count] => 8
	 * [Current] => 1
	 * [ExpirationDate] => 2021-05-31T00:00:00
	 * [Id] => 100247311
	 * [ProductId] => 1403
	 * [Name] => Intense Bootcamp (Name of the actual service)
	 * [PaymentDate] => 2021-03-31T00:00:00
	 * [Program] => Array
	 *     (
	 *         [Id] => 10
	 *         [Name] => Boot Camp (Program by which was purchsed ie: Free Class)
	 *         [ScheduleType] => Enrollment
	 *         [CancelOffset] => 0
	 *         [ContentFormats] => Array
	 *             (
	 *                 [0] => InPerson
	 *             )
	 *
	 *     )
	 * [Remaining] => 8
	 * [SiteId] => -99
	 * [Action] => None
	 *
	 * @since: 1.0.1
	 * @param int $client_id for MBO access.
	 *
	 * @return array numeric array of client services
	 */
	public function get_client_services( $client_id ) {

		// Create the MBO Object.
		$this->get_mbo_results();

		$result = $this->mb->GetClientServices(
			array( 'clientId' => $client_id )
		);

		if ( ! array_key_exists( 'ClientServices', $result ) ) {
			return array();
		}

		return $result['ClientServices'];
	}

	/**
	 * Create MBO Account
	 *
	 * @since 5.4.7
	 *
	 * TODO: clarify
	 * param array containing 'UserEmail' 'UserFirstName' 'UserLastName'
	 *
	 * @param int $client_id for MBO.
	 *
	 * @return array|bool either error or new client details
	 */
	public function password_reset_email_request( $client_id = array() ) {

		// Crate the MBO Object.
		$this->get_mbo_results();

		$result = $this->mb->SendPasswordResetEmail( $client_id );

		return $result;
	}


	/**
	 * Check Client Logged In
	 *
	 * Is there a session containing the MBO_GUID of current user
	 * The $client_info is an object, as is mbo_result. Not an
	 * array as is returned from MBO API.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function check_client_logged() {

		$client_info = $this->session->get( 'MBO_Client' );

		if ( empty( $client_info ) ) {
			return false;
		}
		// Just returning the (bool) result fails.
        // @codingStandardsIgnoreStart because strict test fails
		return ( 1 == (bool) $client_info->mbo_result ) ? 1 : 0;
        // @codingStandardsIgnoreEnd
	}

	/**
	 * Get API version, create API Interface Object
	 *
	 * @since 1.0.1
	 *
	 * @param int $api_version in case we need to call on API v5,
	 * as in for client login.
	 *
	 * @return array of MBO schedule data.
	 */
	public function get_mbo_results( $api_version = 6 ) {

		if ( 6 === $api_version ) {
			$this->mb = $this->instantiate_mbo_api();
		} else {
			$this->mb = $this->instantiate_mbo_api( 5 );
		}

		if ( ! $this->mb || 'NO_API_SERVICE' === $this->mb ) {
			return false;
		}

		return true;
	}

	/**
	 * Return an array of MBO Class Objects, ordered by date, then time.
	 *
	 * This is a limited version of the Retrieve Classes method used in horizontal schedule
	 *
	 * @since 1.0.1
	 * @param array $client_schedule as returned from MBO.
	 *
	 * @return array of Objects from Single_event class, in Date (and time) sequence.
	 */
	public function sortClassesByDateThenTime( $client_schedule = array() ) {

		$classes_by_date_then_time = array();

		/*
		* For some reason, when there is only a single class in the client
		* schedule, the 'Visits' array contains that visit, but when there are multiple
		* visits then the array of visits is under 'Visits'/'Visit'
		*/

		if ( is_array(
			$client_schedule['GetClientScheduleResult']['Visits']['Visit'][0]
		) ) {
			// Multiple visits.
			$visit_array_scope = $client_schedule['GetClientScheduleResult']['Visits']['Visit'];
		} else {
			$visit_array_scope = $client_schedule['GetClientScheduleResult']['Visits'];
		}

		foreach ( $visit_array_scope as $visit ) {
			// Make a timestamp of just the day to use as key for that day's classes.
			$just_date = wp_date( 'Y-m-d', $visit['StartDateTime'] );

			/*
			 * Create a new array with a key for each date YYYY-MM-DD,
			 * and corresponding value an array of class details.
			 */

			$single_event = new Schedule\MiniScheduleItem( $visit );

			if ( ! empty( $classes_by_date_then_time[ $just_date ] ) ) {
				array_push( $classes_by_date_then_time[ $just_date ], $single_event );
			} else {
				$classes_by_date_then_time[ $just_date ] = array( $single_event );
			}
		}

		/* They are not ordered by date so order them by date */
		ksort( $classes_by_date_then_time );

		foreach ( $classes_by_date_then_time as $class_date => &$classes ) {
			/*
			* $classes is an array of all classes for given date
			* Take each of the class arrays and order it by time
			* $classes_by_date_then_time should have a length of seven, one for
			* each day of the week.
			*/
			usort(
				$classes,
				function ( $a, $b ) {
					if ( $a->start_datetime === $b->start_datetime ) {
						return 0;
					}
					return $a->start_datetime < $b->start_datetime ? -1 : 1;
				}
			);
		}

		return $classes_by_date_then_time;
	}


	/**
	 * Make Numeric Array
	 *
	 * Make sure that we have an array
	 *
	 * @param string|array $data a string|array.
	 * @return array
	 */
	private function make_numeric_array( $data ) {

		return ( isset( $data[0] ) ) ? $data : array( $data );
	}
}
