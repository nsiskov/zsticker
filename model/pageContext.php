<?php
class PageContext {
	public $canonicalUrl;
	public $pageId;
	
	private $texts;
	public $lang;
	
	public $serverContext;
	
	function __construct($pageId, $texts, $lang, $serverContext) {
		
		$messages = $texts->messages[$pageId];
		$this->canonicalUrl = $_SERVER['HTTP_HOST'] . $serverContext . '/' . $lang . '/'. $pageId . '.html';
		$this->pageId = $pageId;
		$this->texts = $messages;
		$this->lang = $lang;
		$this->serverContext = $serverContext;
	}
	
	function __get($property) {
		return $this->texts[$property];
	}
	
	function get($property) {
		return $this->texts[$property];
	}
	
	function __isset($property) {
		if (isset($this->texts[$property])) {
			return true;
		} 
		return false;
	}
}
?>