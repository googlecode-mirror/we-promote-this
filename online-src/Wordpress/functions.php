<?php

// Include this in the current theme's functions.php file
// Use Example below:
// $path = realpath(dirname(__FILE__))."/../../../WePromoteThis/Wordpress/functions.php";
// include_once $path;
//session_save_path(dirname(__FILE__));
//session_name('WePromoteThis');
//session_start();

remove_action('wp_head', 'jetpack_og_tags');
add_filter('jetpack_enable_open_graph', '__return_false', 99);
add_action('show_user_profile', 'wepromotethis_extra_profile_fields');
add_action('edit_user_profile', 'wepromotethis_extra_profile_fields');
add_action('personal_options_update', 'wepromotethis_save_extra_profile_fields');
add_action('edit_user_profile_update', 'wepromotethis_save_extra_profile_fields');
add_action('wp_enqueue_scripts', 'wepromotethis_custom_scripts');
add_action('admin_enqueue_scripts', 'wepromotethis_custom_admin_scripts');
add_action('wp_head', 'wepromotethis_setup');
add_action('wp_footer', 'wepromotethis_footer');
add_filter('get_search_form', 'wepromotethis_search_form');

//Add login buttons before search form

function wepromotethis_search_form($form) {
	global $hop;

	$plink = "http://www.wepromotethis.com/hop/" . $hop;
	$eplink = urlencode($plink);

	$socialLink = '';
	if (function_exists('display_social4i')) {
		$socialLink = display_social4i("large", "align-right");
		// Modify $socialLink so that the twitter, facebook, and google plus buttons display properly
		$socialLink = preg_replace('/data-url="([^"]+)"/i', 'data-url="' . $plink . '" ', $socialLink);
		$socialLink = preg_replace('/data-counturl="([^"]+)"/i', 'data-counturl="wepromotethis.com" ', $socialLink);
		$socialLink = preg_replace('/data-text="([^"]+)"/i', 'data-text="I just turned my computer into an ATM. Thanks #WPT !!! " ', $socialLink);
		$socialLink = preg_replace('/(<g:plusone.* href=)"([^"]+)"/i', '$1"https://plus.google.com/106500919528675853199"', $socialLink);
		$socialLink = preg_replace('/(<fb:like.* href=|fb-like.* data-href=)"([^"]+)"/i', '$1"https://www.facebook.com/pages/WePromoteThiscom/367519556648222?ref=hl"', $socialLink);
	}

	if (function_exists('theme_my_login')) {
		//global $theme_my_login;
		//$themeMyLoginForm = $theme_my_login -> shortcode(wp_parse_args('[widget_theme_my_login]'));
		//$themeMyLoginForm = $theme_my_login -> shortcode(wp_parse_args('[theme_my_login]'));
		ob_start();
		theme_my_login(wp_parse_args('[widget_theme_my_login]'));
		//theme_my_login(wp_parse_args('[theme_my_login]'));
		$themeMyLoginForm = ob_get_contents();
		ob_end_clean();
	}

	//$themeMyLoginForm = wp_login_form( array('echo' => false) );

	$form .= "<div class='register_button wpt_loggedout'>
    <div>
    " . $themeMyLoginForm . "
    </div>
    </div>

    <div class='logout_button wpt_loggedin'>
    <a href='" . wp_logout_url(home_url()) . "' title='Logout'>Logout</a>
    </div>
    
    <div class='sociallink_page_header'>
    " . $socialLink . "
    </div>
    ";
	return $form;
}

function wepromotethis_footer() {
	echo("<div id='hidden_social_connect_form'>");
	$socialConnectForm = do_action('social_connect_form');
	echo('</div>');
}

/**
 * Enqueue style-file
 */
function wepromotethis_custom_scripts($hook) {
	//wp_enqueue_style('wepromotethis_style', "http://WePromoteThis.com/WePromoteThis/Wordpress/style.css");
	wepromotethis_js_script($hook);

}

function wepromotethis_custom_admin_scripts($hook) {
	if ('profile.php' != $hook)
		return;
	wepromotethis_js_script($hook);
}

