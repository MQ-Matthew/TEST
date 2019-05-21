<?php

// 用户组模块
namespace Home\Controller;

class GroupController extends HomeController {
	protected $config = array('app_type' => 'master');

	//过滤查询字段
	function _search_filter(&$map) {
		$map['is_del'] = array('eq', '0');
		$keyword = I('keyword');
		if (!empty($keyword)) {
			$map['User.name|emp_no|user_id'] = array('like', "%" . $keyword . "%");
		}
	}

	public function index() {
		$list = M("Group") -> order('sort asc') -> select();
		$this -> assign('list', $list);
		$this -> display();
	}

	public function del($id) {
		$model = M("Group");
		$where_group['id'] = array('eq', $id);
		$model -> where($where_group) -> delete();

		$model = M("Group_user");
		$where_group_user['id'] = array('eq', $id);
		$model -> where($where_group_user) -> delete();
		$this -> success('删除成功');
	}

	public function get_node_list() {
		$role_id = $_POST["role_id"];
		$model = D("Role");
		$data = $model -> get_node_list($role_id);
		if ($data !== false) {// 读取成功
			$return['data'] = $data;
			$return['status'] = 1;
			$this -> ajaxReturn($return);
		}
	}

	public function user($id) {
		$map = $this -> _search();
		if (method_exists($this, '_search_filter')) {
			$this -> _search_filter($map);
		}

		$row_info = M("Group") -> find($id);
		$this -> assign('row_info', $row_info);

		$where_group_user['group_id'] = array('eq', $id);
		$group_user = M("GroupUser") -> where($where_group_user) -> getField('user_id', true);

		if (!empty($group_user)) {
			//$where_user_list['id'] = array('in', $group_user);
			$where_user_list['user_id'] = array('in', $group_user);
		} else {
			$where_user_list['_string'] = '1=2';
		}
		//dump($where_user_list);
		//$user_list = D("UserView") -> where($where_user_list)->order("sort") -> select();
		$user_list = M("Group_user") -> where($where_user_list)->order("sort") -> select();
		//dump($user_list);
		$this -> assign("user_list", $user_list);
		$this -> display();
	}

	public function add_user($group_id) {
		$map = $this -> _search();
		if (method_exists($this, '_search_filter')) {
			$this -> _search_filter($map);
		}
		$this -> assign('group_id', $group_id);

		//$model = D("Group");
		//$user_list = $model -> get_user_list($group_id);
		$user_list = M("GroupUser")->getField('user_id',true); //修改为每个员工只能加入一个组
		if (!empty($user_list)) {
			$map['id'] = array('not in', $user_list);
		}
		$user_list = D("UserView") -> where($map) -> select();
		$this -> assign("user_list", $user_list);
		$this -> display();
	}

	public function del_user($group_id, $user_id) {
		$model = D("Group");
		$result = $model -> del_user($group_id, $user_id);
		if ($result === false) {
			$this -> error('操作失败！');
		} else {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('操作成功！');
		}
	}

	public function save_user($group_id, $user_id) {
		$model = D("Group");
		$result = $model -> save_user($user_id, $group_id);
		if ($result === false) {
			$this -> error('操作失败！');
		} else {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('操作成功！');
		}
	}

	public function edit_user($group_id, $user_id) {
		//部门列表
		$dept_list = M("Dept") -> where('is_del=0 and pid=1') -> order('sort asc') -> getField('id,name');
		//dump($dept_list);
		$this -> assign('dept_list', $dept_list);

		$map['user_id']  = $user_id;
		$map['group_id'] = $group_id;
		$map['id_del'] = 0;
		$list = M("Group_user")->where($map)->find();
		//dump($list); 
		$this -> assign("list", $list);
		$this -> display();
	}

	public function update_user() {		
		//用户存储
		$data['id'] = I('id');
		$data['group_id'] = I('group_id');
		$data['user_id'] = I('user_id');
		$data['user_name'] = I('user_name');
		$data['dept_id'] = I('dept_id');
		$data['amount'] = I('amount');
		$data['remark'] = I('remark');
		$data['sort'] = I('sort');
		//dump($data);
		$model = M("Group_user");
		//dump($data);die();
		//$data['group_id'] = $_POST;
		$list = $model -> save($data);
		if (false !== $list) {
			// $this -> assign('jumpUrl', U("Group/user?id={$data['group_id']}"));
			// $this -> success('编辑成功!', U('Flow/read?id=$flow_id'));
			$this -> success('编辑成功!', U("Group/user?id={$data['group_id']}"));
			//成功提示
		} else {
			$this -> error('编辑失败333!');
			//错误提示
		}
	}
}
?>