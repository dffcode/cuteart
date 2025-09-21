<?php
/**
 * Mailpoet integration class.
 *
 * @since 1.9.10
 *
 * @package OMAPI
 * @author  Justin Sternberg
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mailpoet integration class.
 *
 * @since 1.9.10
 */
class OMAPI_MailPoet {

	/**
	 * Check to see if Mailpoet is active.
	 *
	 * @since 1.2.3
	 *
	 * @return bool
	 */
	public static function is_active() {
		return class_exists( 'WYSIJA_object' ) || class_exists( 'MailPoet\\API\\API' );
	}

	/**
	 * Returns the available MailPoet lists.
	 *
	 * @since 1.0.0
	 *
	 * @return array An array of MailPoet lists.
	 */
	public function get_lists() {

		// Prepare variables.
		$mailpoet    = null;
		$lists       = array();
		$ret         = array();
		$list_id_key = 'id';

		// Get lists. Check for MailPoet 3 first. Default to legacy.
		if ( class_exists( '\\MailPoet\\Config\\Initializer' ) ) {
			/**
			 * Get the MailPoet lists.
			 *
			 * @see https://github.com/mailpoet/mailpoet/blob/c8fa9f007fd6fa39c17cd45e919566726a0578d7/doc/api_methods/GetLists.md
			 */
			$lists = \MailPoet\API\API::MP( 'v1' )->getLists();
		} else {
			$mailpoet    = WYSIJA::get( 'list', 'model' );
			$lists       = $mailpoet->get( array( 'name', 'list_id' ), array( 'is_enabled' => 1 ) );
			$list_id_key = 'list_id';
		}

		// Add default option.
		$ret[] = array(
			'name'  => esc_html__( 'Select your MailPoet list...', 'optin-monster-api' ),
			'value' => 'none',
		);

		// Loop through the list data and add to array.
		foreach ( (array) $lists as $list ) {
			$ret[] = array(
				'name'  => $list['name'],
				'value' => $list[ $list_id_key ],
			);
		}

		/**
		 * Filters the MailPoet lists.
		 *
		 * @param array       $ret      The MailPoet lists array.
		 * @param array       $lists    The raw MailPoet lists array. Format differs by plugin verison.
		 * @param WYSIJA|null $mailpoet The MailPoet object if using legacy. Null otherwise.
		 */
		return apply_filters( 'optin_monster_api_mailpoet_lists', $ret, $lists, $mailpoet );
	}

	/**
	 * Returns the available MailPoet subscriber fields.
	 *
	 * @see https://github.com/mailpoet/mailpoet/blob/c8fa9f007fd6fa39c17cd45e919566726a0578d7/doc/api_methods/GetSubscriberFields.md
	 *
	 * @since 1.9.8
	 *
	 * @return array An array of MailPoet subscriber fields.
	 */
	public function get_subscriber_fields() {
		// Get lists. Check for MailPoet 3.
		return class_exists( '\\MailPoet\\Config\\Initializer' )
			? \MailPoet\API\API::MP( 'v1' )->getSubscriberFields()
			: array();
	}

	/**
	 * Returns the available MailPoet custom fields formatted for dropdown.
	 *
	 * @since 1.9.8
	 *
	 * @return array An array of MailPoet custom fields.
	 */
	public function get_custom_fields_dropdown_values() {
		// Prepare variables.
		$ret            = array();
		$default_fields = array( 'email', 'first_name', 'last_name' );

		// Add default option.
		$ret[] = array(
			'name'  => esc_html__( 'Select the custom field...', 'optin-monster-api' ),
			'value' => '',
		);

		$subscriber_fields = $this->get_subscriber_fields();

		// Loop through the list data and add to array.
		foreach ( (array) $subscriber_fields as $subscriber_field ) {
			if ( in_array( $subscriber_field['id'], $default_fields, true ) ) {
				continue;
			}

			$ret[] = array(
				'name'  => $subscriber_field['name'],
				'value' => $subscriber_field['id'],
			);
		}

		/**
		 * Filters the MailPoet custom fields.
		 *
		 * @param array. $ret               The MailPoet custom fields array, except
		 *                                  first name, last name and email
		 * @param array. $subscriber_fields The raw MailPoet subscriber fields array.
		 *                                  Format differs by plugin verson.
		 */
		return apply_filters( 'optin_monster_api_mailpoet_custom_fields', $ret, $subscriber_fields );
	}

