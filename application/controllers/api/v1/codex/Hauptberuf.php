<?php
/**
 * FH-Complete
 *
 * @package		FHC-API
 * @author		FHC-Team
 * @copyright	Copyright (c) 2016, fhcomplete.org
 * @license		GPLv3
 * @link		http://fhcomplete.org
 * @since		Version 1.0
 * @filesource
 */
// ------------------------------------------------------------------------

if(!defined('BASEPATH')) exit('No direct script access allowed');

class Hauptberuf extends APIv1_Controller
{
	/**
	 * Hauptberuf API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model HauptberufModel
		$this->load->model('codex/hauptberuf_model', 'HauptberufModel');
		// Load set the uid of the model to let to check the permissions
		$this->HauptberufModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getHauptberuf()
	{
		$hauptberufID = $this->get('hauptberuf_id');
		
		if(isset($hauptberufID))
		{
			$result = $this->HauptberufModel->load($hauptberufID);
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}

	/**
	 * @return void
	 */
	public function postHauptberuf()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['hauptberuf_id']))
			{
				$result = $this->HauptberufModel->update($this->post()['hauptberuf_id'], $this->post());
			}
			else
			{
				$result = $this->HauptberufModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($hauptberuf = NULL)
	{
		return true;
	}
}