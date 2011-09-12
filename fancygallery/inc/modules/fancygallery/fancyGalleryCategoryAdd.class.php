<?php

class fancyGalleryCategoryAdd extends windowAdd
{
	public $table = "fancygallery_category";
	
	public $primary = array("rsn");
	
	public $fields = array(
		"title" => array(
			"type" => "text",
			"label" => "Name",
			"empty" => false
		)
	);
	
	function saveItem()
	{
		global $user;
		parent::saveItem();
		
		// If temp dir does not exist, create it
		$tempDir = dirname(__FILE__)."/../../template/fancygallery/tempUpload/";
		if(!is_dir($tempDir))
			mkdir($tempDir);
			
		// If base dir does not exist, create it
		$baseDir = dirname(__FILE__)."/../../upload/fancygallery/";
		if(!is_dir($baseDir))
			mkdir($baseDir);
		
		// Create hash for new gallery, check if dir exists in base dir, if not, create it
		$hash = md5(getArgv("title") ."-". mktime());
		if(!is_dir($baseDir.$hash))
			mkdir($baseDir.$hash);

		// Update table entry with hash, creator, cdate and mdate
		dbUpdate(
			$this->table, 
			array("rsn" => $this->last),
			array(
				"hash" => $hash, 
				"creator" => $user->user_rsn,
				"cdate" => time(), 
				"mdate" => time()
			)
		);
		
		return true;
	}
}

?>