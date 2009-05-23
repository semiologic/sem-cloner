<?php
/*
Plugin Name: Semiologic Cloner
Plugin URI: http://www.semiologic.com/software/sem-cloner/
Description: Lets you clone a Semiologic Pro site's preferences.
Version: 1.4 alpha
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: sem-cloner-info
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/

define('sem_cloner_version', '1.4');

class sem_cloner
{
	#
	# init()
	#
	
	function init()
	{
		add_action('init', array('sem_cloner', 'rpc'));
		
		if ( !get_option('sem_cloner_key') )
		{
			update_option('sem_cloner_key', uniqid(rand()));
		}
	} # init()
	
	
	#
	# rpc()
	#
	
	function rpc()
	{
		if ( !isset($_REQUEST['method']) || $_REQUEST['method'] != 'sem_cloner' ) return;
		
		# Reset WP
		$GLOBALS['wp_filter'] = array();
		$GLOBALS['cache_enabled'] = false;

		while ( @ob_end_clean() );

		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		// always modified
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		// HTTP/1.1
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		// HTTP/1.0
		header('Pragma: no-cache');

		# Set the response format.
		header( 'Content-Type:text/xml; charset=utf-8' );
		echo '<?xml version="1.0" encoding="utf-8" ?>';
		
		# Validate user
		if ( !isset($_REQUEST['key']) || $_REQUEST['key'] != get_option('sem_cloner_key') )
		{
			die('<error>Access Denied</error>');
		}

		# Execute RPC
		if ( !class_exists('sem_cloner') ) {
			include_once dirname(__FILE__) . 'sem-cloner-admin.php';
		}
		
		sem_cloner_admin::export();
		die;
	} # rpc()
} # sem_cloner

sem_cloner::init();


if ( is_admin() )
{
	include dirname(__FILE__) . '/sem-cloner-admin.php';
}
?>