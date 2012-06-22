<?php
// read the post from PayPal system and add 'cmd'
$req = 'cmd=_notify-validate';

foreach ( $_POST as $key => $value ) {
	$value = urlencode ( stripslashes ( $value ) );
	$req .= "&$key=$value";
}

// post back to PayPal system to validate
$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen ( $req ) . "\r\n\r\n";
$fp = fsockopen ( 'ssl://www.paypal.com', 443, $errno, $errstr, 30 );

// assign posted variables to local variables
$item_name = $_POST ['item_name'];
$item_number = $_POST ['item_number'];
$payment_status = $_POST ['payment_status'];
$payment_amount = $_POST ['mc_gross'];
$payment_currency = $_POST ['mc_currency'];
$txn_id = $_POST ['txn_id'];
$receiver_email = $_POST ['receiver_email'];
$payer_email = $_POST ['payer_email'];

if (! $fp) {
	// HTTP ERROR
} else {
	fputs ( $fp, $header . $req );
	while ( ! feof ( $fp ) ) {
		$res = fgets ( $fp, 1024 );
		if (strcmp ( $res, "VERIFIED" ) == 0) {
			// check that receiver_email is your Primary PayPal email
			if (strcmp ( $receiver_email, "royalrealtyrei@gmail.com" ) == 0) {
				// check the payment_status is Completed
				if (strcmp ( $payment_status, "Completed" ) == 0) {
					// check that payment_currency is correct
					if (strcmp ( $payment_currency, "USD" ) == 0) {
						//check that payment_amount
						$credits = 0;
						switch ($payment_amount) {
							default :
								$credits = 1;
								break;
						}
						require_once 'FiverrAccountCreator.php';
						$args = array ('paymentAmount' => $payment_amount, 'paypal' => 1, 'tid' => $txn_id, 'credits' => $credits, 'paypalemail' => $payer_email );
						$obj = new FiverrAccountCreator ( $args );
					}
				}
			}
		} else if (strcmp ( $res, "INVALID" ) == 0) {
			// log for manual investigation
		}
	}
	fclose ( $fp );
}
?>