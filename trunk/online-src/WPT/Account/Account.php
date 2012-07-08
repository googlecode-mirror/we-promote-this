<?php

abstract class Account {
	public $userName;
	public $password;
	public $email;
	public $firstName;
	public $lastName;
	private $valid;
	
	function isValid() {
		return $this->valid;
	}
	
	function setValid($b) {
		$this->valid = $b;
	}
}
?>