<?php

class fancygallery extends baseModule
{
	public function admin()
	{
		if(getArgv(2) == 'fancygallery' && getArgv(3) == 'global')
		{
			require('inc/module/fancygallery/fancyGalleryGlobal.class.php');
			$sub = getArgv(5);
			switch(getArgv(4))
			{
				case 'search':
					if($sub == 'categories')
						fancyGalleryGlobal::searchCategories();
					else
						fancyGalleryGlobal::searchAlbums();
					break;
					
				case 'add':
					switch($sub)
					{
						case 'category':
							fancyGalleryGlobal::addCategory();
							break;
						case 'album':
							fancyGalleryGlobal::addAlbum();
							break;
						case 'image':
							fancyGalleryGlobal::addImage();
							break;
					}
					break;
					
				case 'load':
					switch($sub)
					{
						case 'album':
							fancyGalleryGlobal::loadAlbum();
							break;
						case 'images':
							fancyGalleryGlobal::loadImages();
							break;
						case 'lastModified':
							fancyGalleryGlobal::loadLastModified();
							break;
					}
					break;
					
				case 'save':
					switch($sub)
					{
						case 'album':
							fancyGalleryGlobal::saveAlbum();
							break;
						case 'imagesInfo':
							fancyGalleryGlobal::saveImagesInfo();
							break;
						case 'imagesOrder':
							fancyGalleryGlobal::saveImageOrder();
							break;
						case 'hidden':
							fancyGalleryGlobal::saveHidden();
							break;
					}
					break;
					
				case 'delete':
					switch($sub)
					{
						case 'album':
							fancyGalleryGlobal::deleteAlbum();
							break;
						case 'image':
							fancyGalleryGlobal::deleteImage();
							break;
					}
					break;
			}
			
			json(0);
		}
	}
	
	public static function viewAlbums( $pConf )
	{
		// Get settings from conf
		$perPage = $pConf['perPage'] + 0;
		$maxPages = $pConf['maxPages'] + 0;
		$display = $pConf['display'];
		$template = $pConf['template_'.$display];
		$categories = $pConf['categories'];

		// Possible extra info
		$thumbCount = isset($pConf['thumbCount']) ? $pConf['thumbCount'] : 0;

		// Get categories string
		$getFromCats = implode(',', $categories);
		
		// When not set, default to 5
		if($perPage == 0)
			$perPage = 5;
		
		// Fetch start from page variable
		$page = getArgv('e1')+0;
		$page = ($page <= 0) ? 1 : $page;
		
		$start = 0;
		if($page > 1)
			$start = ($perPage * $page) - $perPage;
		
		// Create SQL code
		$now = time();
		$sql = "
			SELECT a.*, c.title AS categoryTitle
			FROM %pfx%fancygallery_album a, %pfx%fancygallery_category c
			WHERE 
				    a.category IN ($getFromCats)
				AND a.hidden = 0
				AND c.rsn = a.category
				AND (a.show_ = 0 OR a.show_ <= $now)
				AND (a.hide_ = 0 OR a.hide_ >= $now)
			ORDER BY
				a.modified DESC
			LIMIT $perPage OFFSET $start
		";
		
		$sqlCount = "
			SELECT COUNT(*) AS albumCount
			FROM %pfx%fancygallery_album a
			WHERE
				    a.category IN ($getFromCats)
				AND a.hidden = 0
				AND (a.show_ = 0 OR a.show_ <= $now)
				AND (a.hide_ = 0 OR a.hide_ >= $now)
		";
		
		// Get and assign count to smarty
		$countRow = dbExfetch($sqlCount, 1);
		$count = $countRow['albumCount'];
		tAssign('count', $count);
		
		// Assign pages count
		$pages = 1;
		if($count > 0)
			$pages = ceil($count / $perPage);
		if($maxPages == 0)
			$pConf['maxPages'] = $pages;
			
		tAssign('pages', $pages);
		tAssign('currentPage', $page);
		
		// Get albums
		$list = dbExfetch($sql, DB_FETCH_ALL);
		
		// Adjust the number of thumbnails shown
        $imgCount = 0;
        if($display == 'thumb')
            $imgCount = 1;
        else if($display == 'thumbs')
            $imgCount = $thumbCount;

        // Load image and thumbnail information
        foreach($list as &$album)
            self::addImages($album, $imgCount);
		
		// Assign albums and config
		tAssign('albums', $list);
		tAssign('pConf', $pConf);
		
		kryn::addJs("kryn/mootools-core.js");
        kryn::addJs("kryn/mootools-more.js");
        kryn::addJs("fancygallery/js/humantimes.js");
        kryn::addCss("fancygallery/css/detailalbum/slideshow/default.css");
		kryn::addCss("fancygallery/css/viewalbums/$template.css");
		kryn::addCss("fancygallery/css/viewalbums/$display/$template.css");
		kryn::addJs("fancygallery/js/viewalbums/$display/$template.js");
		return tFetch("fancygallery/viewalbums/$display/$template.tpl");
	}
	
