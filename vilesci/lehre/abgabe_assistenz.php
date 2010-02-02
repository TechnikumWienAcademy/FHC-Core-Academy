<?php
/* Copyright (C) 2006 Technikum-Wien
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 * Authors: Christian Paminger 		< christian.paminger@technikum-wien.at >
 *          Andreas Oesterreicher 	< andreas.oesterreicher@technikum-wien.at >
 *          Rudolf Hangl 			< rudolf.hangl@technikum-wien.at >
 *          Gerald Simane-Sequens 	< gerald.simane-sequens@technikum-wien.at >
 */
		
/*******************************************************************************************************
 *				               abgabe_assistenz
 * 		abgabe_assistenz ist die Assistenzoberfläche des Abgabesystems 
 * 			            für Diplom- und Bachelorarbeiten
 *******************************************************************************************************/
	require_once('../../config/vilesci.config.inc.php');
	require_once('../../include/basis_db.class.php');
		if (!$db = new basis_db())
			die('Es konnte keine Verbindung zum Server aufgebaut werden.');

	require_once('../../include/functions.inc.php');
	require_once('../../include/datum.class.php');
	require_once('../../include/person.class.php');
	require_once('../../include/benutzer.class.php');
	require_once('../../include/benutzerberechtigung.class.php');
	require_once('../../include/mitarbeiter.class.php');
	require_once('../../include/variable.class.php');

	if (!$getuid = get_uid())
			die('Keine UID gefunden !  <a href="javascript:history.back()">Zur&uuml;ck</a>');
				
	$htmlstr = "";
	$erstbegutachter='';
	$zweitbegutachter='';
	$fachbereich_kurzbz='';
	//$p2id='';

	$stg_kz=(isset($_REQUEST['stg_kz'])?$_REQUEST['stg_kz']:'');
	if(!is_numeric($stg_kz) && $stg_kz!='')
		die('Bitte vor dem Aufruf Studiengang ausw&auml;hlen!');
	$stgbez='';
	
	$trenner='';
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($getuid);

if(!$rechte->isBerechtigt('admin', $stg_kz, 'suid') && !$rechte->isBerechtigt('assistenz', $stg_kz, 'suid') && !$rechte->isBerechtigt('assistenz', null, 'suid', $fachbereich_kurzbz) )
	die('Sie haben keine Berechtigung f&uuml;r diesen Studiengang  <a href="javascript:history.back()">Zur&uuml;ck</a>');
	
$trenner = new variable();
$trenner->loadVariables($getuid);
	
$sql_query = "SELECT * 
			FROM (SELECT DISTINCT ON(tbl_projektarbeit.projektarbeit_id) public.tbl_studiengang.bezeichnung as stgbez,* FROM lehre.tbl_projektarbeit  
			LEFT JOIN public.tbl_benutzer on(uid=student_uid) 
			LEFT JOIN public.tbl_person on(tbl_benutzer.person_id=tbl_person.person_id)
			LEFT JOIN lehre.tbl_lehreinheit using(lehreinheit_id) 
			LEFT JOIN lehre.tbl_lehrveranstaltung using(lehrveranstaltung_id) 
			LEFT JOIN public.tbl_studiengang using(studiengang_kz)
			WHERE (projekttyp_kurzbz='Bachelor' OR projekttyp_kurzbz='Diplom')
			AND public.tbl_benutzer.aktiv 
			AND lehre.tbl_projektarbeit.note IS NULL 
			AND public.tbl_studiengang.studiengang_kz='$stg_kz'   
			ORDER BY tbl_projektarbeit.projektarbeit_id desc) as xy 
		ORDER BY nachname";

