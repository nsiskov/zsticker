<?php
class User {
	private $userId;
	public $userName;
	private $firstName;
	private $lastName;
	public $authHash;
	public $albums;
	public $currentAlbum;
	
	function __construct($userId, $userName, $firstName, $lastName, $albums) {
		$this->userId = $userId;
		$this->userName = $userName;
		$this->firstName = $firstName;
		$this->lastName = $lastName;
		$this->albums = $albums;
		$this->currentAlbum = reset($albums);
	}
	
	public function getUserId() {
		return $this->userId;
	}
	
	public function getFullName() {
 		return $this->firstName . " " . $this->lastName;
	}
	
	public function getFirstName() {
		return $this->firstName;
	}
}