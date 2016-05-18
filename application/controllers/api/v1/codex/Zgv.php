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

class Zgv extends APIv1_Controller
{
	/**
	 * Zgv API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model ZgvModel
		$this->load->model('codex/zgv_model', 'ZgvModel');
		// Load set the uid of the model to let to check the permissions
		$this->ZgvModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getZgv()
	{
		$zgvID = $this->get('zgv_id');
		
		if(isset($zgvID))
		{
			$result = $this->ZgvModel->load($zgvID);
			
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
	public function postZgv()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['zgv_id']))
			{
				$result = $this->ZgvModel->update($this->post()['zgv_id'], $this->post());
			}
			else
			{
				$result = $this->ZgvModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($zgv = NULL)
	{
		return true;
	}
}