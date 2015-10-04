<?php

class UrlRewrite {
	
	private static $rules = array(
			array('name'=>'rule0', 
					  'pattern'=>"/(.*?)&lng=([a-z]{2})(.*)/i", 
					  'replacement'=>'${2}/${1}${3}',
					  'callback'=> false,
					  'processOther'=> true),
			array('name'=>'rule1', 
					  'pattern'=>"/entry\\.php\?t=([_a-zA-Z0-9]+)&?(.*)/i", 
					  'replacement'=>array('UrlRewrite','rep1'),
					  'callback'=> true,
					  'processOther'=> false),
			
			array('name'=>'rule2',
					  'pattern'=>'/sample\\.php/i',
					  'replacement'=>'bingo.php',
					  'callback'=> false,
					  'processOther'=> false)
	);
	
	public static function r($url) {
		$result = $url;
		foreach (self::$rules as $rule) {
			$replacements = 0;
			if ($rule['callback']) {
				$result = preg_replace_callback($rule['pattern'], $rule['replacement'], $result, -1, $replacements);
			} else {
				$result = preg_replace($rule['pattern'], $rule['replacement'], $result, -1, $replacements);
			}
			if ($replacements > 0) {
				if (!$rule['processOther']) {
					break;
				}
			}
		}
		return $result;
	}
	
	private static function rep1($matches)
	{
		$retString = $matches[1] . ".html";
		if (isset($matches[2]) && $matches[2] != null) {
			$retString = $retString . "?" . $matches[2];
		}
		return $retString;
	}
}
//TEST

//  test('login.html?nako=siskov', UrlRewrite::r('entry.php?t=login&nako=siskov'));
//  test('login.html?nako=siskov&siskov=nake', UrlRewrite::r('entry.php?t=login&nako=siskov&siskov=nake'));
//  test('login.html', UrlRewrite::r('entry.php?t=login'));
//  test('bingo.php', UrlRewrite::r('sample.php'));
//  test('mk/login.html?nako=siskov', UrlRewrite::r('entry.php?t=login&nako=siskov&lng=mk'));
//  test('en/login.html?nako=siskov&siskov=nake', UrlRewrite::r('entry.php?t=login&lng=en&nako=siskov&siskov=nake'));
 
//  function test($expected, $tested) {
//    $result = $tested; 
//    if ($expected != $tested) {
//      $result = "ERROR:" .  $result . " but expected" . $expected;
//    } else {
//      $result = "OK:" .  $result;
//    }
//    echo $result . "\n";
//  }

?>