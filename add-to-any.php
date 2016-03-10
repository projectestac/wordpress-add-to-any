<?php
/*
Plugin Name: AddToAny Share Buttons
Plugin URI: https://www.addtoany.com/
Description: Share buttons for your pages including AddToAny's universal sharing button, Facebook, Twitter, Google+, Pinterest, WhatsApp and many more.  [<a href="options-general.php?page=addtoany">Settings</a>]
Version: 1.6.14
Author: AddToAny
Author URI: https://www.addtoany.com/
Text Domain: add-to-any
Domain Path: /languages
*/

// Explicitly globalize to support bootstrapped WordPress
global $_addtoany_counter, $_addtoany_init, $_addtoany_targets, $A2A_locale, $A2A_FOLLOW_services,
	$A2A_SHARE_SAVE_plugin_basename, $A2A_SHARE_SAVE_options, $A2A_SHARE_SAVE_plugin_dir, $A2A_SHARE_SAVE_plugin_url_path, $A2A_SHARE_SAVE_services;

$A2A_SHARE_SAVE_plugin_basename = plugin_basename( dirname( __FILE__ ) );
$A2A_SHARE_SAVE_plugin_dir = untrailingslashit( plugin_dir_path( __FILE__ ) );
$A2A_SHARE_SAVE_plugin_url_path = untrailingslashit( plugin_dir_url( __FILE__ ) );

// HTTPS?
$A2A_SHARE_SAVE_plugin_url_path = is_ssl() ? str_replace( 'http:', 'https:', $A2A_SHARE_SAVE_plugin_url_path ) : $A2A_SHARE_SAVE_plugin_url_path;
// Set AddToAny locale (JavaScript)
$A2A_locale = ! isset ( $A2A_locale ) ? '' : $A2A_locale;
// Set plugin options
$A2A_SHARE_SAVE_options = get_option( 'addtoany_options' );

include_once( $A2A_SHARE_SAVE_plugin_dir . '/addtoany.compat.php' );
include_once( $A2A_SHARE_SAVE_plugin_dir . '/addtoany.services.php' );

function A2A_SHARE_SAVE_init() {
	global $A2A_SHARE_SAVE_plugin_url_path,
		$A2A_SHARE_SAVE_plugin_basename, 
		$A2A_SHARE_SAVE_options;
	
	if ( get_option( 'A2A_SHARE_SAVE_button' ) ) {
		A2A_SHARE_SAVE_migrate_options();
		$A2A_SHARE_SAVE_options = get_option( 'addtoany_options' );
	}
	
	load_plugin_textdomain( 'add-to-any', false, $A2A_SHARE_SAVE_plugin_basename . '/languages/' );
}
add_filter( 'init', 'A2A_SHARE_SAVE_init' );

function A2A_SHARE_SAVE_link_vars( $linkname = false, $linkurl = false ) {
	global $post;
	
	// Set linkname
	if ( ! $linkname ) {
		if ( isset( $post ) ) {
			$linkname = html_entity_decode( strip_tags( get_the_title( $post->ID ) ), ENT_QUOTES, 'UTF-8' );
		}
		else {
			$linkname = '';
		}
	}
	
	$linkname_enc = rawurlencode( $linkname );
	
	// Set linkurl
	if ( ! $linkurl ) {
		if ( isset( $post ) ) {
			$linkurl = get_permalink( $post->ID );
		}
		else {
			$linkurl = '';
		}
	}
	
	$linkurl_enc = rawurlencode( $linkurl );
	
	return compact( 'linkname', 'linkname_enc', 'linkurl', 'linkurl_enc' );
}

// Combine ADDTOANY_SHARE_SAVE_ICONS and ADDTOANY_SHARE_SAVE_BUTTON
function ADDTOANY_SHARE_SAVE_KIT( $args = array() ) {
	global $_addtoany_counter;
	
	$_addtoany_counter++;
	
	$options = get_option( 'addtoany_options' );
	
	$defaults = array(
		'output_later'         => false,
		'icon_size'			   => isset( $options['icon_size'] ) ? $options['icon_size'] : '',
	);
	
	$args = wp_parse_args( $args, $defaults );
	extract( $args );
	
	// If universal button disabled, and not manually disabled through args
	if ( isset( $options['button'] ) && $options['button'] == 'NONE' && ! isset( $args['no_universal_button'] ) ) {
		// Pass this setting on to ADDTOANY_SHARE_SAVE_BUTTON
		// (and only via this ADDTOANY_SHARE_SAVE_KIT function because it is used for automatic placement)
		$args['no_universal_button'] = true;
	}
	
	// Custom icons enabled?
	$custom_icons = ( isset( $options['custom_icons'] ) && $options['custom_icons'] == 'url' && isset( $options['custom_icons_url'] ) ) ? true : false;

	$kit_additional_classes = '';
	$kit_style = '';
	
	// Add additional classNames to .a2a_kit
	if ( isset( $args['kit_additional_classes'] ) ) {
		// Append space and className(s)
		$kit_additional_classes .= ' ' . $args['kit_additional_classes'];
	}
	
	// Set a2a_kit_size_## class name unless "icon_size" is set to '16'
	if ( $custom_icons ) {
		// If vertical style (.a2a_vertical_style)
		if ( strpos( $kit_additional_classes, 'a2a_vertical_style' ) !== false ) {
			// Use width (if specified) for .a2a_kit_size_## class name to size default service counters
			$icon_size_classname = isset( $options['custom_icons_width'] ) ? ' a2a_kit_size_' . $options['custom_icons_width'] : '';
		} else {
			// Use height (if specified) for .a2a_kit_size_## class name to size default service counters
			$icon_size_classname = isset( $options['custom_icons_height'] ) ? ' a2a_kit_size_' . $options['custom_icons_height'] : '';
		}
	// a2a_kit_size_32 if no icon size, or no_small_icons arg is true
	} elseif ( ! isset( $icon_size ) || isset( $args['no_small_icons'] ) && true == $args['no_small_icons'] ) {
		$icon_size_classname = ' a2a_kit_size_32';
	// a2a_kit_size_16
	} elseif ( isset( $icon_size ) && $icon_size == '16' ) {
		$icon_size_classname = '';
	// a2a_kit_size_## custom icon size
	} elseif ( isset( $icon_size ) ) {
		$icon_size_classname = ' a2a_kit_size_' . $icon_size;
	}
	
	// Add addtoany_list className unless disabled (for floating buttons)
	if ( ! isset( $args['no_addtoany_list_classname'] ) ) {
		$kit_additional_classes .= ' addtoany_list';
	}
	
	// Add style attribute if set
	if ( isset( $args['kit_style'] ) ) {
		$kit_style = ' style="' . $args['kit_style'] . '"';
	}
	
	if ( ! isset( $args['html_container_open'] ) ) {
		$args['html_container_open'] = '<div class="a2a_kit' . $icon_size_classname . $kit_additional_classes . ' a2a_target"';
		$args['html_container_open'] .= ' id="wpa2a_' . $_addtoany_counter . '"'; // ID is later removed by JS (for AJAX)
		$args['html_container_open'] .= $kit_style;
		$args['html_container_open'] .= '>';
		$args['is_kit'] = true;
	}
	if ( ! isset( $args['html_container_close'] ) )
		$args['html_container_close'] = "</div>";
	// Close container element in ADDTOANY_SHARE_SAVE_BUTTON, not prematurely in ADDTOANY_SHARE_SAVE_ICONS
	$html_container_close = $args['html_container_close']; // Cache for _BUTTON
	unset($args['html_container_close']); // Avoid passing to ADDTOANY_SHARE_SAVE_ICONS since set in _BUTTON
				
	if ( ! isset( $args['html_wrap_open'] ) )
		$args['html_wrap_open'] = "";
	if ( ! isset( $args['html_wrap_close'] ) )
		$args['html_wrap_close'] = "";
	
	$kit_html = ADDTOANY_SHARE_SAVE_ICONS( $args );
	
	$args['html_container_close'] = $html_container_close; // Re-set because unset above for _ICONS
	unset( $args['html_container_open'] ); // Avoid passing to ADDTOANY_SHARE_SAVE_BUTTON since set in _ICONS
	
	$kit_html .= ADDTOANY_SHARE_SAVE_BUTTON( $args );
	
	if ( true == $output_later )
		return $kit_html;
	else
		echo $kit_html;
}

