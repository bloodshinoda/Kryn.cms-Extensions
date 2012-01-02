<?php

class fancyGalleryGlobal extends krynModule
{
/**
 ** SEARCH
 */
	
	public static function searchCategories()
	{
		$q = getArgv('q', 1); // Category title
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
		$q = getArgv('q', 1); // Album title
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
		
		$title = trim(getArgv('title', 1));
		
		// Existing category?
		$sql = "
			SELECT rsn
			FROM %pfx%fancygallery_category
			WHERE title='$title'
		";
		$found = dbExfetch($sql, 1);
		
		if($found !== false)
			json(0); // Failed
		
		$vars = array(
			'title' => getArgv('title'),
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
		
		// Temp dir
        if(!krynFile::exists('/fancygallery/temp/'))
        {
            if(!krynFile::createFolder('/fancygallery/temp/'))
            {
                kLog('fancygallery', 'Temp dir could not be created.');
                json(0);
            }
        }

		// Upload base dir
        if(!krynFile::exists('/fancygallery/upload/'))
        {
            if(!krynFile::createFolder('/fancygallery/upload/'))
            {
                kLog('fancygallery', 'Upload directory could not be created.');
                json(0);
            }
        }

		// Upload dir
		do
		{ // New dir while hash dir already exists
			$hash = md5($title.'-'.time());
        } while(krynFile::exists('/fancygallery/upload/'.$hash));
        // Make dir for images and thumbnails (recursively)
        if(!krynFile::createFolder('/fancygallery/upload/'.$hash.'/t/'))
        {
            kLog('fancygallery', 'Album directory could not be created ('.$hash.')');
            json(0);
        }

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

		// Pre process file location (Strip pre and post ", change \/ into /)
		$fileLocation = str_replace('\\/', '/', substr($fileLocation, 1, strlen($fileLocation)-2));
		$fileLocation = str_replace('//', '/', $fileLocation);

		// Get album info
		$albumInfo = dbExfetch("SELECT hash FROM %pfx%fancygallery_album WHERE rsn=$album", 1);
		if($albumInfo === false)
        {
            kLog('fancygallery', 'Requested album not found ('.$album.')');
			json(0); // Album not found
        }

        // Album path
        $albumPath = '/fancygallery/upload/' . $albumInfo['hash'] . '/';

        // Make hashed filename and check existence
		do
		{
			$hash = md5($fileName.'-'.time());
			$newLoc = $albumPath . $hash . $fileType;
		} while(krynFile::exists($newLoc));

        // Move file to new location
        if(!krynFile::move($fileLocation, $newLoc))
        {
            kLog('fancygallery', 'Moving file failed. From \''.$fileLocation.'\' to \''.$newLoc.'\'');
            json(0);
        }

		// Create small thumbnail for fancygallery (fixed size of 150x100)
		self::createThumbnail($newLoc, $albumPath.'t/'.$hash.$fileType, 150, 100);
		
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
     * @return bool True on success
	 */
	private static function createThumbnail($sFile, $tFile, $width, $height)
	{
        if(!krynFile::exists($sFile))
        { // Image not found
            kLog('fancygallery', 'Can not create thumbnail: Source file does not exist. ('.$sFile.')');
            return false;
        }
		if(krynFile::exists($tFile))
        { // Thumbnail already exists
            kLog('fancygallery', 'Can not create thumbnail: Thumbnail already exists. ('.$tFile.')');
			return false;
        }

        // TODO: Find a good alternative for getimagesize and imagesave, now I need to prepend 'inc/template'
        $sFile = 'inc/template'.$sFile;
        $tFile = 'inc/template'.$tFile;

        // Get image info
		list($sWidth, $sHeight, $sType) = getimagesize($sFile);
		
		// 1 = GIF, 2 = JPG, 3 = PNG, 4 = SWF, 5 = PSD, 6 = BMP,
		// 7 = TIFF(orden de bytes intel), 8 = TIFF(orden de bytes motorola),
		// 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF, 15 = WBMP, 16 = XBM.
        $imgCreate = null;
        $imgSave = null;

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

        // Check if the type is supported
        if(!$imgCreate || !$imgSave)
        { // Unsupported image type
            $fileType = substr($sFile, strrpos($sFile, '.'));
            kLog('fancygallery', 'Cannot create thumbnail: Unsupported image type. ('.$sType.': '.$fileType.')');
            return false;
        }

        // Create images from source and for the thumbnail
		$imgSource = $imgCreate($sFile); // Source image
		$imgThumb  = imagecreatetruecolor($width, $height); // Thumbnail image

        // Crop height when needed
		$scaleWidth = $sWidth / $width;
		$copyHeight = $height * $scaleWidth;
        // Copy height should not exceed source height
		if($copyHeight > $sHeight)
			$copyHeight = $sHeight;
		// Resample source image into thumbnail
		imagecopyresampled($imgThumb, $imgSource, 0, 0, 0, 0, $width, $height, $sWidth, $copyHeight);
        // Save thumbnail
		$imgSave($imgThumb, $tFile);

        return true;
	}

/**
 ** LOAD 
 */
	public static function loadAlbum()
	{
		$a = getArgv('a')+0;
		
		$sql = "
			SELECT
				a.title, a.description, a.category, a.hidden, a.show_, a.hide_, 
				uc.username AS creator, a.created, um.username AS modifier, a.modified
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
				um.username as modifier, a.modified
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
        $dir = 'inc/template/fancygallery/upload/'.$hash;
		if(is_dir($dir))
			delDir($dir);
		
		json(1);
	}
	
	public static function deleteImage()
	{
		$rsn = getArgv('rsn')+0;
		
		// Image info
		$sql = "
			SELECT
				fgi.hash as imageHash,
				fga.hash as albumHash
			FROM
				%pfx%fancygallery_image fgi,
				%pfx%fancygallery_album fga
			WHERE
				    fgi.rsn = $rsn
				AND fga.rsn = fgi.album
		";
		
		$deleteInfo = dbExfetch($sql, 1);
		
		// File path
		$pathImg = 'inc/template/fancygallery/upload/'.$deleteInfo['albumHash'].'/'.$deleteInfo['imageHash'];
		$pathThumb = 'inc/template/fancygallery/upload/'.$deleteInfo['albumHash'].'/t/'.$deleteInfo['imageHash'];
		
		if(is_file($pathImg))
			@unlink($pathImg);
		else
			json(0); // File not found
        if(is_file($pathThumb))
            @unlink($pathThumb);
        else
            json(0); // File not found
		
		// Delete from database
		dbDelete('fancygallery_image', "rsn = $rsn");
		
		json(1); // Success
	}
}

?>