	/**
	 * Opts the user into MailPoet.
	 *
	 * @since 1.0.0
	 */
	public function handle_ajax_call() {
		/*
		 * Check the nonce is correct first.
		 *
		 * As this is a front end form to store the visitor's data in a mailing
		 * list no capability check is required.
		 */
		check_ajax_referer( 'omapi', 'nonce' );

		// Prepare variables.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$optin_data = ! empty( $_REQUEST['optinData'] ) && is_array( $_REQUEST['optinData'] ) ? wp_unslash( $_REQUEST['optinData'] ) : array();
		$data       = array_merge( $_REQUEST, $optin_data );
		unset( $data['optinData'] );

		$optin = OMAPI::get_instance()->get_optin_by_slug( stripslashes( $data['optin'] ) );
		$list  = get_post_meta( $optin->ID, '_omapi_mailpoet_list', true );

		$user = $this->prepare_subscriber_data( $optin, $data );

		// Store the data.
		$data = array(
			'user'      => $user,
			'user_list' => array( 'list_ids' => array( $list ) ),
		);

		/**
		 * Filters the data before saving to MailPoet.
		 *
		 * @param array  $data    The data to save.
		 * @param array  $request Deprecated. Use $_REQUEST instead.
		 * @param string $list    The list to add the lead.
		 */
		$data = apply_filters( 'optin_monster_pre_optin_mailpoet', $data, array(), $list );

		// Save the subscriber. Check for MailPoet 3 first. Default to legacy.
		if ( class_exists( 'MailPoet\\API\\API' ) ) {
			// Customize the lead data for MailPoet 3.
			if ( isset( $user['firstname'] ) ) {
				$user['first_name'] = $user['firstname'];
				unset( $user['firstname'] );
			}

			if ( isset( $user['lastname'] ) ) {
				$user['last_name'] = $user['lastname'];
				unset( $user['lastname'] );
			}

			try {
				/**
				 * Try to find the subscriber by email.
				 *
				 * @see https://github.com/mailpoet/mailpoet/blob/c8fa9f007fd6fa39c17cd45e919566726a0578d7/doc/api_methods/GetSubscriber.md
				 */
				$subscriber = \MailPoet\API\API::MP( 'v1' )->getSubscriber( $user['email'] );
			} catch ( Exception $e ) {
				$subscriber = false;
			}

			try {
				if ( $subscriber ) {
					/**
					 * Subscribe the existing subscriber to the list.
					 *
					 * @see https://github.com/mailpoet/mailpoet/blob/c8fa9f007fd6fa39c17cd45e919566726a0578d7/doc/api_methods/SubscribeToList.md
					 */
					\MailPoet\API\API::MP( 'v1' )->subscribeToList( $subscriber['email'], $list );
				} else {
					/**
					 * Add a new subscriber.
					 *
					 * @see https://github.com/mailpoet/mailpoet/blob/c8fa9f007fd6fa39c17cd45e919566726a0578d7/doc/api_methods/AddSubscriber.md
					 */
					\MailPoet\API\API::MP( 'v1' )->addSubscriber( $user, array( $list ) );
				}
			} catch ( Exception $e ) {
				return wp_send_json_error( $e->getMessage(), 400 );
			}
		} else {
			$user_helper = WYSIJA::get( 'user', 'helper' );
			$user_helper->addSubscriber( $data );
		}

		// Send back a response.
		wp_send_json_success();
	}

