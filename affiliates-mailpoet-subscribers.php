<?php
/**
 * Plugin Name: Affiliates Mailpoet Subscribers
 * Plugin URI: http://www.netpad.gr
 * Description: Automatically add a new affiliate to Affiliates Subscriber list after registration
 * Version: 1.0
 * Author: George Tsiokos
 * Author URI: http://www.netpad.gr
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright (c) 2015-2016 "gtsiokos" George Tsiokos www.netpad.gr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
add_action( 'admin_notices', 'ams_check_dependencies' );

function ams_check_dependencies () {
	$active_plugins = get_option( 'active_plugins', array() );
	$affiliates_is_active = in_array( 'affiliates/affiliates.php', $active_plugins ) || in_array( 'affiliates-pro/affiliates-pro.php', $active_plugins ) || in_array( 'affiliates-enterprise/affiliates-enterprise.php', $active_plugins );
	$mailpoet_is_active = in_array( 'wysija-newsletters/index.php', $active_plugins );
	
	if ( !$affiliates_is_active ) {
		echo "<div class='error'>The <strong>Affiliates Mailpoet Subscribers</strong> plugin requires one of the <a href='http://wordpress.org/plugins/affiliates/'>Affiliates</a>, <a href='http://www.itthinx.com/shop/affiliates-pro/'>Affiliates Pro</a> or <a href='http://www.itthinx.com/shop/affiliates-enterprise/'>Affiliates Enterprise</a> plugins to be installed and activated.</div>";
	}
	
	if ( !$mailpoet_is_active ) {
		echo "<div class='error'>The <strong>Affiliates Mailpoet Subscribers</strong> plugin requires the <a href='https://wordpress.org/plugins/wysija-newsletters/'>MailPoet Newsletters</a> plugin to be installed and activated.</div>";
	}	
}


add_action( 'affiliates_after_register_affiliate', 'add_affiliate_to_mailpoet_list' );

function add_affiliate_to_mailpoet_list ( $userdata ) {

	$new_affiliate_email 		= $userdata['user_email'];
	$new_affiliate_firstname 	= $userdata['first_name'];
	$new_affiliate_lastname 	= $userdata['last_name'];
	$new_affiliate_user_login	= $userdata['user_login'];
	$new_affiliate 				= get_user_by( 'login', $new_affiliate_user_login );
	$new_affiliate_id 			= $new_affiliate->ID;
	$existing_subscriber 		= false;
	$list_id 					= 1;
	
	$model_list = WYSIJA::get( 'list' , 'model' );
	$mailpoet_lists = $model_list -> get( array( 'name', 'list_id' ), array( 'is_enabled' => 1 ) );
	foreach ( $mailpoet_lists as $mail_list ) {
		if( $mail_list['name'] == 'Affiliates' ) {
			$list_id = $mail_list['list_id'];
		}		
	}
	
	//if Affiliates list doesn't exist, add it
	if ( $list_id == 1 ) {
		$data_list = array();
		$data_list['is_enabled'] 	= 1;
		$data_list['name'] 			= 'Affiliates';
		$data_list['description'] 	= 'the affiliate\'s list';
		$model_list -> insert( $data_list );		
		$affiliates_list = $model_list -> get( array( 'list_id' ), array( 'is_enabled' => 1, 'name' => 'Affiliates' ) );
		$list_id = $affiliates_list[0]['list_id'];		
	} else {
		$model_user_id = WYSIJA::get( 'user', 'model' );
		$user_id_wpuser_id = $model_user_id -> get( array( 'user_id'), array( 'wpuser_id' => $new_affiliate_id ) );
		$mail_poet_user_id = $user_id_wpuser_id['user_id'];
		
		$model_user_list = WYSIJA::get( 'user_list', 'model' );
		$lists_users = $model_user_list -> get( array( 'user_id' ), array( 'list_id' => $list_id ) );
		foreach ( $lists_users as $list_user ) {
			if ( $list_user['user_id'] == $mail_poet_user_id ) {
				$existing_subscriber = true;
			}
		}
	}

	if ( ! $existing_subscriber ) {
		
		//gather user data
		$user_data = array(
				'email' => $new_affiliate_email,
				'firstname' => $new_affiliate_firstname,
				'lastname' => $new_affiliate_lastname
		);
	
		$data_subscriber = array(
				'user' => $user_data,
				'user_list' => array( 'list_ids' => array( $list_id ) )
		);
		
		$helper_user = WYSIJA::get( 'user', 'helper' );
		$helper_user -> addSubscriber( $data_subscriber );
	}
}
?>