<?php

$PERSON_ID = getAuthPersonId();
$ALL_FUNKTIONEN_OE_KURZBZ = "('" . implode("','", array_keys($all_funktionen_oe_kurzbz)) . "')";
$ALL_OE_KURZBZ_BERECHTIGT = "('" . implode("','", $all_oe_kurzbz_berechtigt) . "')";
$RELEVANT_PRESTUDENT_STATUS = "('Aufgenommener', 'Student', 'Incoming', 'Diplomand', 'Abbrecher', 'Unterbrecher', 'Absolvent')";
$LANGUAGE_INDEX = getUserLanguage() == 'German' ? '1' : '2';

// get issues for the oes of the logged user or for the persons (students, oe-zuordnung) of the oes
$query = "WITH zustaendigkeiten AS (
			SELECT fehlercode,
				CASE
					WHEN zst.person_id = ".$PERSON_ID;

		if (!isEmptyArray($all_funktionen_oe_kurzbz))
		{
			$query .= " OR (zst.oe_kurzbz IN $ALL_FUNKTIONEN_OE_KURZBZ AND zst.funktion_kurzbz IS NULL)  /* if oe is specified in fehler_zustaendigkeiten */";

			// check for each oe for each function if zustaendig
			foreach ($all_funktionen_oe_kurzbz as $oe_kurzbz => $funktionen_kurzbz)
			{
				foreach ($funktionen_kurzbz as $funktion_kurzbz)
				{
					$query .= " OR (zst.oe_kurzbz = '$oe_kurzbz' AND zst.funktion_kurzbz = '$funktion_kurzbz')";
				}
			}
		}

		$query .= " THEN TRUE
					ELSE FALSE
				END AS \"zustaendig\"
			FROM system.tbl_fehler_zustaendigkeiten zst 
		)";

$query .= "SELECT issue_id, fehlercode AS \"Fehlercode\", iss.fehlercode_extern AS \"Fehlercode extern\", datum AS \"Datum\",
       		inhalt AS \"Inhalt\", inhalt_extern AS \"Inhalt extern\", iss.person_id AS \"PersonId\", iss.oe_kurzbz AS \"OE\", 
       		ftyp.bezeichnung_mehrsprachig[".$LANGUAGE_INDEX."] AS \"Fehlertyp\", stat.bezeichnung_mehrsprachig[".$LANGUAGE_INDEX."] AS \"Fehlerstatus\",
       		verarbeitetvon AS \"Verarbeitet von\",verarbeitetamum AS \"Verarbeitet am\", fr.app AS \"Applikation\",
       		fr.fehlertyp_kurzbz AS \"Fehlertypcode\", iss.status_kurzbz AS \"Statuscode\",
       		pers.vorname AS \"Vorname\", pers.nachname AS \"Nachname\", 
                         CASE
                             WHEN
                                 EXISTS(SELECT 1
                                        FROM zustaendigkeiten
                                        WHERE fehlercode = iss.fehlercode
                                          AND zustaendig = FALSE) /* If Zuständigkeit is defined for different oe/person, not zustaendig. */
                                 THEN 'Nein'
                             ELSE 'Ja' /* If no Zuständigkeit defined or defined for oe/person of user, zustaendig. */
                             END AS \"Hauptzuständig\"
       			FROM system.tbl_issue iss
				JOIN system.tbl_fehler fr USING (fehlercode)
				JOIN system.tbl_fehlertyp ftyp USING (fehlertyp_kurzbz)
				JOIN system.tbl_issue_status stat USING (status_kurzbz)
				LEFT JOIN public.tbl_person pers ON iss.person_id = pers.person_id
		 		WHERE EXISTS ( /* if oe or person is specified in fehler_zustaendigkeiten */
				    SELECT 1 FROM zustaendigkeiten
				    WHERE fehlercode = iss.fehlercode
				    AND zustaendig = TRUE)";

// show issue if it is assigend to oe of logged in user or to student of oe of logged in user
if (!isEmptyArray($all_oe_kurzbz_berechtigt))
{
	$query .= " OR iss.oe_kurzbz IN $ALL_OE_KURZBZ_BERECHTIGT /* if issue is for oe */";

	$query .= " OR (iss.oe_kurzbz IS NULL AND EXISTS ( /* if person_id of issue is a student of studiengang oe */
						SELECT 1 FROM public.tbl_prestudent ps
						JOIN public.tbl_prestudentstatus pss USING (prestudent_id)
						JOIN public.tbl_studiengang stg USING (studiengang_kz)
						WHERE person_id = iss.person_id
						AND stg.oe_kurzbz IN $ALL_OE_KURZBZ_BERECHTIGT
						AND pss.status_kurzbz IN $RELEVANT_PRESTUDENT_STATUS
						AND NOT EXISTS (SELECT 1 /* irrelevant if already finished studies and studied a while ago */
										FROM public.tbl_prestudentstatus ps_finished
										JOIN public.tbl_studiensemester sem_finished USING (studiensemester_kurzbz)
										WHERE prestudent_id = ps.prestudent_id 
										AND status_kurzbz IN ('Absolvent','Abbrecher','Abgewiesener')
										AND datum::date + interval '2 months' < NOW()
										AND EXISTS (SELECT 1 FROM public.tbl_prestudent /* if more recent prestudent exists, still display the issue */
													JOIN public.tbl_prestudentstatus USING (prestudent_id)
													JOIN public.tbl_studiensemester USING (studiensemester_kurzbz)
													WHERE tbl_prestudentstatus.status_kurzbz IN $RELEVANT_PRESTUDENT_STATUS 
													AND person_id = ps.person_id
													AND prestudent_id <> ps_finished.prestudent_id
													AND tbl_studiensemester.start::date > sem_finished.start::date)
						)
					)
				)";
}