	private static function addImages(&$album, $amount=0)
	{
        // Base SQL query
        $rsn = $album['rsn'];
        $sql = "
            SELECT hash, title, description
            FROM %pfx%fancygallery_image i
            WHERE
                    album = $rsn
                AND hidden = 0
            ORDER BY order_
        ";

        // Set limit when needed
        if($amount > 0)
            $sql .= "LIMIT $amount";

        // Get images
        $images = dbExfetch($sql, -1);

        // Process all images, correct locations
        foreach($images as &$img)
        {
            $img['imgLoc'] = 'inc/template/fancygallery/upload/'.$album['hash'].'/'.$img["hash"];
            $img['thumbLoc'] = 'inc/template/fancygallery/upload/'.$album['hash'].'/t/'.$img["hash"];
        }

        if(!count($images))
        {
            $images[] = array(
                'imgLoc' => 'inc/template/fancygallery/empty.png',
                'thumbLoc' => 'inc/template/fancygallery/empty.png'
            );
        }

        $album['images'] = $images;
	}
	
	public static function detailAlbum( $pConf )
	{
	    // Get settings from conf
        $display = $pConf['display'];
        $template = $pConf['template_'.$display];
        
        // Get images
        $albumRsn = $pConf['single'] ? $pConf['album'] : getArgv('e1')+0;
        
        $sql = "
            SELECT a.*, c.title AS categoryTitle
            FROM %pfx%fancygallery_album a, %pfx%fancygallery_category c
            WHERE 
                    a.rsn = $albumRsn
                AND a.hidden = 0
                AND c.rsn = a.category
        ";
        $album = dbExfetch($sql, 1);

        // Add images to album
        self::addImages($album);

        // Assign album
        tAssign('album', $album);
        
        // Check if width and height are set, if not, use defaults (700x700)
        if($pConf["width"]+0 == 0)
            $pConf["width"] = 700;
        if($pConf["height"]+0 == 0)
            $pConf["height"] = 700;
        
        tAssign('rsn', $albumRsn);
        tAssign('pConf', $pConf);
        return tFetch("fancygallery/detailalbum/$display/$template.tpl");
	}
	
	function humanTimeDiff($date)
	{
	    $secsDay = 86400;
	    $secsHour = 3600;
	    $secsMinute = 60;
	
	    $currentTime = time();
	    $diffTime  = $currentTime - $date;
	    $startOfDay = $currentTime - ($currentTime % $secsDay) - ((int)substr(date('O'), 0, 3)*$secsHour);
	
	    // More than a week ago
	    if($date < $startOfDay - 7*$secsDay)
	        return date("l j F Y H:i", $date);
	    else if($date < $startOfDay - $secsDay)
	        return date("l H:i", $date);
	    else if($date < $startOfDay)
	        return "yesterday ".date("H:i", $date);
	    else if($diffTime > 5*$secsHour)
	        return "today ".date("H:i", $date);
	    else if($diffTime > 2*$secsHour)
	        return date("G", $diffTime)." hours ago";
	    else if($diffTime > $secsHour)
	        return "about an hour ago";
	    else if($diffTime > 2*$secsMinute)
	        return (substr(date("i", $diffTime), 0, 1) == "0" ? substr(date("i", $diffTime), 1) : date("i", $diffTime))." minutes ago";
	    else if($diffTime > $secsMinute)
	        return "about a minute ago";
	    else
	        return "a few seconds ago";
	}
}

?>