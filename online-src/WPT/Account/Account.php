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
	
	function setValid(bool $b) {
		$this->valid = $b;
	}
}
?>