if(!$erg=$db->db_query($sql_query))
{
	$errormsg='Fehler beim Laden der Betreuungen';
}
else
{
	//$htmlstr .= "<form name='formular'><input type='hidden' name='check' value=''></form>";
	$htmlstr .= "<form name='multitermin' action='abgabe_assistenz_multitermin.php' title='Serientermin' target='al_detail' method='POST'>";
	$htmlstr .= "<table id='t1' class='liste table-autosort:2 table-stripeclass:alternate table-autostripe'>\n";
	$htmlstr .= "<thead><tr class='liste'>\n";
	$htmlstr .= "<th></th><th class='table-sortable:default'>UID</th>
				<th>Email</th>
				<th class='table-sortable:default'>Sem.</th>
				<th class='table-sortable:default'>Vorname</th>
				<th class='table-sortable:alphanumeric'>Nachname</th>";
	$htmlstr .= "<th class='table-sortable:default'>Typ</th>
				<th>Titel</th>
				<th class='table-sortable:alphanumeric'>1.Begutachter</th>
				<th class='table-sortable:alphanumeric'>2.Begutachter</th>";
	$htmlstr .= "</tr></thead><tbody>\n";
	$i = 0;
	while($row=$db->db_fetch_object($erg))
	{
		$erstbegutachter='';
		$zweitbegutachter='';
		$muid='';
		$muid2='';
		$mituid='';
		$p2id='';
		$stgbez=$row->stgbez;
		//Betreuer suchen
		$qry_betr="SELECT trim(COALESCE(nachname,'')||', '||COALESCE(titelpre,'')||' '||COALESCE(vorname,'')||' '||COALESCE(titelpost,'')) as first, '' as second, 
		public.tbl_mitarbeiter.mitarbeiter_uid, '' as kontakt, public.tbl_person.person_id  
		FROM public.tbl_person JOIN lehre.tbl_projektbetreuer ON(lehre.tbl_projektbetreuer.person_id=public.tbl_person.person_id)
		LEFT JOIN public.tbl_benutzer ON(public.tbl_benutzer.person_id=public.tbl_person.person_id) 
		LEFT JOIN public.tbl_mitarbeiter ON(public.tbl_benutzer.uid=public.tbl_mitarbeiter.mitarbeiter_uid)    
		WHERE projektarbeit_id='$row->projektarbeit_id' 
		AND (tbl_projektbetreuer.betreuerart_kurzbz='Erstbegutachter' OR tbl_projektbetreuer.betreuerart_kurzbz='Betreuer')
		UNION
		SELECT '' as first, trim(COALESCE(nachname,'')||', '||COALESCE(titelpre,'')||' '||COALESCE(vorname,'')||' '||COALESCE(titelpost,'')) as second, 
		public.tbl_mitarbeiter.mitarbeiter_uid, 
		(SELECT kontakt FROM public.tbl_kontakt WHERE person_id=tbl_person.person_id AND kontakttyp='email' AND zustellung LIMIT 1) as kontakt, public.tbl_person.person_id   
		FROM public.tbl_person JOIN lehre.tbl_projektbetreuer ON(lehre.tbl_projektbetreuer.person_id=public.tbl_person.person_id)
		LEFT JOIN public.tbl_benutzer ON(public.tbl_benutzer.person_id=public.tbl_person.person_id) 
		LEFT JOIN public.tbl_mitarbeiter ON(public.tbl_benutzer.uid=public.tbl_mitarbeiter.mitarbeiter_uid) 
		WHERE projektarbeit_id='$row->projektarbeit_id' 
		AND tbl_projektbetreuer.betreuerart_kurzbz='Zweitbegutachter'
		";

		if(!$betr=$db->db_query($qry_betr))
		{
			$errormsg='Fehler beim Laden der Betreuer';
		}
		else
		{
			while($row_betr=$db->db_fetch_object($betr))
			{
				if($row_betr->first!='' && $row_betr->mitarbeiter_uid!=NULL)
				{
					if(trim($erstbegutachter==''))
					{
						$erstbegutachter=$row_betr->first;
						$muid=$row_betr->mitarbeiter_uid."@".DOMAIN;
						$mituid=$row_betr->mitarbeiter_uid;
					}
					else 
					{
						$erstbegutachter.=", ".$row_betr->first;
						$muid.=", ".$row_betr->mitarbeiter_uid."@".DOMAIN;
					}
				} 
				if($row_betr->second!='')
				{
					$zweitbegutachter=$row_betr->second;
					$p2id=$row_betr->person_id;
					if($row_betr->mitarbeiter_uid!='' && $row_betr->mitarbeiter_uid!=NULL)
					{
						$muid2=$row_betr->mitarbeiter_uid."@".DOMAIN;
					}
					else 
					{
						if($row_betr->kontakt!='' && $row_betr->kontakt!=NULL)
						{
							$muid2=$row_betr->kontakt;
						}
					}
				}
									
			}
		}
		$htmlstr .= "   <tr class='liste".($i%2)."'>\n";
		$htmlstr .= "		<td><input type='checkbox' id='mc_".$row->projektarbeit_id."' name='mc_".$row->projektarbeit_id."' ></td>";
		//Anzeige 
		$qry_end="SELECT * FROM campus.tbl_paabgabe WHERE paabgabetyp_kurzbz='end' AND projektarbeit_id='$row->projektarbeit_id' ORDER BY datum DESC";
		if(!$result_end=$db->db_query($qry_end))
		{
			$htmlstr .= "       <td><a href='abgabe_assistenz_details.php?uid=".$row->uid."&projektarbeit_id=".$row->projektarbeit_id."&erst=".$mituid."&p2id=".$p2id."&titel=".$row->titel."' target='al_detail' title='Details anzeigen'>".$row->uid."</a></td>\n";
		}
		else
		{
			if($db->db_num_rows($result_end)>0)
			{
				$bgcol='';
				if($row_end=$db->db_fetch_object($result_end))
				{
					if($row_end->abgabedatum==NULL)
					{
						if ($row_end->datum<=date('Y-m-d'))
						{
							$bgcol='#FF0000';
						}
						elseif (($row_end->datum>date('Y-m-d')) && ($row_end->datum<date('Y-m-d',mktime(0, 0, 0, date("m")  , date("d")+11, date("Y")))))
						{
							$bgcol='#FFFF00';
						}
						else 
						{
							$bgcol='#FFFFFF';
						}
					}
					else 
					{
						if($row_end->abgabedatum>$row_end->datum)
						{
							$bgcol='#EA7B7B';
						}
						else 
						{
							$bgcol='#00FF00';
						}
					}
					if($bgcol!='')
					{
						$htmlstr .= "       <td style='background-color:".$bgcol."'><a href='abgabe_assistenz_details.php?uid=".$row->uid."&projektarbeit_id=".$row->projektarbeit_id."&erst=".$mituid."&p2id=".$p2id."&titel=".$row->titel."' target='al_detail' title='Details anzeigen'>".$row->uid."</a></td>\n";
					}
					else 
					{
						$htmlstr .= "       <td><a href='abgabe_assistenz_details.php?uid=".$row->uid."&projektarbeit_id=".$row->projektarbeit_id."&erst=".$mituid."&p2id=".$p2id."&titel=".$row->titel."' target='al_detail' title='Details anzeigen'>".$row->uid."</a></td>\n";				
					}
				}
				else 
				{
					$htmlstr .= "       <td><a href='abgabe_assistenz_details.php?uid=".$row->uid."&projektarbeit_id=".$row->projektarbeit_id."&erst=".$mituid."&p2id=".$p2id."&titel=".$row->titel."' target='al_detail' title='Details anzeigen'>".$row->uid."</a></td>\n";				
				}
			}
			else 
			{
				$htmlstr .= "       <td><a href='abgabe_assistenz_details.php?uid=".$row->uid."&projektarbeit_id=".$row->projektarbeit_id."&erst=".$mituid."&p2id=".$p2id."&titel=".$row->titel."' target='al_detail' title='Details anzeigen'>".$row->uid."</a></td>\n";				
			}
		}
		$htmlstr .= "	    <td align= center><input type='hidden' name='st_".$row->projektarbeit_id."' value='$row->uid@".DOMAIN."'><a href='mailto:$row->uid@".DOMAIN."?subject=".$row->projekttyp_kurzbz."arbeitsbetreuung bei Studiengang $row->stgbez'><img src='../../skin/images/email.png' alt='email' title='Email an Studenten'></a></td>";
		$htmlstr .= "       <td>".$row->studiensemester_kurzbz."</td>\n";
		$htmlstr .= "       <td>".$row->vorname."</td>\n";
		$htmlstr .= "       <td>".$row->nachname."</td>\n";
		$htmlstr .= "       <td>".$row->projekttyp_kurzbz."</td>\n";
		$htmlstr .= "       <td>".$row->titel."</td>\n";
		
		//$htmlstr.="<a href='mailto:$row->uid@".DOMAIN."?subject=".$row->projekttyp_kurzbz."arbeitsbetreuung%20von%20".$row->vorname."%20".$row->nachname."'>
		//<img src='../../../skin/images/email.png' alt='email' title='Email an Betreuer schreiben'></a>";
	
		if($muid != NULL && $muid !='')
		{
			$htmlstr .= "       <td><a href='mailto:$muid?subject=".$row->projekttyp_kurzbz."arbeitsbetreuung%20von%20".$row->vorname."%20".$row->nachname." bei Studiengang $row->stgbez' title='Email an Erstbegutachter'>".$erstbegutachter."</a></td>\n";
		}
		else
		{
			$htmlstr .= "       <td>".$erstbegutachter."</td>\n";
		}
		if($muid2 != NULL && $muid2 !='')
		{
			$htmlstr .= "       <td><a href='mailto:".$muid2."?subject=".$row->projekttyp_kurzbz."arbeitsbetreuung%20von%20".$row->vorname."%20".$row->nachname." bei Studiengang $row->stgbez' title='Email an Zweitbegutachter'>".$zweitbegutachter."</a></td>\n";
		}
		else
		{
			$htmlstr .= "       <td>".$zweitbegutachter."</td>\n";
		}
		$htmlstr .= "   </tr>\n";
		$i++;
	}
	$htmlstr .= "</tbody></table>\n";
	$htmlstr .= "<input type='hidden' name='stg_kz' value='".$stg_kz."'>\n";
	$htmlstr .= "<input type='hidden' name='p2id' value='".$p2id."'>\n";
	$htmlstr .= "<table><tr><td><input type='checkbox' name='alle' id='alle' onclick='markiere()'> alle markieren  </td></tr><tr><td>&nbsp;</td></tr><tr>\n";
	$htmlstr .= "<td rowspan=2><input type='submit' name='multi' value='Terminserie anlegen' title='Termin f&uuml;r mehrere Personen anlegen.'></td>";
	$htmlstr .= "<td rowspan=2><input type='button' name='stmail' value='E-Mail Studierende' title='E-Mail an mehrere Studierende schicken.$stgbez' onclick='stserienmail(\"".$trenner->variable->emailadressentrennzeichen."\",\"".$stgbez."\")'></td></tr></table>\n";
	$htmlstr .= "</form>";
}

