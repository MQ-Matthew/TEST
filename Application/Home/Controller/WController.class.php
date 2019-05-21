<?php

namespace Home\Controller;
use Think\Controller;

class WController extends Controller {
	protected $config = array('app_type' => 'public');

	public function sign_up() {

		$user = M('User');
		$list = $user -> limit(1) -> field('emp_no,substring(emp_no,2) as emp_no2') -> where("emp_no like 'w%'") -> order("-emp_no2 asc") -> select();
		if (empty($list)) {
			$data = 'w001';
		} else {

			$x = intval($list[0]['emp_no2']) + 1;
			if ($x < 10) {
				$y = "w00" . $x;
			} elseif ($x >= 10) {
				$y = "w0" . $x;
			}
			$data = $y;

		}
		$this -> assign('data', $data);
		$this -> display();
	}

	public function sign_up_save() {

		$user = M('User');

		$user -> emp_no = I('data');
		$user -> password = md5(I('data'));
		$user -> position_id = 1;
		$user -> dept_id = 1;
		$user -> name = I('data');
		$user -> create_time = time();
		$user -> westatus = 1;
		$user -> openid = I('data');

		$result = $user -> add();
		$role['role_id'] = 1;
		$role['user_id'] = $result;
		M('RoleUser') -> add($role);

		if ($result) {
			$mobile_tel = I('mobile_tel');
			if (!empty($mobile_tel)) {
				import("Weixin.ORG.Util.Weixin");
				$weixin = new \Weixin();
				$mobile_tel = trim($mobile_tel, '+-');
				$emp_no = I('data');
				$name = I('data');

				$weixin -> add_user($emp_no, $name, $mobile_tel);
				$weixin -> update_user($emp_no, $name, $mobile_tel, $is_del);
			}

			$data['data'] = I('data');
			$data['tel'] = I('mobile_tel');
			$data['status'] = 1;
			$this -> ajaxReturn($data);
		}
	}

	public function sign_up_show() {
		$data['data'] = I('data');
		$data['mobile_tel'] = I('tel');

		$this -> assign('data', $data);
		$this -> display();
	}

}