function ADDTOANY_SHARE_SAVE_ICONS( $args = array() ) {
	// $args array: output_later, html_container_open, html_container_close, html_wrap_open, html_wrap_close, linkname, linkurl
	
	global $A2A_SHARE_SAVE_plugin_url_path, 
		$A2A_SHARE_SAVE_services,
		$A2A_FOLLOW_services;
	
	$options = get_option( 'addtoany_options' );
	
	$linkname = ( isset( $args['linkname'] ) ) ? $args['linkname'] : FALSE;
	$linkurl = ( isset( $args['linkurl'] ) ) ? $args['linkurl'] : FALSE;
	
	$args = array_merge( $args, A2A_SHARE_SAVE_link_vars( $linkname, $linkurl ) ); // linkname_enc, etc.
	
	$defaults = array(
		'linkname'             => '',
		'linkurl'              => '',
		'linkname_enc'         => '',
		'linkurl_enc'          => '',
		'output_later'         => false,
		'html_container_open'  => '',
		'html_container_close' => '',
		'html_wrap_open'       => '',
		'html_wrap_close'      => '',
		'icon_size'			   => isset( $options['icon_size'] ) ? $options['icon_size'] : '',
		'is_follow'            => false,
		'no_universal_button'  => false,
		'buttons'              => array(),
	);
	
	$args = wp_parse_args( $args, $defaults );
	extract( $args );
	
	$is_amp = function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ? true : false;
	
	// False if "icon_size" is set to '16', or no_small_icons arg is true
	$large_icons = ( isset( $icon_size ) && $icon_size == '16' 
		&& ( ! isset( $no_small_icons ) || false == $no_small_icons )
	) ? false : true;
	// False if AMP endpoint
	$large_icons = $is_amp ? false : $large_icons;
	
	// Directory of either custom icons or the packaged icons
	if ( isset( $options['custom_icons'] ) && $options['custom_icons'] == 'url' && isset( $options['custom_icons_url'] ) ) {
		// Custom icons expected at a specified URL, i.e. //example.com/blog/uploads/addtoany/icons/custom/
		$icons_dir = $options['custom_icons_url'];
		$icons_type = ( isset( $options['custom_icons_type'] ) ) ? $options['custom_icons_type'] : 'png';
		$icons_width = ( isset( $options['custom_icons_width'] ) ) ? $options['custom_icons_width'] : '';
		$icons_height = ( isset( $options['custom_icons_height'] ) ) ? $options['custom_icons_height'] : '';
		$custom_icons = true;
	} else {
		// Packaged 16px icons
		$icons_dir = $A2A_SHARE_SAVE_plugin_url_path . '/icons/';
		$icons_type = 'png';
	}
	
	// If Follow kit
	if ( $is_follow ) {
		// Make available services extensible via plugins, themes (functions.php), etc.
		$services = apply_filters( 'A2A_FOLLOW_services', $A2A_FOLLOW_services );
		$service_codes = ( is_array( $services ) ) ? array_keys( $services ) : array();
		
		// Services set by "buttons" arg
		$active_services = ! empty ( $buttons ) ? array_keys( $buttons ) : array();
	// Else Share kit
	} else {
		// Make available services extensible via plugins, themes (functions.php), etc.
		$services = apply_filters( 'A2A_SHARE_SAVE_services', $A2A_SHARE_SAVE_services );
		$service_codes = ( is_array( $services ) ) ? array_keys( $services ) : array();
		
		// Include Facebook Like and Twitter Tweet etc. unless no_special_services arg is true
		if ( ! isset( $no_special_services ) || false == $no_special_services ) {
			array_unshift( $service_codes, 'facebook_like', 'twitter_tweet', 'google_plusone', 'google_plus_share', 'pinterest_pin' );
		}
	
		// Use default services if services have not been selected yet
		$active_services = ( isset( $options['active_services'] ) ) ? $options['active_services'] : array( 'facebook', 'twitter', 'google_plus' );
		// Services set by "buttons" arg? Then use "buttons" arg instead
		$active_services = ! empty ( $buttons ) ? $buttons : $active_services;
	}
	
	$ind_html = "" . $html_container_open;
	
	foreach( $active_services as $active_service ) {
		
		$custom_service = false;
		
		if ( ! in_array( $active_service, $service_codes ) )
			continue;

		if ( $active_service == 'facebook_like' || $active_service == 'twitter_tweet' || $active_service == 'google_plusone' || $active_service == 'google_plus_share' || $active_service == 'pinterest_pin' ) {
			$special_args = $args;
			$special_args['output_later'] = true;
			$link = ADDTOANY_SHARE_SAVE_SPECIAL( $active_service, $special_args );
		} else {
			$service = $services[ $active_service ];
			$safe_name = $active_service;
			$name = $service['name'];
			
			// If Follow kit and HREF specified
			if ( $is_follow && isset( $service['href'] ) ) {
				$follow_id = $buttons[ $active_service ]['id'];
				if ( 'feed' == $safe_name ) {
					// For "feed" service, stored ID value is actually the URL
					$href = $follow_id;
				} else {
					// For all other services, replace
					$href = str_replace( '${id}', $follow_id, $service['href'] );
				}
				$href = ( 'feed' == $safe_name ) ? $follow_id : $href;
				
				// If icon_url is set, presume custom service
				if ( isset( $service['icon_url'] ) ) {
					$custom_service = true;
				}
			// Else if Share Kit and HREF specified, presume custom service
			} elseif ( isset( $service['href'] ) ) {
				$custom_service = true;
				$href = $service['href'];
				if ( isset( $service['href_js_esc'] ) ) {
					$href_linkurl = str_replace( "'", "\'", $linkurl );
					$href_linkname = str_replace( "'", "\'", $linkname );
				} else {
					$href_linkurl = $linkurl_enc;
					$href_linkname = $linkname_enc;
				}
				$href = str_replace( "A2A_LINKURL", $href_linkurl, $href );
				$href = str_replace( "A2A_LINKNAME", $href_linkname, $href );
				$href = str_replace( " ", "%20", $href );
			}
			
			// AddToAny counter enabled?
			$counter_enabled = ( ! $is_follow // Disable counters on Follow Kits
				&& in_array( $active_service, array( 'facebook', 'pinterest', 'linkedin', 'reddit' ) )
				&& isset( $options['special_' . $active_service . '_options'] )
				&& isset( $options['special_' . $active_service . '_options']['show_count'] ) 
				&& $options['special_' . $active_service . '_options']['show_count'] == '1' 
			) ? true : false;
			
			$icon = isset( $service['icon'] ) ? $service['icon'] : 'default'; // Just the icon filename
			$icon_url = isset( $service['icon_url'] ) ? $service['icon_url'] : false;
			$icon_url = $is_amp && ! $icon_url ? 'https://static.addtoany.com/buttons/' . $icon . '.svg' : $icon_url;
			$width_attr = isset( $service['icon_width'] ) ? ' width="' . $service['icon_width'] . '"' : ' width="16"';
			$width_attr = $is_amp && isset( $icon_size ) ? ' width="' . $icon_size . '"' : $width_attr;
			$height_attr = isset( $service['icon_height'] ) ? ' height="' . $service['icon_height'] . '"' : ' height="16"';
			$height_attr = $is_amp && isset( $icon_size ) ? ' height="' . $icon_size . '"' : $height_attr;
			
			$url = ( isset( $href ) ) ? $href : "http://www.addtoany.com/add_to/" . $safe_name . "?linkurl=" . $linkurl_enc . "&amp;linkname=" . $linkname_enc;
			$src = ( $icon_url ) ? $icon_url : $icons_dir . $icon . '.' . $icons_type;
			$counter = ( $counter_enabled ) ? ' a2a_counter' : '';
			$class_attr = ( $custom_service ) ? '' : ' class="a2a_button_' . $safe_name . $counter . '"';
			$rel_nofollow = $is_follow ? '' : ' rel="nofollow"'; // ($is_follow indicates a Follow Kit. 'nofollow' is for search crawlers. Different things)
			
			// Set dimension attributes if using custom icons and dimension is specified
			if ( isset( $custom_icons ) ) {
				$width_attr = ! empty( $icons_width ) ? ' width="' . $icons_width . '"' : '';
				$height_attr = ! empty( $icons_height ) ? ' height="' . $icons_height . '"' : '';
			}
			
			$link = $html_wrap_open . "<a$class_attr href=\"$url\" title=\"$name\"$rel_nofollow target=\"_blank\">";
			$link .= ( $large_icons && ! isset( $custom_icons ) && ! $custom_service ) ? "" : "<img src=\"$src\"" . $width_attr . $height_attr . " alt=\"$name\"/>";
			$link .= "</a>" . $html_wrap_close;
		}
		
		$ind_html .= $link;
	}
	
	$ind_html .= $html_container_close;
	
	if ( true == $output_later )
		return $ind_html;
	else
		echo $ind_html;
}

