<?php

/**
 * @Author: Timi Wahalahti
 * @Date:   2017-08-22 16:47:15
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2017-10-27 14:03:51
 */

if ( ! class_exists( 'DTIM_Wishlist_REST' ) ) :

	class DTIM_Wishlist_REST {
		public static $instance;

		public static function init() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new DTIM_Wishlist_REST();
			}

			return self::$instance;
		} // end init

		protected function __construct() {
			// REST route for adding item to wishlist.
			add_action( 'rest_api_init', function () {
				register_rest_route( 'wishlist/v1', '/update', array(
					'methods'		=> WP_REST_Server::CREATABLE,
					'callback'	=> array( $this, 'update_wishlist_item' ),
				) );
			} );

			// REST route for removing item from wishlist.
			add_action( 'rest_api_init', function () {
				register_rest_route( 'wishlist/v1', '/delete', array(
					'methods'		=> WP_REST_Server::DELETABLE,
					'callback'	=> array( $this, 'delete_wishlist_item' ),
				) );
			} );

			// REST route for getting wishlist content.
			if ( apply_filters( 'dtim_wishlist_rest_support_get', false ) ) {
				add_action( 'rest_api_init', function () {
					register_rest_route( 'wishlist/v1', '/get', array(
						'methods'		=> WP_REST_Server::READABLE,
						'callback'	=> array( $this, 'get_wishlist' ),
					) );
				} );
			}
		} // end __construct

		public function update_wishlist_item( WP_REST_Request $request ) {
			$update = DTIM_Wishlist::update_wishlist_item( $request->get_param( 'item_id' ), $request->get_param( 'item_customizations' ) );

			if ( is_wp_error( $update ) ) {
				return rest_ensure_response( $update );
			}

			if ( ! $update->status ) {
				// send somekind of error
				return rest_ensure_response( 'Something went wrong with your request.' );
			}

			$response = array(
				'added'		=> true,
				'updated'	=> false,
				'code'		=> $update->code,
			);

			if ( 'item-updated' === $update->code  ) {
				$response['added'] = false;
				$response['updated'] = true;
			}

			return rest_ensure_response( array(
				'status'		=> $response,
				'wishlist'	=> DTIM_Wishlist::get_wishlist(),
			) );
		} // end update_wishlist_item

		public function delete_wishlist_item( WP_REST_Request $request ) {
			$delete = DTIM_Wishlist::delete_wishlist_item( $request->get_param( 'item_id' ) );

			if ( is_wp_error( $delete ) ) {
				return rest_ensure_response( $delete );
			}

			return rest_ensure_response( array(
				'status'		=> array(
					'deleted'	=> true,
					'code'		=> $delete->code,
				),
				'wishlist'	=> DTIM_Wishlist::get_wishlist(),
			) );
		}

		public function get_wishlist( WP_REST_Request $request ) {
			return rest_ensure_response( DTIM_Wishlist::get_wishlist() );
		} // end get_wishlist
	}
endif;