function wepromotethis_js_script($hook) {
	wp_enqueue_script('wepromotethis_javascript', 'http://WePromoteThis.com/WePromoteThis/Wordpress/scripts.js');

	global $current_user, $wpdb;

	get_currentuserinfo();

	$accountNum = $wpdb -> get_var("
    Select COUNT(u1.meta_key)
    FROM wp_usermeta AS u1
    JOIN wp_usermeta AS u2 ON (u1.user_id = u2.user_id)
    WHERE u1.user_id=" . $current_user -> ID . " AND u1.meta_key LIKE 'youtube%' AND u1.meta_value IS NOT NULL AND CHAR_LENGTH(u1.meta_value)>0
    AND u2.meta_key=CONCAT(u1.meta_key,'_password') AND u2.meta_value IS NOT NULL AND CHAR_LENGTH(u2.meta_value)>0
    ");
	$accountNum++;

	// This allows us to pass PHP variables to the Javascript code. We can pass multiple vars in the array.
	wp_localize_script('wepromotethis_javascript', 'wptuser', array(
		'ytAccounts' => $accountNum,
		'isUserLoggedIn' => is_user_logged_in()
	));
}

function wepromotethis_setup() {
	global $hop;

	if ($_COOKIE['hop'] != '') {
		$hop = $_COOKIE['hop'];
	}
	else
	if (isset($_SESSION['hop'])) {
		$hop = $_SESSION['hop'];
	}
	else {
		// Find the user with the lowest downline count
		$hop = "cq2smooth";
	}

	if (isset($_REQUEST['hop'])) {
		$hop = $_REQUEST['hop'];

	}

	if (isset($_REQUEST['fb_ref'])) {
		$fbref = $_REQUEST['fb_ref'];
		$hop = $fbref;
	}

	if (is_numeric($hop)) {
		$hop = get_usermeta($hop, 'clickbank');
	}

	global $current_user, $wpdb;

	get_currentuserinfo();

	//If user is logged in hop is now that user's clickbank id
	$tmphop = esc_attr(get_the_author_meta('clickbank', $current_user -> ID));
	if (isset($tmphop) && strlen($tmphop) > 0) {
		$hop = $tmphop;
	}

	$_SESSION['hop'] = $hop;
	setcookie("hop", $hop, time() + 604800, "/", ".wepromotethis.com");

	if (isset($_REQUEST['debug'])) {
		if (isset($fbref)) {
			echo("FB Ref: $fbref<br>");
		}
		echo("Referrer: $hop<br>");
	}

	$plink = "http://www.wepromotethis.com/hop/" . $hop;
	$eplink = urlencode($plink);

	echo('<link rel="canonical" href="' . $eplink . '" />
    <link rel="canonical" href="https://www.wepromotethis.com" />
    <link rel="canonical" href="https://www.wepromotethis.com/hop" />
    <link rel="canonical" href="https://www.facebook.com/pages/WePromoteThiscom/367519556648222?ref=hl" />
    ');
	echo('
        <meta content="website" property="og:type" />
        <meta content="WePromoteThis.com" property="og:title" />' . '<meta content="https://www.facebook.com/pages/WePromoteThiscom/367519556648222?ref=hl" property="og:url" />' . '<meta content="' . $eplink . '" property="og:url" />' . '<meta content="When you become a member of WePromoteThis.com you join a network of users who donate their computer\'s idle time. WePromoteThis.com sends information to your computer which it uses to create a video and upload back to WepromoteThis.com. Behind the scenes We will post these videos on the web loaded with your ClickBank affiliate ID. When internet surfers come across these videos, click on your affiliate product link, and make a purchase, YOU get paid!!!!" property="og:description" />
        <meta content="We Promote This" property="og:site_name" />
        <meta content="http://wepromotethis.com/WePromoteThis/WPT/wepromotethis-fblike.png" property="og:image" />
        <meta content="291806197568104" property="fb:app_id" />
        <meta content="41000130" property="fb:admins" />
        ');
}

function wepromotethis_extra_profile_fields($user) {
	echo('<h3>Extra Profile Information</h3>');
	wepromotethis_show_clickbank_fields($user, true);
	wepromotethis_show_youtube_fields($user, true);
}

function wepromotethis_show_clickbank_fields($user, $outsideTabe = false) {
	$firsttd = "td";

	if ($outsideTabe) {
		$firsttd = "th";
		echo('<table class="form-table profile-table">');
	}
	echo('
    	<tr>
    		<' . $firsttd . '><label for="clickbank">ClickBank</label></' . $firsttd . '>
    		<td>
    		<input type="text" name="clickbank" id="clickbank" value="' . esc_attr(get_the_author_meta('clickbank', $user -> ID)) . '" class="regular-text" />
    		<br />
    		<span class="description">Please enter your ClickBank username (nickname).</span></td>
    	</tr>
    	<tr>
    		<' . $firsttd . '><label for="clickbank_clerk_api_key">ClickBank Clerk API Key</label></' . $firsttd . '>
    		<td>
    		<input type="text" name="clickbank_clerk_api_key" id="clickbank_clerk_api_key" value="' . esc_attr(get_the_author_meta('clickbank_clerk_api_key', $user -> ID)) . '" class="regular-text" />
    		<br />
    		<span class="description">Please enter your ClickBank Clerk API Key.</span></td>
    	</tr>
   
	');

	if ($outsideTabe) {
		echo('</table>');
	}

}

function wepromotethis_show_youtube_fields($user, $outsideTabe = false) {
	global $wpdb;
	$wpdb -> hide_errors();
	nocache_headers();
	$ytAccounts = $wpdb -> get_results("
	Select u1.meta_value AS 'user_id', u2.meta_value AS 'user_password'
	FROM wp_usermeta AS u1
	JOIN wp_usermeta AS u2 ON (u1.user_id = u2.user_id)
	WHERE u1.user_id=" . $user -> ID . " AND u1.meta_key LIKE 'youtube%' AND u1.meta_value IS NOT NULL AND CHAR_LENGTH(u1.meta_value)>0
	AND u2.meta_key=CONCAT(u1.meta_key,'_password') AND u2.meta_value IS NOT NULL AND CHAR_LENGTH(u2.meta_value)>0
	");

	$firsttd = "td";
	$addYoutubeColSpan = 'colspan="2"';
	$addYoutubeAlign = 'center';

	if ($outsideTabe) {
		echo('<table class="form-table profile-table">');
		$firsttd = "th";
		$addYoutubeColSpan = '';
		$addYoutubeAlign = 'left';
	}

	$accountNum = 0;
	foreach ($ytAccounts as $ytAccount) {
		$accountNum++;
		$ytName = "youtube" . $accountNum;
		$ytPass = "youtube" . $accountNum . "_password";
		$ytValue = $ytAccount -> user_id;
		$ytPassValue = $ytAccount -> user_password;

		echo('
        	<tr><td colspan="2"><br></td></tr>
        	<tr>
        		<' . $firsttd . ' class="white"><label for="Youtube ' . $accountNum . ' UserName">Youtube ' . $accountNum . ' Username</label></' . $firsttd . '>
        		<td>
        		<input type="text" name="' . $ytName . '" id="' . $ytName . '" value="' . $ytValue . '" class="regular-text" />
        		</td>
        	</tr>
        	<tr>
        		<' . $firsttd . ' class="white"><label for="Youtube ' . $accountNum . ' Password">Youtube ' . $accountNum . ' Password</label></' . $firsttd . '>
        		<td>
        		<input type="text" name="' . $ytPass . '" id="' . $ytPass . '" value="' . $ytPassValue . '" class="regular-text" />
        		</td>
        	</tr>
        	
	');
	}// end for loop
	$accountNum++;
	echo('
    	<tr>
    		<td colspan="2">
    		<br>
    		</td>
    	</tr>
    	<tr>
    		<' . $firsttd . ' class="white"><label for="Youtube ' . $accountNum . ' UserName">Youtube ' . $accountNum . ' Username</label></' . $firsttd . '>
    		<td>
    		  <input type="text" name="youtube' . $accountNum . '" id="youtube' . $accountNum . '" value="" class="regular-text" />
    		</td>
    	</tr>
    	<tr>
    		<' . $firsttd . ' class="white"><label for="Youtube ' . $accountNum . ' Password">Youtube ' . $accountNum . ' Password</label></' . $firsttd . '>
    		<td>
    		  <input type="text" name="youtube' . $accountNum . '_password" id="youtube' . $accountNum . '_password" value="" class="regular-text" />
    		</td>
    	</tr>
    	<tr><td colspan="2"><br></td></tr>
    	<tr id="addYoutubeTR">
    		<td align="'.$addYoutubeAlign.'" '.$addYoutubeColSpan.'>
    		  <input type="button" class="button button-primary" value="Add Youtube Account" onClick="addYoutubeAccount();">
    		</td>
    	</tr>
    	<tr><td colspan="2"><br></td></tr>
	');

	/*
	echo('    	<tr id="addYoutubeFromFileTR">
    		<' . $firsttd . ' class="white"><label for="Upload File">Upload Accounts From File</label></' . $firsttd . '>
    		<td align="left">
    		  <input type="file" class="button button-primary" value="Add Youtube Accounts From File">
    		</td>
    	</tr>
	');
	 */ 

	if ($outsideTabe) {
		echo('</table>');
	}
}

function wepromotethis_save_extra_profile_fields($user_id) {

	if (!current_user_can('edit_user', $user_id))
		return false;

	/* Copy and paste this line for additional fields. Make sure to change 'clickbank' to the field ID. */
	update_usermeta($user_id, 'clickbank', $_POST['clickbank']);
	update_usermeta($user_id, 'clickbank_clerk_api_key', $_POST['clickbank_clerk_api_key']);

	update_usermeta($user_id, 'cb_referer', $_POST['cb_referer']);

	// Update users meta data for youtube accounts
	foreach ($_POST as $index => $value) {
		if (stripos($index, "youtube") !== false) {
			update_usermeta($user_id, $index, $value);
		}
	}
	
	
	
}
?>
