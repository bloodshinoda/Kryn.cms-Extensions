<?php

class fancyGalleryGlobal extends baseModule
{
/**
 ** SEARCH
 */
	
	public static function searchCategories()
	{
		$q = getArgv('q'); // FIXME: Should be getArgv('q', true);
		$q = str_replace('*', '%', $q);

		// Query categories
		$sql = "
			SELECT rsn, title
			FROM %pfx%fancygallery_category
			WHERE title LIKE '$q'
			ORDER BY title
			LIMIT 30 OFFSET 0
		";
		
		json(dbExfetch($sql, -1));
	}
	
	public static function searchAlbums()
	{
		$c = getArgv('c')+0; // Category rsn
		$q = getArgv('q'); // FIXME: Should be getArgv('q', true);
		$q = str_replace('*', '%', $q);
		
		if($c)
			$sql = "
				SELECT rsn, title
				FROM %pfx%fancygallery_album
				WHERE 
					    category = '$c'
					AND title LIKE '$q'
				ORDER BY title
				LIMIT 30 OFFSET 0
			";
		else
			$sql = "
				SELECT rsn, title
				FROM %pfx%fancygallery_album
				WHERE title LIKE '$q'
				ORDER BY title
				LIMIT 30 OFFSET 0
			";
			
		json(dbExfetch($sql, -1));
	}

/**
 ** ADD 
 */
	
	public static function addCategory()
	{
		global $user;
		
		$title = trim(getArgv('title'));
		
		// Does the title already exist?
		$sql = "
			SELECT rsn
			FROM %pfx%fancygallery_category
			WHERE title='$title'
		";
		$found = dbExfetch($sql, 1);
		
		if($found !== false)
			json(0); // Failed
		
		$vars = array(
			'title' => $title,
			'creator' => $user->user_rsn,
			'created' => time(),
			'modifier' => $user->user_rsn,
			'modified' => time()
		);
		dbInsert('fancygallery_category', $vars);
		
		json(1); // Success
	}
	
	public static function addAlbum()
	{
		global $user;
		
		$c = getArgv('c')+0;
		$title = trim(getArgv('title'));
		
		$vars = array(
			'category' => $c,
			'title' => $title,
			'description' => '',
			'hash' => '',
			'hidden' => 1, // By default make an album hidden, you want to edit it without it being visible right away
			'creator' => $user->user_rsn,
			'created' => time(),
			'modifier' => $user->user_rsn,
			'modified' => time()
		);
		$rsn = dbInsert('fancygallery_album', $vars);
		
		// Base dir
		$baseDir = dirname(__FILE__).'/../../';
		
		// Temp dir
		$tempDir = $baseDir.'template/fancygallery/tempUpload/';
		if(!is_dir($tempDir))
			mkdir($tempDir);
			
		// Upload base dir
		$uploadBaseDir = $baseDir.'upload/fancygallery/';
		if(!is_dir($uploadBaseDir))
			mkdir($uploadBaseDir);
			
		// Upload dir
		do 
		{ // New dir while dir exists
			$hash = md5($title.'-'.time());
		} while(is_dir($uploadBaseDir.$hash.'/'));
		mkdir($uploadBaseDir.$hash.'/'); // Images dir
		mkdir($uploadBaseDir.$hash.'/t/'); // Thumbnail dir
		
		// Update database entry
		dbUpdate(
			'fancygallery_album',
			array('rsn' => $rsn),
			array('hash' => $hash)
		);
		
		json(1);
	}
	