function ADDTOANY_SHARE_SAVE_BUTTON( $args = array() ) {
	
	// $args array = output_later, html_container_open, html_container_close, html_wrap_open, html_wrap_close, linkname, linkurl, no_universal_button

	global $A2A_SHARE_SAVE_plugin_url_path, $_addtoany_targets, $_addtoany_counter, $_addtoany_init;
	
	$options = get_option( 'addtoany_options' );
	
	$linkname = (isset($args['linkname'])) ? $args['linkname'] : false;
	$linkurl = (isset($args['linkurl'])) ? $args['linkurl'] : false;
	$_addtoany_targets = ( isset( $_addtoany_targets ) ) ? $_addtoany_targets : array();

	$args = array_merge($args, A2A_SHARE_SAVE_link_vars($linkname, $linkurl)); // linkname_enc, etc.
	
	$defaults = array(
		'linkname' => '',
		'linkurl' => '',
		'linkname_enc' => '',
		'linkurl_enc' => '',
		'use_current_page' => false,
		'output_later' => false,
		'is_kit' => false,
		'html_container_open' => '',
		'html_container_close' => '',
		'html_wrap_open' => '',
		'html_wrap_close' => '',
		'icon_size'	=> isset( $options['icon_size'] ) ? $options['icon_size'] : '',
		'no_small_icons' => false,
		'no_universal_button' => false,
	);
	
	$args = wp_parse_args( $args, $defaults );
	extract( $args );
	
	// If not enclosed in an AddToAny Kit, count & target this button (instead of Kit) for async loading
	if ( ! $args['is_kit'] ) {
		$_addtoany_counter++;
		$button_class = ' a2a_target';
		$button_id = ' id="wpa2a_' . $_addtoany_counter . '"';  // ID is later removed by JS (for AJAX)
	} else {
		$button_class = '';
		$button_id = '';
	}
	
	/* AddToAny button */
	
	$is_feed = is_feed();
	$is_amp = function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ? true : false;
	$button_target = $is_amp ? ' target="_blank"' : '';
	$button_href_querystring = ($is_feed || $is_amp) ? '#url=' . $linkurl_enc . '&amp;title=' . $linkname_enc : '';
	
	// If universal button is enabled
	if ( ! $args['no_universal_button'] ) {
	
		if ( ! isset( $options['button'] ) || 'A2A_SVG_32' == $options['button'] 
			// Or, no_small_icons is true and a custom universal icon is not enabled (permitting a custom universal button in floating bar) 
			|| isset( $no_small_icons ) && true == $no_small_icons && ( ! isset( $options['button'] ) || 'CUSTOM' != $options['button'] )
		) {
			// If AMP (Accelerated Mobile Page)
			if ( $is_amp ) {
				$button_src    = 'https://static.addtoany.com/buttons/a2a.svg';
				$button_width  = isset( $icon_size ) ? ' width="' . $icon_size .'"'  : ' width="32"';
				$button_height = isset( $icon_size ) ? ' height="' . $icon_size .'"'  : ' height="32"';
			} else {
				// Skip button IMG for A2A icon insertion
				$button_text = '';	
			}
		} else if ( isset( $options['button'] ) && 'CUSTOM' == $options['button'] ) {
			$button_src		= $options['button_custom'];
			$button_width	= '';
			$button_height	= '';
		} else if ( isset( $options['button'] ) && 'TEXT' == $options['button'] ) {
			$button_text	= stripslashes( $options[ 'button_text'] );
			// Do not display universal icon (when large icons are used)
			$button_class  .= ' addtoany_no_icon';
		} else {
			$button_attrs	= explode( '|', $options['button'] );
			$button_fname	= $button_attrs[0];
			$button_width	= ' width="' . $button_attrs[1] . '"';
			$button_height	= ' height="' . $button_attrs[2] . '"';
			$button_src		= $A2A_SHARE_SAVE_plugin_url_path . '/' . $button_fname;
			$button_text	= ( isset( $options['button_text'] ) ) ? stripslashes( $options['button_text'] ) : 'Share' ;
		}
		
		$style = '';
		
		if ( isset( $button_fname ) && ( $button_fname == 'favicon.png' || $button_fname == 'share_16_16.png' ) ) {
			if ( ! $is_feed ) {
				$style_bg	= 'background:url(' . $A2A_SHARE_SAVE_plugin_url_path . '/' . $button_fname . ') no-repeat scroll 4px 0px;';
				$style		= ' style="' . $style_bg . 'padding:0 0 0 25px;display:inline-block;height:16px;vertical-align:middle"'; // padding-left:21+4 (4=other icons padding)
				
				// Wrap in <span> to avoid showing the core-AddToAny Kit icon in addition to plugin's icon
				$button_text = ( isset( $button_text ) ) ? '<span>' . $button_text . '</span>' : '<span></span>';
			}
		}
		
		if ( isset( $button_text ) && ( ! isset( $button_fname) || ! $button_fname || $button_fname == 'favicon.png' || $button_fname == 'share_16_16.png' ) ) {
			$button = $button_text;
		} else {
			$style = '';
			$button	= '<img src="' . $button_src . '"' . $button_width . $button_height . ' alt="Share"/>';
		}
		
		if ( isset( $options['button_show_count'] ) && $options['button_show_count'] == '1' ) {
			$button_class .= ' a2a_counter';
		}
		
		$button_html = $html_container_open . $html_wrap_open . '<a class="a2a_dd' . $button_class . ' addtoany_share_save" href="https://www.addtoany.com/share' .$button_href_querystring . '"' . $button_id
			. $style . $button_target
			. '>' . $button . '</a>';
	
	} else {
		// Universal button disabled
		$button_html = '';
	}
	
	// Hook to disable script output
	// Example: add_filter( 'addtoany_script_disabled', '__return_true' );
	$script_disabled = apply_filters( 'addtoany_script_disabled', false );
	// Doing AJAX? (the DOING_AJAX constant can be unreliable)
	$ajax = ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) ? true : false;
	
	// If not a feed and script is not disabled,
	// and not admin (unless doing AJAX, probably through admin-ajax.php)
	if ( ! $is_feed && ! $script_disabled && ( ! is_admin() || $ajax ) ) {
		if ($use_current_page) {
			$button_config = "\n{title:document.title,"
				. "url:location.href}";
			$_addtoany_targets[] = $button_config;
		} else {
			// wp_json_encode available since 4.1
			$linkname_sanitized = function_exists( 'wp_json_encode' ) ? wp_json_encode ( $linkname ) : json_encode( $linkname );
			$button_config = "\n{title:". $linkname_sanitized . ','
				. 'url:"' . $linkurl . '"}';
			$_addtoany_targets[] = $button_config;
		}
		
		// If doing AJAX (the DOING_AJAX constant can be unreliable)
		if ( $ajax ) {
			$javascript_button_config = "<script type=\"text/javascript\"><!--\n"
				. "if(wpa2a.targets)wpa2a.targets.push("
					. $button_config
				. ");\n";
			
			if ( ! $_addtoany_init ) {
				// Catch post-load event to support infinite scroll (and more?)
				$javascript_button_config .= "\nif('function'===typeof(jQuery))"
					. "jQuery(document).ready(function($){"
						. "$('body').on('post-load',function(){"
							. "if(wpa2a.script_ready)wpa2a.init();"
							. "wpa2a.script_load();" // Load external script if not already called
						. "});"
					. "});";
			}
			
			$javascript_button_config .= "\n//--></script>\n";
		} else {
			$javascript_button_config = '';
		}
		
		if ( ! $_addtoany_init ) {
			$javascript_load_early = "\n<script type=\"text/javascript\"><!--\n"
				. "if(wpa2a)wpa2a.script_load();"
				. "\n//--></script>\n";
		} else {
			$javascript_load_early = "";
		}
		
		$button_html .= $javascript_load_early . $javascript_button_config;
		$_addtoany_init = true;
	}
	
	// Closing tags come after <script> to validate in case the container is a list element
	$button_html .= $html_wrap_close . $html_container_close;
	
	if ( isset( $output_later ) && $output_later == true )
		return $button_html;
	else
		echo $button_html;
}

