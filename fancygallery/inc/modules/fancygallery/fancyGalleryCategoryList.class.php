<?php

class fancyGalleryCategoryList extends windowList
{
	public $table = "fancygallery_category";
	public $tableAlbum = "fancygallery_album";
	public $tableImage = "fancygallery_image";
	public $tableSetting = "fancygallery_setting";
	
	public $itemsPerPage = 10;
	public $orderBy = "title";
	
	public $filter = array("title");
	
	public $add = true;
	public $edit = true;
	public $remove = true;
	
	public $primary = array("rsn");
	
	public $columns = array(
		"title" => array(
			"label" => "Title",
			"type" => "text",
			"width" => 150
		),
		"modifier" => array(
			"label" => "Modifier",
			"type" => "select",
			"table" => "system_user",
			"table_label" => "username",
			"table_key" => "rsn"
		),
		"modified" => array(
			"label" => "Last modified",
			"type" => "datetime",
			"width" => 125
		)
	);
	
	function deleteItem()
	{
		$item = getArgv("item");
		
		// Update albums to category 0
		dbUpdate($this->tableAlbum, array('rsn' => $item['rsn']), array('category' => 0));
		
		// Go on with delete
		parent::deleteItem();
		return true;
	}
	
	function removeSelected()
	{
		// Get selected items
		$selection = json_decode(getArgv('selected'), 1);
		
		// Delete each item
		foreach($selection as $selected)
		{
			// Update albums to category 0
			dbUpdate($this->tableAlbum, array('rsn' => $selected['rsn']), array('category' => 0));
		}
		
		parent::removeSelected();
		return true;
	}
}

?>