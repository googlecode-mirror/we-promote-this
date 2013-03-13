<?php
// Include this in the current theme's functions.php file
// Use Example below:
// $path = realpath(dirname(__FILE__))."/../../../WePromoteThis/Wordpress/functions.php";
// include_once $path;
//session_save_path(dirname(__FILE__));
//session_name('WePromoteThis');
//session_start();

remove_action( 'wp_head', 'jetpack_og_tags' );
add_filter('jetpack_enable_open_graph', '__return_false', 99); 
add_action('show_user_profile', 'my_show_extra_profile_fields');
add_action('edit_user_profile', 'my_show_extra_profile_fields');
add_action('personal_options_update', 'my_save_extra_profile_fields');
add_action('edit_user_profile_update', 'my_save_extra_profile_fields');
add_action('wp_enqueue_scripts', 'wpt_custom_scripts');
add_action('admin_enqueue_scripts', 'wpt_custom_admin_scripts');
add_action('wp_head', 'setupWPT');
add_action('wp_footer', 'setupFooter');
add_filter('get_search_form', 'my_search_form');

//Add login buttons before search form

function my_search_form($form) {

    $socialLink = '';
    if (function_exists('display_social4i')) {
        $socialLink = display_social4i("large", "align-right");
    }

    if (function_exists('theme_my_login')) {
        global $theme_my_login;
        //$themeMyLoginForm = $theme_my_login -> shortcode(wp_parse_args('[widget_theme_my_login]'));
        $themeMyLoginForm = $theme_my_login -> shortcode(wp_parse_args('[theme_my_login]'));
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

function setupFooter() {
    echo("<div id='hidden_social_connect_form'>");
    $socialConnectForm = do_action('social_connect_form');
    echo('</div>');
}

/**
 * Enqueue style-file
 */
function wpt_custom_scripts($hook) {
    wp_enqueue_style('wpt_style', "http://WePromoteThis.com/WePromoteThis/Wordpress/style.css");
    wpt_js_sctipt($hook);

}

function wpt_custom_admin_scripts($hook) {
    if ('profile.php' != $hook)
        return;
    wpt_js_sctipt();
}

function wpt_js_sctipt($hook) {
    wp_enqueue_script('wpt_javascript', 'http://WePromoteThis.com/WePromoteThis/Wordpress/scripts.js');

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
    wp_localize_script('wpt_javascript', 'wptuser', array('ytAccounts' => $accountNum, 'isUserLoggedIn' => is_user_logged_in()));
}

function setupWPT() {

    global $hop;

    if ($_COOKIE['hop'] != '') {
        $hop = $_COOKIE['hop'];
    } else if (isset($_SESSION['hop'])) {
        $hop = $_SESSION['hop'];
    } else {
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
    <link rel="canonical" href="https://www.wepromotethis.com">
    <link rel="canonical" href="https://www.wepromotethis.com/hop">
    <link rel="canonical" href="https://www.facebook.com/pages/WePromoteThiscom/367519556648222">
    ');
    echo('
        <meta content="website" property="og:type">
        <meta content="WePromoteThis.com" property="og:title">
        <meta content="https://www.facebook.com/pages/WePromoteThiscom/367519556648222" property="og:url">
        <meta content="When you become a member of WePromoteThis.com you join a network of users who donate their computer\'s idle time. WePromoteThis.com sends information to your computer which it uses to create a video and upload back to WepromoteThis.com. Behind the scenes We will post these videos on the web loaded with your ClickBank affiliate ID. When internet surfers come across these videos, click on your affiliate product link, and make a purchase, YOU get paid!!!!" property="og:description">
        <meta content="We Promote This" property="og:site_name">
        <meta content="http://wepromotethis.com/WePromoteThis/WPT/wepromotethis-fblike.png" property="og:image">
        <meta content="291806197568104" property="fb:app_id">
        <meta content="41000130" property="fb:admins">
        ');
}

function my_show_extra_profile_fields($user) {
    echo('<h3>Extra Profile Information</h3>');
    show_clickbank_fields($user);
    show_youtube_fields($user);
}

function show_clickbank_fields($user) {
    echo('
    <table class="form-table">
    	<tr>
    		<th><label for="clickbank">ClickBank</label></th>
    		<td>
    		<input type="text" name="clickbank" id="clickbank" value="' . esc_attr(get_the_author_meta('clickbank', $user -> ID)) . '" class="regular-text" />
    		<br />
    		<span class="description">Please enter your ClickBank username (nickname).</span></td>
    	</tr>
    	<tr>
    		<th><label for="clickbank_clerk_api_key">ClickBank Clerk API Key</label></th>
    		<td>
    		<input type="text" name="clickbank_clerk_api_key" id="clickbank_clerk_api_key" value="' . esc_attr(get_the_author_meta('clickbank_clerk_api_key', $user -> ID)) . '" class="regular-text" />
    		<br />
    		<span class="description">Please enter your ClickBank Clerk API Key.</span></td>
    	</tr>
	');

}

function show_youtube_fields($user) {
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

    $accountNum = 0;
    foreach ($ytAccounts as $ytAccount) {
        $accountNum++;
        $ytName = "youtube" . $accountNum;
        $ytPass = "youtube" . $accountNum . "_password";
        $ytValue = $ytAccount -> user_id;
        $ytPassValue = $ytAccount -> user_password;

        echo('
        	<tr>
        		<td colspan="2">
        		<br>
        		</td>
        	</tr>
        	<tr>
        		<td class="white">Youtube ' . $accountNum . ' Username</td>
        		<td>
        		<input type="text" name="' . $ytName . '" id="' . $ytName . '" value="' . $ytValue . '" style="width: 300px;" />
        		</td>
        	</tr>
        	<tr>
        		<td class="white">Youtube ' . $accountNum . ' Password</td>
        		<td>
        		<input type="text" name="' . $ytPass . '" id="' . $ytPass . '" value="' . $ytPassValue . '" style="width: 300px;" />
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
    		<td class="white">Youtube ' . $accountNum . ' Username</td>
    		<td>
    		  <input type="text" name="youtube' . $accountNum . '" id="youtube' . $accountNum . '" value="" style="width: 300px;" />
    		</td>
    	</tr>
    	<tr>
    		<td class="white">Youtube ' . $accountNum . ' Password</td>
    		<td>
    		  <input type="text" name="youtube' . $accountNum . '_password" id="youtube' . $accountNum . '_password" value="" style="width: 300px;" />
    		</td>
    	</tr>
    	<tr id="addYoutubeTR">
    		<td align="center"  colspan="2">
    		  <input type="button" class="button button-primary" value="Add Youtube Account" onClick="addYoutubeAccount();">
    		</td>
    	</tr>
');
}

function my_save_extra_profile_fields($user_id) {

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