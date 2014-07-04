<?php

class GateKeeperException extends Exception {

	
	
}



/**
 * GateKeeperController
 */

class GateKeeperController  {
	
	protected $token;

	protected $headers = array();
	protected $groups = array();

	function __construct($token) {
		$this->token = $token;
		$headers = apache_request_headers();
		foreach($headers AS $k => $v) {
			if (preg_match('/^UWAP-/', $k)) {
				$this->headers[$k] = $v;
			}
		}

		if (!empty($this->headers['UWAP-Groups'])) {
			$gr = explode(',', $this->headers['UWAP-Groups']);
			foreach($gr AS $g) {
				$this->groups[$g] = 1;
			}
		}

	}

	public function getClientID() {
		return $this->headers['UWAP-ClientID'];
	}
	public function getUserID() {
		return $this->headers['UWAP-UserID'];
	}

	public function getGroups() {
		return $this->groups;
	}

	public function requireMemberOf($group) {
		if (!isset($this->groups[$group])) {
			throw new GateKeeperException('Access denied: User is not a member of the group ' . $group);
		}
	}

	public function requireUser() {
		if (empty($this->headers['UWAP-UserID'])) {
			throw new GateKeeperException('Missing authenticated user through UWAP');
		}
		return $this;
	}

	public function requireToken() {
		if (empty($this->headers['UWAP-X-Auth'])) {
			throw new GateKeeperException('Missing required UWAP credentials');
		}
		if ($this->headers['UWAP-X-Auth'] !== $this->token) {
			throw new GateKeeperException('Invalid UWAP credentials');
		}
		return $this;
	}

	public function debug() {
		return array(
			'headers' => $this->headers,
			'groups' => $this->groups
		);

	}


} 

