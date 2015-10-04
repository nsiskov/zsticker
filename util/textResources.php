<?php

class TextResources {
	
	public $messages;
	public $lang;
	
	public function __construct($lang) {
		$load = true;
		if (isset($_SESSION['lang'])) {
			if ($_SESSION['lang'] == $lang) {
				$load = false;
			}
		}
		 
		if ($load) {
			$keys = parse_ini_file("localization/textResources_" . $lang . ".ini", true);
			$_SESSION['messages'] = $keys;
			$_SESSION['lang'] = $lang;
		}
		$keys = $this->messages;
		$this->lang = $lang;
		$this->messages = $_SESSION['messages'];
	}

	public function get($name) {
		$path = explode("_", $name);
		$source = $this->messages;
		foreach ($path as $crumb) {
			$source = $source[$crumb];
			if (!is_array($source)) {
				return $source;
			}
		}
		
		while (is_array($source)) {
			$source = $source[0];
		}
		
		return $source;
	}
	
	public function __get($name) {
		return $this->get($name);
	}
	
	public function __isset($name) {
		$path = explode("_", $name);
		$source = $this->messages;
		foreach ($path as $crumb) {
			$source = $source[$crumb];
			if (!is_array($source)) {
				return $source;
			}
		}
		
		while (is_array($source)) {
			$source = $source[0];
		}
		
		if (!is_array($source)) {
			return true;
		}
		return false;
	}
}

?>