function ADDTOANY_SHARE_SAVE_SPECIAL( $special_service_code, $args = array() ) {
	// $args array = output_later, linkname, linkurl
	
	if ( is_feed() ) {
		return;
	}
	
	$options = get_option( 'addtoany_options' );
	
	$linkname = ( isset( $args['linkname'] ) ) ? $args['linkname'] : FALSE;
	$linkurl = ( isset( $args['linkurl'] ) ) ? $args['linkurl'] : FALSE;
	
	$args = array_merge( $args, A2A_SHARE_SAVE_link_vars( $linkname, $linkurl ) ); // linkname_enc, etc.
	extract( $args );
	
	$special_anchor_template = '<a class="a2a_button_%1$s addtoany_special_service"%2$s></a>';
	$custom_attributes = '';
	
	if ( $special_service_code == 'facebook_like' ) {
		$custom_attributes .= ( $options['special_facebook_like_options']['verb'] == 'recommend' ) ? ' data-action="recommend"' : '';
		$custom_attributes .= ' data-href="' . $linkurl . '"';
		$special_html = sprintf( $special_anchor_template, $special_service_code, $custom_attributes );
	}
	
	elseif ( $special_service_code == 'twitter_tweet' ) {
		$custom_attributes .= ' data-url="' . $linkurl . '"';
		$custom_attributes .= ' data-text="' . $linkname . '"';
		$special_html = sprintf( $special_anchor_template, $special_service_code, $custom_attributes );
	}
	
	elseif ( $special_service_code == 'google_plusone' ) {
		$custom_attributes .= ( $options['special_google_plusone_options']['show_count'] == '1' ) ? '' : ' data-annotation="none"';
		$custom_attributes .= ' data-href="' . $linkurl . '"';
		$special_html = sprintf( $special_anchor_template, $special_service_code, $custom_attributes );
	}
	
	elseif ( $special_service_code == 'google_plus_share' ) {
		$custom_attributes .= ( $options['special_google_plus_share_options']['show_count'] == '1' ) ? '' : ' data-annotation="none"';
		$custom_attributes .= ' data-href="' . $linkurl . '"';
		$special_html = sprintf( $special_anchor_template, $special_service_code, $custom_attributes );
	}
	
	elseif ( $special_service_code == 'pinterest_pin' ) {
		$custom_attributes .= ( $options['special_pinterest_pin_options']['show_count'] == '1' ) ? '' : ' data-pin-config="none"';
		$custom_attributes .= ' data-url="' . $linkurl . '"';
		$special_html = sprintf( $special_anchor_template, $special_service_code, $custom_attributes );
	}
	
	if ( isset( $output_later ) && $output_later == true )
		return $special_html;
	else
		echo $special_html;
}

