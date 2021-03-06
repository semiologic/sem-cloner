<?php
/*
Plugin Name: Semiologic Cloner
Plugin URI: http://www.semiologic.com/software/sem-cloner/
Description: RETIRED - Lets you clone a Semiologic Pro site. It also works with normal WordPress installations. Cloning non-Semiologic Pro plugins and themes may result in unexpected behaviors.
Version: 1.4.10
Author: Denis de Bernardy & Mike Koepke
Author URI: https://www.semiologic.com
Text Domain: sem-cloner
Domain Path: /lang
License: Dual licensed under the MIT and GPLv2 licenses
*/

/*
Terms of use
------------

This software is copyright Denis de Bernardy & Mike Koepke, and is distributed under the terms of the MIT and GPLv2 licenses.
**/

/*
 * This plugin has been retired.  No further development will occur on it.
 * */

// Disable the plugin

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'sem-cloner/sem-cloner.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);


define('sem_cloner_version', '1.4');


/**
 * sem_cloner
 *
 * @package Semiologic Cloner
 **/

class sem_cloner {
	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;

	/**
	 * URL to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_url = '';

	/**
	 * Path to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_path = '';

	/**
	 * Access this plugin’s working instance
	 *
	 * @wp-hook plugins_loaded
	 * @return  object of this class
	 */
	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;

		return self::$instance;
	}


	/**
	 * Loads translation file.
	 *
	 * Accessible to other classes to load different language files (admin and
	 * front-end for example).
	 *
	 * @wp-hook init
	 * @param   string $domain
	 * @return  void
	 */
	public function load_language( $domain )
	{
		load_plugin_textdomain(
			$domain,
			FALSE,
			dirname(plugin_basename(__FILE__)) . '/lang'
		);
	}

	/**
	 * Constructor.
	 *
	 *
	 */

	public function __construct() {
		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );
		$this->load_language( 'sem-cloner' );

		add_action( 'plugins_loaded', array ( $this, 'init' ) );
    }

	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		// register actions and filters
		if ( !is_admin() ) {
			add_action('init', array($this, 'rpc'), 1000000);
		}
		else {
			add_action('admin_menu', array($this, 'admin_menu'));

            if ( !get_option('sem_cloner_key') )
                update_option('sem_cloner_key', uniqid(rand()));

			add_action('load-tools_page_sem-cloner', array($this, 'sem_cloner_admin'));
		}
	}

	/**
	* sem_cloner_admin()
	*
	* @return void
	**/
	function sem_cloner_admin() {
		if ( !class_exists('sem_cloner_admin') )
			include_once $this->plugin_path . '/sem-cloner-admin.php';
	}

    /**
	 * admin_menu()
	 *
	 * @return void
	 **/
	function admin_menu() {
		if ( function_exists('is_super_admin') && !is_super_admin() )
			return;
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
		if ( function_exists('is_multisite') && is_multisite() )
			return;
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

$sem_cloner = sem_cloner::get_instance();