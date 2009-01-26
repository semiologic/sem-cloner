<?php
class sem_cloner_admin
{
	#
	# init()
	#
	
	function init()
	{
		add_action('admin_menu', array('sem_cloner_admin', 'admin_menu'));
	} # init()
	
	
	#
	# admin_menu()
	#
	
	function admin_menu()
	{
		add_management_page(
			__('Clone'),
			__('Clone'),
			'administrator',
			__FILE__,
			array('sem_cloner_admin', 'admin_page')
			);
	} # admin_menu()
	
	
	#
	# admin_page()
	#
	
	function admin_page()
	{
		echo '<div class="wrap">' . "\n";
		
		echo '<form method="post" action="">' . "\n";
		
		echo '<h2>' . 'Semiologic Cloner' . '</h2>' . "\n";
		
		if ( sem_cloner_admin::exec() )
		{
			$msg = <<<EOF

Your site has been cloned successfully!

A few options may nonetheless need some attention. In particular:

- Settings / Cache
- Settings / Google Analytics

Happy blogging!
EOF;
			echo wpautop($msg);
		}
		else
		{
			sem_cloner_admin::start();
		}
		
		echo '</form>' . "\n";
		
		echo '</div>' . "\n";
	} # admin_page()
	
	
	#
	# start()
	#
	
	function start()
	{
		wp_nonce_field('sem_cloner');
		
		$site_url = user_trailingslashit(get_option('home'));
		$site_key = strip_tags(get_option('sem_cloner_key'));
		
		echo '<p>' . '<strong>This site\'s Url</strong>: ' . $site_url . '</p>' . "\n";
		
		echo '<p>' . '<strong>This site\'s Key</strong>: ' . $site_key . '</p>' . "\n";
		
		echo '<hr />' . "\n";
		
		echo '<p>' . 'The form that follows will allow you to import another site\'s options into this one. Both sites need to be running the same version of the Semiologic cloner plugin.' . '</p>' . "\n";
		
		echo '<p>' . 'Please enter the url and the key of the site that you wish to copy. You will find the site key under Tools / Clone on the other site.' . '</p>' . "\n";
		
		echo '<table class="form-table">' . "\n";
		
		foreach ( array(
			'site_url' => 'Site Url',
			'site_key' => 'Site Key',
			)
			as $field => $label )
		{
			echo '<tr valign="top">'
				. '<th scope="row">'
				. $label
				. '</th>'
				. '<td>'
				. '<input type="text" name="' . $field . '" size="58" style="width: 90%;"'
				. ' value="' . ( $field != 'site_pass' ? attribute_escape($_POST[$field]) : '' ) . '"'
				. ' />'
				. '</td>'
				. '</tr>';
		}
		
		echo '</table>' . "\n";
		
		echo '<p class="submit">'
			. '<input type="submit" value="' . 'Clone Site' . '" />' . '</p>' . "\n";
	} # start()
	
	
	#
	# exec()
	#
	
	function exec()
	{
		if ( !$_POST ) return false;
		
		check_admin_referer('sem_cloner');
		
		foreach ( array('site_url', 'site_key') as $field )
		{
			if ( !isset($_POST[$field]) || !$_POST[$field] )
			{
				echo '<div class="error">'
					. '<p>'
					. 'Please fill in all of the fields.'
					. '</p>'
					. '</div>' . "\n";
				
				return false;
			}
			
			$$field = stripslashes($_POST[$field]);
		}
		
		if ( $site_key == get_option('sem_cloner_key') )
		{
			unset($_POST['site_key']);
			
			echo '<div class="error">'
				. '<p>'
				. 'You\'ve filled this site\'s key, rather than the site key of the site that you wish to clone.'
				. '</p>'
				. '</div>' . "\n";
			
			return false;
		}
		
		$url = $site_url
			. '?method=sem_cloner'
			. '&key=' . urlencode($site_key)
			. '&data=';
		
		foreach ( array('version', 'options') as $data )
		{
			$$data = wp_remote_fopen($url . $data);
			
			if ( is_wp_error($$data) )
			{
				echo '<div class="error">'
					. '<p>' . $$data->get_error_message() . '</p>'
					. '</div>' . "\n";
				
				return false;
			}
			
			if ( preg_match("|<error>(.*)</error>|", $$data, $match) )
			{
				$msg = strip_tags($match[1]);
				
				echo '<div class="error">'
					. '<p>' . $msg . '</p>'
					. '<p>' . 'Please make sure that the site you are seeking to clone is using the same version of Semiologic Cloner as this one.' . '</p>'
					. '</div>' . "\n";
				
				return false;
			}
			
			if ( strpos($$data, "<data>") === false )
			{
				echo '<div class="error">'
					. '<p>' . 'Invalid data was returned. Please make sure the Semiologic Cloner plugin is active on the site you are seeking to clone.' . '</p>'
					. '</div>' . "\n";
				
				return false;
			}

			$$data = preg_replace("/^.*<data>|<\/data>.*$/", '', $$data);
			$$data = @base64_decode($$data);
			#if ( $data == 'options' ) { dump(htmlspecialchars($$data)); die; }
			$$data = 'return ' . $$data . ';';
			$$data = eval($$data);
			
			if ( $$data === false ) return false;
			
			switch ( $data )
			{
			case 'version':
				break;
				if ( $$data != sem_cloner_version )
				{
					echo '<div class="error">'
						. '<p>' . 'Version mismatch. Please make sure the site you are cloning is running the same version of Semiologic Cloner as this one.' . '</p>'
						. '</div>' . "\n";
					
					return false;
				}
				break;
			
			case 'options':
				foreach ( $$data as $option_name => $option_value )
				{
					switch ( $option_name )
					{
					case 'mediacaster':
						$old_value = get_option($option_name);
						$option_value['itunes'] = $old_value['itunes'];
						update_option($option_name, $option_value);
						break;

					case 'sem_seo':
						$old_value = get_option($option_name);
						foreach ( array('title', 'keywords', 'description') as $var )
						{
							$option_value[$var] = $old_value[$var];
						}
						update_option($option_name, $option_value);
						break;

					case 'sem_api_key':
						if ( !get_option('sem_api_key') )
						{
							update_option($option_name, $option_value);
						}
						break;

					default:
						update_option($option_name, $option_value);
						break;
					}

					if ( $plugins = get_option('active_plugins') )
					{
						$old_plugin_page = $GLOBALS['plugin_page'];
						unset($GLOBALS['plugin_page']);

						foreach ( $plugins as $plugin )
						{
							if ( $plugin && file_exists(ABSPATH . PLUGINDIR . '/' . $plugin) )
							{
								include_once(ABSPATH . PLUGINDIR . '/' . $plugin);
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
		if ( function_exists('wp_cache_disable') ) wp_cache_disable();
		
		return true;
	} # exec()
} # sem_cloner_admin

sem_cloner_admin::init();
?>