	/**
	 * Prepares the subscriber data that will be inserted/updated into the Mailpoet's API.
	 *
	 * @since 2.16.3
	 *
	 * @param WP_Post $optin The optin object.
	 * @param array   $data  The lead data from the optin request.
	 *
	 * @return array         The updated subscriber data.
	 */
	protected function prepare_subscriber_data( $optin, $data ) {
		$email = ! empty( $data['email'] ) ? stripslashes( $data['email'] ) : false;
		$name  = ! empty( $data['name'] ) ? stripslashes( $data['name'] ) : false;

		$user = array();

		// Possibly split name into first and last.
		if ( $name ) {
			$names = explode( ' ', $name );
			if ( isset( $names[0] ) ) {
				$user['firstname'] = $names[0];
			}

			if ( isset( $names[1] ) ) {
				$user['lastname'] = $names[1];
			}
		}

		// Save the email address.
		$user['email'] = $email;

		// Save the custom fields data into user array.
		$user += $this->prepare_lead_custom_fields( $data, $optin->ID );

		return $user;
	}

	/**
	 * Prepares and returns the custom fields to be sent to Mailpoet.
	 *
	 * @since 2.16.3
	 *
	 * @param array $data     The request data.
	 * @param int   $optin_id The optin ID.
	 *
	 * @return array          The updated user data containing the custom fields and values as they will be sent to Mailpoet.
	 */
	protected function prepare_lead_custom_fields( $data, $optin_id ) {
		$custom_fields = $this->get_lead_custom_fields( $optin_id, $data );

		if ( empty( $custom_fields ) ) {
			// If there's nothing to map, return an empty array.
			return array();
		}

		$parsed_custom_fields = array();

		$mapped_custom_fields      = get_post_meta( $optin_id, '_omapi_mailpoet_mapped_fields', true );
		$should_auto_create_fields = get_post_meta( $optin_id, '_omapi_mailpoet_fields_auto_create', true );

		// Save a copy of the mapped fields as they saved in the database.
		$saved_mapped_fields = $mapped_custom_fields;

		$mp_fields       = $this->get_subscriber_fields();
		$mp_fields_by_id = array();
		foreach ( $mp_fields as $field ) {
			$mp_fields_by_id[ $field['id'] ] = $field;
		}

		if ( empty( $mapped_custom_fields ) ) {
			$mapped_custom_fields = array();
		}

		// Keep old phone field mapping for backward compatibility.
		$legacy_phone_field_mapping = get_post_meta( $optin_id, '_omapi_mailpoet_phone_field', true );
		if ( ! empty( $legacy_phone_field_mapping ) ) {
			// If there's a mapping for the phone field that means we already set the legacy mapping or the user manually
			// set a different mapping. That means we don't need to set the legacy mapping.
			if ( empty( $mapped_custom_fields['lead.phoneInput'] ) ) {
				$mapped_custom_fields['lead.phoneInput'] = $legacy_phone_field_mapping;
			}
		}

		foreach ( $custom_fields as $field_id => $field ) {

			if ( empty( $mapped_custom_fields[ $field_id ] ) && ! $should_auto_create_fields ) {
				// If the field is not mapped and auto field creation is disabled, skip it.
				continue;
			}

			$field_key = '';

			if ( ! empty( $mapped_custom_fields[ $field_id ] ) && ! empty( $mp_fields_by_id[ $mapped_custom_fields[ $field_id ] ] ) ) {
				// If the field is already mapped and the mapped field exists, use the mapped field.
				$field_key = $mapped_custom_fields[ $field_id ];
			} elseif ( $should_auto_create_fields ) {
				// If the field is not mapped, but auto field creation is enabled, create and map the field.

				// Create the custom field in MailPoet.
				$created_field = $this->create_custom_field( $field['label'], $field_id );
				$field_key     = $created_field['id'];

				// Add the new created custom field to the mapped fields meta.
				$mapped_custom_fields[ $field_id ] = $field_key;
			}

			// If the field key is empty, skip it. Safety check.
			if ( empty( $field_key ) ) {
				continue;
			}

			// If the value is an array (e.g. for checkboxes), we are converting it to a string.
			$parsed_custom_fields[ $field_key ] = is_array( $field['value'] ) ?
				implode( ', ', $field['value'] ) :
				$field['value'];
		}

		if ( $saved_mapped_fields !== $mapped_custom_fields ) {
			// Updating the mapped fields meta if new fields are added.
			update_post_meta( $optin_id, '_omapi_mailpoet_mapped_fields', $mapped_custom_fields );
		}

		return $parsed_custom_fields;
	}

