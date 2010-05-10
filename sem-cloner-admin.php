<?php
class sem_cloner_admin {
	/**
	 * edit_options()
	 *
	 * @return void
	 **/

	function edit_options() {
		if ( !current_user_can('manage_options') )
			return;
		
		echo '<div class="wrap">' . "\n";
		
		echo '<form method="post" action="">' . "\n";
		
		screen_icon();
		
		echo '<h2>' . __('Semiologic Cloner', 'sem-cloner') . '</h2>' . "\n";
		
		if ( function_exists('is_multisite') && is_multisite() ) {
			echo '<p>'
				. __('Semiologic Cloner cannot work in multisite environements.', 'sem-cloner')
				. '</p>' . "\n";
		} elseif ( sem_cloner_admin::exec() ) {
			echo '<p>'
				. __('Your site has been cloned successfully!', 'sem-cloner')
				. '</p>' . "\n"
				. '<p>'
				. __('A few options may nonetheless need some attention. Among them:', 'sem-cloner')
				. '</p>' . "\n"
				. '<ul>' . "\n"
				. '<li>'
				. __('Settings / Cache', 'sem-cloner')
				. '</li>'
				. '<li>'
				. __('Settings / Google Analytics', 'sem-cloner')
				. '</li>'
				. '</ul>' . "\n"
				;
		} else {
			sem_cloner_admin::start();
		}
		
		echo '</form>' . "\n";
		
		echo '</div>' . "\n";
	} # edit_options()
	
	
	/**
	 * start()
	 *
	 * @return void
	 **/
	
	function start() {
		wp_nonce_field('sem_cloner');
		
		$site_url = user_trailingslashit(get_option('home'));
		$site_key = strip_tags(get_option('sem_cloner_key'));
		
		echo '<p>' . '<strong>' . __('This site\'s Url:', 'sem-cloner') . '</strong><br /> <input type="text" class="code" size="58" readonly="readonly" value="' . esc_attr($site_url) . '"/></p>' . "\n";
		
		echo '<p>' . '<strong>' . __('This site\'s Key:', 'sem-cloner') . '</strong><br /> <input type="text" class="code" size="58" readonly="readonly" value="' . $site_key . '"/></p>' . "\n";
		
		echo '<hr />' . "\n";
		
		echo '<p>' . __('The form that follows will allow you to import another site\'s options into this one. Both sites need to be running the same version of the Semiologic Cloner plugin.', 'sem-cloner') . '</p>' . "\n";
		
		echo '<p>' . __('Please enter the url and the key of the site that you wish to copy below. You will find the needed details under Tools / Clone on that site.', 'sem-cloner') . '</p>' . "\n";
		
		echo '<table class="form-table">' . "\n";
		
		foreach ( array(
			'site_url' => __('Site Url', 'sem-cloner'),
			'site_key' => __('Site Key', 'sem-cloner'),
			)
			as $field => $label ) {
			echo '<tr valign="top">'
				. '<th scope="row">'
				. $label
				. '</th>'
				. '<td>'
				. '<input type="text" name="' . $field . '" size="58" class="widefat"'
				. ' value="' . ( $field != 'site_pass' && isset($_POST[$field]) ? esc_attr(stripslashes($_POST[$field])) : '' ) . '"'
				. ' />'
				. '</td>'
				. '</tr>';
		}
		
		echo '</table>' . "\n";
		
		echo '<div class="submit">'
			. '<input type="submit" value="' . esc_attr(__('Clone Site', 'sem-cloner')) . '" />'
			. '</p>' . "\n";
	} # start()
	
	
	/**
	 * exec()
	 *
	 * @return void
	 **/
	
