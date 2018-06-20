<?php
/**
 * FH-Complete
 *
 * @package		FHC-Helper
 * @author		FHC-Team
 * @copyright	Copyright (c) 2016 fhcomplete.org
 * @license		GPLv3
 * @since		Version 1.0.0
 */

/**
 * FHC Helper
 *
 * @subpackage	Helpers
 * @category	Helpers
 */

if (! defined('BASEPATH')) exit('No direct script access allowed');

const DEFAULT_SANCHO_HEADER_IMG = 'sancho_header_du_hast_neue_nachrichten.jpg';

/**
 * Send single Mail with Sancho Design and Layout.
 * @param string $vorlage_kurzbz Name of the template for specific mail content.
 * @param array $vorlage_data Associative array with specific mail content varibales
 *  to be replaced in the content template.
 * @param string $to Email-adress.
 * @param string $subject Subject of mail.
 * @param string $headerImg	Filename of the specific Sancho header image.
 * @return void
 */
function sendMail($vorlage_kurzbz, $vorlage_data, $to, $subject, $headerImg = DEFAULT_SANCHO_HEADER_IMG)
{
	$ci =& get_instance();
	$ci->load->library('email');
	$ci->load->library('MailLib');
	
	$sanchoHeader_img = 'skin/images/sancho/'. $headerImg;
	$sanchoFooter_img = 'skin/images/sancho/sancho_footer.jpg';

	// Embed sancho header and footer image
	// reset important to ensure embedding of images when called in a loop
	$ci->email->clear(true);  // clear vars and attachments
	$ci->email->attach($sanchoHeader_img);
	$ci->email->attach($sanchoFooter_img);
	$cid_header = $ci->email->attachment_cid($sanchoHeader_img); // sets unique content id for embedding
	$cid_footer = $ci->email->attachment_cid($sanchoFooter_img); // sets unique content id for embedding

	// Set specific mail content into specific content template
	$content = _parseMailContent($vorlage_kurzbz, $vorlage_data);
	
	// overall main content data array
	$layout = array(
		'CID_header' => $cid_header,
		'CID_footer' => $cid_footer,
		'content' => $content
	);

	// Set overall main content into the sancho mail template
	$body = _parseMailContent('Sancho_Mail_Template', $layout);

	// Send mail
	$ci->maillib->send('sancho@'. DOMAIN, $to, $subject, $body);
}

/**
 * Replace variables in the mail content template with specific mail content data.
 * @param string $vorlage_kurzbz Name of the template for specific mail content.
 * @param array $vorlage_data Associative array with specific mail content varibales
 *  to be replaced in the content template.
 * @return string
 */
function _parseMailContent($vorlage_kurzbz, $vorlage_data)
{
	$ci =& get_instance();
	$ci->load->library('VorlageLib');
	
	$result = $ci->vorlagelib->getVorlagetextByVorlage($vorlage_kurzbz);

	if (isSuccess($result))
	{
		// If the text and the subject of the template are not empty
		if (is_array($result->retval) && count($result->retval) > 0 &&
			!empty($result->retval[0]->text))
		{
			// Parses template text
			$parsedText = $ci->vorlagelib->parseVorlagetext($result->retval[0]->text, $vorlage_data);

			return $parsedText;
		}
	}
}
