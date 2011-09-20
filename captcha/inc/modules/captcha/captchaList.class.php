<?php

class captchaList extends windowList
{
	public $table = 'captcha';
	public $itemsPerPage = 20;
	public $orderBy = 'created';
	
	public $iconDelete = 'cross.png';
	
	public $add = false;
	public $edit = false;
	public $remove = true;
	
	public $filter = array('created', 'origin');
	public $primary = array('rsn');
	
	public $columns = array(
		'created' => array(
			'label' => 'Created',
			'type' => 'datetime',
			'width' => 100
		),
		'origin' => array(
			'label' => 'Origin',
			'type' => 'text',
			'width' => 100
		),
		'rsn' => array(
			'label' => 'rsn',
			'type' => 'text',
			'width' => 200
		),
		'code_' => array(
			'label' => 'Code',
			'type' => 'text',
			'width' => 75
		)
	);
}

?>