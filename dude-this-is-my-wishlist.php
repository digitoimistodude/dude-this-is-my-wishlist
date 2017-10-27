<?php
/**
 * Plugin Name: Dude this is my wishlist
 * Plugin URI:  http://dude.fi/koodiluola
 * Description: Simple and developer friendly wishlist plugin.
 * Version:     0.1.0
 * Author:      Digitoimisto Dude
 * Author URI:  http://dude.fi
 * License:     GPLv2+
 *
 * @Date:   2017-08-22 16:03:31
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2017-10-27 14:00:15
 * @package Dude this is my wishlist
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'DTIM_Wishlist' ) ) :

	class DTIM_Wishlist {
		public static $post_types;
		public static $support_qty = false;
		public static $instance;

		public static function init() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new DTIM_Wishlist();
			}

			return self::$instance;
		}

		private function __construct() {
			DTIM_Wishlist::$post_types = apply_filters( 'dtim_wishlist_post_types', array( 'product' ) );
			DTIM_Wishlist::$support_qty = apply_filters( 'dtim_wishlist_support_qty', DTIM_Wishlist::$support_qty );

			// TODO: setup session only when needed
			require_once( plugin_dir_path( __FILE__ ) . 'lib/wp-session-manager/wp-session-manager.php' );

			// Set WP_session expiration to 1 hour.
			add_filter( 'wp_session_expiration', function() { return 60 * 60; } );

			require_once 'classes/class-dtim-wishlist-rest.php';
			DTIM_Wishlist_REST::init();
		}

		public static function post_exists( $id ) {
  		return is_string( get_post_status( $id ) );
		} // end post_exists

		public static function get_wishlist() {
			// Get existing wishlist from WP_Session or return a empty one.
			$wp_session = WP_Session::get_instance();
			if ( isset( $wp_session['wishlist_items'] ) ) {
				return unserialize( $wp_session['wishlist_items'] );
			} else {
				return array();
			}
		} // end get_wishlist

		public static function update_wishlist_item( $item_id = 0, $customizations = array() ) {
			// Check that item really exists.
			if ( ! self::post_exists( $item_id ) ) {
				return new WP_Error( 'not_exist', 'Item does not exist!', array( 'status' => 400 ) );
			}

			// Check that item's post type is allowed.
			if ( ! in_array( get_post_type( $item_id ), DTIM_Wishlist::$post_types, true ) ) {
				return new WP_Error( 'not_allowed', "Item's post type is not allowed.", array( 'status' => 400 ) );
			}

			// Get existing wishlist from WP_Session or make a new empty one.
			$wishlist_items = self::get_wishlist();

			if ( isset( $wishlist_items[ $item_id ] ) ) {
				// Item is in wishlist already, so update it.
				$wishlist_items[ $item_id ]['user_customizations'] = json_decode( $customizations, true );

				if ( DTIM_Wishlist::$support_qty ) {
					$wishlist_items[ $item_id ]['quantity'] = intval( $wishlist_items[ $item_id ]['quantity'] ) + 1;
				}

				$return_code = 'item-updated';
			} else {
				// Add item to wishlist.
				$wishlist_items[ $item_id ] = array(
					'item_id'							=> intval( $item_id ),
					'item_name'						=> get_the_title( $item_id ),
					'quantity'						=> 1,
					'user_customizations'	=> json_decode( $customizations, true ),
				);

				$return_code = 'item-added';
			}

			// Save new wishlist to session.
			$wp_session = WP_Session::get_instance();
			$wp_session['wishlist_items'] = serialize( $wishlist_items );

			return (object) array(
				'status'	=> true,
				'code'		=> $return_code,
			);
		} // end update_wishlist_item

		public static function delete_wishlist_item( $item_id = 0 ) {
			$wishlist_items = self::get_wishlist();

			if ( isset( $wishlist_items[ $item_id ] ) ) {
				unset( $wishlist_items[ $item_id ] );
				$wp_session = WP_Session::get_instance();
				$wp_session['wishlist_items'] = serialize( $wishlist_items );

				return (object) array(
					'status'	=> true,
					'code'		=> 'item-deleted',
				);
			} else {
				return new WP_Error( 'not_exist', "Item is not in wishlist!", array( 'status' => 400 ) );
			}
		} // end delete_wishlist_item

		// function to clear whole wishlist

		public function clear_wishlist() {
			wp_session_unset();
		} // end clear_wishlist
	}

endif;

// Init the plugin.
DTIM_Wishlist::init();

if ( ! function_exists( 'get_wishlist' ) ) {
	function get_wishlist() {
		return DTIM_Wishlist::get_wishlist();
	}
}

if ( ! function_exists( 'clear_wishlist' ) ) {
	function clear_wishlist() {
		return DTIM_Wishlist::clear_wishlist();
	}
}
