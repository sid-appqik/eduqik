<?php 
require_once('./Rest.inc.php');

class Main Extends REST{

	public function __construct(){
		parent::__construct();
	}

	public function gerSupervisor($emp_id){
		$this->query("SELECT e.mobile FROM assign_to_supervisor s JOIN employee e ON (s.parent_id=e.id) WHERE s.emp_id='$emp_id'");
		return $this->fetchAssoc();
	}

	public function getTasksList(){
		
	}

}



 ?>