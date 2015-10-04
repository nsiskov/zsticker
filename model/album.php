<?php
class Album {
	
	public $albumId;
	public $albumName;
	public $stickerCount;
	
	function __construct($albumId, $albumName, $stickerCount) {
		$this->albumId = $albumId;
		$this->albumName = $albumName;
		$this->stickerCount = $stickerCount;
	}
	
	function getAlbumName() {
		return $this->albumName;
	}
}
?>