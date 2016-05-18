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

class Firmentyp extends APIv1_Controller
{
	/**
	 * Firmentyp API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model FirmentypModel
		$this->load->model('ressource/firmentyp_model', 'FirmentypModel');
		// Load set the uid of the model to let to check the permissions
		$this->FirmentypModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getFirmentyp()
	{
		$firmentypID = $this->get('firmentyp_id');
		
		if(isset($firmentypID))
		{
			$result = $this->FirmentypModel->load($firmentypID);
			
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
	public function postFirmentyp()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['firmentyp_id']))
			{
				$result = $this->FirmentypModel->update($this->post()['firmentyp_id'], $this->post());
			}
			else
			{
				$result = $this->FirmentypModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($firmentyp = NULL)
	{
		return true;
	}
}