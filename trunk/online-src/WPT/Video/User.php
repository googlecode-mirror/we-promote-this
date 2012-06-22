<?php

class User {
	public $user_id;
	public $user_password;
	
	public function toString(){
	return "('".$this->user_id."','".$this->user_password."')";
	}
}
?>