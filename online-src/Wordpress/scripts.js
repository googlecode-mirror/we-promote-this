window.fbAsyncInit = function() {
	FB.init({
		appId : '291806197568104',
		channelUrl : 'http://www.wepromotethis.com/WePromoteThis/WPT/FBChannel.php', // Channel File
		status : true,
		cookie : true,
		xfbml : true
	});
	FB.getLoginStatus(function(response) {
		if (response.status === 'connected') {
			// the user is logged in and has authenticated your
			// app, and response.authResponse supplies
			// the user's ID, a valid access token, a signed
			// request, and the time the access token
			// and signed request each expire
			var uid = response.authResponse.userID;
			var accessToken = response.authResponse.accessToken;
			// jQuery('.register_button').hide();
		} else if (response.status === 'not_authorized') {
			// the user is logged in to Facebook,
			// but has not authenticated your app
		} else {
			// the user isn't logged in to Facebook.
		}
	});
	FB.Event.subscribe("auth.logout", function() {
		window.location = '/wp-login.php?action=logout'
	});

	// Additional initialization code here
};

// Load the SDK Asynchronously
( function(d) {
		var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
		if (d.getElementById(id)) {
			return;
		}
		js = d.createElement('script');
		js.id = id;
		js.async = true;
		js.src = "//connect.facebook.net/en_US/all.js";
		ref.parentNode.insertBefore(js, ref);
	}(document));

var ytAccounts = parseInt(wptuser.ytAccounts, 10) + 1;
// Note the handle name and the parameter name that we had declared in
// 'wp_localize_script'.

/* for other browsers */
window.onload = updateLoggedInCSS;

var loginFormShowing = false;

function updateLoggedInCSS() {

	// Modify Login link
	var themelogin = jQuery('#theme-my-login2');
	var parentDiv = themelogin.parent();
	var themelogin = themelogin.detach();
	parentDiv.html('<a href="#" onClick="showLoginForm();" style="float:right;">Login / Register:</a>').append(themelogin);

	if (wptuser.isUserLoggedIn) {
		jQuery('.wpt_loggedin').show();
		jQuery('.wpt_loggedout').hide();
	} else {
		jQuery('.wpt_loggedin').hide();
		jQuery('.wpt_loggedout').show();
	}

	loadSocialConnect();
}

function showLoginForm() {
	var themelogin = jQuery('#theme-my-login2');
	if (loginFormShowing) {
		themelogin.slideUp();
		loginFormShowing = false;
	} else {
		themelogin.slideDown();
		loginFormShowing = true;
	}
}

function addYoutubeAccount() {
	var insertRow = '<tr><td colspan="2"><br></td></tr>';
	insertRow += '<tr><td class="white">Youtube ' + ytAccounts + ' Username</td><td><input type="text" name="youtube' + ytAccounts + '" id="youtube' + ytAccounts + '" value="" style="width: 300px;" /></td></tr>';
	insertRow += '<tr><td class="white">Youtube ' + ytAccounts + ' Password</td><td><input type="text" name="youtube' + ytAccounts + '_password" id="youtube' + ytAccounts + '_password" value="" style="width: 300px;" /></td></tr>';
	jQuery("#addYoutubeTR").before(insertRow);
	ytAccounts++;
}

function loadSocialConnect() {
	// Load Social Connect From into the checkout table
	var connectForm = jQuery('#hidden_social_connect_form').detach();
	if (jQuery('#pmpro_user_fields').length > 0) {
		jQuery('#pmpro_user_fields tbody tr td').prepend(connectForm);
		jQuery('#social_connect_form_td').append(connectForm);
		connectForm.show();
	}
}
