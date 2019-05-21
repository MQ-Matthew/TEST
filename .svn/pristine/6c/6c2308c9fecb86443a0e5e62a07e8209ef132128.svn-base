<?php

namespace Home\Controller;

class DeptGradeController extends HomeController {
	//app 类型
	protected $config = array('app_type' => 'master');

	function _search_filter(&$map) {
		$keyword=I('keyword');
		if (!empty($keyword)) {
			$map['grade_no|name'] = array('like', "%" . $keyword . "%");
		}
	}

	public function index() {
		$model = M("DeptGrade");
		$list = $model -> order('sort') -> select();
		$this -> assign('list', $list);
		$this -> display();
	}

	function del($id) {		
		$this -> _destory($id);
	}

}
?>