	/**
	 * Returns the custom fields for the lead.
	 *
	 * @since 2.16.3
	 *
	 * @param int   $optin_id The optin ID.
	 * @param array $data The request data.
	 *
	 * @return array The custom fields for the lead.
	 */
	protected function get_lead_custom_fields( $optin_id, $data ) {
		$meta_fields = ! empty( $data['meta'] ) ? stripslashes_deep( $data['meta'] ) : array();
		$smart_tags  = ! empty( $data['tags'] ) ? stripslashes_deep( $data['tags'] ) : array();

		$optin_fields_config = get_post_meta( $optin_id, '_omapi_mailpoet_optin_fields_config', true );
		$optin_meta_fields_by_name = array();

		if ( ! empty( $optin_fields_config['meta'] ) ) {
			foreach ( $optin_fields_config['meta'] as $field_config ) {
				$optin_meta_fields_by_name[ $field_config['name'] ] = $field_config;
			}
		}

		$custom_fields = array();

		foreach ( $meta_fields as $key => $value ) {
			$field_config = ! empty( $optin_meta_fields_by_name[ $key ] ) ? $optin_meta_fields_by_name[ $key ] : array();

			$custom_fields[ 'meta.' . $key ] = array(
				'value' => $value,
				'label' => ! empty( $field_config['label'] ) ? $field_config['label'] : $key,
			);
		}

		// Add the coupon code and label to the meta fields, if exists.
		if ( ! empty( $smart_tags['coupon_code'] ) ) {
			$custom_fields['tags.couponCode']  = array(
				'value' => $smart_tags['coupon_code'],
				'label' => 'Coupon Code',
			);
			$custom_fields['tags.couponLabel']  = array(
				'value' => $smart_tags['coupon_label'],
				'label' => 'Coupon Label',
			);
		}

		// Add the privacy consent to the meta fields, if exists.
		if ( ! empty( $data['FieldsElement--privacyText-checkbox'] ) ) {
			$custom_fields['lead.privacyText'] = array(
				'value' => $data['FieldsElement--privacyText-checkbox'],
				'label' => 'Privacy Consent',
			);
		}

		// Add the phone number to the meta fields, if exists.
		if ( ! empty( $data['phone'] ) ) {
			$custom_fields['lead.phoneInput'] = array(
				'value' => $data['phone'],
				'label' => 'Phone Number',
			);
		}

		return $custom_fields;
	}

	/**
	 * Create a custom field in MailPoet.
	 *
	 * @see https://github.com/mailpoet/mailpoet/blob/trunk/doc/api_methods/AddSubscriberField.md
	 *
	 * @since 2.16.3
	 *
	 * @param string $name  The field name.
	 * @param string $label The field label.
	 *
	 * @return array        The created custom field.
	 */
	protected function create_custom_field( $name, $label ) {
		$data = array(
			'name'  => $name,
			'type'  => 'TEXT',
			'label' => $label,
		);

		// We are creating the custom fields only for Mailpoet 3 and later.
		return \MailPoet\API\API::MP( 'v1' )->addSubscriberField( $data );
	}
}