$query .= " ORDER BY CASE
				WHEN iss.status_kurzbz = '".IssuesLib::STATUS_NEU."' THEN 0
				WHEN iss.status_kurzbz = '".IssuesLib::STATUS_IN_BEARBEITUNG."' THEN 1
				ELSE 2
			END,
			CASE
				WHEN fehlertyp_kurzbz = '".IssuesLib::ERRORTYPE_CODE."' THEN 0
				WHEN fehlertyp_kurzbz = '".IssuesLib::WARNINGTYPE_CODE."' THEN 1
				ELSE 2
			END,
			datum DESC, fehlercode, issue_id DESC";

$filterWidgetArray = array(
    'query' => $query,
	'app' => 'core',
	'datasetName' => 'issues',
	'filter_id' => $this->input->get('filter_id'),
    'tableUniqueId' => 'issues',
    'requiredPermissions' => 'admin',
    'datasetRepresentation' => 'tablesorter',
	'checkboxes' => 'issue_id',
    'columnsAliases' => array(
    	'ID',
		ucfirst($this->p->t('fehlermonitoring', 'fehlercode')),
		ucfirst($this->p->t('fehlermonitoring', 'fehlercodeExtern')),
		ucfirst($this->p->t('global', 'datum')),
		ucfirst($this->p->t('fehlermonitoring', 'inhalt')),
		ucfirst($this->p->t('fehlermonitoring', 'inhaltExtern')),
		'PersonId',
		ucfirst($this->p->t('lehre', 'organisationseinheit')),
		ucfirst($this->p->t('fehlermonitoring', 'fehlertyp')),
		ucfirst($this->p->t('fehlermonitoring', 'fehlerstatus')),
		ucfirst($this->p->t('fehlermonitoring', 'verarbeitetVon')),
		ucfirst($this->p->t('fehlermonitoring', 'verarbeitetAm')),
		ucfirst($this->p->t('global', 'applikation')),
		ucfirst($this->p->t('fehlermonitoring', 'fehlertypcode')),
		ucfirst($this->p->t('fehlermonitoring', 'statuscode')),
		ucfirst($this->p->t('person', 'vorname')),
		ucfirst($this->p->t('person', 'nachname')),
		ucfirst($this->p->t('fehlermonitoring', 'hauptzustaendig'))
    ),
	'formatRow' => function($datasetRaw) {

		if ($datasetRaw->{'Fehlercode extern'} == null)
		{
			$datasetRaw->{'Fehlercode extern'} = '-';
		}

		if ($datasetRaw->{'Inhalt'} == null)
		{
			$datasetRaw->{'Inhalt'} = '-';
		}

		if ($datasetRaw->{'Inhalt extern'} == null)
		{
			$datasetRaw->{'Inhalt extern'} = '-';
		}

		if ($datasetRaw->{'PersonId'} == null)
		{
			$datasetRaw->{'PersonId'} = '-';
		}

		if ($datasetRaw->{'OE'} == null)
		{
			$datasetRaw->{'OE'} = '-';
		}

		if ($datasetRaw->{'Verarbeitet am'} == null)
		{
			$datasetRaw->{'Verarbeitet am'} = '-';
		}

		if ($datasetRaw->{'Verarbeitet von'} == null)
		{
			$datasetRaw->{'Verarbeitet von'} = '-';
		}

		return $datasetRaw;
	},
	'markRow' => function($datasetRaw) {

		$mark = '';

		if ($datasetRaw->Statuscode == IssuesLib::STATUS_BEHOBEN)
			$mark = "text-success";
		elseif ($datasetRaw->Statuscode == IssuesLib::STATUS_NEU || $datasetRaw->Statuscode == IssuesLib::STATUS_IN_BEARBEITUNG)
		{
			if ($datasetRaw->Fehlertypcode == IssuesLib::ERRORTYPE_CODE)
			{
				$mark = "text-danger";
			}
			elseif ($datasetRaw->Fehlertypcode == IssuesLib::WARNINGTYPE_CODE)
			{
				$mark = "text-warning";
			}
		}

		return $mark;
	}
);

echo $this->widgetlib->widget('FilterWidget', $filterWidgetArray);
