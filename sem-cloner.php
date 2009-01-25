<?php
/*
Plugin Name: Semiologic Cloner
Plugin URI: http://www.semiologic.com/software/marketing/sem-cloner/
Description: Lets you clone a Semiologic Pro site's preferences.
Version: 1.2 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Update Package: https://members.semiologic.com/media/plugins/sem-cloner/sem-cloner.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/

define('sem_cloner_version', '1.2');

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
		sem_cloner::export();
		die;
	} # rpc()
	
	
	#
	# export()
	#
	
	function export()
	{
		$data = $_REQUEST['data'];
		
		if ( !in_array($data, array('version', 'options')) )
		{
			die('<error>Invalid Request</error>');
		}
		
		$$data = false;

		switch ( $data )
		{
		case 'version':
			$$data = sem_cloner_version;
			break;

		case 'options':
			global $wpdb;
			
			$option_names = (array) $wpdb->get_col("
				SELECT option_name
				FROM $wpdb->options
				WHERE option_name NOT IN (
						'home',
						'siteurl',
						'blogname',
						'blogdescription',
						'admin_email',
						'default_category',
						'db_version',
						'secret',
						'page_uris',
						'sem_links_db_changed',
						'wp_autoblog_feeds',
						'wp_hashcash_db',
						'posts_have_fulltext_index',
						'permalink_redirect_feedburner',
						'sem_google_analytics_params',
						'google_analytics',
						'falbum_options',
						'do_smart_ping',
						'blog_public',
						'countdown_datefile',
						'remains_to_ping',
						'rewrite_rules',
						'upload_path',
						'show_on_front',
						'page_on_front',
						'page_for_posts',
						'sem_static_front_cache',
						'wpcf_email',
						'wpcf_subject_suffix',
						'wpcf_success_msg',
						'sem_newsletter_manager_params',
						'semiologic',
						'sem_docs',
						'feedburner_settings',
						'doing_cron',
						'update_core',
						'update_plugins',
						'update_themes',
						'version_checker',
						'sem_versions',
						'permalink_structure',
						'category_base',
						'tag_base',
						'sem_nav_menus',
						'sem5_nav',
						'google_analytics',
						'cron',
						'fix_wysiwyg',
						'recently_edited',
						'script_manager',
						'sem5_docs',
						'sem5_docs_updated',
						'auth_salt',
						'dealdotcom_deal',
						'dismissed_update_core',
						'feedsmith_token',
						'nowReadingVersions',
						'recently_activated',
						'sem_entropy',
						'sem_docs_version',
						'wporg_popular_tags',
						'sem_cloner_key'
						)
				AND option_name NOT LIKE '%cache%'
				AND option_name NOT LIKE '%Cache%'
				AND option_name NOT LIKE 'mailserver\_%'
				AND option_name NOT LIKE 'sm\_%'
				AND option_name NOT LIKE '%hashcash%'
				AND option_name NOT LIKE 'wp\_cron\_%'
				AND option_name NOT LIKE 'wpnavt\_%'
				AND option_name NOT REGEXP '^rss_[0-9a-f]{32}'
				AND option_name NOT LIKE 'IWG\_%'
				AND option_name NOT LIKE '%\_salt'
				AND option_name NOT LIKE '%\_seed'
				AND option_name NOT LIKE 'search\_reloaded\_%'
				AND option_name NOT LIKE 'SUP\_%'
				AND option_name NOT LIKE 'xml\_sitemaps%'
				AND option_name NOT LIKE 'uninstall\_%'
				;");
			
			$options = array();
			
			foreach ( $option_names as $option_name )
			{
				$option = get_option($option_name);
				
				# discard objects
				if ( sem_cloner::has_object($option) )
				{
					continue;
				}
			
				$options[$option_name] = $option;
			}
			
			$$data = $options;
			
			break;
		}

		$$data = var_export($$data, true);
		$$data = $$data;
		$$data = base64_encode($$data);
		$$data = wordwrap($$data, 75, "\n", 1);
		$$data = '<data>'
			. "\n"
			. $$data
			. "\n"
			. '</data>';

		echo $$data;
		
		die;
	} # export()
	
	
	#
	# has_object()
	#
	
	function has_object($option)
	{
		if ( is_string($option) || is_numeric($option) || is_bool($option) )
		{
			return false;
		}
		elseif ( gettype($option) == 'object' )
		{
			return true;
		}
		elseif ( is_array($option) )
		{
			foreach ( $option as $opt )
			{
				if ( sem_cloner::has_object($opt) )
				{
					return true;
				}
			}
		}
			
		return false;
	} # has_object()
} # sem_cloner

sem_cloner::init();


if ( is_admin() )
{
	include dirname(__FILE__) . '/sem-cloner-admin.php';
}
?>