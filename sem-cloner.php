<?php
/*
Plugin Name: Semiologic Cloner
Plugin URI: http://www.semiologic.com/software/sem-cloner/
Description: Lets you clone a Semiologic Pro site. It also works with normal WordPress installations, but cloning non-Semiologic Pro plugins and themes may result in unexpected behaviors.
Version: 1.4 RC2
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: sem-cloner
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('sem-cloner', false, dirname(plugin_basename(__FILE__)) . '/lang');

define('sem_cloner_version', '1.4');


/**
 * sem_cloner
 *
 * @package Semiologic Cloner
 **/

class sem_cloner {
	/**
	 * admin_menu()
	 *
	 * @return void
	 **/
	
	function admin_menu() {
		add_management_page(
			__('Clone', 'sem-cloner'),
			__('Clone', 'sem-cloner'),
			'manage_options',
			'sem-cloner',
			array('sem_cloner_admin', 'edit_options')
			);
	} # admin_menu()
	
	
	/**
	 * rpc()
	 *
	 * @return void
	 **/
	
	function rpc() {
		if ( !isset($_REQUEST['method']) || $_REQUEST['method'] != 'sem_cloner' )
			return;
		
		# Reset WP
		$GLOBALS['cache_enabled'] = false;
		
		$levels = ob_get_level();
		for ($i=0; $i<$levels; $i++)
			ob_end_clean();

		status_header(200);
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		// always modified
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		// HTTP/1.1
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		// HTTP/1.0
		header('Pragma: no-cache');

		# Set the response format.
		header('Content-Type:text/xml; charset=utf-8');
		echo '<?xml version="1.0" encoding="utf-8" ?>';
		
		# Validate user
		if ( !isset($_REQUEST['key']) || $_REQUEST['key'] != get_option('sem_cloner_key') )
			die('<error>' . __('Access Denied', 'sem-cloner') . '</error>');

		# Execute RPC
		if ( !class_exists('sem_cloner_admin') )
			include dirname(__FILE__) . '/sem-cloner-admin.php';
		
		sem_cloner_admin::export();
		die;
	} # rpc()
} # sem_cloner


function sem_cloner_admin() {
	if ( !class_exists('sem_cloner_admin') )
		include dirname(__FILE__) . '/sem-cloner-admin.php';
}

add_action('load-tools_page_sem-cloner', 'sem_cloner_admin');


add_action('init', array('sem_cloner', 'rpc'), 1000000);
add_action('admin_menu', array('sem_cloner', 'admin_menu'));

if ( !get_option('sem_cloner_key') )
	update_option('sem_cloner_key', uniqid(rand()));
?>