<?php
/**
 * affiliates-woo-sync.php
 *
 * Copyright (c) 2017 Antonio Blanco http://www.eggemplo.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Antonio Blanco	
 * @package affiliates-woo-sync
 * @since affiliates-woo-sync 1.0.0
 *
 * Plugin Name: Affiliates Woocommerce Sync
 * Plugin URI: http://www.eggemplo.com
 * Description: Synchronizes the status of referrals according to the status of previous orders
 * Version: 1.0.0
 * Author: eggemplo
 * Author URI: http://www.eggemplo.com
 * License: GPLv3
 */

class AffiliatesWooSync_Plugin {

	private static $notices = array ();

	public static function init() {
		add_action ( 'init', array ( __CLASS__, 'wp_init' ) );
		add_action ( 'admin_notices', array ( __CLASS__, 'admin_notices' ) );
	}

	public static function wp_init() {
		if ( !defined( 'AFFILIATES_EXT_VERSION' ) ) {
			self::$notices [] = "<div class='error'>" . __ ( '<strong>Affiliates Woocommerce Sync</strong> plugin requires <a href="https://www.itthinx.com/shop/affiliates-pro/?affiliates=51" target="_blank">Affiliates Pro</a>.' ) . "</div>";
		} else {

			add_action ( 'admin_menu', array ( __CLASS__, 'admin_menu' ), 40 );

		}
	}

	public static function admin_notices() {
		if (! empty ( self::$notices )) {
			foreach ( self::$notices as $notice ) {
				echo $notice;
			}
		}
	}
	
	/**
	 * Adds the admin section.
	 */
	public static function admin_menu() {
		$admin_page = add_submenu_page(
				'affiliates-admin',
				__( 'Woo Sync' ),
				__( 'Woo Sync' ),
				'manage_options',
				'affiliateswoosync',
				array( __CLASS__, 'affiliateswoosync_settings' )
		);
	
	}
	
	
	public static function affiliateswoosync_settings () {
		global $wpdb;
		$referrals_table = _affiliates_get_tablename( 'referrals' );
		?>
		<h2><?php echo __( 'Affiliates Woocommerce Sync' ); ?></h2>
		<?php 
		$alert = "";
		if ( isset( $_POST['submit'] ) ) {

			$alert = '<p>' . __( 'Synchronized referrals:' ) . '</p>';

			$query = esc_sql( "SELECT * FROM $referrals_table" );
			$referrals = $wpdb->get_results( $query);
			if ( count( $referrals ) > 0 ) {
				foreach ( $referrals as $referral ) {
					if ( ( $referral->status !== 'rejected' ) && ( $referral->status !== 'closed' ) ) {
					$data = maybe_unserialize( $referral->data );
						if ( isset( $data['order_id'] ) && ( $data['order_id']['domain'] == 'affiliates-woocommerce') ) {
							$order_id = $referral->post_id;
							$order = Affiliates_WooCommerce_Integration::get_order( $order_id );
							$order_status = $order->get_status();
	
							$alert .= 'referral ' . $referral->referral_id . ', ';
	
							if ( $order_status == 'cancelled' ) {
								Affiliates_WooCommerce_Integration::order_status_cancelled( $order_id );
							}
							if ( $order_status == 'completed' ) {
								Affiliates_WooCommerce_Integration::order_status_completed( $order_id );
							}
							if ( $order_status == 'failed' ) {
								Affiliates_WooCommerce_Integration::order_status_failed( $order_id );
							}
							if ( $order_status == 'on-hold' ) {
								Affiliates_WooCommerce_Integration::order_status_on_hold( $order_id );
							}
							if ( $order_status == 'pending' ) {
								Affiliates_WooCommerce_Integration::order_status_pending( $order_id );
							}
							if ( $order_status == 'processing' ) {
								Affiliates_WooCommerce_Integration::order_status_processing( $order_id );
							}
							if ( $order_status == 'refunded' ) {
								Affiliates_WooCommerce_Integration::order_status_refunded( $order_id );
							}
						}
					}
				}
			}

		}
		if ($alert != "")
			echo '<div style="background-color: #ffffe0;border: 1px solid #993;padding: 1em;margin-right: 1em;">' . $alert . '</div>';
		
		?>
		<div class="wrap" style="border: 1px solid #ccc; padding:10px;">
		
		<form method="post" action="">

		<?php
			submit_button("Sync referrals");
			settings_fields( 'affiliateswoosync-settings' );
		?>

		</form>
		</div>
		<?php
		}

}
AffiliatesWooSync_Plugin::init();