	function exec() {
		if ( !$_POST )
			return false;
		
		check_admin_referer('sem_cloner');
		
		foreach ( array('site_url', 'site_key') as $field ) {
			if ( !isset($_POST[$field]) || !$_POST[$field] ) {
				echo '<div class="error">'
					. '<p>'
					. __('Please fill in all of the fields.', 'sem-cloner')
					. '</p>'
					. '</div>' . "\n";
				
				return false;
			}
			
			$$field = stripslashes($_POST[$field]);
		}
		
		if ( $site_key == get_option('sem_cloner_key') ) {
			unset($_POST['site_key']);
			
			echo '<div class="error">'
				. '<p>'
				. __('You\'ve filled this site\'s key, rather than the site key of the site that you wish to clone.', 'sem-cloner')
				. '</p>'
				. '</div>' . "\n";
			
			return false;
		}
		
		$url = $site_url
			. '?method=sem_cloner'
			. '&key=' . urlencode($site_key)
			. '&data=';
		
		foreach ( array('version', 'options') as $data ) {
			$response = wp_remote_get( $url . $data, array('timeout' => 20) );
			if ( is_wp_error( $response ) ) {
				$$data = $response;
			} else {
				$$data = $response['body'];
			}
			
			if ( is_wp_error($$data) ) {
				echo '<div class="error">'
					. '<p>' . esc_html( $$data->get_error_message() ) . '</p>'
					. '</div>' . "\n";
				
				return false;
			}
			
			if ( preg_match("|<error>(.*)</error>|", $$data, $match) ) {
				$msg = strip_tags($match[1]);
				
				echo '<div class="error">'
					. '<p>' . $msg . '</p>' . "\n"
					. '<p>'
					. __('Please make sure that the site you are seeking to clone is using the same version of Semiologic Cloner as this one.', 'sem-cloner')
					. '</p>' . "\n"
					. '</div>' . "\n";
				
				return false;
			}
			
			if ( strpos($$data, "<data>") === false ) {
				echo '<div class="error">'
					. '<p>'
					. __('Invalid data was returned. Please make sure the Semiologic Cloner plugin is active on the site you are seeking to clone.', 'sem-cloner')
					. '</p>'
					. '</div>' . "\n";
				
				return false;
			}

			$$data = preg_replace("/^.*<data>|<\/data>.*$/", '', $$data);
			$$data = @base64_decode($$data);
			#if ( $data == 'options' ) { dump(esc_html($$data)); die; }
			$$data = 'return ' . $$data . ';';
			$$data = eval($$data);
			
			if ( $$data === false )
				return false;
			
			switch ( $data ) {
			case 'version':
				break;
				if ( $$data != sem_cloner_version ) {
					echo '<div class="error">'
						. '<p>'
						. __('Version mismatch. Please make sure the site you are cloning is running the same version of Semiologic Cloner as this one.', 'sem-cloner')
						. '</p>'
						. '</div>' . "\n";
					
					return false;
				}
				break;
			
			case 'options':
				foreach ( $$data as $option_name => $option_value ) {
					switch ( $option_name ) {
					case 'mediacaster':
						$old_value = get_option($option_name);
						$option_value['itunes'] = $old_value['itunes'];
						$option_value['longtail'] = $old_value['longtail'];
						update_option($option_name, $option_value);
						break;

					case 'sem_seo':
						$old_value = get_option($option_name);
						foreach ( array('title', 'keywords', 'description') as $var )
							$option_value[$var] = $old_value[$var];
						update_option($option_name, $option_value);
						break;

					case 'sem_api_key':
						if ( !get_option('sem_api_key') )
							update_option($option_name, $option_value);
						break;

					default:
						update_option($option_name, $option_value);
						break;
					}

					if ( $plugins = get_option('active_plugins') ) {
						$old_plugin_page = $GLOBALS['plugin_page'];
						unset($GLOBALS['plugin_page']);

						foreach ( $plugins as $plugin ) {
							if ( $plugin && file_exists(WP_PLUGIN_DIR . '/' . $plugin) ) {
								include_once(WP_PLUGIN_DIR . '/' . $plugin);
								do_action('activate_' . $plugin);
							}
						}

						$GLOBALS['plugin_page'] = $old_plugin_page;
					}
				}
				
				break;
			}
		}
		
		# turn cache off if it exists
		if ( function_exists('wp_cache_disable') )
			wp_cache_disable();
		
		return true;
	} # exec()
	
	
	/**
	 * export()
	 *
	 * @return void
	 **/
	
	function export() {
		$data = $_REQUEST['data'];
		
		if ( !in_array($data, array('version', 'options')) )
			die('<error>' . __('Invalid Request', 'sem-cloner') . '</error>');
		
		$$data = false;

		switch ( $data ) {
		case 'version':
			$$data = sem_cloner_version;
			break;

		case 'options':
			global $wpdb;
			
			$option_names = (array) $wpdb->get_col("
				SELECT DISTINCT option_name
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
						'sem_nav_menus',
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
						'sem_cloner_key',
						'sem_custom_published',
						'widget_navbar',
						'widget_footer',
						'widget_nav_menu',
						'ysearch'
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
				AND option_name NOT LIKE '\_transient%'
				AND option_name NOT LIKE '%\_created'
				AND option_name NOT LIKE '%\_changed'
				AND option_name NOT LIKE 'uninstall\_%'
				;");
			
			$options = array();
			
			foreach ( $option_names as $option_name ) {
				$option = get_option($option_name);
				
				# discard objects
				if ( sem_cloner_admin::has_object($option) )
					continue;
			
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
	
	
	/**
	 * has_object()
	 *
	 * @return void
	 **/
	
	function has_object($option) {
		if ( is_string($option) || is_numeric($option) || is_bool($option) ) {
			return false;
		} elseif ( gettype($option) == 'object' ) {
			return true;
		} elseif ( is_array($option) ) {
			foreach ( $option as $opt ) {
				if ( sem_cloner_admin::has_object($opt) ) {
					return true;
				}
			}
		}
			
		return false;
	} # has_object()
} # sem_cloner_admin
?>