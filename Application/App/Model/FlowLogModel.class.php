<?php

namespace App\Model;
use Think\Model;

class  FlowLogModel extends CommonModel {
	/*
	function _before_insert(&$data, $options) {
		dump(&$data);
		$emp_no = $data["emp_no"];
		$where['emp_no'] = array('eq', $emp_no);
		$user_id = M("User") -> where($where) -> getField("id");
		$user_name = M("User") -> where($where) -> getField("name");
		$data["user_id"] = $user_id;
		$data["user_name"] = $user_name;

	}*/
}
?>