<?php

namespace Home\Controller;

class RankController extends HomeController {
	protected $config=array('app_type'=>'master');
	function _search_filter(&$map) {
		$keyword=I('keyword');
		if (!empty($keyword)) {
			$map['rank_no|name'] = array('like', "%" .$keyword . "%");
		}
	}
	
	function del(){
		$id=$_POST['id'];
		$this->_destory($id);		
	}
}
?>