<?php


class KIND {
	protected $db;
	function __construct($pg) {
		// echo "Connecting to KIND " . $pg . "\n";
		$this->db = pg_connect($pg);
	}

	function getKind() {
		$sql = 'SELECT org.id, org.navn, org.kortnavn, org.orgnr
		    FROM org
		    WHERE org.id IN (
		        SELECT org_id
		            FROM tjenesteabonnement
		                JOIN status_codes_tjenesteabonnement ON tjenesteabonnement.status = status_codes_tjenesteabonnement.id
		                JOIN tjeneste ON tjenesteabonnement.tjeneste_id = tjeneste.id
		            WHERE status_codes_tjenesteabonnement.status=\'Installert\'
		                AND tjeneste.navn = \'urn:mace:feide.no:services:no.uninett.feidekundeportal\'
        )';
		$res = pg_query($this->db, $sql);

		$data = array();

		while ($row = pg_fetch_row($res)) {
			
			$newEntry = array(
				'orgnr' => FeedBuilder::slim($row[3]),
				'kind.kortnavn' => $row[2],
				'kind.title' => $row[1],
				'kind.id' => $row[0],
			);
			// $this->add(array('id' => $row[0]), $newEntry);
			// $this->addEntry($newEntry);
			// print_r($newEntry);
			$data[] = $newEntry;
			if (preg_match('/ kommune/', $row[0])) {
				
			}
		}
		return $data;
	}	

}