	public static function addImage()
	{
		global $user;
		
		$fileLocation = getArgv('fileLocation');
		$fileName = getArgv('fileName');
		$fileType = getArgv('fileType');
		$album = getArgv('album')+0;
		
		// Preprocess file location (Strip pre and post ", change \/ into /)
		$fileLocation = str_replace('\\/', '/', substr($fileLocation, 1, strlen($fileLocation)-2));
		$fileLocation = str_replace('//', '/', $fileLocation);
		// Remove / prefix
		if(substr($fileLocation, 0, 1) == '/')
			$fileLocation = substr($fileLocation, 1);
		
		// Get album info
		$albumInfo = dbExfetch("SELECT hash FROM %pfx%fancygallery_album WHERE rsn=$album", 1);
		if($albumInfo === false)
			json(0); // Album not found
			
		// Move file
		$baseDir = dirname(__FILE__).'/../../';
		$oldLoc = $baseDir . 'template/' . $fileLocation;
		$newDir = $baseDir . 'upload/fancygallery/' . $albumInfo['hash'] . '/';
		
		do
		{
			$hash = md5($fileName.'-'.time());
			$newLoc = $newDir . $hash . $fileType;
		} while(is_file($newLoc));
		
		if(!rename($oldLoc, $newLoc))
			json(0);
			
		// Create small thumbnail for fancygallery (fixed size of 150x100)
		self::createThumbnail($newLoc, $newDir.'t/'.$hash.$fileType, 150, 100);
		
		// Get last number from order
		$orderInfo = dbExfetch("SELECT order_ FROM %pfx%fancygallery_image WHERE album=$album ORDER BY order_ DESC", 1);
		if($orderInfo === false)
			$order = 0;
		else 
			$order = $orderInfo['order_']+1;
			
		// Prepare title
		$title = substr($fileName, 0, strlen($fileName)-strlen($fileType));
		
		// Add entry to database
		$vars = array(
			'album' => $album,
			'hash' => $hash.$fileType, // Yes, fileType is added
			'title' => $title,
			'description' => '',
			'order_' => $order,
			'hidden' => 0, // Default visible
			'creator' => $user->user_rsn,
			'created' => time(),
			'modifier' => $user->user_rsn,
			'modified' => time()
		);
		
		$rsn = dbInsert('fancygallery_image', $vars);
		
		json(array(
			'albumHash' => $albumInfo['hash'],
			'rsn' => $rsn,
			'hash' => $hash.$fileType, // Same as database insert
			'title' => $title,
			'description' => '',
			'hidden' => 0, // Same as database insert
			'order' => $order
		));
	}
	
	/**
	 * Creates a thumbnail of specified width and height of the source image.
	 * <p><strong>Note:</strong> Excess height of the thumbnail will be cropped
	 * @param string $sFile Source image location
	 * @param string $tFile Thumbnail location
	 * @param integer $width Max width of the thumbnail
	 * @param integer $height Max height of the thumbnail (excess will be cropped)
	 */
	private static function createThumbnail($sFile, $tFile, $width, $height)
	{
		if(!is_file($sFile))
			return false; // Image not found
		if(is_file($tFile))
			return false; // Thumbnail already exists
			
		list($sWidth, $sHeight, $sType) = getimagesize($sFile);
		
		// 1 = GIF, 2 = JPG, 3 = PNG, 4 = SWF, 5 = PSD, 6 = BMP,
		// 7 = TIFF(orden de bytes intel), 8 = TIFF(orden de bytes motorola),
		// 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF, 15 = WBMP, 16 = XBM. 
		switch($sType)
		{
			case 1:
				$imgCreate = 'imagecreatefromgif';
				$imgSave = 'imagegif';
				break;
			case 2:
				$imgCreate = 'imagecreatefromjpeg';
				$imgSave = 'imagejpeg';
				break;
			case 3:
				$imgCreate = 'imagecreatefrompng';
				$imgSave = 'imagepng';
				break;
		}
		
		$imgS = $imgCreate($sFile); // Source image
		$imgT = imagecreatetruecolor($width, $height); // Thumbnail image
		
		$scaleWidth = $sWidth / $width;
		$copyHeight = $height * $scaleWidth; // Crop height when needed
		if($copyHeight > $sHeight)
			$copyHeight = $sHeight; // Copy height should not exceed source height
			
		imagecopyresampled($imgT, $imgS, 0, 0, 0, 0, $width, $height, $sWidth, $copyHeight);
		$imgSave($imgT, $tFile);
	}

/**
 ** LOAD 
 */
	public static function loadAlbum()
	{
		$a = getArgv('a')+0;
		
		$sql = "
			SELECT
				a.title, a.description, a.category, a.hidden, a.show_, a.hide_, uc.username as 'creator', a.created, um.username as 'modifier', a.modified
			FROM
				%pfx%fancygallery_album a,
				%pfx%system_user uc,
				%pfx%system_user um
			WHERE
				    a.rsn = $a
				AND uc.rsn = a.creator
				AND um.rsn = a.modifier
		";
		
		json(dbExfetch($sql, 1));
	}
	
