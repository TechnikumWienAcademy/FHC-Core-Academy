<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Incoming shouldn't have austrian home address.
 */
class CORE_INOUT_0007 implements IIssueResolvedChecker
{
	public function checkIfIssueIsResolved($params)
	{
		if (!isset($params['person_id']) || !is_numeric($params['person_id']))
			return error('Person Id missing, issue_id: '.$params['issue_id']);

		if (!isset($params['studiensemester_kurzbz']) || isEmptyString($params['studiensemester_kurzbz']))
			return error('Studiensemester missing, issue_id: '.$params['issue_id']);

		$this->_ci =& get_instance(); // get code igniter instance

		$this->_ci->load->library('issues/PlausicheckLib');

		// check if issue persists
		$checkRes = $this->_ci->plausichecklib->getIncomingHeimatNationOesterreich($params['studiensemester_kurzbz'], null, $params['person_id']);

		if (isError($checkRes)) return $checkRes;

		if (hasData($checkRes))
			return success(false); // not resolved if issue is still present 
		else
			return success(true); // resolved otherwise
	}
}