if ( ! function_exists( 'A2A_menu_locale' ) ) {
	function A2A_menu_locale() {
		global $A2A_locale;
		$locale = get_locale();
		if ( $locale == 'en_US' || $locale == 'en' || $A2A_locale != '' )
			return false;
			
		$A2A_locale = 'a2a_localize = {
	Share: "' . __( "Share", 'add-to-any' ) . '",
	Save: "' . __( "Save", 'add-to-any' ) . '",
	Subscribe: "' . __( "Subscribe", 'add-to-any' ) . '",
	Email: "' . __( "Email", 'add-to-any' ) . '",
	Bookmark: "' . __( "Bookmark", 'add-to-any' ) . '",
	ShowAll: "' . __( "Show all", 'add-to-any' ) . '",
	ShowLess: "' . __( "Show less", 'add-to-any' ) . '",
	FindServices: "' . __( "Find service(s)", 'add-to-any' ) . '",
	FindAnyServiceToAddTo: "' . __( "Instantly find any service to add to", 'add-to-any' ) . '",
	PoweredBy: "' . __( "Powered by", 'add-to-any' ) . '",
	ShareViaEmail: "' . __( "Share via email", 'add-to-any' ) . '",
	SubscribeViaEmail: "' . __( "Subscribe via email", 'add-to-any' ) . '",
	BookmarkInYourBrowser: "' . __( "Bookmark in your browser", 'add-to-any' ) . '",
	BookmarkInstructions: "' . __( "Press Ctrl+D or \u2318+D to bookmark this page", 'add-to-any' ) . '",
	AddToYourFavorites: "' . __( "Add to your favorites", 'add-to-any' ) . '",
	SendFromWebOrProgram: "' . __( "Send from any email address or email program", 'add-to-any' ) . '",
	EmailProgram: "' . __( "Email program", 'add-to-any' ) . '",
	More: "' . __( "More&#8230;", 'add-to-any' ) . '"
};
';
		return $A2A_locale;
	}
}

function ADDTOANY_FOLLOW_KIT( $args = array() ) {
	// Args are passed on to ADDTOANY_SHARE_SAVE_KIT
	$defaults = array(
		'linkname' => '',
		'linkurl' => '',
		'linkname_enc' => '',
		'linkurl_enc' => '',
		'use_current_page' => true,
		'output_later' => false,
		'is_follow' => true,
		'is_kit' => true,
		'no_special_services' => true,
		'no_universal_button' => true,
		//'no_small_icons' => true,
		'kit_additional_classes' => '',
		'kit_style' => '',
		'services' => array(),
	);
	
	$args = wp_parse_args( $args, $defaults );
	
	// Add a2a_follow className to Kit classes
	$args['kit_additional_classes'] = 'a2a_follow';
	
	$follow_html = ADDTOANY_SHARE_SAVE_KIT( $args );
	
	if ( isset( $args['output_later'] ) && $args['output_later'] == true )
		return $follow_html;
	else
		echo $follow_html;
}

