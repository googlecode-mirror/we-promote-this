var ytAccounts = parseInt(wptuser.ytAccounts, 10) + 1;
//Note the handle name and the parameter name that we had declared in 'wp_localize_script'.

/* for other browsers */
window.onload = updateLoggedInCSS;

var loginFormShowing = false;

function updateLoggedInCSS() {

	//Modify Login link
	var themelogin = jQuery('#theme-my-login2');
	//themelogin.hide();
	themelogin.parent().html('<a href="#" onClick="showLoginForm();" style="float:right;">Login / Register:</a>').append(themelogin);

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
	return;
	// Load Social Connect From into the checkout table
	var connectForm = jQuery('#hidden_social_connect_form').detach();
	if (jQuery('#pmpro_user_fields').length > 0) {
		jQuery('#pmpro_user_fields tbody tr td').prepend(connectForm);
		jQuery('#social_connect_form_td').append(connectForm);
		connectForm.show();
	}
}
