<?php
	include('../../config.inc.php');
	include('../../../include/functions.inc.php');

	//if (!isset($REMOTE_USER))
	//	$REMOTE_USER='pam';
	$uid=get_uid();
	$ansicht=false; //Wenn ein anderer User sich das Profil ansieht (Bei Personensuche)
	if(isset($_GET['uid']))	
	{
		$uid=stripslashes($_GET['uid']);
		$ansicht=true;
	}
		
	$stg = '';
	if (!$conn = @pg_pconnect(CONN_STRING))
	   	die("Es konnte keine Verbindung zum Server aufgebaut werden.");
	if(!($erg=pg_exec($conn, "SELECT * FROM tbl_person WHERE uid='$uid'")))
		die(pg_last_error($conn));
	$num_rows=pg_num_rows($erg);
	if ($num_rows==1)
	{
		$vornamen=pg_result($erg,0,"vornamen");
		$nachname=pg_result($erg,0,"nachname");
		$gebdatum=pg_result($erg,0,"gebdatum");
		$gebort=pg_result($erg,0,"gebort");
		$titel=pg_result($erg,0,"titel");
		$email=pg_result($erg,0,"email");
		$email_alias=pg_result($erg,0,"alias");
		$hp=pg_result($erg,0,"homepage");
	}
	if(!($erg_stud=pg_exec($conn, "SELECT studiengang_kz, semester, verband, gruppe, matrikelnr, kurzbz AS stgkz, tbl_studiengang.bezeichnung AS stgbz FROM tbl_student NATURAL JOIN tbl_studiengang WHERE uid='$uid'")))
		die(pg_last_error($conn));
	$stud_num_rows=pg_numrows($erg_stud);
	if ($stud_num_rows==1)
	{
		$stg=pg_result($erg_stud,0,"studiengang_kz");
		$stgbez=pg_result($erg_stud,0,"stgbz");
		$stgkz=pg_result($erg_stud,0,"stgkz");
		$semester=pg_result($erg_stud,0,"semester");
		$verband=pg_result($erg_stud,0,"verband");
		$gruppe=pg_result($erg_stud,0,"gruppe");
		$matrikelnr=pg_result($erg_stud,0,"matrikelnr");
	}
	if(!($erg_lekt=pg_exec($conn, "SELECT * FROM tbl_mitarbeiter WHERE uid='$uid'")))
		die(pg_last_error($conn));
	$lekt_num_rows=pg_num_rows($erg_lekt);
	if ($lekt_num_rows==1)
	{
		$row=pg_fetch_object($erg_lekt,0);
		$kurzbz=$row->kurzbz;
		$tel=$row->telefonklappe;
	}

	// Mail-Groups
	if(!($erg_mg=pg_exec($conn, "SELECT mailgrp_kurzbz, beschreibung FROM tbl_mailgrp NATURAL JOIN tbl_personmailgrp WHERE tbl_personmailgrp.uid='$uid' ORDER BY mailgrp_kurzbz")))
		die(pg_last_error($conn));
	$nr_mg=pg_numrows($erg_mg);
?>

<html>
<head>
<title>Profil</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" href="../../../skin/cis.css" type="text/css">
</head>

<body>
<H2>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>&nbsp;<a href="../index.php">Lehrveranstaltungsplan</a> &gt;&gt; Userprofil</td>
		<td align="right"><A href="../help/index.html" class="hilfe" target="_blank">HELP&nbsp;</A></td>
	</tr>
	</table>
