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

class Akte extends APIv1_Controller
{
	/**
	 * Akte API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model AkteModel
		$this->load->model('crm/akte_model', 'AkteModel');
		// Load set the uid of the model to let to check the permissions
		$this->AkteModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getAkte()
	{
		$akteID = $this->get('akte_id');
		
		if(isset($akteID))
		{
			$result = $this->AkteModel->load($akteID);
			
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
	public function postAkte()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['akte_id']))
			{
				$result = $this->AkteModel->update($this->post()['akte_id'], $this->post());
			}
			else
			{
				$result = $this->AkteModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($akte = NULL)
	{
		return true;
	}
}