function ADDTOANY_SHARE_SAVE_FLOATING( $args = array() ) {
	$options = get_option( 'addtoany_options' );
	
	$floating_html = '';

	// Overridable by args below
	$vertical_type = ( isset( $options['floating_vertical'] ) && 'none' != $options['floating_vertical'] ) ? $options['floating_vertical'] : false;
	$horizontal_type = ( isset( $options['floating_horizontal'] ) && 'none' != $options['floating_horizontal'] ) ? $options['floating_horizontal'] : false;

	if ( is_singular() ) {
		// Disabled for this singular post?
		$sharing_disabled = get_post_meta( get_the_ID(), 'sharing_disabled', true );
		$sharing_disabled = apply_filters( 'addtoany_sharing_disabled', $sharing_disabled );
		
		if ( ! empty( $sharing_disabled ) ) {
			// Overridable by args below
			$vertical_type   = false;
			$horizontal_type = false;
		}
	}

	// Args are passed on to ADDTOANY_SHARE_SAVE_KIT
	$defaults = array(
		'linkname' => '',
		'linkurl' => '',
		'linkname_enc' => '',
		'linkurl_enc' => '',
		'use_current_page' => true,
		'output_later' => false,
		'is_floating' => true,
		'is_kit' => true,
		'no_addtoany_list_classname' => true,
		'no_special_services' => true,
		'no_small_icons' => true,
		'kit_additional_classes' => '',
		'kit_style' => '',
	);
	
	$args = wp_parse_args( $args, $defaults );
	
	// Individual floating type args can override saved options
	if ( isset( $args['vertical_type'] ) && $args['vertical_type'] === true ) {
		$vertical_type = true;
	}
	if ( isset( $args['horizontal_type'] ) && $args['horizontal_type'] === true ) {
		$horizontal_type = true;
	}

	// If either floating type is enabled
	// Expect either a string from options, or a boolean from args
	if ( $vertical_type || $horizontal_type ) {
		// Vertical type?
		if ( $vertical_type ) {
			// Top position
			$position = ( isset( $options['floating_vertical_position'] ) ) ? $options['floating_vertical_position'] . 'px' : '100px';
			// Left or right offset
			$offset = ( isset( $options['floating_vertical_offset'] ) ) ? $options['floating_vertical_offset'] . 'px' : '0px';
		
			// Add a2a_vertical_style className to Kit classes
			$args['kit_additional_classes'] = 'a2a_floating_style a2a_vertical_style';
			
			// Add declarations to Kit style attribute
			if ( 'left_docked' === $vertical_type ) {
				$args['kit_style'] = 'left:' . $offset . ';top:' . $position . ';';
			} elseif ( 'right_docked' === $vertical_type ) {
				$args['kit_style'] = 'right:' . $offset . ';top:' . $position . ';';
			}
			
			$floating_html .= ADDTOANY_SHARE_SAVE_KIT( $args );
		}
		
		// Horizontal type?
		if ( $horizontal_type ) {
			// Left or right position
			$position = ( isset( $options['floating_horizontal_position'] ) ) ? $options['floating_horizontal_position'] . 'px' : '0px';
			// Bottom offset
			$offset = ( isset( $options['floating_horizontal_offset'] ) ) ? $options['floating_horizontal_offset'] . 'px' : '0px';

			// Add a2a_default_style className to Kit classes
			$args['kit_additional_classes'] = 'a2a_floating_style a2a_default_style';
			
			// Add declarations to Kit style attribute
			if ( 'left_docked' === $horizontal_type ) {
				$args['kit_style'] = 'bottom:' . $offset . ';left:' . $position . ';';
			} elseif ( 'right_docked' === $horizontal_type ) {
				$args['kit_style'] = 'bottom:' . $offset . ';right:' . $position . ';';
			}
			
			$floating_html .= ADDTOANY_SHARE_SAVE_KIT( $args );
		}
	}
	
	if ( isset( $args['output_later'] ) && $args['output_later'] == true )
		return $floating_html;
	else
		echo $floating_html;
}


function A2A_SHARE_SAVE_head_script() {
	// Hook to disable script output
	// Example: add_filter( 'addtoany_script_disabled', '__return_true' );
	$script_disabled = apply_filters( 'addtoany_script_disabled', false );
	
	if ( is_admin() || is_feed() || $script_disabled )
		return;
		
	$options = get_option( 'addtoany_options' );
	
	$http_or_https = ( is_ssl() ) ? 'https' : 'http';

	// Use local cache?
	$cache = ( isset( $options['cache'] ) && '1' == $options['cache'] ) ? true : false;
	$upload_dir = wp_upload_dir();
	$static_server = ( $cache ) ? $upload_dir['baseurl'] . '/addtoany' : $http_or_https . '://static.addtoany.com/menu';
	
	// Enternal script call + initial JS + set-once variables
	$additional_js = ( isset( $options['additional_js_variables'] ) ) ? $options['additional_js_variables'] : '' ;
	$script_configs = ( ( $cache ) ? "\n" . 'a2a_config.static_server="' . $static_server . '";' : '' )
		. ( ( isset( $options['onclick'] ) && '1' == $options['onclick'] ) ? "\n" . 'a2a_config.onclick=1;' : '' )
		. ( ( isset( $options['show_title'] ) && '1' == $options['show_title'] ) ? "\n" . 'a2a_config.show_title=1;' : '' )
		. ( ( $additional_js ) ? "\n" . stripslashes( $additional_js ) : '' );
	$A2A_SHARE_SAVE_external_script_called = true;
	
	$javascript_header = "\n" . '<script type="text/javascript">' . "<!--\n"
	
			. "var a2a_config=a2a_config||{},"
			. "wpa2a={done:false,"
			. "html_done:false,"
			. "script_ready:false,"
			. "script_load:function(){"
				. "var a=document.createElement('script'),"
					. "s=document.getElementsByTagName('script')[0];"
				. "a.type='text/javascript';a.async=true;"
				. "a.src='" . $static_server . "/page.js';"
				. "s.parentNode.insertBefore(a,s);"
				. "wpa2a.script_load=function(){};"
			. "},"
			. "script_onready:function(){"
				. "wpa2a.script_ready=true;"
				. "if(wpa2a.html_done)wpa2a.init();"
			. "},"
			. "init:function(){"
				. "for(var i=0,el,target,targets=wpa2a.targets,length=targets.length;i<length;i++){"
					. "el=document.getElementById('wpa2a_'+(i+1));"
					. "target=targets[i];"
					. "a2a_config.linkname=target.title;"
					. "a2a_config.linkurl=target.url;"
					. "if(el){"
						. "a2a.init('page',{target:el});"
						. "el.id='';" // Remove ID so AJAX can reuse the same ID
					. "}"
					. "wpa2a.done=true;"
				. "}"
				. "wpa2a.targets=[];" // Empty targets array so AJAX can reuse from index 0
			. "}"
		. "};"
		
		. "a2a_config.callbacks=a2a_config.callbacks||[];"
		. "a2a_config.callbacks.push({ready:wpa2a.script_onready});"
		. "a2a_config.templates=a2a_config.templates||{};"
		. A2A_menu_locale()
		. $script_configs
		
		. "\n//--></script>\n";
	
	 echo $javascript_header;
}

add_action( 'wp_head', 'A2A_SHARE_SAVE_head_script' );

function A2A_SHARE_SAVE_footer_script() {
	global $_addtoany_targets;
	
	// Hook to disable script output
	// Example: add_filter( 'addtoany_script_disabled', '__return_true' );
	$script_disabled = apply_filters( 'addtoany_script_disabled', false );
	
	if ( is_admin() || is_feed() || $script_disabled )
		return;
		
	$_addtoany_targets = ( isset( $_addtoany_targets ) ) ? $_addtoany_targets : array();
	
	$floating_html = ADDTOANY_SHARE_SAVE_FLOATING( array( 'output_later' => true ) );
	
	$javascript_footer = "\n" . '<script type="text/javascript">' . "<!--\n"
		. "wpa2a.targets=["
			. implode( ",", $_addtoany_targets )
		. "];\n"
		. "wpa2a.html_done=true;"
		. "if(wpa2a.script_ready&&!wpa2a.done)wpa2a.init();" // External script may load before html_done=true, but will only init if html_done=true.  So call wpa2a.init() if external script is ready, and if wpa2a.init() hasn't been called already.  Otherwise, wait for callback to call wpa2a.init()
		. "wpa2a.script_load();" // Load external script if not already called with the first AddToAny button.  Fixes issues where first button code is processed internally but without actual code output
		. "\n//--></script>\n";
	
	echo $floating_html . $javascript_footer;
}

