<?php
/*--------------------------------------------------------------------
 小微OA系统 - 让工作更轻松快乐

 Copyright (c) 2013 http://www.smeoa.com All rights reserved.

 Author:  jinzhu.yin<smeoa@qq.com>

 Support: https://git.oschina.net/smeoa/xiaowei
--------------------------------------------------------------*/

namespace Home\Controller;

class GovDocAuthController extends HomeController {
	protected $config = array('app_type' => 'master',admin=>'set_role,get_role_list');
	//过滤查询字段
	function _search_filter(&$map) {
		$map['is_del'] = array('eq', '0');
		$keyword = I('keyword');
		if (!empty($keyword)) {
			$map['User.name|emp_no|Position.name|Dept.name'] = array('like', "%" . $keyword . "%");
		}
	}
	public function index() {
		$map = $this -> _search();
		if (method_exists($this, '_search_filter')) {
			$this -> _search_filter($map);
		}
		$user_list = D("UserView") -> where($map)->select();
		$this -> assign("user_list", $user_list);

		$role_list=array();
		$role_list[0]['name']='文档管理';
		$role_list[0]['value']='0';
		$role_list[1]['name']='领导';
		$role_list[1]['value']='1';
		$role_list[2]['name']='部门负责人';
		$role_list[2]['value']='2';
		$role_list[3]['name']='基本权限';
		$role_list[3]['value']='3';
		$this -> assign("role_list", $role_list);
		$this -> display();
	}
	//未完成
	public function get_role_list() {
	    $model = M('gov_doc_auth');
		$id = I('id');
		$where['user_id']=$id;
		$data = $model ->where($where)->find();

		if ($data !== false) {// 读取成功
			$data2['set_auth']=$data['set_auth'];
			$this -> ajaxReturn($data2);
		}
	}
	public function set_role($emp_id,$role_list){
		$model = M("gov_doc_auth");
		foreach ($emp_id as $v){
			$data['user_id']=$v;
			$data['set_auth']=$role_list[0];
			$where['user_id']=$v;
			$list=$model->where($where)->count();
			if($list){
				$result=$model->where($where)->save($data);
			}else{
				$result=$model->add($data);
			}
		}
		if ($result === false) {
			$this -> error('操作失败！');
		} else {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('操作成功！');
		}
		
	}

	
}
