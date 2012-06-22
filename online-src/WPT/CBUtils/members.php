<?
// This simple PHP / Mysql membership script was created by www.funkyvision.co.uk
// You are free to use this script at your own risk
// Please visit our website for more updates..
session_start ();
include_once "config.php";
if (! isset ( $_SESSION ['username'] ) || ! isset ( $_SESSION ['password'] )) {
	header ( "Location: login.php" );
} else {
	$fetch_users_data = mysql_fetch_object ( mysql_query ( "SELECT * FROM `members` WHERE username='" . $_SESSION ['username'] . "'" ) );
}
echo "Welcome <b>" . $fetch_users_data->username . "</b> <a href='logout.php'>Logout</a><br><br>You have " . $fetch_users_data->credits . " credit(s) left.
View Your Accounts <a href='FiverrAccountCreator.php?excel=1' target='_blank'><img src='Includes/excel_icon.gif' valign='center' alt='accounts spreadsheet'></a><br><hr>
";
// SANDBOX
echo('Buy More<br><form target="paypal" action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="VX9LWB2SBXNZU">
<table>
<tr><td><input type="hidden" name="on0" value="Account Credits">Account Credits</td></tr><tr><td><select name="os0">
	<option value="10 Credits">10 Credits $1.00</option>
	<option value="50 Credits">50 Credits $4.00</option>
	<option value="150 Credits">150 Credits $10.00</option>
</select> </td></tr>
</table>
<input type="hidden" name="currency_code" value="USD">
<input type="image" src="https://www.sandbox.paypal.com/WEBSCR-640-20110429-1/en_US/i/btn/btn_cart_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.sandbox.paypal.com/WEBSCR-640-20110429-1/en_US/i/scr/pixel.gif" width="1" height="1">
</form>

');


/*
//REAL
echo ('Buy More<br><form target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="PAHW47MVNA9VU">
<table>
<tr><td><input type="hidden" name="on0" value="Account Credits">Account Credits</td></tr><tr><td><select name="os0">
	<option value="10 Credits">10 Credits $1.00</option>
	<option value="50 Credits">50 Credits $4.00</option>
	<option value="150 Credits">150 Credits $10.00</option>
</select> </td></tr>
</table>
<input type="hidden" name="currency_code" value="USD">
<input type="image" src="https://www.paypalobjects.com/WEBSCR-640-20110429-1/en_US/i/btn/btn_cart_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110429-1/en_US/i/scr/pixel.gif" width="1" height="1">
</form>');
*/

echo ('
<form name="accountsNeeded" action="FiverrAccountCreator.php" method="Post" target="creationframe">
<table>
<tr>
<td>
<image src="http://ping.fm/_images/icons/twitter.png" alt="Twitter"> Twitter
</td><td> 
<input type="text" name="twitter_number" value="0" style="width:50px;height:23px;font-weight:bold;" />
</td>
</tr>

<tr colspan=2>
<td align="center">
<input type="submit" value="Create">
</td>
</tr>
</table>
<input type="hidden" name="create" value="1">
</form>

<iframe name="creationframe" width="100%" height="200px" scrolling="auto" frameborder="0"></iframe>
');
?>
