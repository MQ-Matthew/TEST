<?php

namespace Home\Controller;

class ProfileController extends HomeController {
	protected $config=array('app_type'=>'personal');
	
	function index(){	
		$plugin['date'] = true;
		$this -> assign("plugin", $plugin);
		$user=D("UserView")->find(get_user_id());
		$data = M()->getlastsql();
		//print_r($data);die;
		//$a = get_user_id();
		//$user=D("UserView")->select()->limit(1);
		// print_r($user);die;
		//print_r($user->getLastSql());
		$this->assign("vo",$user);



        $map['user_id'] = array('eq',get_user_id());
        $infos = M('User_file')->where($map)->select();

        foreach($infos as $k=>$v ){
            $info = $v;
        }

        //dump($info);die;

        $this->assign('info',$info);

        $this->assign('str',$info['jiaoyu']);
		$this->assign('zz_str',$info['zz_jiaoyu']);



		$this->display();
		
	}

	//重置密码
	public function reset_pwd(){
		$id = get_user_id();
		$password = $_POST['password'];
		if ('' == trim($password)) {
			$this -> error('密码不能为空！');
		}

        ///dump($password);
		$User = M('User');
		$User -> password = md5($password);

        ////dump(md5($password));die;

		$User -> id = $id;
		$result = $User -> save();		
		if (false !== $result) {
			if (C('LDAP_LOGIN')) {
				import("Home.ORG.Util.Ldap");
				$ldap_server = C('LDAP_SERVER');
				$ldap_port = C('LDAP_PORT');
				$ldap_user = C('LDAP_USER');
				$ldap_pwd = C('LDAP_PWD');
				$ldap_base_dn = C('LDAP_BASE_DN');

				$ldap = new \Ldap($ldap_server, $ldap_port, $ldap_user, $ldap_pwd, $ldap_base_dn);
				$emp_no = get_emp_no($id);
				$where_dept['id'] = array('eq', $id);
				$dept_id = M("User") -> where($where_dept) -> getField('dept_id');
				$dept_name = get_dept_name($dept_id);

				$ldap -> reset_pwd($emp_no, $dept_name, $password);
				if (!$ldap -> status) {
					$this -> error($ldap -> info);
				}				
			}			
			$this -> assign('jumpUrl', get_return_url());
			$this -> success("密码修改成功");
		} else {
			$this -> error('重置密码失败！');
		}
	}

	public function password(){	
		$this -> display();
	}

	function save(){
		$model = D("User");
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		session('user_pic', $model->pic);
		// 更新数据
		$list = $model -> save();
		if (false !== $list) {
			//成功提示
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('编辑成功!');
		} else {
			//错误提示
			$this -> error('编辑失败!');
		}
	}
}
?>