add_action( 'wp_footer', 'A2A_SHARE_SAVE_footer_script' );


function A2A_SHARE_SAVE_add_to_content( $content ) {
	global $wp_current_filter;
	
	// Don't add to get_the_excerpt because it's too early and strips tags (adding to the_excerpt is allowed)
	if ( in_array( 'get_the_excerpt', (array) $wp_current_filter ) ) {
		// Return early
		return $content;
	}
	
	$sharing_disabled = get_post_meta( get_the_ID(), 'sharing_disabled', true );
	$sharing_disabled = apply_filters( 'addtoany_sharing_disabled', $sharing_disabled );
	
	if ( 
		// Private post
		get_post_status( get_the_ID() ) == 'private' ||
		// Sharing disabled on post
		! empty( $sharing_disabled ) 
	) {
		// Return early
		return $content;
	}
	
	$is_feed = is_feed();
	$options = get_option( 'addtoany_options' );
	$post_type = get_post_type( get_the_ID() );
	
	if ( 
		( 
			// Legacy tags
			// <!--sharesave--> tag
			strpos( $content, '<!--sharesave-->' ) === false || 
			// <!--nosharesave--> tag
			strpos( $content, '<!--nosharesave-->' ) !== false
		) &&
		(
			// Posts
			// All posts
			( is_singular('post') && isset( $options['display_in_posts'] ) && $options['display_in_posts'] == '-1' ) ||
			// Front page posts		
			( is_home() && isset( $options['display_in_posts_on_front_page'] ) && $options['display_in_posts_on_front_page'] == '-1' ) ||
			// Archive page posts (Category, Tag, Author and Date pages)
			( is_archive() && isset( $options['display_in_posts_on_archive_pages'] ) && $options['display_in_posts_on_archive_pages'] == '-1' ) ||
			// Search results posts (same as Archive page posts option)
			( is_search() && isset( $options['display_in_posts_on_archive_pages'] ) && $options['display_in_posts_on_archive_pages'] == '-1' ) || 
			// Excerpt (the_excerpt is the current filter)
			( 'the_excerpt' == current_filter() && isset( $options['display_in_excerpts'] ) && $options['display_in_excerpts'] == '-1' ) ||
			// Posts in feed
			( $is_feed && isset( $options['display_in_feed'] ) && $options['display_in_feed'] == '-1' ) ||
			
			// Custom post types
			( $post_type && isset( $options['display_in_cpt_' . $post_type] ) && $options['display_in_cpt_' . $post_type] == '-1' ) ||
			
			// Pages
			// Individual pages
			( is_page() && isset( $options['display_in_pages'] ) && $options['display_in_pages'] == '-1' ) ||
			// <!--nosharesave--> legacy tag
			( (strpos( $content, '<!--nosharesave-->') !== false ) )
		)
	) {
		// Return early
		return $content;
	}
	
	$kit_args = array(
		"output_later" => true,
		"is_kit" => ( $is_feed ) ? false : true,
	);
	
	// If a Sharing Header is set
	if ( isset( $options['header'] ) && '' != $options['header'] ) {
		$html_header = '<div class="addtoany_header">' . stripslashes( $options['header'] ) . '</div>';
	} else {
		$html_header = '';
	}
	
	if ( $is_feed ) {
		$container_wrap_open = '<p>';
		$container_wrap_close = '</p>';
		$kit_args['html_container_open'] = '';
		$kit_args['html_container_close'] = '';
		$kit_args['html_wrap_open'] = '';
		$kit_args['html_wrap_close'] = '';
	} else {
		$container_wrap_open = '<div class="addtoany_share_save_container %s">'; // Contains placeholder
		$container_wrap_open .= $html_header;
		$container_wrap_close = '</div>';
	}
	
	$options['position'] = isset( $options['position'] ) ? $options['position'] : 'bottom';
	
	if ($options['position'] == 'both' || $options['position'] == 'top') {
		// Prepend to content
		$content = sprintf( $container_wrap_open, 'addtoany_content_top' ) . ADDTOANY_SHARE_SAVE_KIT($kit_args) . $container_wrap_close . $content;
	}
	if ( $options['position'] == 'bottom' || $options['position'] == 'both') {
		// Append to content
		$content .= sprintf( $container_wrap_open, 'addtoany_content_bottom' ) . ADDTOANY_SHARE_SAVE_KIT($kit_args) . $container_wrap_close;
	}
	
	return $content;
}

add_filter( 'the_content', 'A2A_SHARE_SAVE_add_to_content', 98 );
add_filter( 'the_excerpt', 'A2A_SHARE_SAVE_add_to_content', 98 );


// [addtoany url="http://example.com/page.html" title="Some Example Page"]
function A2A_SHARE_SAVE_shortcode( $attributes ) {
	extract( shortcode_atts( array(
		'url'     => 'something',
		'title'   => 'something else',
		'buttons' => '',
	), $attributes ) );
	
	$linkname = isset( $attributes['title'] ) ? $attributes['title'] : false;
	$linkurl = isset( $attributes['url'] ) ? $attributes['url'] : false;
	$buttons = ! empty( $buttons ) ? explode ( ',', $buttons ) : array();
	
	$output_later = true;

	return '<div class="addtoany_shortcode">'
		. ADDTOANY_SHARE_SAVE_KIT( compact( 'linkname', 'linkurl', 'output_later', 'buttons' ) )
		. '</div>';
}

add_shortcode( 'addtoany', 'A2A_SHARE_SAVE_shortcode' );


