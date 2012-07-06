<?php

abstract class Account {
	public $userName;
	public $password;
	public $email;
	public $firstName;
	public $lastName;
	
	abstract function isEmailConfirmNeeded();
	
}

?>