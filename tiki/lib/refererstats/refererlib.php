<?php

class RefererLib extends TikiLib {
	function RefererLib($db) {
		# this is probably uneeded now
		if (!$db) {
			die ("Invalid db object passed to RefererLib constructor");
		}

		$this->db = $db;
	}

	function clear_referer_stats() {
		$query = "delete from tiki_referer_stats";

		$result = $this->query($query);
	}

	function list_referer_stats($offset, $maxRecords, $sort_mode, $find) {
		if ($find) {
			$findesc = $this->qstr('%' . $find . '%');
			$mid = " where (`referer` like ?)";
			$bindvars = array($findesc);
		} else {
			$mid = "";
		}

		$query = "select * from `tiki_referer_stats` $mid order by ".$this-convert_sortmode($sort_mode);;
		$query_cant = "select count(*) from `tiki_referer_stats` $mid";
		$result = $this->query($query,$bindvars,$maxRecords,$offset);
		$cant = $this->getOne($query_cant,$bindvars);
		$ret = array();

		while ($res = $result->fetchRow()) {
			$ret[] = $res;
		}

		$retval = array();
		$retval["data"] = $ret;
		$retval["cant"] = $cant;
		return $retval;
	}
}

$refererlib = new RefererLib($dbTiki);

?>