function A2A_SHARE_SAVE_stylesheet() {
	global $A2A_SHARE_SAVE_options, $A2A_SHARE_SAVE_plugin_url_path;
	
	$options = $A2A_SHARE_SAVE_options;
	
	// Use stylesheet?
	if ( ! isset( $options['inline_css'] ) || $options['inline_css'] != '-1' && ! is_admin() ) {
	
		wp_enqueue_style( 'A2A_SHARE_SAVE', $A2A_SHARE_SAVE_plugin_url_path . '/addtoany.min.css', false, '1.12' );
	
		// wp_add_inline_style requires WP 3.3+
		if ( '3.3' <= get_bloginfo( 'version' ) ) {
		
			// Prepare inline CSS
			$inline_css = '';
			
			$vertical_type = ( isset( $options['floating_vertical'] ) && 'none' != $options['floating_vertical'] ) ? $options['floating_vertical'] : false;
			$horizontal_type = ( isset( $options['floating_horizontal'] ) && 'none' != $options['floating_horizontal'] ) ? $options['floating_horizontal'] : false;
			
			// If vertical bar is enabled
			if ( $vertical_type && 
				// and respsonsiveness is enabled
				( ! isset( $options['floating_vertical_responsive'] ) || '-1' != $options['floating_vertical_responsive'] )
			) {
				
				// Get min-width for media query
				$vertical_max_width = ( 
					isset( $options['floating_vertical_responsive_max_width'] ) && 
					is_numeric( $options['floating_vertical_responsive_max_width'] ) 
				) ? $options['floating_vertical_responsive_max_width'] : '980';
				
				// Set media query
				$inline_css .= '@media screen and (max-width:' . $vertical_max_width . 'px){' . "\n"
					. '.a2a_floating_style.a2a_vertical_style{display:none;}' . "\n"
					. '}';
				
			}
			
			// If horizontal bar is enabled
			if ( $horizontal_type && 
				// and respsonsiveness is enabled
				( ! isset( $options['floating_horizontal_responsive'] ) || '-1' != $options['floating_horizontal_responsive'] )
			) {
				
				// Get max-width for media query
				$horizontal_min_width = ( 
					isset( $options['floating_horizontal_responsive_min_width'] ) && 
					is_numeric( $options['floating_horizontal_responsive_min_width'] ) 
				) ? $options['floating_horizontal_responsive_min_width'] : '981';
				
				// Insert newline if there is inline CSS already
				$inline_css = 0 < strlen( $inline_css ) ? $inline_css . "\n" : $inline_css;
				
				// Set media query
				$inline_css .= '@media screen and (min-width:' . $horizontal_min_width . 'px){' . "\n"
					. '.a2a_floating_style.a2a_default_style{display:none;}' . "\n"
					. '}';
				
			}
			
			// If additional CSS (custom CSS for AddToAny) is set
			if ( ! empty( $options['additional_css'] ) ) {
				$custom_css = stripslashes( $options['additional_css'] );
				
				// Insert newline if there is inline CSS already
				$inline_css = 0 < strlen( $inline_css ) ? $inline_css . "\n" : $inline_css;
				
				$inline_css .= $custom_css;
			}
			
			// If there is inline CSS
			if ( 0 < strlen( $inline_css ) ) {
				// Insert inline CSS
				wp_add_inline_style( 'A2A_SHARE_SAVE', $inline_css );	
			}
		
		}
		
	}
	
}

add_action( 'wp_print_styles', 'A2A_SHARE_SAVE_stylesheet' );


/**
 * Cache AddToAny
 */

function A2A_SHARE_SAVE_refresh_cache() {
	$contents = wp_remote_fopen( 'https://www.addtoany.com/ext/updater/files_list/' );
	$file_urls = explode( "\n", $contents, 20 );
	$upload_dir = wp_upload_dir();
	
	// Make directory if needed
	if ( ! wp_mkdir_p( dirname( $upload_dir['basedir'] . '/addtoany/foo' ) ) ) {
		$message = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), dirname( $new_file ) );
		return array( 'error' => $message );
	}
	
	if ( count( $file_urls ) > 0 ) {
		for ( $i = 0; $i < count( $file_urls ); $i++ ) {
			// Download files
			$file_url = trim( $file_urls[ $i ] );
			$file_name = substr( strrchr( $file_url, '/' ), 1, 99 );
			
			// Place files in uploads/addtoany directory
			wp_remote_get( $file_url, array(
				'filename' => $upload_dir['basedir'] . '/addtoany/' . $file_name,
				'stream'   => true, // Required to use `filename` arg
			) );
		}
	}
}

function A2A_SHARE_SAVE_schedule_cache() {
	// WP "Cron" requires WP version 2.1
	$timestamp = wp_next_scheduled( 'A2A_SHARE_SAVE_refresh_cache' );
	if ( ! $timestamp) {
		// Only schedule if currently unscheduled
		wp_schedule_event( time(), 'daily', 'A2A_SHARE_SAVE_refresh_cache' );
	}
}

function A2A_SHARE_SAVE_unschedule_cache() {
	$timestamp = wp_next_scheduled( 'A2A_SHARE_SAVE_refresh_cache' );
	wp_unschedule_event( $timestamp, 'A2A_SHARE_SAVE_refresh_cache' );
}



/**
 * Admin Options
 */

if ( is_admin() ) {
	include_once( $A2A_SHARE_SAVE_plugin_dir . '/addtoany.admin.php' );
}

function A2A_SHARE_SAVE_add_menu_link() {
	$page = add_options_page(
		__( 'AddToAny Share Settings', 'add-to-any' ),
		__( 'AddToAny', 'add-to-any' ),
		'manage_options',
		'addtoany',
		'A2A_SHARE_SAVE_options_page'
	);
	
	/* Using registered $page handle to hook script load, to only load in AddToAny admin */
	add_filter( 'admin_print_scripts-' . $page, 'A2A_SHARE_SAVE_scripts' );
}

add_filter( 'admin_menu', 'A2A_SHARE_SAVE_add_menu_link' );

function A2A_SHARE_SAVE_widgets_init() {
	global $A2A_SHARE_SAVE_plugin_dir;
	
	include_once( $A2A_SHARE_SAVE_plugin_dir . '/addtoany.widgets.php' );
	register_widget( 'A2A_SHARE_SAVE_Widget' );
	register_widget( 'A2A_Follow_Widget' );
}

add_action( 'widgets_init', 'A2A_SHARE_SAVE_widgets_init' );

// Place in Option List on Settings > Plugins page 
function A2A_SHARE_SAVE_actlinks( $links, $file ) {
	// Static so we don't call plugin_basename on every plugin row.
	static $this_plugin;
	
	if ( ! $this_plugin ) {
		$this_plugin = plugin_basename( __FILE__ );
	}
	
	if ( $file == $this_plugin ) {
		$settings_link = '<a href="options-general.php?page=addtoany">' . __( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link ); // before other links
	}
	
	return $links;
}

add_filter( 'plugin_action_links', 'A2A_SHARE_SAVE_actlinks', 10, 2 );
