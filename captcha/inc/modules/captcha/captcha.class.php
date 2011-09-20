<?php

class captcha extends baseModule
{
	/**
	 * Create image with code from origin-key combination 
	 * @param string $origin Origin of request (preferably extention key)
	 * @param string $key Key to lookup
	 * @return <strong>image/gif</strong> Image containing the key, empty image when the origin-key combination has no code.
	 */
	public static function showCaptcha($origin, $key)
	{
		@ob_end_clean();
		
		$fontsPath = dirname(__FILE__) . '/fonts/';
		
		$fonts = array(
			'anonymous.gdf',
			'automatic.gdf',
			'borringlesson.gdf'
		);
		
		$randomCharacters = 0;
		$textColors = array(
		        array(255,   0,   0),
		        array(  0,   0, 255)
		);
		
		$backgroundTextColors = array(
	        	array(131, 131, 131),
	        	array(166, 166, 166),
	        	array(192, 192, 192),
	        	array(217, 217, 217),
		        array(237, 237, 237)
		);
		
		$backgroundColor = array(0xff, 0x00, 0xff);
		
		// Config end
		
		$fontNums = array();
		$maxWidth = 0;
		$maxHeight = 0;
		
		foreach($fonts as $font)
		{
			$fontNums[] = $loadedFont = imageloadfont($fontsPath.$font);
			$width = imagefontwidth($loadedFont);
			$height = imagefontheight($loadedFont);

			if($width > $maxWidth)
				$maxWidth = $width;
			if($height > $maxHeight)
				$maxHeight = $height;
		}
		
		$key = getArgv('e1', 1);
		$code = self::getValue($origin, $key);
		
		if($code == null || $code == "")
			exit();
		
		header('Content-Type: image/gif');
		
		$imgWidth = $maxWidth * strlen($code) + 20;
		$imgHeight = $maxHeight * 2;
		$img = imagecreatetruecolor($imgWidth, $imgHeight);
		$bgColor = imagecolorallocate($img, $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
		imagefilledrectangle($img, 0, 0, $imgWidth, $imgHeight, $bgColor);
		imagecolortransparent($img, $bgColor);
		
		$colors = array();
		foreach($backgroundTextColors as $backgroundTextColor)
		        $colors[] = imagecolorallocate($img, $backgroundTextColor[0], $backgroundTextColor[1], $backgroundTextColor[2]);
		
		$textColor = $textColors[rand(0, count($textColors)-1)];
		$textColor = imagecolorallocate($img, $textColor[0], $textColor[1], $textColor[2]);
		
		$maxFont = count($fonts) - 1;
		$maxColor = count($colors) -1;
		$maxRandWidth = $imgWidth - $maxWidth;
		$maxTextHeight = $maxHeight - 5;
		$textLength = strlen($code);
		$aOrd = ord('A');
		
		// Random characters
		if(!$randomCharacters)
			$randomCharacters = $textLength * 3;
		for($i=0; $i<$randomCharacters; $i++)
			imagechar($img, $fontNums[rand(0, $maxFont)], rand(0, $maxRandWidth), rand(0, $maxHeight), chr(rand(0, 25) + $aOrd), $colors[rand(0, $maxColor)]);
		
		// Text
		for($i=0; $i<$textLength; $i++)
			imagechar($img, $fontNums[rand(0, $maxFont)], 10 + ($i * $maxWidth), rand(5, $maxTextHeight), $code{$i}, $textColor);
		
		imagegif($img);
		imagedestroy($img);
		
		exit();
	}
	
	/**
	 * Create a new captcha code
	 * @param string $origin Origin of request (preferably extention key)
	 * @param string $length Length of the code to generate, default 6
	 * @return <strong>array</strong> Array containing key and code as follows:
	 * <table style="margin-left: 20px;">
	 * <tr><td><strong>key</strong></td><td>Key of the captcha entry</td></tr>
	 * <tr><td><strong>code</strong></td><td>Code of the captcha entry</td></tr>
	 * </table>
	 */
	public static function createCaptcha($origin, $length = 6)
	{
		// Key generation
		$key = null;
		do
		{ $key = md5(rand());
		} while(self::getValue($origin, $key) !== null);
		
		// Value generation
		$aOrd = ord('A');
		$text = '';
		for($i=0; $i<$length; $i++)
			$text .= chr(rand(0, 25) + $aOrd);
		
		// Current timestamp
		$now = time();
		
		// Insert into database
		dbInsert(
			'captcha',
			array(
				'rsn' => $key,
				'origin' => $origin,
				'code_' => $text,
				'created' => $now
			)
		);
		
		// Return array with key and value
		return array(
			'key' => $key,
			'code' => $text
		);
	}
	
	/**
	 * Check if the given value matches the code belonging to the origin-key combination
	 * @param string $origin Origin of request (preferably extention key)
	 * @param string $key Key to lookup
	 * @param string $value Value to be tested
	 * @return <strong>boolean</strong> True if the value matches
	 */
	public static function checkCaptcha($origin, $key, $value)
	{
		// Retrieve code
		$code = self::getValue($origin, $key);
		
		// When code is null return false, else check if code and value are similar
		return $code === null ? false : strtoupper($value) == $code;
	}
	
	/**
	 * Get the value of a captcha origin-key combination
	 * @param string $origin Origin of request (preferably extention key)
	 * @param string $key Key to lookup
	 * @return <strong>string</strong> Value of the captcha or null when the origin-key combination is not found 
	 */
	private static function getValue($origin, $key)
	{
		$query = "
			SELECT code_
			FROM %pfx%captcha
			WHERE origin='$origin' AND rsn='$key'
		";
		$sql = dbExfetch($query, 1);
		
		// Return null on no sql result
		return $sql === false ? null : $sql['code_']; 
	}
	
	/**
	 * Prune all captchas older than one day. 
	 */
	public static function pruneCaptcha()
	{
		// All captchas older than 24 hours can be deleted
		dbDelete('captcha', 'created < '.(time() - 86400));
	}
}

?>