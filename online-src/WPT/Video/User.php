<?php

class User {
	public $user_id;
	public $user_password;
	public $user_wp_id;
	
	public function __toString(){
	return "('".$this->user_id."','".$this->user_password."','".$this->user_wp_id."')";
	}
}
?>