?>
<html>
<head>
<title>Abgabesystem_Assistenzsicht</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="../../skin/vilesci.css" type="text/css">
<link rel="stylesheet" href="../../include/js/tablesort/table.css" type="text/css">
<script src="../../include/js/tablesort/table.js" type="text/javascript"></script>
<script language="JavaScript" type="text/javascript">
function confdel()
{
	if(confirm("Diesen Datensatz wirklick loeschen?"))
		return true;
	return false;
}
function markiere()
{
	var items=document.getElementsByTagName('input');
	var alle=document.getElementById('alle');
	for each(item in items)
	{
		if(item.type=='checkbox')
		{
			item.checked=alle.checked;
		}
	}
}
function stserienmail(trenner, stgbez)
{
	//alert("test!!!");
	var studenten=document.getElementsByTagName('input');
	var adressen='';
	for each(students in studenten)
	{
		if(students.type=='hidden' && students.name.substr(0,3)=="st_")
		{
			var id = "mc_"+students.name.substr(3);
			if(document.getElementById(id).checked)
			{
				if(adressen=='')
				{
					adressen=students.value;
				}
				else
				{
					adressen=adressen+trenner+students.value;
				}
			}
		}
	}
	window.location.href="mailto:"+adressen+"?subject=Bachelor- bzw. Diplomarbeitsbetreuungen bei Studiengang "+stgbez;
}
</script>
</head>

<body class="background_main">
<?php 
echo "<h2><a href='../../cis/cisdocs/Projektarbeitsabgabe_FHTW_Anleitung_A.pdf' target='_blank'><img src='../../skin/images/information.png' alt='Anleitung' title='Anleitung BaDa-Abgabe' border=0></a>&nbsp;&nbsp;Bachelor-/Diplomarbeitsbetreuungen (Studiengang $stg_kz, $stgbez)</h2>";


    echo $htmlstr;
?>

</body>
</html>