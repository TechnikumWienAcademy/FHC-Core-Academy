<?php

class Webservicelog_model extends DB_Model
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->dbTable = 'system.tbl_webservicelog';
		$this->pk = 'webservicelog_id';
	}
}