	public static function loadImages()
	{
		$a = getArgv('a')+0;
		
		$albumHash = dbExfetch("SELECT hash FROM %pfx%fancygallery_album WHERE rsn = $a", 1);
		if($albumHash === false)
			json(0); // Album not found
		
		$sql = "
			SELECT rsn, hash, title, description, order_, hidden
			FROM %pfx%fancygallery_image
			WHERE album = $a
			ORDER BY order_
		";
		
		json(array('albumHash' => $albumHash['hash'], 'images' => dbExfetch($sql, -1)));
	}
	
	public static function loadLastModified()
	{
		$a = getArgv('a')+0;
		
		$sql = "
			SELECT
				um.username as 'modifier', a.modified
			FROM
				%pfx%fancygallery_album a,
				%pfx%system_user um
			WHERE
				    a.rsn = $a
				AND um.rsn = a.modifier
		";
		
		json(dbExfetch($sql, 1));
	}
	
/**
 ** SAVE
 */
	
	public static function saveAlbum()
	{
		global $user;
		
		$rsn = getArgv('rsn');
		
		$vars = array(
			'title' => getArgv('title'),
			'description' => getArgv('description'),
			'category' => getArgv('category')+0,
			'hidden' => getArgv('hidden')+0,
			'show_' => getArgv('show')+0,
			'hide_' => getArgv('hide')+0,
			'modifier' => $user->user_rsn,
			'modified' => time()
		);
		
		$res = dbUpdate('fancygallery_album', array('rsn'=>$rsn), $vars);
		
		json($res !== false ? 1 : 0);
	}
	
	public static function saveImagesInfo()
	{
		global $user;
		
		$info = getArgv('info');
		
		foreach($info as $img)
		{
			kLog('img', print_r($img, true));
			
			$vars = array(
				'title' => $img['title'],
				'description' => $img['description'],
				'modifier' => $user->user_rsn,
				'modified' => time()
			);
			
			dbUpdate('fancygallery_image', array('rsn' => $img['rsn']), $vars);
		}
		
		json(1);
	}
	
	public static function saveImageOrder()
	{
		global $user;
		
		$info = getArgv('info');
		
		foreach($info as $img)
		{
			$vars = array(
				'order_' => $img['order'],
				'modifier' => $user->user_rsn,
				'modified' => time()
			);
			
			dbUpdate('fancygallery_image', array('rsn' => $img['rsn']), $vars);
		}
		
		json(1);
	}
	
	public static function saveHidden()
	{
		$rsn = getArgv('rsn')+0;
		$hidden = getArgv('hidden')+0;
		
		$res = dbUpdate('fancygallery_image', array('rsn' => $rsn), array('hidden' => $hidden));
		
		if($res === false)
			json(0);
		else
			json(1); // Success
	}
	
/**
 ** DELETE
 */
	public static function deleteAlbum()
	{
		$rsn = getArgv('a')+0;
		
		// Get hash
		$hash = dbExfetch("SELECT hash FROM %pfx%fancygallery_album WHERE rsn = $rsn", 1);
		$hash = $hash['hash'];
		
		// Delete images and album from database
		dbDelete('fancygallery_image', "album = $rsn");
		dbDelete('fancygallery_album', "rsn = $rsn");
		
		// Delete album from harddisk
		$uploadBaseDir = dirname(__FILE__).'/../../upload/fancygallery/';
		if(is_dir($uploadBaseDir.$hash.'/'))
			delDir($uploadBaseDir.$hash.'/');
		
		json(1);
	}
	
	public static function deleteImage()
	{
		$rsn = getArgv('rsn')+0;
		
		// Image info
		$sql = "
			SELECT
				fgi.hash as 'imageHash',
				fga.hash as 'albumHash'
			FROM
				%pfx%fancygallery_image fgi,
				%pfx%fancygallery_album fga
			WHERE
				    fgi.rsn = $rsn
				AND fga.rsn = fgi.album
		";
		
		$deleteInfo = dbExfetch($sql, 1);
		
		// Delete from disk
		$uploadBaseDir = dirname(__FILE__).'/../../upload/fancygallery/';
		$fileLoc = $uploadBaseDir.$deleteInfo['albumHash'].'/'.$deleteInfo['imageHash'];
		
		if(is_file($fileLoc))
			@unlink($fileLoc);
		else
			json(0); // File not found
		
		// Delete from database
		// TODO: Should we concern order_ ?
		dbDelete('fancygallery_image', "rsn = $rsn");
		
		json(1); // Success
	}
}

?>