</H2>
Results: <?php echo $num_rows; ?><br>
	Username: <?php echo $uid; ?><br><br>
	<HR>
	<?php
	if ($num_rows==1)
	{
		?>
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
  		<tr>
    		<td colspan="2">
      		<P>
      			<FONT class="beschriftung">Titel: </FONT> <?php echo $titel; ?> <br>
        		<FONT class="beschriftung">Vornamen: </FONT> <?php echo $vornamen; ?> <br>
        		<FONT class="beschriftung">Nachname: </FONT> <?php echo $nachname; ?> <br>
        		<?php
        		if(!$ansicht)
        		{
        		?>
        			<FONT class="beschriftung">Geburtsdatum: </FONT> <?php echo $gebdatum; ?> <br>
        			<FONT class="beschriftung">Geburtsort: </FONT> <?php echo $gebort; ?> <br>
        		<?php
        		}
        		?>
        		<!--<b>Passwort</b><br>
        		<a href="password.php">Passwort &auml;ndern</a></FONT><br>-->
      		</P>
      		<P>
      			<b>eMail</b><br>
        		<FONT class="beschriftung">Technikum:</FONT><a href='mailto:<?php echo $uid; ?>@technikum-wien.at'> <?php echo $uid; ?>@technikum-wien.at</a><br>
        		
        		<?php
        		if($email_alias!='')
        		{
        		?>
        			<FONT class="beschriftung">Alias:</FONT> <a href='mailto:<?php echo $email_alias; ?>@technikum-wien.at'><?php echo $email_alias; ?>@technikum-wien.at</a>
        		<?php
				}	
				?>
        		<!--<FONT class="beschriftung"><small>(fr&uuml;hestens ab Sommer in Verwendung)</small></FONT><BR>-->
        		<?php 
        	    if($email!='')
        	       	echo "<FONT class='beschriftung'>Extern:</FONT>$email";
        	    ?>	

        	</P>
        	<?php 
        	if($hp!='')
        		echo "<P><b>Homepage</b><br><a href='$hp' target='_blank'>$hp</a></p>";
        	?>
        	
        	<br>
    		</td>
    		<td colspan="2">
      			<?php
      			echo '<P>';
				if ($stud_num_rows==1)
				{
					?>      				
      				<b>Student</b><br><br>
        			<FONT class="beschriftung">Studiengang: </FONT><?php echo $stgbez; ?><br>
        			<FONT class="beschriftung">Semester: </FONT><?php echo $semester; ?><br>
        			<FONT class="beschriftung">Verband: </FONT><?php echo $verband; ?><br>
        			<FONT class="beschriftung">Gruppe: </FONT><?php echo $gruppe; ?><br>
	        		<FONT class="beschriftung">Matrikelnummer: </FONT><?php echo $matrikelnr; ?><br />
	        		<?php 
	        		if(!$ansicht)
	        		{
	        			?>
	        		<br />
	        		<A href='../../campus/notenliste.php'>Leistungsbeurteilung</a><br />
	        		
      				<?php
	        		}
				}
				if ($lekt_num_rows==1)
				{
					?>
      				<P>
      				<b>Lektor</b><br><br>
        			<FONT class="beschriftung">Kurzzeichen: </FONT><?php echo $kurzbz; ?><BR>
        			<?php
        			if($tel!='')
        				echo "<FONT class='beschriftung'>Telefon TW: </FONT>+43 1 333 40 77- $tel<BR><BR>";
        			
        			if(!$ansicht)
        			{?>
        			<A href="zeitwunsch.php?uid=<?php echo $uid; ?>">Zeitw&uuml;nsche</A><BR>
        			<A href="lva_liste.php?uid=<?php echo $uid; ?>">Lehrveranstaltungen</A>
        			<?php
        			}        			
				}
				if(!$ansicht)
				{
					echo "";
					echo "";
				}
				echo "</P>";
				?>
    		</td>
  		</tr>
  		<TR>
    		<TD colspan="2">
      		<P><B>Mail-Verteiler</B><BR><BR>
      		<?php
      		if(!$ansicht)
        		echo "<SMALL>Sie sind Mitglied in folgenden Verteilern:</SMALL>";
        	else
        		echo "<SMALL>Der User $uid ist Mitglied in folgenden Verteilern:</SMALL>";
        	?>
        	</P>
    		</TD>
    		<TD> </TD>
  		</TR>
  		<?php
  		for($i=0;$i<$nr_mg;$i++)
		{
			$row=pg_fetch_object($erg_mg,$i);
			echo '<TR><TD><A href="mailto:'.$row->mailgrp_kurzbz.'@technikum-wien.at">'.$row->mailgrp_kurzbz.'&nbsp;</TD>';
    		echo "<TD>&nbsp;$row->beschreibung</TD><TD></TD></TR>";
		}
		if (isset($matrikelnr))
		{
			echo '<TR><TD><A href="mailto:'.strtolower($stgkz).'_std@technikum-wien.at">'.strtolower($stgkz).'_std&nbsp;</TD>';
    		echo "<TD>&nbsp;Alle Studenten von $stgbez</TD><TD></TD></TR>";
			echo '<TR><TD><A href="mailto:'.strtolower($stgkz).$semester.'@technikum-wien.at">'.strtolower($stgkz).$semester.'&nbsp;</TD>';
    		echo "<TD>&nbsp;Alle Studenten von $stgkz $semester</TD><TD></TD></TR>";
			echo '<TR><TD><A href="mailto:'.strtolower($stgkz).$semester.strtolower($verband).'@technikum-wien.at">'.strtolower($stgkz).$semester.strtolower($verband).'&nbsp;</TD>';
    		echo "<TD>&nbsp;Alle Studenten von $stgkz $semester$verband</TD><TD></TD></TR>";
			echo '<TR><TD><A href="mailto:'.strtolower($stgkz).$semester.strtolower($verband).$gruppe.'@technikum-wien.at">'.strtolower($stgkz).$semester.strtolower($verband).$gruppe.'&nbsp;</TD>';
    		echo "<TD>&nbsp;Alle Studenten von $stgkz $semester$verband$gruppe</TD><TD></TD></TR>";
		}
		
		
		$mail = 'vilesci@technikum-wien.at';
		if($stg=='')
		{
			$stg = 0;
		}
			//Wenn eine Assistentin fuer diesen Studiengang eingetragen ist, 
			//dann werden die aenderungswuesche an diese Adresse gesendet
			$qry = "SELECT email FROM tbl_studiengang where studiengang_kz='$stg'";
			if($row=pg_fetch_object(pg_query($conn,$qry)))
			{
				if($row->email!='')
					$mail = $row->email;
				else 
					$mail = 'vilesci@technikum-wien.at';
			}
		
		
			?>
			</table>
			<BR><HR>
			Sollten ihre Daten nicht stimmen, wenden sie sich bitte an die <a href="mailto:<?php echo $mail ?>?subject=Datenkorrektur&body=Die%20Profildaten%20fuer%20User%20'<?php echo $uid; ?>'%20sind%20nicht%20korrekt.%0D
				Hier die richtigen Daten:%0DNachname:%20<?php echo $nachname;?>%0DVornamen:%20<?php echo $vornamen;?>%0DGeburtsdatum:%20<?php echo $gebdatum;?>
				%0DGeburtsort:%20<?php echo $gebort;?>%0DTitel:%20<?php echo $titel;?>
				%0D%0D***%0DPlatz fuer weitere (nicht angefuehrte Daten)%0D***">zustšndige Assistentin</a>
			<?php
	}
	else
	{
		?>
		<br><br>
		Es wurden keine oder mehrere Profile f&uuml;r ihren Useraccount gefunden.
		<br>
		Bitte wenden sie sich an die <a href="mailto:vilesci@technikum-wien.at?subject=Profilfehler&body=Es wurden zuviele oder zuwenige Profile fuer User <?php echo $uid; ?> gefunden. %0DBitte kontrollieren sie die Datenbank!%0D%0DMeine Daten sind:%0DNachname:%0DVornamen:%0D...">Administration</a>
		<?php
	}
	?>
</body>
</html>
