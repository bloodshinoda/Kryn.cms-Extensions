<?php

class fancyGalleryCategoryEdit extends windowEdit
{
	public $table = "fancygallery_category";
	
	public $primary = array("rsn");
	
	public $fields = array(
		"title" => array(
			"type" => "text",
			"label" => "Title",
			"empty" => false
		)
	);
}

?>