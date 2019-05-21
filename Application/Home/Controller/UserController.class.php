<?php


// 后台用户模块
namespace Home\Controller;

class UserController extends HomeController {
	//protected $config = array('app_type' => 'master');//如果是这个参数，在授权时将会访问不到，会是空白页面
    protected $config = array('app_type' => 'personal');//如果是这个参数，就可以授权了

	function _search_filter(&$map) {
		$keyword = I('keyword');
		if (!empty($keyword)) {
			$map['name|emp_no'] = array('like', "%" . $keyword . "%");
		}
	}

	public function index() {
        
		$plugin['date'] = true;
		$this -> assign("plugin", $plugin);

		$model = M("Position");
		$list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
		$this -> assign('position_list', $list);

		$model = M("Dept");
		$list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
		$this -> assign('dept_list', $list);
        
        $model = M("User");
        $list = $model -> where('is_del=0') -> order('id asc') -> getField('emp_no,name');
        $this -> assign('agent_list', $list);
        

		if (isset($_POST['eq_is_del'])) {
			$eq_is_del = $_POST['eq_is_del'];
		} else {
			$eq_is_del = "0";
		}
		//die;
		$this -> assign('eq_is_del', $eq_is_del);

		$map = $this -> _search();
		if (method_exists($this, '_search_filter')) {
			$this -> _search_filter($map);
		}
		$map['is_del'] = array('eq', $eq_is_del);

		$model = D("User");

		if (!empty($model)) {
			$this -> _list($model, $map, "emp_no");
		}
		$this -> display();
	}

	public function add() {
		$plugin['date'] = true;
		$this -> assign("plugin", $plugin);

		$model = M("Position");
		$list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
		$this -> assign('position_list', $list);

		$model = M("Dept");
		$list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
		$this -> assign('dept_list', $list);

		$this -> display();
	}

	// 检查帐号
	public function check_account() {
		if (!preg_match('/^[a-z]\w{4,}$/i', $_POST['emp_no'])) {
			$this -> error('用户名必须是字母，且5位以上！');
		}
		$User = M("User");
		// 检测用户名是否冲突
		$name = I('emp_no'); ;
		$result = $User -> getByAccount($name);
		if ($result) {
			$this -> error('该编码已经存在！');
		} else {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('该编码可以使用！');
		}
	}

	// 插入数据
	protected function _insert($name = "User") {
		// 创建数据对象
		$model = D("User");
		if (!$model -> create()) {
			$this -> error($model -> getError());
		} else {
            $Identity = 'dp_'.I('dept_id').'_'.I('position_id').'|';
            $Identity_name = '<'.I('dept_name').'-'.I('position_name').'>';
			// 写入帐号数据    
			$model -> letter = get_letter($model -> name);
			$model -> password = !isset($model->password) ? md5('111111'):md5($model -> emp_no);
			$model -> dept_id = I('dept_id');
            $model -> identity =  $Identity;
            $model -> identity_name =  $Identity_name;
			$model -> openid = $model -> emp_no;
			$model -> westatus = 1;
			$emp_no = $model -> emp_no;
			$name = $model -> name;
			$mobile_tel = $model -> mobile_tel;

			if ($result = $model -> add()) {
				$data['id'] = $result;
				M("UserConfig") -> add($data);
				if (get_system_config('system_license')) {
					if (!empty($mobile_tel)) {
						import("Weixin.ORG.Util.Weixin");
						$weixin = new \Weixin();
						$mobile_tel = trim($mobile_tel, '+-');
						$weixin -> add_user($emp_no, $name, $mobile_tel);
					}
				}
                syncdate_log('A','User',$result); //记录更新日志
				$this -> assign('jumpUrl', get_return_url());
				$this -> success('用户添加成功！');
			} else {
				$this -> error('用户添加失败！');
			}
		}
	}

	protected function _update($name = "User") {
		$model = D($name);
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
        
		// 更新数据
		$model -> __set('letter', get_letter($model -> __get('name')));
        //基础身份处理
        $m_identity = I('identity');        
        $identity_array = explode('|', substr($m_identity, 0, -1));       
        $identity_base_array = explode('_', $identity_array[0]);       
        $model -> dept_id =  $identity_base_array[1];      //部门id
        $model -> position_id =  $identity_base_array[2];  //职位id
        
		$emp_no = $model -> emp_no;
		$name = $model -> name;
		$mobile_tel = $model -> mobile_tel;
               
		$is_del = $model -> is_del;
		$list = $model -> save(); 
		if (false !== $list) {
			if (get_system_config('system_license')) {
				if (!empty($mobile_tel)) {
					import("Weixin.ORG.Util.Weixin");
					$weixin = new \Weixin();
					$mobile_tel = trim($mobile_tel, '+-');
					$weixin -> add_user($emp_no, $name, $mobile_tel);
					$weixin -> update_user($emp_no, $name, $mobile_tel, $is_del);
				}
			}
            syncdate_log('U','User',I('id')); //记录更新日志 
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('编辑成功!');
		} else {
			//错误提示
			$this -> error('编辑失败!');
		}
	}

	private function _weixin_sync($user_list) {

		import("Weixin.ORG.Util.Weixin");
		$weixin = new \Weixin($agent_id);

		$where['emp_no'] = array('in', $user_list);
		$user_list = M("User") -> where(array('is_del' => 0)) -> getField('emp_no,name,mobile_tel');

		$error_code_desc = C('WEIXIN_ERROR_CODE');

		foreach ($user_list as $key => $val) {
			$emp_no = $val['emp_no'];
			$name = $val['name'];
			$mobile_tel = trim($val['mobile_tel'], '+-');
			$error_code =         json_decode($weixin -> add_user($emp_no, $name, $mobile_tel)) -> errcode;
 //
				//
				// $error_code =       json_decode($weixin -> add_user($emp_no, $name, $mobile_tel)) -> errcode;
				//
				// $error_code =           json_decode($weixin -> add_user($emp_no, $name, $mobile_tel)) -> errcode;
				//
				// $error_code =           json_decode($weixin -> add_user($emp_no, $name, $mobile_tel)) -> errcode;
				//
				// $error_code =              json_decode($weixin -> add_user($emp_no, $name, $mobile_tel)) -> errcode;
			

			$list[$key]['error_code'] = $error_code;
			$list[$key]['desc'] = $error_code_desc[$error_code];
			$list[$key]['emp_no'] = $key;
		}
		$this -> assign('list', $list);
		$this -> display();
	}

	protected function add_default_role($user_id) {
		//新增用户自动加入相应权限组
		$RoleUser = M("RoleUser");
		$RoleUser -> user_id = $user_id;
		// 默认加入网站编辑组
		$RoleUser -> role_id = 3;
		$RoleUser -> add();
	}

	//重置密码
	public function reset_pwd() {
		$id = $_POST['user_id'];
		$password = $_POST['password'];
		if ('' == trim($password)) {
			$this -> error('密码不能为空!');
		}
		$User = M('User');
		$User -> password = md5($password);
		$User -> id = $id;
		$result = $User -> save();
		if (false !== $result) {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success("密码修改成功");
		} else {
			$this -> error('重置密码失败！');
		}
	}

	function del_pwd() {
		$id = $_POST['user_id'];
		$User = M('User');
		$where['id'] = array('in', $id);
		$data['pay_pwd'] = '';
		$result = $User -> where($where) -> save($data);
		if (false !== $result) {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success("密码清除成功");
		} else {
			$this -> error('清除密码失败！');
		}
	}

	public function password() {
		$this -> assign("id", I('id'));
		$this -> display();
	}

	function json() {
		header("Content-Type:text/html; charset=utf-8");
		$key = $_REQUEST['key'];

		$model = M("User");
		$where['name'] = array('like', "%" . $key . "%");
		$where['emp_no'] = array('like', "%" . $key . "%");
		$where['_logic'] = 'or';
		$map['_complex'] = $where;
		$list = $model -> where($map) -> field('id,name') -> select();
		exit(json_encode($list));
	}

	function del() {
		$id = I('user_id');
		$admin_user_list = C('ADMIN_USER_LIST');
 
		if (!empty($admin_user_list)) {
			$where['emp_no'] = array('not in', $admin_user_list);
		}
		$where['id'] = array('in', $id);

		$user_id = M("User") -> where($where) -> getField('id', TRUE);
		$emp_no = M("User") -> where($where) -> getField('emp_no', TRUE);

		if (get_system_config('system_license')) {
			import("Weixin.ORG.Util.Weixin");
			$weixin = new \Weixin();
			$restr = $weixin -> del_user($emp_no);
		}
        
        syncdate_log('D','User',implode(",",$emp_no)); //记录更新日志
		//R('Ldap/del', array($emp_no));
		$this -> _destory($user_id);
	}

	public function import() {
		$opmode = $_POST["opmode"];
		//var_dump($opmode);die;
		if ($opmode == "import") {
			$import_user = array();
			$File = D('File');
			$file_driver = C('DOWNLOAD_UPLOAD_DRIVER');
			
			$info = $File -> upload($_FILES, C('DOWNLOAD_UPLOAD'), C('DOWNLOAD_UPLOAD_DRIVER'), C("UPLOAD_{$file_driver}_CONFIG"));
			//var_dump($info);die;
			if (!$info) {
				$this -> error('上传错误');
			} else {
				//取得成功上传的文件信息
				//$uploadList = $upload -> getUploadFileInfo();
				Vendor('Excel.PHPExcel');
				//导入thinkphp第三方类库

				$import_file = $info['uploadfile']["path"];
				//去掉/截取
				$import_file = substr($import_file, 4);
				//var_dump($import_file);die;

				$objPHPExcel = \PHPExcel_IOFactory::load($import_file);
				//$objPHPExcel = \PHPExcel_IOFactory::load("Uploads/Download/user/2017-12/5a37369e4adc7.xlsx");
				//var_dump($objPHPExcel);die;
				//$objPHPExcel = \PHPExcel_IOFactory::load('Uploads/Download/Org/2014-12/547e87ac4b0bf.xls');
				$dept = M("Dept") -> getField('name,id');
				$position = M("Position") -> getField('name,id');
				$role = M("Role") -> getField('name,id');
				$sheetData = $objPHPExcel -> getActiveSheet() -> toArray(null, true, true, true);
				//var_dump($sheetData);die;
				/*$model = D("User");
				for ($i = 2; $i <= count($sheetData); $i++) {
					$data = array();
					$import_user[] = $sheetData[$i]["A"];
					$data_user['emp_no'] = $sheetData[$i]["A"];
					$data_user['name'] = $sheetData[$i]["B"];

					$data_user['dept_id'] = $dept[$sheetData[$i]["C"]];
					$data_user['position_id'] = $position[$sheetData[$i]["D"]];

					$data_user['duty'] = $sheetData[$i]["J"];
					$data_user['office_tel'] = $sheetData[$i]["F"];
					$data_user['mobile_tel'] = $sheetData[$i]["G"];
					$data_user['sex'] = $sheetData[$i]["H"];
					$data_user['birthday'] = $sheetData[$i]["I"];
					$data_user['open_id'] = $sheetData[$i]["A"];
					$data_user['westatus'] = 1;
					$data_user['password'] = md5($sheetData[$i]["A"]);

					$role_list = explode($sheetData[$i]["E"]);
					foreach ($role_list as $key => $val) {
						$data_role[] = $role[$val];
					}
					//$user_id = M("User") -> add($data_user);
					$this -> add_role($user_id, $data_role);
					
				}*/
				//$model = D("User_file");
				for ($i = 3; $i <= count($sheetData); $i++) {
					$data_ufile['user_id'] = $sheetData[$i]["A"];
					$data_ufile['xrzw'] = $sheetData[$i]["E"];
					$data_ufile['xrzsj'] = $sheetData[$i]["F"];
					$data_ufile['age'] = $sheetData[$i]["J"];
					$data_ufile['minzu'] = $sheetData[$i]["K"];
					$data_ufile['jiguan'] = $sheetData[$i]["L"];
					$data_ufile['csd'] = $sheetData[$i]["M"];
					$data_ufile['gzsj'] = $sheetData[$i]["N"];
					$data_ufile['gongli'] = $sheetData[$i]["P"];
					$data_ufile['bndkxsdxxjts'] = $sheetData[$i]["Q"];
					$data_ufile['lzsj'] = $sheetData[$i]["R"];
					$data_ufile['csbjslynx'] = $sheetData[$i]["S"];
					$data_ufile['htlx'] = $sheetData[$i]["T"];
					$data_ufile['pyhtqx'] = $sheetData[$i]["U"];
					$data_ufile['xpgw'] = $sheetData[$i]["V"];
					$data_ufile['gwsj'] = $sheetData[$i]["W"];
					$data_ufile['bgwnx'] = $sheetData[$i]["X"];
					$data_ufile['gwjb'] = $sheetData[$i]["Y"];
					$data_ufile['xpgw_type'] = $sheetData[$i]["Z"];
					$data_ufile['gzjb'] = $sheetData[$i]["AA"];
					$data_ufile['gzjbsj'] = $sheetData[$i]["AB"];
					$data_ufile['zzmm'] = $sheetData[$i]["AC"];
					$data_ufile['rtsj'] = $sheetData[$i]["AD"];
					$data_ufile['jrdpsj'] = $sheetData[$i]["AE"];
					$data_ufile['ypzyjszc'] = $sheetData[$i]["AF"];
					$data_ufile['ypzyjszcsj'] = $sheetData[$i]["AG"];
					$data_ufile['jsdj'] = $sheetData[$i]["AH"];
					$data_ufile['xpzc'] = $sheetData[$i]["AI"];
					$data_ufile['zcjbsrpr'] = $sheetData[$i]["AJ"];
					$data_ufile['xzchjbprsj'] = $sheetData[$i]["AK"];
					$data_ufile['xzwjbpxsj'] = $sheetData[$i]["AL"];
					$data_ufile['zgxueli'] = $sheetData[$i]["AQ"];
					$data_ufile['zgxuewei'] = $sheetData[$i]["AR"];
					$data_ufile['sfzh'] = $sheetData[$i]["AW"];
					$data_ufile['jtdz'] = $sheetData[$i]["AX"];
					$data_ufile['yzbm'] = $sheetData[$i]["AY"];
					$data_ufile['tel'] = $sheetData[$i]["AZ"];
					$data_ufile['beizhu'] = $sheetData[$i]["BA"];
					
					
					//$ufile_id = M("User_file") -> add($data_ufile);
					
				}
				//var_dump($data_ufile);die;
				//var_dump($sheetData[3]);die;
				if (get_system_config('system_license')) {
					$this -> _weixin_sync($import_user);
				}
				$this -> assign('jumpUrl', get_return_url());
				$this -> success('导入成功！');
			}
		} else {
			$this -> display();
		}
	}

	function add_role($user_id, $role_list) {
		$role_list = explode(",", $role_list);
		$role_list = array_filter($role_list);
		$RoleUser = M("RoleUser");
		$RoleUser -> user_id = $user_id;
		foreach ($role_list as $role_id) {
			$RoleUser -> role_id = $role_id;
			$RoleUser -> add();
		}
	}


    /**
     * 图表绘制 (员工信息一览)
     * zzp
     * 2017.9.13
     */
    public function member(){
		//员工总数
		
		// $where_user['emp_no']  = array('not in','admin,shixunchao,yangkun,wangning');
		
        $where_user['is_del'] = array('eq', 0);
		$where_user['peoplestatus'] = array('eq', 0);
        $user_count = M("User") -> where($where_user) -> count();
        $this -> assign('user_count', $user_count);
		
		
		//部门总数
        $where_dept['is_del'] = array('eq', 0);
        $where_dept['pid']=array('eq',1);
        $dept_count = M("Dept") -> where($where_dept) -> count();
        $this -> assign('dept_count', $dept_count);
		
		//部门个数
		
		$where_pid['pid']= array('eq',1);
        $names = M("Dept") -> where($where_pid) ->field('id')-> select();
		//echo M("Dept")->getLastSql()."<br>";
		$len =  count($names);
		
		//var_dump($names);
		//var_dump($len);die;
		//各部门人数
		for ($i=0; $i<$len; $i++) {
		  $where_deptid['emp_no']  = array('not in','admin,shixunchao,yangkun,wangning');
		  $where_deptid['peoplestatus'] = array('eq', 0);
		  $where_deptid['is_del'] = array('eq',0);
		  $where_deptid['dept_id'] = array('eq', current($names[$i]));
		  $dpren[] = M("User") -> where($where_deptid) -> count();
		  //echo M("User")->getLastSql()."<br>"; 
		} 
		
        $this -> assign('dpren', $dpren);
		
		
		
		//学历人数
		$where_boshixueli['is_del'] = array('eq', 0);
		$where_benkexueli['degree'] = array('eq',"本科");
		$benkexueli = M("User_education") -> where($where_benkexueli) -> count();

		/*$where_zz_benkexueli['zz_degree'] = array('eq',"本科");
		$where_zz_benkexueli['zz_start']  = array('neq','');
		$zz_benkexueli = M("User_zzeducation") -> where($where_zz_benkexueli) -> count();
        $benkexueli=$benkexueli + $zz_benkexueli ;*/
		//echo M("User_education")->getLastSql."<br>";

		$this -> assign('benkexueli', $benkexueli);
		
		$where_shuoshixueli['is_del'] = array('eq', 0);
		$where_shuoshixueli['degree'] = array('eq',"硕士");
		$where_shuoshixueli['user_id'] = array('neq',1);
		$shuoshixueli = M("User_education") -> where($where_shuoshixueli) -> count();
		$this -> assign('shuoshixueli', $shuoshixueli);
		
		
		$where_boshixueli['is_del'] = array('eq', 0);
		$where_boshixueli['degree'] = array('eq',"博士");
		$where_boshixueli['user_id'] = array('neq',1);
		$boshixueli = M("User_education") -> where($where_boshixueli) -> count();
		$this -> assign('boshixueli', $boshixueli);
		//var_dump($boshixueli);die;
		//
		$where_xueli['is_del'] = array('eq', 0);
		$where_xueli['user_id'] = array('neq',1);
		$xueli_count = M("User_education") -> where($where_xueli) -> count();
		$qtxueli = $xueli_count-$boshixueli-$shuoshixueli-$benkexueli;
		$this -> assign('qtxueli', $qtxueli);
		//var_dump($qtxueli);die;
		
		
		//岗位人数
		$where_xpgl['is_del'] = array('eq', 0);
		$where_xpgl['xpgw_type'] = array('eq', '管理');
        $user_guanli = M("User_file") -> where($where_xpgl) -> count();
		//var_dump($user_guanli);die;
        $this -> assign('user_guanli', $user_guanli);
		
		$where_xpjs['is_del'] = array('eq', 0);
		$where_xpjs['xpgw_type'] = array('eq', '技术');
        $user_jishu = M("User_file") -> where($where_xpjs) -> count();
		//var_dump($user_jishu);die;
        $this -> assign('user_jishu', $user_jishu);
		
		$where_xpgr['is_del'] = array('eq', 0);
		$where_xpgr['xpgw_type'] = array('eq', '工人');
        $user_gongren = M("User_file") -> where($where_xpgr) -> count();
		//var_dump($user_gongren);die;
		$this -> assign('user_gongren', $user_gongren);
		
		//领导岗位人数
		$where_xpldgl['is_del'] = array('eq', 0);
		$where_xpldgl['xrzw'] = array('neq', "");
		$where_xpldgl['xpgw_type'] = array('eq', '管理');
        $user_ldguanli = M("User_file") -> where($where_xpldgl) -> count();
		//var_dump($user_guanli);die;
        $this -> assign('user_ldguanli', $user_ldguanli);
		
		$where_xpldjs['is_del'] = array('eq', 0);
		$where_xpldjs['xrzw'] = array('neq', "");
		$where_xpldjs['xpgw_type'] = array('eq', '技术');
        $user_ldjishu = M("User_file") -> where($where_xpldjs) -> count();
		//var_dump($user_jishu);die;
        $this -> assign('user_ldjishu', $user_ldjishu);
		
		
		//查询年龄区间
		
		$sanshi['is_del'] = array('eq', 0);
		$sanshi['age'] = array('elt',30);
		$sanshi['user_id'] = array('neq',1);
		$sanshiage = M("User_file") -> where($sanshi) -> count();
		$this -> assign('sanshiage', $sanshiage);
		
		$sanshi15['is_del'] = array('eq', 0);
		$sanshi15['age'] = array('between',array('31','35'));
		$sanshi15['user_id'] = array('neq',1);
		$sanshi15age = M("User_file") -> where($sanshi15) -> count();
		$this -> assign('sanshi15age', $sanshi15age);
		//var_dump($sanshi15age);die;
		$sanshi60['is_del'] = array('eq', 0);
		$sanshi60['age'] = array('between',array('36','40'));
		$sanshi60['user_id'] = array('neq',1);
		$sanshi60age = M("User_file") -> where($sanshi60) -> count();
		$this -> assign('sanshi60age', $sanshi60age);
		//var_dump($sanshi60age);die;
		$sishi15['is_del'] = array('eq', 0);
		$sishi15['age'] = array('between',array('41','45'));
		$sishi15['user_id'] = array('neq',1);
		$sishi15age = M("User_file") -> where($sishi15) -> count();
		$this -> assign('sishi15age', $sishi15age);
		//var_dump($sishi15age);die;
		$sishi60['is_del'] = array('eq', 0);
		$sishi60['age'] = array('between',array('46','50'));
		$sishi60['user_id'] = array('neq',1);
		$sishi60age = M("User_file") -> where($sishi60) -> count();
		$this -> assign('sishi60age', $sishi60age);
		//var_dump($sishi60age);die;
		$wushi15['is_del'] = array('eq', 0);
		$wushi15['age'] = array('between',array('51','55'));
		$wushi15['user_id'] = array('neq',1);
		$wushi15age = M("User_file") -> where($wushi15) -> count();
		$this -> assign('wushi15age', $wushi15age);
		//var_dump($wushi15age);die;
		$wushi69['is_del'] = array('eq', 0);
		$wushi69['age'] = array('between',array('56','59'));
		$wushi69['user_id'] = array('neq',1);
		$wushi69age = M("User_file") -> where($wushi69) -> count();
		$this -> assign('wushi69age', $wushi69age);
		//var_dump($wushi69age);die;
		$liushi['is_del'] = array('eq', 0);
		$liushi['age'] = array('egt',60);
		$liushi['user_id'] = array('neq',1);
		$liushiage = M("User_file") -> where($liushi) -> count();
		$this -> assign('liushiage', $liushiage);
		//var_dump($liushiage);die;
		
		
		//工龄区间
		$where_glshi['user_id'] = array('neq',1);
		$where_glshi['is_del'] = array('eq', 0);
		$where_glshi['gongli'] = array('elt',10);
		$glshi = M("User_file") -> where($where_glshi) -> count();
		$this -> assign('glshi', $glshi);
		$where_glshiershi['is_del'] = array('eq', 0);
		$where_glshiershi['gongli'] = array('between',array('11','20'));
		$glshiershi = M("User_file") -> where($where_glshiershi) -> count();
		$this -> assign('glshiershi', $glshiershi);
		$where_glershi['is_del'] = array('eq', 0);
		$where_glershi['gongli'] = array('egt',21);
		$glershi = M("User_file") -> where($where_glershi) -> count();
		$this -> assign('glershi', $glershi);
		

		//领导工龄区间
		$where_ldglshi['is_del'] = array('eq',0);
		$where_ldglshi['xrzw'] = array('neq',"");
		$where_ldglshi['gongli'] = array('elt',10);
		$ldglshi = M('User_file')->where($where_ldglshi)->count();
        $this->assign('ldglshi',$ldglshi);
        $where_ldglshiershi['is_del'] = array('eq',0);
        $where_ldglshiershi['xrzw'] = array('neq',"");
        $where_ldglshiershi['gongli'] = array('between',array('11','20'));
        $ldglshiershi = M("User_file")->where($where_ldglshiershi)->count();
        $this->assign('ldglshiershi',$ldglshiershi);
        $where_ldglershi['is_del'] = array('eq',0);
        $where_ldglershi['xrzw'] = array('neq',"");
        $where_ldglershi['gongli'] = array('egt','21');
        $ldglershi = M("User_file")->where($where_ldglershi)->count();
        $this->assign('ldglershi',$ldglershi);



		//民族人数
		$where_mzhan['is_del'] = array('eq', 0);
		$where_mzhan['minzu'] = array('eq','汉族');
		$mzhan = M("User_file") -> where($where_mzhan) -> count();
		$this -> assign('mzhan', $mzhan);
		$where_mzman['is_del'] = array('eq', 0);
		$where_mzman['minzu'] = array('eq','满族');
		$mzman = M("User_file") -> where($where_mzman) -> count();
		$this -> assign('mzman', $mzman);
		$where_mzshe['is_del'] = array('eq', 0);
		$where_mzshe['minzu'] = array('eq','畲族');
		$mzshe = M("User_file") -> where($where_mzshe) -> count();
		$this -> assign('mzshe', $mzshe);
		$where_mzhui['is_del'] = array('eq', 0);
		$where_mzhui['minzu'] = array('eq','回族');
		$mzhui = M("User_file") -> where($where_mzhui) -> count();
		$this -> assign('mzhui', $mzhui);
		$where_mzdong['is_del'] = array('eq', 0);
		$where_mzdong['minzu'] = array('eq','侗族');
		$mzdong = M("User_file") -> where($where_mzdong) -> count();
		$this -> assign('mzdong', $mzdong);
		
		
		//领导民族人数
		$where_ldmzhan['is_del'] = array('eq',0);
		$where_ldmzhan['xrzw'] = array('neq', "");
		$where_ldmzhan['minzu'] = array('eq','汉族');
		$ldmzhan = M("User_file") -> where($where_ldmzhan) -> count();
		$this -> assign('ldmzhan', $ldmzhan);
		$where_ldmzman['is_del'] = array('eq',0);
		$where_ldmzman['xrzw'] = array('neq', "");
		$where_ldmzman['minzu'] = array('eq','满族');
		$ldmzman = M("User_file") -> where($where_ldmzman) -> count();
		$this -> assign('ldmzman', $ldmzman);
		$where_ldmzshe['is_del'] = array('eq',0);
		$where_ldmzshe['xrzw'] = array('neq', "");
		$where_ldmzshe['minzu'] = array('eq','畲族');
		$ldmzshe = M("User_file") -> where($where_ldmzshe) -> count();
		$this -> assign('ldmzshe', $ldmzshe);
		
		
		//性别人数
		$where_fsex['emp_no']  = array('not in','admin,shixunchao,yangkun,wangning');
		$where_fsex['is_del'] = array('eq', 0);
		$where_fsex['id'] = array('neq',1);
		$where_fsex['peoplestatus'] = array('eq', 0);
		$where_fsex['sex'] = array('neq', 'male');
        $user_fsex = M("User") -> where($where_fsex) -> count();
		//var_dump($user_fsex);die;
        $this -> assign('user_fsex', $user_fsex);
		
		$where_sex['emp_no']  = array('not in','admin,shixunchao,yangkun,wangning');
		$where_sex['is_del'] = array('eq', 0);
		$where_sex['id'] = array('neq',1);
		$where_sex['peoplestatus'] = array('eq', 0);
		$where_sex['sex'] = array('eq', 'male');
        $user_sex = M("User") -> where($where_sex) -> count();
		//var_dump($user_sex);die;
        $this -> assign('user_sex', $user_sex);
		
		//领导性别人数
		$where_ldid['is_del'] = array('eq',0);
		$where_ldid['xrzw'] = array('neq', "");
		$user_ldid = M("User_file") -> where($where_ldid) -> field('user_id') -> select();
		$user_ldidlist = rotate($user_ldid);
		$usersex = M("User");
		$map1a['id'] = array('in', $user_ldidlist['user_id']);
		$map1a['sex'] = array('neq', 'male');
		$nv = $usersex -> where($map1a) -> count();
		$this -> assign('nv', $nv);
		//var_dump($nan);die;
		
		$where_ldid['is_del'] = array('eq',0);
		$where_ldid['xrzw'] = array('neq', "");
		$user_ldid = M("User_file") -> where($where_ldid) -> field('user_id') -> select();
		$user_ldidlist = rotate($user_ldid);
		$usersex = M("User");
		$map1b['id'] = array('in', $user_ldidlist['user_id']);
		$map1b['sex'] = array('eq', 'male');
		$nan = $usersex -> where($map1b) -> count();
		$this -> assign('nan', $nan);
		
		
		
		
		//聘用合同类型人数
		$where_htcq['is_del'] = array('eq',0);
		$where_htcq['htlx'] = array('eq', '长期');
        $user_htcq = M("User_file") -> where($where_htcq) -> count();
		//var_dump($user_guanli);die;
        $this -> assign('user_htcq', $user_htcq);
		
		$where_htzq['is_del'] = array('eq',0);
		$where_htzq['htlx'] = array('eq', '中期');
        $user_htzq = M("User_file") -> where($where_htzq) -> count();
		//var_dump($user_jishu);die;
        $this -> assign('user_htzq', $user_htzq);
		
		$where_htdq['is_del'] = array('eq',0);
		$where_htdq['htlx'] = array('eq', '短期');
        $user_htdq = M("User_file") -> where($where_htdq) -> count();
		//var_dump($user_gongren);die;
		$this -> assign('user_htdq', $user_htdq);
		
		//领导聘用合同类型人数
		$where_ldhtcq['is_del'] = array('eq',0);
		$where_ldhtcq['xrzw'] = array('neq', "");
		$where_ldhtcq['htlx'] = array('eq', '长期');
        $user_ldhtcq = M("User_file") -> where($where_ldhtcq) -> count();
		//var_dump($user_ldhtcq);die;
       
        $this -> assign('user_ldhtcq', $user_ldhtcq);
		$where_ldhtzq['is_del'] = array('eq',0);
		$where_ldhtzq['xrzw'] = array('neq', "");
		$where_ldhtzq['htlx'] = array('eq', '中期');
        $user_ldhtzq = M("User_file") -> where($where_ldhtzq) -> count();
		//var_dump($user_ldhtzq);
        $this -> assign('user_ldhtzq', $user_ldhtzq);

		$where_ldhtdq['is_del'] = array('eq',0);
		$where_ldhtdq['xrzw'] = array('neq', "");
		$where_ldhtdq['htlx'] = array('eq', '短期');
        $user_ldhtdq = M("User_file") -> where($where_ldhtdq) -> count();
		//var_dump($user_ldhtdq);die;
		$this -> assign('user_ldhtdq', $user_ldhtdq);
		
		
		
		
		//文件总数
        $file_count = M("File") -> count();
        $this -> assign('file_count', $file_count);
		
		

        $file_spage = M("File") -> sum('size');
        $this -> assign('file_spage', $file_spage);
		
		//政治面貌人数
		$where_zzmmdy['is_del'] = array('eq',0);
		$where_zzmmdy['zzmm'] = array('eq', "中共党员");
		$zmdy_count = M("User_file") -> where($where_zzmmdy) -> count();
		$this -> assign('zmdy_count', $zmdy_count);
		
		$where_zzmmqz['is_del'] = array('eq',0);
		$where_zzmmqz['zzmm'] = array('eq', "群众");
		$zmqz_count = M("User_file") -> where($where_zzmmqz) -> count();
		$this -> assign('zmqz_count', $zmqz_count);
		
		$where_zzmmty['is_del'] = array('eq',0);
		$where_zzmmty['zzmm'] = array('eq', "团员");
		$zmty_count = M("User_file") -> where($where_zzmmty) -> count();
		$this -> assign('zmty_count', $zmty_count);
		
		$where_zzmmzgmzcjh['is_del'] = array('eq',0);
		$where_zzmmzgmzcjh['zzmm'] = array('eq', "中国民主促进会");
		$zmzgmzcjh_count = M("User_file") -> where($where_zzmmzgmzcjh) -> count();
		$this -> assign('zmzgmzcjh_count', $zmzgmzcjh_count);
		
		$where_zzmmzgmzjgh['is_del'] = array('eq',0);
		$where_zzmmzgmzjgh['zzmm'] = array('eq', "中国民主建国会");
		$zmzgmzjgh_count = M("User_file") -> where($where_zzmmzgmzjgh) -> count();
		$this -> assign('zmzgmzjgh_count', $zmzgmzjgh_count);
		
		$where_zzmmzgybdy['is_del'] = array('eq',0);
		$where_zzmmzgybdy['zzmm'] = array('eq', "中共预备党员");
		$zmzgybdy_count = M("User_file") -> where($where_zzmmzgybdy) -> count();
		$this -> assign('zmzgybdy_count', $zmzgybdy_count);
		
		//籍贯人数
		$where_jgbj['is_del'] = array('eq',0);
		$where_jgbj['jiguan'] = array('eq', "北京市");
		$jgbj_count = M("User_file") -> where($where_jgbj) -> count();
		$this -> assign('jgbj_count', $jgbj_count);
		$where_jgzj['is_del'] = array('eq',0);
		$where_jgzj['jiguan'] = array('eq', "浙江省");
		$jgzj_count = M("User_file") -> where($where_jgzj) -> count();
		$this -> assign('jgzj_count', $jgzj_count);
		$where_jghb['is_del'] = array('eq',0);
		$where_jghb['jiguan'] = array('eq', "河北省");
		$jghb_count = M("User_file") -> where($where_jghb) -> count();
		$this -> assign('jghb_count', $jghb_count);
		$where_jgnmg['is_del'] = array('eq',0);
		$where_jgnmg['jiguan'] = array('eq', "内蒙古自治区");
		$jgnmg_count = M("User_file") -> where($where_jgnmg) -> count();
		$this -> assign('jgnmg_count', $jgnmg_count);
		$where_jgsd['is_del'] = array('eq',0);
		$where_jgsd['jiguan'] = array('eq', "山东省");
		$jgsd_count = M("User_file") -> where($where_jgsd) -> count();
		$this -> assign('jgsd_count', $jgsd_count);
		$where_jghenan['is_del'] = array('eq',0);
		$where_jghenan['jiguan'] = array('eq', "河南省");
		$jghenan_count = M("User_file") -> where($where_jghenan) -> count();
		$this -> assign('jghenan_count', $jghenan_count);
		$where_jgah['is_del'] = array('eq',0);
		$where_jgah['jiguan'] = array('eq', "安徽省");
		$jgah_count = M("User_file") -> where($where_jgah) -> count();
		$this -> assign('jgah_count', $jgah_count);
		$where_jgtj['is_del'] = array('eq',0);
		$where_jgtj['jiguan'] = array('eq', "天津市");
		$jgtj_count = M("User_file") -> where($where_jgtj) -> count();
		$this -> assign('jgtj_count', $jgtj_count);
		$where_jgjs['is_del'] = array('eq',0);
		$where_jgjs['jiguan'] = array('eq', "江苏省");
		$jgjs_count = M("User_file") -> where($where_jgjs) -> count();
		$this -> assign('jgjs_count', $jgjs_count);
		$where_jgsc['is_del'] = array('eq',0);
		$where_jgsc['jiguan'] = array('eq', "四川省");
		$jgsc_count = M("User_file") -> where($where_jgsc) -> count();
		$this -> assign('jgsc_count', $jgsc_count);
		$where_jgsx['is_del'] = array('eq',0);
		$where_jgsx['jiguan'] = array('eq', "山西省");
		$jgsx_count = M("User_file") -> where($where_jgsx) -> count();
		$this -> assign('jgsx_count', $jgsx_count);
		$where_jggd['is_del'] = array('eq',0);
		$where_jggd['jiguan'] = array('eq', "广东省");
		$jggd_count = M("User_file") -> where($where_jggd) -> count();
		$this -> assign('jggd_count', $jggd_count);
		$where_jgln['is_del'] = array('eq',0);
		$where_jgln['jiguan'] = array('eq', "辽宁省");
		$jgln_count = M("User_file") -> where($where_jgln) -> count();
		$this -> assign('jgln_count', $jgln_count);
		$where_jghunan['is_del'] = array('eq',0);
		$where_jghunan['jiguan'] = array('eq', "湖南省");
		$jghunan_count = M("User_file") -> where($where_jghunan) -> count();
		$this -> assign('jghunan_count', $jghunan_count);
		$where_jghlj['is_del'] = array('eq',0);
		$where_jghlj['jiguan'] = array('eq', "黑龙江省");
		$jghlj_count = M("User_file") -> where($where_jghlj) -> count();
		$this -> assign('jghlj_count', $jghlj_count);
		$where_jgsh['is_del'] = array('eq',0);
		$where_jgsh['jiguan'] = array('eq', "上海市");
		$jgsh_count = M("User_file") -> where($where_jgsh) -> count();
		$this -> assign('jgsh_count', $jgsh_count);
		$where_jgjx['is_del'] = array('eq',0);
		$where_jgjx['jiguan'] = array('eq', "江西省");
		$jgjx_count = M("User_file") -> where($where_jgjx) -> count();
		$this -> assign('jgjx_count', $jgjx_count);
		$where_jgyn['is_del'] = array('eq',0);
		$where_jgyn['jiguan'] = array('eq', "云南省");
		$jgyn_count = M("User_file") -> where($where_jgyn) -> count();
		$this -> assign('jgyn_count', $jgyn_count);
		$where_jghb['is_del'] = array('eq',0);
		$where_jghb['jiguan'] = array('eq', "湖北省");
		$jghb_count = M("User_file") -> where($where_jghb) -> count();
		$this -> assign('jghb_count', $jghb_count);
		$where_jgjl['is_del'] = array('eq',0);
		$where_jgjl['jiguan'] = array('eq', "吉林省");
		$jgjl_count = M("User_file") -> where($where_jgjl) -> count();
		$this -> assign('jgjl_count', $jgjl_count);
		$where_jgfj['is_del'] = array('eq',0);
		$where_jgfj['jiguan'] = array('eq', "福建省");
		$jgfj_count = M("User_file") -> where($where_jgfj) -> count();
		$this -> assign('jgfj_count', $jgfj_count);
		//var_dump($jgfj_count);die;
		
		
		//领导籍贯
		$where_ldjgbj['is_del'] = array('eq',0);
		$where_ldjgbj['xrzw'] = array('neq', "");
		$where_ldjgbj['jiguan'] = array('eq', "北京市");
		$ldjgbj_count = M("User_file") -> where($where_ldjgbj) -> count();
		$this -> assign('ldjgbj_count', $ldjgbj_count);
		$where_ldjgzj['is_del'] = array('eq',0);
		$where_ldjgzj['xrzw'] = array('neq', "");
		$where_ldjgzj['jiguan'] = array('eq', "浙江省");
		$ldjgzj_count = M("User_file") -> where($where_ldjgzj) -> count();
		$this -> assign('ldjgzj_count', $ldjgzj_count);
		$where_ldjghb['xrzw'] = array('neq', "");
		$where_ldjghb['is_del'] = array('eq',0);
		$where_ldjghb['jiguan'] = array('eq', "河北省");
		$ldjghb_count = M("User_file") -> where($where_ldjghb) -> count();
		$this -> assign('ldjghb_count', $ldjghb_count);
		$where_ldjgnmg['is_del'] = array('eq',0);
		$where_ldjgnmg['xrzw'] = array('neq', "");
		$where_ldjgnmg['jiguan'] = array('eq', "内蒙古自治区");
		$ldjgnmg_count = M("User_file") -> where($where_ldjgnmg) -> count();
		$this -> assign('ldjgnmg_count', $ldjgnmg_count);
		$where_ldjgsd['is_del'] = array('eq',0);
		$where_ldjgsd['xrzw'] = array('neq', "");
		$where_ldjgsd['jiguan'] = array('eq', "山东省");
		$ldjgsd_count = M("User_file") -> where($where_ldjgsd) -> count();
		$this -> assign('ldjgsd_count', $ldjgsd_count);
		$where_ldjghenan['is_del'] = array('eq',0);
		$where_ldjghenan['xrzw'] = array('neq', "");
		$where_ldjghenan['jiguan'] = array('eq', "河南省");
		$ldjghenan_count = M("User_file") -> where($where_ldjghenan) -> count();	
		$this -> assign('ldjghenan_count', $ldjghenan_count);
		$where_ldjgah['is_del'] = array('eq',0);
		$where_ldjgah['xrzw'] = array('neq', "");
		$where_ldjgah['jiguan'] = array('eq', "安徽省");
		$ldjgah_count = M("User_file") -> where($where_ldjgah) -> count();
		$this -> assign('ldjgah_count', $ldjgah_count);
		$where_ldjgtj['is_del'] = array('eq',0);
		$where_ldjgtj['xrzw'] = array('neq', "");
		$where_ldjgtj['jiguan'] = array('eq', "天津市");
		$ldjgtj_count = M("User_file") -> where($where_ldjgtj) -> count();
		$this -> assign('ldjgtj_count', $ldjgtj_count);
		$where_ldjgsx['is_del'] = array('eq',0);
		$where_ldjgsx['xrzw'] = array('neq', "");
		$where_ldjgsx['jiguan'] = array('eq', "山西省");
		$ldjgsx_count = M("User_file") -> where($where_ldjgsx) -> count();
		$this -> assign('ldjgsx_count', $ldjgsx_count);
		$where_ldjgsh['is_del'] = array('eq',0);
		$where_ldjgsh['xrzw'] = array('neq', "");
		$where_ldjgsh['jiguan'] = array('eq', "上海市");
		$ldjgsh_count = M("User_file") -> where($where_ldjgsh) -> count();
		$this -> assign('ldjgsh_count', $ldjgsh_count);
		$where_ldjgjx['is_del'] = array('eq',0);
		$where_ldjgjx['xrzw'] = array('neq', "");
		$where_ldjgjx['jiguan'] = array('eq', "江西省");
		$ldjgjx_count = M("User_file") -> where($where_ldjgjx) -> count();
		$this -> assign('ldjgjx_count', $ldjgjx_count);
		$where_ldjghb['is_del'] = array('eq',0);
		$where_ldjghb['xrzw'] = array('neq', "");
		$where_ldjghb['jiguan'] = array('eq', "湖北省");
		$ldjghb_count = M("User_file") -> where($where_ldjghb) -> count();
		$this -> assign('ldjghb_count', $ldjghb_count);
		$where_ldjgjl['is_del'] = array('eq',0);
		$where_ldjgjl['xrzw'] = array('neq', "");
		$where_ldjgjl['jiguan'] = array('eq', "吉林省");
		$ldjgjl_count = M("User_file") -> where($where_ldjgjl) -> count();
		$this -> assign('ldjgjl_count', $ldjgjl_count);
		
		
		
		
		//职称一览饼
		$where_userzcgj['is_del'] = array('eq',0);
		$where_userzcgj['jsdj'] = array('eq', "高级");
		$userzcgj_count = M("User_file") -> where($where_userzcgj) -> count();
		$this -> assign('userzcgj_count', $userzcgj_count);
		$where_userzczj['is_del'] = array('eq',0);
		$where_userzczj['jsdj'] = array('eq', "中级");
		$userzczj_count = M("User_file") -> where($where_userzczj) -> count();
		$this -> assign('userzczj_count', $userzczj_count);
		$where_userzccj['is_del'] = array('eq',0);
		$where_userzccj['jsdj'] = array('eq', "初级");
		$userzccj_count = M("User_file") -> where($where_userzccj) -> count();
		$this -> assign('userzccj_count', $userzccj_count);
		$where_userzcgr['is_del'] = array('eq',0);
		$where_userzcgr['jsdj'] = array('eq', "工人");
		$userzcgr_count = M("User_file") -> where($where_userzcgr) -> count();
		$this -> assign('userzcgr_count', $userzcgr_count);
		$userzcqt['is_del'] = array('eq',0);
		$userzcqt['user_id'] = array('neq',1);
		$userzczs_count = M("User_file")->where($userzcqt) -> count();
		$userzcqt_count = $userzczs_count - $userzcgj_count - $userzczj_count - $userzccj_count - $userzcgr_count;
		$this -> assign('userzcqt_count', $userzcqt_count);
		
		
		
		//职称一览柱
		$where_userjs13['is_del'] = array('eq',0);
		$where_userjs13['gzjb'] = array('eq', "技术13级");
		$userjs13_count = M("User_file") -> where($where_userjs13) -> count();
		$this -> assign('userjs13_count', $userjs13_count);
		$where_userjs12['is_del'] = array('eq',0);
		$where_userjs12['gzjb'] = array('eq', "技术12级");
		$userjs12_count = M("User_file") -> where($where_userjs12) -> count();
		$this -> assign('userjs12_count', $userjs12_count);
		$where_userjs11['is_del'] = array('eq',0);
		$where_userjs11['gzjb'] = array('eq', "技术11级");
		$userjs11_count = M("User_file") -> where($where_userjs11) -> count();
		$this -> assign('userjs11_count', $userjs11_count);
		$where_userjs10['is_del'] = array('eq',0);
		$where_userjs10['gzjb'] = array('eq', "技术10级");
		$userjs10_count = M("User_file") -> where($where_userjs10) -> count();
		$this -> assign('userjs10_count', $userjs10_count);
		$where_userjs9['is_del'] = array('eq',0);
		$where_userjs9['gzjb'] = array('eq', "技术9级");
		$userjs9_count = M("User_file") -> where($where_userjs9) -> count();
		$this -> assign('userjs9_count', $userjs9_count);
		$where_userjs8['is_del'] = array('eq',0);
		$where_userjs8['gzjb'] = array('eq', "技术8级");
		$userjs8_count = M("User_file") -> where($where_userjs8) -> count();
		$this -> assign('userjs8_count', $userjs8_count);
		$where_userjs7['is_del'] = array('eq',0);
		$where_userjs7['gzjb'] = array('eq', "技术7级");
		$userjs7_count = M("User_file") -> where($where_userjs7) -> count();
		$this -> assign('userjs7_count', $userjs7_count);
		$where_userjs6['is_del'] = array('eq',0);
		$where_userjs6['gzjb'] = array('eq', "技术6级");
		$userjs6_count = M("User_file") -> where($where_userjs6) -> count();
		$this -> assign('userjs6_count', $userjs6_count);
		$where_userjs5['is_del'] = array('eq',0);
		$where_userjs5['gzjb'] = array('eq', "技术5级");
		$userjs5_count = M("User_file") -> where($where_userjs5) -> count();
		$this -> assign('userjs5_count', $userjs5_count);
		$where_userjs4['is_del'] = array('eq',0);
		$where_userjs4['gzjb'] = array('eq', "技术4级");
		$userjs4_count = M("User_file") -> where($where_userjs4) -> count();
		$this -> assign('userjs4_count', $userjs4_count);
		
		
		
		
		
		
		//领导政治面貌
		$where_lingdaody['is_del'] = array('eq',0);
		$where_lingdaody['xrzw'] = array('neq', "");
		$where_lingdaody['zzmm'] = array('eq', "中共党员");
		$lingdaody_count = M("User_file") -> where($where_lingdaody) -> count();
		$this -> assign('lingdaody_count', $lingdaody_count);
		
		$where_lingdaoqz['is_del'] = array('eq',0);
		$where_lingdaoqz['xrzw'] = array('neq', "");
		$where_lingdaoqz['zzmm'] = array('eq', "群众");
		$lingdaoqz_count = M("User_file") -> where($where_lingdaoqz) -> count();
		$this -> assign('lingdaoqz_count', $lingdaoqz_count);
		
		$where_lingdaocjh['is_del'] = array('eq',0);
		$where_lingdaocjh['xrzw'] = array('neq', "");
		$where_lingdaocjh['zzmm'] = array('eq', "中国民主促进会");
		$lingdaocjh_count = M("User_file") -> where($where_lingdaocjh) -> count();
		$this -> assign('lingdaocjh_count', $lingdaocjh_count);
		
		//领导年龄
		$where_lingdaoage35['is_del'] = array('eq',0);
		$where_lingdaoage35['age'] = array('between',array('31','35'));
		$where_lingdaoage35['xrzw'] = array('neq', "");
		$lingdaoage35 = M("User_file") -> where($where_lingdaoage35) -> count();
		$this -> assign('lingdaoage35', $lingdaoage35);
		
		$where_lingdaoage40['is_del'] = array('eq',0);
		$where_lingdaoage40['age'] = array('between',array('36','40'));
		$where_lingdaoage40['xrzw'] = array('neq', "");
		$lingdaoage40 = M("User_file") -> where($where_lingdaoage40) -> count();
		$this -> assign('lingdaoage40', $lingdaoage40);
		
		$where_lingdaoage45['is_del'] = array('eq',0);
		$where_lingdaoage45['age'] = array('between',array('41','45'));
		$where_lingdaoage45['xrzw'] = array('neq', "");
		$lingdaoage45 = M("User_file") -> where($where_lingdaoage45) -> count();
		$this -> assign('lingdaoage45', $lingdaoage45);
		
		$where_lingdaoage50['is_del'] = array('eq',0);
		$where_lingdaoage50['age'] = array('between',array('46','50'));
		$where_lingdaoage50['xrzw'] = array('neq', "");
		$lingdaoage50 = M("User_file") -> where($where_lingdaoage50) -> count();
		$this -> assign('lingdaoage50', $lingdaoage50);
		
		$where_lingdaoage55['is_del'] = array('eq',0);
		$where_lingdaoage55['age'] = array('between',array('51','55'));
		$where_lingdaoage55['xrzw'] = array('neq', "");
		$lingdaoage55 = M("User_file") -> where($where_lingdaoage55) -> count();
		$this -> assign('lingdaoage55', $lingdaoage55);
		
		$where_lingdaoage59['is_del'] = array('eq',0);
		$where_lingdaoage59['age'] = array('between',array('56','59'));
		$where_lingdaoage59['xrzw'] = array('neq', "");
		$lingdaoage59 = M("User_file") -> where($where_lingdaoage59) -> count();
		$this -> assign('lingdaoage59', $lingdaoage59);
		
		
		//领导学历
		//
		$where_lingdao['is_del'] = array('eq',0);
		$where_lingdao['xrzw'] = array('neq',"");
		$where_lingdaoid = M("User_file")->where($where_lingdao)->field('user_id')->select();
		$where_lingdaoid = rotate($where_lingdaoid);

		$where_lingdaobk['is_del'] = array('eq',0);
		$where_lingdaobk['degree'] = array('eq',"本科");
		$where_lingdaobk['uid'] = array('in', $where_lingdaoid['user_id']);
		$lingdaobk = M("User_education") -> where($where_lingdaobk) -> count();
        
		$this -> assign('lingdaobk', $lingdaobk);
		$where_lingdaosj['is_del'] = array('eq',0);
		$where_lingdaosj['degree'] = array('eq',"硕士");
		$where_lingdaosj['uid'] = array('in', $where_lingdaoid['user_id']);
		$lingdaosj = M("User_education") -> where($where_lingdaosj) -> count();
		$this -> assign('lingdaosj', $lingdaosj);
		$where_lingdaobs['is_del'] = array('eq',0);
		$where_lingdaobs['degree'] = array('eq',"博士");
		$where_lingdaobs['uid'] = array('in', $where_lingdaoid['user_id']);
		$lingdaobs = M("User_education") -> where($where_lingdaobs) -> count();
		$this -> assign('lingdaobs', $lingdaobs);
	
	    $where_lingdaoqt['is_del'] = array('eq',0);
	    $where_lingdaoqt['uid'] = array('in', $where_lingdaoid['user_id']);
		$ldxueli_count = M("User_education") -> where($where_lingdaoqt) -> count();
		$ldqtxueli_count = $ldxueli_count-$lingdaobk-$lingdaosj-$lingdaobs;
		$this -> assign('ldqtxueli_count', $ldqtxueli_count);
		
		
		
        
        $where_size['type'] = array('eq', 1);
        $file_size = M("SystemLog") -> where($where_size) -> getField('time,data');
        $file_size = conv_flot($file_size);
        $this -> assign('file_size', $file_size);
		
		//print_r($file_size);die;

        //绘制 一览表
        $where_position['is_del'] = array('eq', 0);
        $position = M("Position") -> where($where_position) ->select();

        foreach($position as $k=>$v){
            $where_user['is_del'] = array('eq', 0);
            $where_user['position_id'] = array('eq', $v['id']);

            $new_position[$k]['value'] = M("User") -> where($where_user) ->count();
            $new_position[$k]['name'] = $v['name'];

        }
        $this->assign('position',json_encode($new_position));
        $this -> display();
    }
	
	
	
	//合同到期提醒
	public function hetong(){
		$plugin['date'] = true;
        $this -> assign("plugin", $plugin);

        $model = M("Position");
        $list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
        $this -> assign('position_list', $list);

        $model = M("Dept");
        $list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
        $this -> assign('dept_list', $list);

        $model = M("User");
        $list = $model -> where('is_del=0') -> order('sort asc') -> getField('emp_no,name');
        $this -> assign('agent_list', $list);


        if (isset($_POST['eq_is_del'])) {
            $eq_is_del = $_POST['eq_is_del'];
        } else {
            $eq_is_del = "0";
        }
        //print_r(think_decrypt("MDAwMDAwMDAwMITdma4"));die;
        $this -> assign('eq_is_del', $eq_is_del);

        $map = $this -> _search();


        if (method_exists($this, '_search_filter')) {
            $this -> _search_filter($map);
        }
		
		// $hetongtj['htlx'] = array('neq','长期');
		$hetongtj['is_del'] = array('eq',0);
		// $hetongtj[''] = array('neq', );
		$hetongtj['pyhtqx'] = array('EXP','IS NOT NULL');
		$hetongtj['pyhtqxjs'] = array('EXP','IS NOT NULL');
		$model = M('user_file');
		$hetong = $model->where($hetongtj)->field('user_id,pyhtqxjs')->select();
		$date2 = date("Y-m-d");
		foreach($hetong as $k=>$v){
			$date1 = $v['pyhtqxjs'];
			$monthNum = getMonthNum( $date1 , $date2 );


			$d1 = strtotime($date1);
            $d2 = strtotime($date2);
            $Days = round(($d1-$d2)/3600/24);



			if($Days < 31){
				$arr[] = $v['user_id'];
			}
		}
		
		$map['id']=array('in',$arr);
		$map['peoplestatus']=array('in',array(0,1));
		$model = M('user');
		
		if (!empty($model)) {
            $this -> _lists($model, $map, "emp_no");
        }


        $this -> display();
	}
	
	public function one_key_addition(){
		$plugin['date'] = true;
		// var_dump($plugin);die;
        $this -> assign("plugin", $plugin);
		$this->display();
	}
	
	//按钮添加学历
	public function add_education_data(){
		$map['type'] = 88;
		$map['step'] = 40;
		$flow_model = M('flow');
		$flow_data = $flow_model->where($map)->select();
		$education_model = M('user_education');
		// dump($flow_data);die;
		$arr[] = '';
		foreach($flow_data as $k=>$v){
			// dump($v);die;
			$js = json_decode($v['udf_data'], true);
			// var_dump($js);
			$arr['emp_no'] = $v['emp_no'];
			$arr['name'] = $v['user_name'];
			$arr['uid'] = $v['user_id'];
			$arr['start'] = $js['365'];
			$arr['graduation'] = $js['366'];
			$arr['school_name'] = $js['337'];
			$arr['major'] = $js['338'];
			$arr['system'] = $js['339'];
			$arr['degree'] = $js['340'];
			$arr['degree_wei'] = $js['341'];
			$arr['is_del'] = 0;
			$arr['flow_id'] = $v['id'];
			$arr['add_file'] = $v['add_file'];
			
			$result = $education_model->where("flow_id = ".$arr['flow_id'])->select();
			if(!empty($result)){
				
			}else{
				$education = $education_model->add($arr);
			}
		}
		
		
		$this -> success('添加成功', U('User/one_key_addition'));
		
	
	}
	
	//按钮添加在职学历
	public function add_zzeducation_data(){
		$map['type'] = 89;
		$map['step'] = 40;
		$flow_model = M('flow');
		$flow_data = $flow_model->where($map)->select();
		$zzeducation_model = M('user_zzeducation');
		
		$flow_data = $flow_model->where($map)->select();
		
		$arr[] = '';
		foreach($flow_data as $k=>$v){
			$js = json_decode($v['udf_data'], true);
			$arr['emp_no'] = $v['emp_no'];
			$arr['name'] = $v['user_name'];
			$arr['uid'] = $v['user_id'];
			$arr['zz_start'] = $js['342'];
			$arr['zz_graduation'] = $js['343'];
			$arr['zz_school_name'] = $js['344'];
			$arr['zz_major'] = $js['345'];
			$arr['zz_system'] = $js['346'];
			$arr['zz_degree'] = $js['347'];
			$arr['zz_degree_wei'] = $js['348'];
			$arr['is_del'] = 0;
			$arr['flow_id'] = $v['id'];
			$arr['zz_add_file'] = $v['add_file'];
			
			$result = $zzeducation_model->where("flow_id = ".$arr['flow_id'])->select();
			if(!empty($result)){
				
			}else{
				$education = $zzeducation_model->add($arr);
			}
		}
		
		$this -> success('添加成功', U('User/one_key_addition'));
	}
	
	//一键培训经历
	public function add_train_data(){
		
		$map['type'] = 90;
		$map['step'] = 40;
		$flow_model = M('flow');
		$flow_data = $flow_model->where($map)->select();
		$zzeducation_model = M('user_zzeducation');
		
		$flow_data = $flow_model->where($map)->select();
		
		$arr[] = '';
		foreach($flow_data as $k=>$v){
			$js = json_decode($v['udf_data'], true);
			$arr['emp_no'] = $v['emp_no'];
			$arr['name'] = $v['user_name'];
			$arr['uid'] = $v['user_id'];
			$arr['train_start'] = $js['351'];
			$arr['train_graduation'] = $js['352'];
			$arr['train_name'] = $js['350'];
			$arr['train_hours'] = $js['353'];
			$arr['train_cost'] = $js['354'];
			$arr['train_place'] = $js['355'];
			$arr['host_unit'] = $js['356'];
			$arr['train_agency'] = $js['357'];
			$arr['train_content'] = $js['358'];
			$arr['train_user'] = $js['360'];
			$arr['train_offjob'] = $js['361'];
			$arr['train_remark'] = $js['362'];
			$arr['is_del'] = 0;
			$arr['flow_id'] = $v['id'];
			$arr['train_add_file'] = $v['add_file'];
			
			$result = $zzeducation_model->where("flow_id = ".$arr['flow_id'])->select();
			if(!empty($result)){
				
			}else{
				$education = $zzeducation_model->add($arr);
			}
		}
		
		$this -> success('添加成功', U('User/one_key_addition'));
	}
	
	//一键年度考核
	public function add_assess_data(){
		var_dump('考核');
	}
	
	//一键获奖情况
	public function add_prize_data(){
		var_dump('获奖');
	}
	
	
	

    //图表绘制 start  flot date 插件
    function get_flot_data() {
        $range=I('range');

        switch ($range) {
            case 'm':
                $offset=mktime(0, 0 , 0,date("m")-1,date("d"),date("Y"));
                break;

            case 'q':
                $offset=mktime(0, 0 , 0,date("m")-3,date("d"),date("Y"));
                break;

            case 'y':
                $offset=mktime(0, 0 , 0,date("m")-112,date("d"),date("Y"));
                break;
            default:

                break;
        }
        $where_size['type'] = array('eq', 1);
        $where_size['time']=array('gt',$offset);
        $file_size = M("SystemLog") -> where($where_size) -> getField('time,data');
        $file_size = conv_flot($file_size);

        $where_count['type'] = array('eq', 2);
        $where_count['time']=array('gt',$offset);
        $file_count = M("SystemLog") -> where($where_count) -> getField('time,data');
        $file_count = conv_flot($file_count);

        $return['file_size']=$file_size;
        $return['file_count']=$file_count;
        $this->ajaxReturn($return);
    }

    function RandAbc($length = "") {//返回随机字符串
        $str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        return str_shuffle($str);
    }

    function get_auth() {
        $server_info = $this -> _SERVER('SERVER_NAME') . '|' . $this -> _SERVER('REMOTE_ADDR');
        $server_info .= '|' . $this -> _SERVER('DOCUMENT_ROOT');

        //$result = @file_get_contents('http://www.smeoa.com/get_auth.php?' . base64_encode($server_info));
        $result = "";
        return $result;
    }

    function _GET($n) {
        return isset($_GET[$n]) ? $_GET[$n] : NULL;
    }

    function _SERVER($n) {
        return isset($_SERVER[$n]) ? $_SERVER[$n] : '[undefine]';
    }
    //图表绘制 end


    /**
     * 员工档案
     * zzp
     * 2017.9.13
     */
    public function member_list(){

       // dump($_POST);

        $plugin['date'] = true;
        $this -> assign("plugin", $plugin);

        $model = M("Position");
        $list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
        $this -> assign('position_list', $list);
		//print_r($list);die;
        $model = M("Dept");
        $list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
        $this -> assign('dept_list', $list);
		
        $model = M("User");
        $list = $model -> where('is_del=0') -> order('id asc') -> getField('emp_no,name');
        $this -> assign('agent_list', $list);


        if (isset($_POST['eq_is_del'])) {
            $eq_is_del = $_POST['eq_is_del'];
        } else {
            $eq_is_del = "0";
        }
        //print_r(think_decrypt("MDAwMDAwMDAwMITdma4"));die;
		//print_r($eq_is_del);die;
		
        $this -> assign('eq_is_del', $eq_is_del);

        $map = $this -> _search();

        //dump($map);die;

        if (method_exists($this, '_search_filter')) {
            $this -> _search_filter($map);
        }


        $map['is_del'] = array('eq', $eq_is_del);
		$map['peoplestatus'] = array('exp',' IN (0,1) ');
		

        $model = D("User");

        if (!empty($model)) {
            $this -> _list($model, $map, "emp_no");
        }

       // dump(M("User")->getLastSql());die;

        $this -> display();
    }
	
	//我的档案
	/*public function my_list(){
		$plugin['date'] = true;
		$this -> assign("plugin", $plugin);
		$user=D("UserView")->find(get_user_id());
		//print_r($user);die;
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
	}*/
	
	
	
	//我的档案
	public function my_list(){
		$plugin['date'] = true;
        $plugin['uploader'] = true;
        $this -> assign("plugin", $plugin);//控件
		$id = get_user_id();
		//var_dump($_POST['kaohetime']);die;
        //$id = I('param.id');
		//$id = $GET['id'];
		//var_dump($id);die;
        $vo = M('User')->find($id);
        $this -> assign('vo', $vo);//基本信息

        $map['user_id'] = array('eq',get_user_id());
        $infos = M('User_file')->where($map)->select();

        foreach($infos as $k=>$v ){
            $info = $v;
        }

        //print_r($info);die;
        $this->assign('info',$info);//详细信息


        //全日制学历数据
		$map['uid'] = array('eq',$id);
        $infos = M('User_education')->where($map)->select();
        $this->assign('educationinfo',$infos);

        //在职学历数据
		$map['uid'] = array('eq',$id);
        $infos = M('User_zzeducation')->where($map)->select();

        // foreach($infos as $k=>$v ){
        //     $info = $v;
        // }
        $this->assign('zzeducationinfo',$infos);


        //培训数据
		$map['uid'] = array('eq',$id);
        $infos = M('User_train')->where($map)->select();
        $this->assign('traininfo',$infos);

        //年度考核数据
        $map['uid'] = array('eq',$id);
        $infos = M('User_check')->where($map) -> order('year desc')->select();
        $this->assign('checkinfo',$infos);

        //获奖情况数据
        $map['uid'] = array('eq',$id);
        $infos = M('User_award')->where($map)->select();
        $this->assign('awardinfo',$infos);

        //工作经历数据
        $map['uid'] = array('eq',$id);
        $infos = M('User_work')->where($map)->select();
        $this->assign('workinfo',$infos);

        //职称评审数据
        $map['uid'] = array('eq',$id);
        $infos = M('User_review')->where($map)->select();
        $this->assign('reviewinfo',$infos);

        $hjnf = array('请选择','1990','1991','1992','1993','1994','1995','1996','1997','1998','1999','2000','2001','2002','2003','2004','2005','2006','2007','2008','2009','2010','2011','2012','2013','2014','2015','2016','2017',);

        $this->assign('hjnf',$hjnf);




  //       if(empty($info['jiaoyu'])){
  //           $this->assign('str',"''");//全日制教育信息
  //       }else{
  //           $this->assign('str',$info['jiaoyu']);//全日制教育信息
  //       }

  //       if(empty($info['zz_jiaoyu'])){
  //           $this->assign('zz_str',"''");//在职教育信息
  //       }else{
  //           $this->assign('zz_str',$info['zz_jiaoyu']);//在职教育信息
  //       }
		
		// if(empty($info['peixun'])){
  //           $this->assign('px_str',"''");//培训经历
  //       }else{
  //           $this->assign('px_str',$info['peixun']);//培训经历
  //       }

  //       if(empty($info['kaohe'])){
  //           $this->assign('kaohe_str',"''");//年度考核
  //       }else{
  //           $this->assign('kaohe_str',$info['kaohe']);//年度考核
  //       }

  //       if(empty($info['huojiang'])){
  //           $this->assign('huojiang_str',"''");//年
  //       }else{
  //           $this->assign('huojiang_str',$info['huojiang']);//
  //       }

  //       if(empty($info['gongzuo'])){
  //           $this->assign('gongzuo_str',"''");//工作经历
  //       }else{
  //           $this->assign('gongzuo_str',$info['gongzuo']);//工作经历
  //       }
		
		// if(empty($info['zhicheng'])){
  //           $this->assign('zhicheng_str',"''");//职称经历
  //       }else{
  //           $this->assign('zhicheng_str',$info['zhicheng']);//职称经历
  //       }
		
		$this->display();
	}
	
	
	
    //基础身份显示
	public function basic_list(){

       // dump($_POST);

        $plugin['date'] = true;
        $this -> assign("plugin", $plugin);

        $model = M("Position");
        $list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
        $this -> assign('position_list', $list);
		// dump($list);

        $model = M("Dept");
        $list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
        $this -> assign('dept_list', $list);

        $model = M("User");
        $list = $model -> where('is_del=0') -> order('sort asc') -> getField('emp_no,name');
        $this -> assign('agent_list', $list);


        if (isset($_POST['eq_is_del'])) {
            $eq_is_del = $_POST['eq_is_del'];
        } else {
            $eq_is_del = "0";
        }
        //print_r(think_decrypt("MDAwMDAwMDAwMITdma4"));die;
        $this -> assign('eq_is_del', $eq_is_del);

        $map = $this -> _search();

        //dump($map);die;

        if (method_exists($this, '_search_filter')) {
            $this -> _search_filter($map);
        }


        $map['is_del'] = array('eq', $eq_is_del);
		
		$map['peoplestatus'] = array('exp',' IN (0,1) ');
        $model = D("User");

        if (!empty($model)) {
            $this -> _list($model, $map, "emp_no");
        }

       // dump(M("User")->getLastSql());die;
		
        $this -> display();
    }
    /**
     * 员工档案 的列表信息
     * zzp
     * 2017.9.9
     */
    public function member_edit(){
        $plugin['date'] = true;
        $plugin['uploader'] = true;
        $this -> assign("plugin", $plugin);//控件
		//var_dump($_POST['kaohetime']);die;
        $id = I('param.id');

        $vo = M('User')->find($id);
        $this -> assign('vo', $vo);//基本信息

        //全日制学历数据
		$map['uid'] = array('eq',$id);
		$map['is_del'] = array('eq',0);
        $infos = M('User_education')->where($map)->select();
        $this->assign('educationinfo',$infos);

        //在职学历数据
		$map['uid'] = array('eq',$id);
		$map['is_del'] = array('eq',0);
        $infos = M('User_zzeducation')->where($map)->select();
        $this->assign('zzeducationinfo',$infos);


        //培训数据
		$map['uid'] = array('eq',$id);
        $infos = M('User_train')->where($map)->select();
        $this->assign('traininfo',$infos);

        //考核数据
		$map['uid'] = array('eq',$id);
        $infos = M('User_check')->where($map) -> order('year desc')->select();
        $this->assign('checkinfo',$infos);

        //获奖数据
		$map['uid'] = array('eq',$id);
        $infos = M('User_award')->where($map)->select();
        $this->assign('awardinfo',$infos);
		
		//工作数据
		$map['uid'] = array('eq',$id);
        $infos = M('User_work')->where($map)->select();
        $this->assign('workinfo',$infos);

        //评审数据
		$map['uid'] = array('eq',$id);
        $infos = M('User_review')->where($map)->select();
        $this->assign('reviewinfo',$infos);

        $f_map['user_id'] = array('eq',$id);
        $infos = M('User_file')->where($f_map)->find();
        $this->assign('info',$infos);
        // dump($infos);die;
        //print_r($info);die;
        



  //       if(empty($info['jiaoyu'])){
  //           $this->assign('str',"''");//全日制教育信息
  //       }else{
  //           $this->assign('str',$info['jiaoyu']);//全日制教育信息
  //       }

  //       if(empty($info['zz_jiaoyu'])){
  //           $this->assign('zz_str',"''");//在职教育信息
  //       }else{
  //           $this->assign('zz_str',$info['zz_jiaoyu']);//在职教育信息
  //       }
		
		// if(empty($info['peixun'])){
  //           $this->assign('px_str',"''");//培训经历
  //       }else{
  //           $this->assign('px_str',$info['peixun']);//培训经历
  //       }

  //       if(empty($info['kaohe'])){
  //           $this->assign('kaohe_str',"''");//年度考核
  //       }else{
  //           $this->assign('kaohe_str',$info['kaohe']);//年度考核
  //       }

  //       if(empty($info['huojiang'])){
  //           $this->assign('huojiang_str',"''");//年
  //       }else{
  //           $this->assign('huojiang_str',$info['huojiang']);//
  //       }

  //       if(empty($info['gongzuo'])){
  //           $this->assign('gongzuo_str',"''");//工作经历
  //       }else{
  //           $this->assign('gongzuo_str',$info['gongzuo']);//工作经历
  //       }
		
		// if(empty($info['zhicheng'])){
  //           $this->assign('zhicheng_str',"''");//职称经历
  //       }else{
  //           $this->assign('zhicheng_str',$info['zhicheng']);//职称经历
  //       }
		
		
		//var_dump($info['kaohe']{["kaohe_time"]});die;
		//var_dump(json_decode($info['kaohe']['kaohe_time']));die;
		





        //民族
        $minzu = array("请选择","汉族","蒙古族","回族","藏族","维吾尔族","苗族","彝族","壮族","布依族","朝鲜族","满族","侗族","瑶族","白族","土家族","哈尼族","哈萨克族","傣族","黎族","傈僳族","佤族","畲族","高山族","拉祜族","水族","东乡族","纳西族","景颇族","柯尔克孜族","土族","达斡尔族","仫佬族","羌族","布朗族","撒拉族","毛南族","仡佬族","锡伯族","阿昌族","普米族","塔吉克族","怒族","乌孜别克族","俄罗斯族","鄂温克族","德昂族","保安族","裕固族","京族","塔塔尔族","独龙族","鄂伦春族","赫哲族","门巴族","珞巴族","基诺族");
        //省份
        $shenfen = array("请选择","北京市","天津市","河北省","山西省","内蒙古自治区","辽宁省","吉林省","黑龙江省","上海市","江苏省","浙江省","安徽省","福建省","江西省","山东省","河南省","湖北省","湖南省","广东省","广西壮族自治区","海南省","重庆市","四川省","贵州省","云南省","西藏自治区","陕西省","甘肃省","青海省","宁夏回族自治区","新疆维吾尔自治区","香港特别行政区","澳门特别行政区","台湾省","其它");
        //政治面貌
        $zzmm= array("请选择","中共党员","中共预备党员","团员","中国民主促进会","中国民主建国会","群众");
        //技术级别
        $jsdj = array("请选择","高级","中级","初级","工人");
        //工作状态
        $gzzt = array('在岗','借调','援藏','援疆','其他');
		//获奖年份
		$hjnf = array('请选择','1990','1991','1992','1993','1994','1995','1996','1997','1998','1999','2000','2001','2002','2003','2004','2005','2006','2007','2008','2009','2010','2011','2012','2013','2014','2015','2016','2017',);
        
		$this->assign('gzzt',$gzzt);
        $this->assign('jsdj',$jsdj);
        $this->assign('minzu',$minzu);
        $this->assign('shenfen',$shenfen);
        $this->assign('zzmm',$zzmm);
		$this->assign('hjnf',$hjnf);
		$hjnfjs[] = json_encode($hjnf);
		$this->assign('hjnfjs',$hjnfjs);
		//var_dump($hjnfjs);die;
		//var_dump($kaohe_str['kaohe_time']);die;

        $this->display();
    }
    //上传
    public function upload() {
        $this -> _upload();
    }
    //下载
    function down($attach_id) {
        $this -> _down($attach_id);
    }
    /**
     * 员工档案的添加
     * zzp
     * 2017.9.10
     */
    public function user_add() {
        $plugin['date'] = true;
        $this -> assign("plugin", $plugin);

        $model = M("Position");
        $list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
        $this -> assign('position_list', $list);

        $model = M("Dept");
        $list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
        $this -> assign('dept_list', $list);

        $this -> display();
    }

    /**
     * 员工档案 的添加,编辑  保存
     * zzp
     * 2017.9.9
     */
    public function user_save(){
    	// dump($_POST);die;
           $education_model = M('user_education');
           $map['id'] = array('in',$_POST['education_id']);
           $mapemp['uid'] = array('eq',$_POST['id']);
           $data = $education_model->where($mapemp)->Field('id')->select();
           // var_dump($data);die;
           if(!empty($data)){
	           $data = rotate($data);
	           $intersection = array_diff($data['id'], $_POST['education_id']);
	           $isdel['is_del'] = 1;
	           foreach ($intersection as $k => $v) {
	           		$education_model -> where(array('id' => $v)) -> save($isdel);
	           }
           }
           // var_dump($intersection);die;
           foreach ($_POST['education_id'] as $key => $value) {
           		if(in_array($value,$data['id'])){
           			$zhi['start'] =  $_POST['start'][$key];
           			$zhi['graduation'] =  $_POST['graduation'][$key];
           			$zhi['school_name'] =  $_POST['school_name'][$key];
           			$zhi['major'] =  $_POST['major'][$key];
           			$zhi['system'] =  $_POST['system'][$key];
           			$zhi['degree'] =  $_POST['degree'][$key];
           			$zhi['degree_wei'] =  $_POST['degree_wei'][$key];
           			$zhi['add_file'] =  $_POST['add_file'][$key];
           			$education_model -> where(array('id' => $value)) -> save($zhi);
           		}else if ($value == '') {
           			// var_dump('a');die;
           			$zhi['emp_no'] = get_emp_no($_POST['id']);
           			$zhi['name'] = get_user_name($_POST['id']);
           			$zhi['uid'] = $_POST['id'];
           			$zhi['start'] =  $_POST['start'][$key];
           			$zhi['graduation'] =  $_POST['graduation'][$key];
           			$zhi['school_name'] =  $_POST['school_name'][$key];
           			$zhi['major'] =  $_POST['major'][$key];
           			$zhi['system'] =  $_POST['system'][$key];
           			$zhi['degree'] =  $_POST['degree'][$key];
           			$zhi['degree_wei'] =  $_POST['degree_wei'][$key];
           			$zhi['add_file'] =  $_POST['add_file'][$key];
           			$education_model -> add($zhi);
           		}
           }

           //在职教育
           $zzeducation_model = M('user_zzeducation');
           $map['id'] = array('in',$_POST['zz_education_id']);
           $mapemp['uid'] = array('eq',$_POST['id']);
           $data = $zzeducation_model->where($mapemp)->Field('id')->select();
           
           if(!empty($data)){
	           $data = rotate($data);
	           $intersection = array_diff($data['id'], $_POST['zz_education_id']);
	           $isdel['is_del'] = 1;
	           foreach ($intersection as $k => $v) {
	           		$zzeducation_model -> where(array('id' => $v)) -> save($isdel);
	           }
           }
           // var_dump($intersection);die;
           foreach ($_POST['zz_education_id'] as $key => $value) {
           		if(in_array($value,$data['id'])){
           			$zhi['zz_start'] =  $_POST['zz_start'][$key];
           			$zhi['zz_graduation'] =  $_POST['zz_graduation'][$key];
           			$zhi['zz_school_name'] =  $_POST['zz_school_name'][$key];
           			$zhi['zz_major'] =  $_POST['zz_major'][$key];
           			$zhi['zz_system'] =  $_POST['zz_system'][$key];
           			$zhi['zz_degree'] =  $_POST['zz_degree'][$key];
           			$zhi['zz_degree_wei'] =  $_POST['zz_degree_wei'][$key];
           			$zhi['zz_add_file'] =  $_POST['zz_add_file'][$key];
           			$zzeducation_model -> where(array('id' => $value)) -> save($zhi);
           		}else if ($value == '') {
           			$zhi['emp_no'] = get_emp_no($_POST['id']);
           			$zhi['name'] = get_user_name($_POST['id']);
           			$zhi['uid'] = $_POST['id'];
           			$zhi['zz_start'] =  $_POST['zz_start'][$key];
           			$zhi['zz_graduation'] =  $_POST['zz_graduation'][$key];
           			$zhi['zz_school_name'] =  $_POST['zz_school_name'][$key];
           			$zhi['zz_major'] =  $_POST['zz_major'][$key];
           			$zhi['zz_system'] =  $_POST['zz_system'][$key];
           			$zhi['zz_degree'] =  $_POST['zz_degree'][$key];
           			$zhi['zz_add_file'] =  '';
           			$zzeducation_model -> add($zhi);
           		}
           }
          // var_dump($_POST['train_id']);die;
          //培训
           $traineducation_model = M('user_train');
           $map['id'] = array('in',$_POST['train_id']);
           $mapemp['uid'] = array('eq',$_POST['id']);
           $data = $traineducation_model->where($mapemp)->Field('id')->select();
           
           if(!empty($data)){
	           $data = rotate($data);
	           $intersection = array_diff($data['id'], $_POST['train_id']);
	           $isdel['is_del'] = 1;
	           foreach ($intersection as $k => $v) {
	           		$traineducation_model -> where(array('id' => $v)) -> save($isdel);
	           }
           }
           // var_dump($intersection);die;
           foreach ($_POST['train_id'] as $key => $value) {
           		if(in_array($value,$data['id'])){
           			$zhi['train_name'] =  $_POST['train_name'][$key];
           			$zhi['train_start'] =  $_POST['train_start'][$key];
           			$zhi['train_graduation'] =  $_POST['train_graduation'][$key];
           			$zhi['train_hours'] =  $_POST['train_hours'][$key];
           			$zhi['train_cost'] =  $_POST['train_cost'][$key];
           			$zhi['train_place'] =  $_POST['train_place'][$key];
           			$zhi['host_unit'] =  $_POST['host_unit'][$key];
           			$zhi['train_agency'] =  $_POST['train_agency'][$key];
           			$zhi['train_content'] =  $_POST['train_content'][$key];
           			$zhi['train_user'] =  $_POST['train_user'][$key];
           			$zhi['train_offjob'] =  $_POST['train_offjob'][$key];
           			$zhi['train_remark'] =  $_POST['train_remark'][$key];
           			$zhi['train_add_file'] =  $_POST['train_add_file'][$key];
           			$traineducation_model -> where(array('id' => $value)) -> save($zhi);
           		}else if ($value == '') {
           			$zhi['emp_no'] = get_emp_no($_POST['id']);
           			$zhi['name'] = get_user_name($_POST['id']);
           			$zhi['uid'] = $_POST['id'];
           			$zhi['train_name'] =  $_POST['train_name'][$key];
           			$zhi['train_start'] =  $_POST['train_start'][$key];
           			$zhi['train_graduation'] =  $_POST['train_graduation'][$key];
           			$zhi['train_hours'] =  $_POST['train_hours'][$key];
           			$zhi['train_cost'] =  $_POST['train_cost'][$key];
           			$zhi['train_place'] =  $_POST['train_place'][$key];
           			$zhi['host_unit'] =  $_POST['host_unit'][$key];
           			$zhi['train_agency'] =  $_POST['train_agency'][$key];
           			$zhi['train_content'] =  $_POST['train_content'][$key];
           			$zhi['train_user'] =  $_POST['train_user'][$key];
           			$zhi['train_offjob'] =  $_POST['train_offjob'][$key];
           			$zhi['train_remark'] =  $_POST['train_remark'][$key];
           			$zhi['train_add_file'] =  '';
           			// dump($zhi);die;
           			$traineducation_model -> add($zhi);
           		}
           }


           //年度考核
           $check_model = M('user_check');
           $map['id'] = array('in',$_POST['check_id']);
           $mapemp['uid'] = array('eq',$_POST['id']);
           $data = $check_model->where($mapemp)->Field('id')->select();
           
           if(!empty($data)){
	           $data = rotate($data);
	           $intersection = array_diff($data['id'], $_POST['check_id']);
	           $isdel['is_del'] = 1;
	           foreach ($intersection as $k => $v) {
	           		$check_model -> where(array('id' => $v)) -> save($isdel);
	           }
           }
           // var_dump($intersection);die;
           foreach ($_POST['check_id'] as $key => $value) {
           		if(in_array($value,$data['id'])){
           			
           			$zhi['year'] =  $_POST['check_year'][$key];
           			$zhi['result'] =  $_POST['check_result'][$key];
           			$zhi['remark'] =  $_POST['check_remark'][$key];
           			$zhi['check_add_file'] =  $_POST['check_add_file'][$key];
           			// dump($zhi['check_add_file']);die;
           			$check_model -> where(array('id' => $value)) -> save($zhi);
           		}else if ($value == '') {
           			$zhi['emp_no'] = get_emp_no($_POST['id']);
           			$zhi['name'] = get_user_name($_POST['id']);
           			$zhi['uid'] = $_POST['id'];
           			$zhi['year'] =  $_POST['check_year'][$key];
           			$zhi['result'] =  $_POST['check_result'][$key];
           			$zhi['remark'] =  $_POST['check_remark'][$key];
           			$zhi['check_add_file'] =  '';
           			$check_model -> add($zhi);
           		}
           }


           //获奖情况
           $check_model = M('user_award');
           $map['id'] = array('in',$_POST['hj_id']);
           $mapemp['uid'] = array('eq',$_POST['id']);
           $data = $check_model->where($mapemp)->Field('id')->select();
           
           if(!empty($data)){
	           $data = rotate($data);
	           $intersection = array_diff($data['id'], $_POST['hj_id']);
	           $isdel['is_del'] = 1;
	           foreach ($intersection as $k => $v) {
	           		$check_model -> where(array('id' => $v)) -> save($isdel);
	           }
           }
           // var_dump($intersection);die;
           foreach ($_POST['hj_id'] as $key => $value) {
           		if(in_array($value,$data['id'])){
           			
           			$zhi['hj_time'] =  $_POST['hj_time'][$key];
           			$zhi['hj_name'] =  $_POST['hj_name'][$key];
           			$zhi['hj_unit'] =  $_POST['hj_unit'][$key];
           			$zhi['hj_type'] =  $_POST['hj_type'][$key];
           			$zhi['hj_remark'] =  $_POST['hj_remark'][$key];
           			$zhi['hj_add_file'] =  $_POST['hj_add_file'][$key];
           			// dump($zhi['hj_add_file']);die;
           			$check_model -> where(array('id' => $value)) -> save($zhi);
           		}else if ($value == '') {
           			$zhi['emp_no'] = get_emp_no($_POST['id']);
           			$zhi['name'] = get_user_name($_POST['id']);
           			$zhi['uid'] = $_POST['id'];
           			$zhi['hj_time'] =  $_POST['hj_time'][$key];
           			$zhi['hj_name'] =  $_POST['hj_name'][$key];
           			$zhi['hj_unit'] =  $_POST['hj_unit'][$key];
           			$zhi['hj_type'] =  $_POST['hj_type'][$key];
           			$zhi['hj_remark'] =  $_POST['hj_remark'][$key];
           			$zhi['hj_add_file'] =  '';
           			// dump($zhi);die;
           			$check_model -> add($zhi);
           		}
           }


           //工作经历
           $work_model = M('user_work');
           $map['id'] = array('in',$_POST['work_id']);
           $mapemp['uid'] = array('eq',$_POST['id']);
           $data = $work_model->where($mapemp)->Field('id')->select();
           
           if(!empty($data)){
	           $data = rotate($data);
	           $intersection = array_diff($data['id'], $_POST['work_id']);
	           $isdel['is_del'] = 1;
	           foreach ($intersection as $k => $v) {
	           		 $work_model -> where(array('id' => $v)) -> save($isdel);
	           		
	           }
           }
           // var_dump($intersection);die;
           foreach ($_POST['work_id'] as $key => $value) {
           		if(in_array($value,$data['id'])){
           			
           			$zhi['work_start'] =  $_POST['work_start'][$key];
           			$zhi['work_end'] =  $_POST['work_end'][$key];
           			$zhi['work_company'] =  $_POST['work_company'][$key];
           			$zhi['work_dep'] =  $_POST['work_dep'][$key];
           			$zhi['work_pos'] =  $_POST['work_pos'][$key];
           			$zhi['work_remark'] =  $_POST['work_remark'][$key];
           			$zhi['work_add_file'] =  $_POST['work_add_file'][$key];
           			// dump($zhi['hj_add_file']);die;
           			$work_model -> where(array('id' => $value)) -> save($zhi);
           		}else if ($value == '') {
           			$zhi['emp_no'] = get_emp_no($_POST['id']);
           			$zhi['name'] = get_user_name($_POST['id']);
           			$zhi['uid'] = $_POST['id'];
           			$zhi['work_start'] =  $_POST['work_start'][$key];
           			$zhi['work_end'] =  $_POST['work_end'][$key];
           			$zhi['work_company'] =  $_POST['work_company'][$key];
           			$zhi['work_dep'] =  $_POST['work_dep'][$key];
           			$zhi['work_pos'] =  $_POST['work_pos'][$key];
           			$zhi['work_remark'] =  $_POST['work_remark'][$key];
           			$zhi['work_add_file'] =  '';
           			// dump($zhi);die;
           			$work_model -> add($zhi);
           		}
           }


           $review_model = M('user_review');
           $map['id'] = array('in',$_POST['review_id']);
           $mapemp['uid'] = array('eq',$_POST['id']);
           $data = $review_model->where($mapemp)->Field('id')->select();
           
           if(!empty($data)){
	           $data = rotate($data);
	           $intersection = array_diff($data['id'], $_POST['review_id']);
	           $isdel['is_del'] = 1;
	           foreach ($intersection as $k => $v) {
	           		$as = $review_model -> where(array('id' => $v)) -> save($isdel);
	           		// dump($as);die;
	           }
           }
           // var_dump($intersection);die;
           foreach ($_POST['review_id'] as $key => $value) {
           		if(in_array($value,$data['id'])){
           			
           			$zhi['reviewtime'] =  $_POST['review_time'][$key];
           			$zhi['reviewtitle'] =  $_POST['review_title'][$key];
           			$zhi['reviewlevel'] =  $_POST['review_level'][$key];
           			$zhi['reviewremark'] =  $_POST['review_remark'][$key];
           			$zhi['review_add_file'] =  $_POST['review_add_file'][$key];
           			// dump($zhi['hj_add_file']);die;
           			$review_model -> where(array('id' => $value)) -> save($zhi);
           		}else if ($value == '') {
           			$zhi['emp_no'] = get_emp_no($_POST['id']);
           			$zhi['name'] = get_user_name($_POST['id']);
           			$zhi['uid'] = $_POST['id'];
           			$zhi['reviewtime'] =  $_POST['review_time'][$key];
           			$zhi['reviewtitle'] =  $_POST['review_title'][$key];
           			$zhi['reviewlevel'] =  $_POST['review_level'][$key];
           			$zhi['reviewremark'] =  $_POST['review_remark'][$key];
           			$zhi['review_add_file'] =  '';
           			// dump($zhi);die;
           			$review_model -> add($zhi);
           		}
           }

           
           
        $model = M('User');

        if(empty($_POST['id'])){
            if (false === $model -> create() ) {
                $this -> error($model -> getError());
            }

            $list = $model -> add();


            if ($list !== false) {//保存成功

                $this -> success('保存成功!',U('User/member_edit',array('id'=>$_POST['id'])));
            } else {
                $this -> error('保存失败!');
                //失败提示
            }


        }else{
        	$where_map['id'] = $_POST['id'];
        	// dump($_POST);die;
            $list = $model ->where($where_map)->save($_POST);
			
            // dump($model->getLastSql());die;
            if ($list !== false) {//保存成功
            	// dump('a');
                $this -> assign('jumpUrl',U('User/member_edit',array('id'=>$_POST['id'])));

               /// $this -> success('保存成功222222!');

                if(!empty($_POST['user_id'])){

                    $mod = M('User_file');
                    $map['user_id'] = $_POST['user_id'];
                    $select = $mod->where($map)->select();

                    $uid = $select[0]['uid'];
                    // dump($select);
                    ///echo $uid;

                    if(empty($select)){

                        if (false === $mod -> create() ) {
                            $this -> error($mod -> getError());
                        }

                        $list_file = $mod -> add();

                        if ($list_file !== false) {//保存成功

                            $this -> success('保存成功!');
                           // $this -> success('新增成功!');
                        } else {
                            $this -> error('保存失败!');
                            //失败提示
                        }


                    }else{

                         $_POST['uid'] = $uid;
                         $map['user_id'] = $_POST['user_id'];
                         $mod = M('User_file');
                         $list_info = $mod ->where($map)->save($_POST);
                         // var_dump($mod->getLastSql());die;
                         if ($list_info !== false) {//保存成功
                             $this -> assign('jumpUrl', get_return_url());
                             $this -> success('保存成功!',U('User/member_edit',array('id'=>$_POST['id'])));
                         } else {
                             $this -> error('保存失败!');
                             //失败提示
                         }

                    }


                }

            }

        }

    }
	
	
	
	
	//查询统计
	public function chaxun(){
		$plugin['date'] = true;
        $this -> assign("plugin", $plugin);

        $model = M("Position");
        $list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
        $this -> assign('position_list', $list);

		//print_r($list);die;
        $model = M("Dept");
        $list = $model -> where('is_del=0') -> order('sort asc') -> getField('id,name');
        $this -> assign('dept_list', $list);
		
		$model = M("User_file");
        $list = $model ->  getField('user_id,zgxueli');
        
		$xueli_list = array_unique($list);
		//var_dump($xueli_list);die;
		$this -> assign('xueli_list', $xueli_list);
		
		$model = M("User_file");
        //$list = $model ->  getField('user_id,xpgw_type');
        $gw_list = $model -> distinct(true) -> field('xpgw_type')->select();
		
		//var_dump($gw_list);die;
		$this -> assign('gw_list', $gw_list);
		
		
		//$model = M("User_file");
        //$list = $model ->  getField('user_id,xpgw_type');
        //$gw_list = $model -> distinct(true) -> field('htlx')->select();
		
		//var_dump($gw_list);die;
		//$this -> assign('gw_list', $gw_list);
		
        $model = M("User");
        $list = $model -> where('is_del=0') -> order('id asc') -> getField('emp_no,name');
        $this -> assign('agent_list', $list);


        if (isset($_POST['eq_is_del'])) {
            $eq_is_del = $_POST['eq_is_del'];
        } else {
            $eq_is_del = "0";
        }
        
        //print_r(think_decrypt("MDAwMDAwMDAwMITdma4"));die;
		//print_r($eq_is_del);die;
		
        $this -> assign('eq_is_del', $eq_is_del);

        $map = $this -> _search();
        
       

        if (method_exists($this, '_search_filter')) {
            $this -> _search_filter($map);
        }


        $map['oa_user.is_del'] = array('eq', $eq_is_del);
		// $map['peoplestatus'] = array('exp',' IN (0,1) ');
		
 
        $model = D("User");
		//$model1 = D("User_file");
		// dump($map);die;
        if (!empty($model)) {
            $this -> _lists($model, $map, "emp_no");
        }
        
        //dump($model->getLastSql());die;

        $this -> display();
		//$this -> success('保存成功!',U('User/test'));
		
	}
	
	//导入Excel方法操作  
    public function importExp()  
    {  
        header("Content-type: text/html;charset=utf-8");//设置页面内容是html编码格式是utf-8  
        $m=D("Webinfo");//连接数据表  
          
        $cell=array();  
  
        //导入Excel前要上传Excel文件到项目文件夹，如果成功进行，如果失败提示错误信息  
        //I('post.ExcelURL','','htmlspecialchars')为获取上传控件传来的文件名称  
        if(I('post.ExcelURL','','htmlspecialchars')!="")  
        {  
            $uploads="Uploads";  
            $upload = new \Think\Upload();// 实例化上传类  
            $upload->maxSize   =     5242880 ;// 设置附件上传大小  
            $upload->exts      =     array('xlsx','xls');// 设置附件上传类型  
            $upload->rootPath  =      './'.$uploads.'/'; // 设置附件上传根目录  
            $upload->subName  = array('date','Ym');  
            // 上传单个文件   
            $info   =   $upload->uploadOne($_FILES['excel']);  
              
            if(!$info) {// 上传错误提示错误信息  
                $this->error($upload->getError());  
            }else{  
                //上传Excel成功  
                $exts = $info['ext'];  
                    $file_name=$uploads.'/'.$info['savepath'].$info['savename'];  
                    $res=$this->getdata($file_name,$exts);  
                  
                //循环读取每行数据，进行写入数据库  
                foreach ( $res as $k => $v )  
                {  
                    if ($k != 0)   
                    {     
                        //获取数据库中的最大ID自增加1  
                        $m->create();  
                        $id=$m->max('ID');  
                          
                        if($id==0||$id==NULL||$id==""){  
                            $id=1;  
                        }  
                        else  
                        {  
                            $id=$id+1;  
                        }  
                        //读取数据后赋给数组data  
                        $data['ID']=$id;  
                        $data ['Name'] = $v [B];  
                        $data ['Site'] =  $v [C];  
  
                        $result = $m->add($data);//添加操作  
                          
                    }  
                }  
                  
                if($result!=0){  
                      
                    $this->success('网站数据导入成功');  
                      
                }else{  
                      
                    $this->error('网站数据导入失败');  
                      
                }  
            }  
        }  
        else  
        {  
            $this->error("请选择上传的文件");  
        }  
    }  
  
    //获取excel文件、读取数据方法  
    public function getdata($file_name,$exts='xls')  
    {  
          
        //导入PHPExcel类库，因为PHPExcel没有用命名空间，只能inport导入  
        import("Org.Util.PHPExcel");  
        //创建PHPExcel对象，注意，不能少了\  
        $PHPExcel=new \PHPExcel();  
          
        if($exts=="xls")  
        {  
            import("Org.Util.PHPExcel.Reader.Excel5");  
            $PHPReader=new \PHPExcel_Reader_Excel5();  
        }  
        else if($exts=="xlsx")  
        {  
            import("Org.Util.PHPExcel.Reader.Excel2007");  
            $PHPReader=new \PHPExcel_Reader_Excel2007();  
        }  
        //载入文件  
        $PHPExcel=$PHPReader->load($file_name);  
        //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推  
        $currentSheet=$PHPExcel->getSheet(0);  
          
        //获取总列数  
        $allColumn=$currentSheet->getHighestColumn();  
          
        //获取总行数  
        $allRow=$currentSheet->getHighestRow();  
          
        $excelData = array();   
        //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始  
        for($currentRow=2;$currentRow<=$allRow;$currentRow++){  
            //从哪列开始，A表示第一列  
            for($currentColumn='B';$currentColumn<=$allColumn;$currentColumn++){  
                //数据坐标  
                $address=$currentColumn.$currentRow;  
                //读取到的数据，保存到数组$arr中  
                 $excelData[$currentRow][$currentColumn] = $currentSheet-> getCell($address)-> getValue();  
                   
            }  
          
        }  
         return $excelData;  
    }


//全部导出数据方法  
        public function allExp()  
        {  
    //链接所导出的数据表  
	
        $xlsModel = D('User');  
  
    //“WID,WName,WebSite,Remark”为所查询的字段，“Status=2”查询条件  
        $goods_list  = $xlsModel->join('oa_User_file  ON oa_User.id = oa_User_file.user_id')->field('oa_User.id,
		name,sex,dept_id,sfzh,xrzw,xrzsj,birthday,minzu,jiguan,jiguan_shi,csd,csd_shi,gzsj,gongli,bndkxsdxxjts,
		lzsj,csbjslynx,htlx,pyhtqx,pyhtqxjs,xpgw,gwsj,bgwnx,xpgw_type,gzjb,gzjbsj,zzmm,rtsj,jrdpsj,
		ypzyjszc,ypzyjszcsj,jsdj,xpzc,zcjbsrpr,xzchjbprsj,xzwjbpxsj,jiaoyu,zz_jiaoyu,zgxueli,zgxuewei,
		jtdz,yzbm,mobile_tel,beizhu')->where('oa_User.peoplestatus=0')->select();  
		//var_dump('aaa');die;
        $count=1;//导出Excel序号排列  
  
        $data = array();  
    //循环查询后的数据，进行每一列  
        foreach ($goods_list as $k=>$goods_info){  
            $data[$k][id] = $count++;//序号列  
            $data[$k][name] = $goods_info['name'];//名称列  
             if($goods_info['sex'] == 'male'){ 
				 $data[$k][sex] = '男'; 
			 } else {
				 $data[$k][sex] = '女';
			 }
			 $data[$k][dept_id] = get_dept_name($goods_info['dept_id']);
			 $data[$k][sfzh] = $goods_info['sfzh'];
			 $data[$k][xrzw] = $goods_info['xrzw'];
			 if($goods_info['xrzsj'] == '0000-00-00'){
				 $data[$k][xrzsj] = '';
			 }else{
				 $data[$k][xrzsj] = $goods_info['xrzsj'];
			 }
			 $data[$k][birthday] = $goods_info['birthday'];
			 $data[$k][minzu] = $goods_info['minzu'];
			 $data[$k][jiguan] = $goods_info['jiguan'].$goods_info['jiguan_shi'];
			 $data[$k][csd] = $goods_info['csd'].$goods_info['csd_shi'];
			 $data[$k][gzsj] = $goods_info['gzsj'];
			 $data[$k][gongli] = $goods_info['gongli'];
			 $data[$k][bndkxsdxxjts] = $goods_info['bndkxsdxxjts'];
			 $data[$k][lzsj] = $goods_info['lzsj'];
			 $data[$k][csbjslynx] = $goods_info['csbjslynx'];
			 $data[$k][htlx] = $goods_info['htlx'];
			 if($goods_info['pyhtqx'] == '0000-00-00' && $goods_info['pyhtqxjs'] == '0000-00-00'){
				 $data[$k][pyhtqx] = '';
			 }else{
				 $data[$k][pyhtqx] = $goods_info['pyhtqx'].'/'.$goods_info['pyhtqxjs'];
			 }
			 $data[$k][xpgw] = $goods_info['xpgw'];
			 $data[$k][gwsj] = $goods_info['gwsj'];
			 $data[$k][bgwnx] = $goods_info['bgwnx'];
			 $data[$k][xpgw_type] = $goods_info['xpgw_type'];
			 $data[$k][gzjb] = $goods_info['gzjb'];
			 $data[$k][gzjbsj] = $goods_info['gzjbsj'];
			 $data[$k][zzmm] = $goods_info['zzmm'];
			 
			 if($goods_info['rtsj'] == '0000-00-00'){
				 $data[$k][rtsj] = '';
			 }else{
				 $data[$k][rtsj] = $goods_info['rtsj'];
			 }
			 
			 if($goods_info['jrdpsj'] == '0000-00-00'){
				 $data[$k][jrdpsj] = '';
			 }else{
				 $data[$k][jrdpsj] = $goods_info['jrdpsj'];
			 }
			 
			 $data[$k][ypzyjszc] = $goods_info['ypzyjszc'];
			 if($goods_info['ypzyjszcsj'] == '0000-00-00'){
				 $data[$k][ypzyjszcsj] = '';
			 }else{
				 $data[$k][ypzyjszcsj] = $goods_info['ypzyjszcsj'];
			 }
			 if($goods_info['jsdj'] == '请选择'){
				 $data[$k][jsdj] = '';
			 }else{
				 $data[$k][jsdj] = $goods_info['jsdj'];
			 }
			 $data[$k][xpzc] = $goods_info['xpzc'];
			 $data[$k][zcjbsrpr] = $goods_info['zcjbsrpr'];
			 
			 if($goods_info['xzchjbprsj'] == '0000-00-00'){
				 $data[$k][xzchjbprsj] = '';
			 }else{
				 $data[$k][xzchjbprsj] = $goods_info['xzchjbprsj'];
			 }
			 if($goods_info['xzwjbpxsj'] == '0000-00-00'){
				 $data[$k][xzwjbpxsj] = '';
			 }else{
				 $data[$k][xzwjbpxsj] = $goods_info['xzwjbpxsj'];
			 }
			  $jiaoyu = json_decode($goods_info['jiaoyu'],true);
			  $data[$k][qryuanxiao] = $jiaoyu['data'][0]['jy_xxmc'].'   '.$jiaoyu['data'][0]['jy_zy'];
			  $data[$k][qrxueli] = $jiaoyu['data'][0]['jy_xl'].'   '.$jiaoyu['data'][0]['jy_xw'];
		  
			  $zz_jiaoyu = json_decode($goods_info['zz_jiaoyu'],true);
			  $data[$k][zzyuanxiao] = $zz_jiaoyu['data'][0]['zz_jy_xxmc'].'   '.$zz_jiaoyu['data'][0]['zz_jy_zy'];
			  $data[$k][zzxueli] = $zz_jiaoyu['data'][0]['zz_jy_xl'].'   '.$zz_jiaoyu['data'][0]['zz_jy_xw'];
			  $data[$k][zgxueli] = $goods_info['zgxueli'];
		      $data[$k][zgxuewei] = $goods_info['zgxuewei'];
			  if(!empty($zz_jiaoyu['data'][0]['zz_jy_xxmc'])){
				  $data[$k][biyeyuanxiao] = $zz_jiaoyu['data'][0]['zz_jy_xxmc'];
			  }else{
				  $data[$k][biyeyuanxiao] = $jiaoyu['data'][0]['jy_xxmc'];
			  }
			  if(!empty($zz_jiaoyu['data'][0]['zz_jy_bysj'])){
				  $data[$k][biyesj] = $zz_jiaoyu['data'][0]['zz_jy_bysj'];
			  }else{
				  $data[$k][biyesj] = $jiaoyu['data'][0]['jy_bysj'];
			  }
			  if(!empty($zz_jiaoyu['data'][0]['zz_jy_xz'])){
				  $data[$k][xz] = $zz_jiaoyu['data'][0]['zz_jy_xz'];
			  }else{
				  $data[$k][xz] = $jiaoyu['data'][0]['jy_xz'];
			  }
			  $data[$k][jtdz] = $goods_info['jtdz'];
			  if($goods_info['yzbm']!= '0' ){
				  $data[$k][yzbm] = $goods_info['yzbm'];
			  }else{
				  $data[$k][yzbm] = '';
			  }
			  $data[$k][mobile_tel] = $goods_info['mobile_tel'];
			  $data[$k][beizhu] = $goods_info['beizhu'];
			  
		  
		  //var_dump($data);die;
        }
		
      
    //每列表的名称
		
        foreach ($data as $field=>$v){  
            if($field == 'id'){  
                $headArr['id'][]='序号';  
				$headArr['id'][]='id'; 
            }  
  
            if($field == 'name'){  
                $headArr['name'][]='名称';
				$headArr['name'][]='name';
            }  
  
            if($field == 'sex'){  
                $headArr['sex'][]='性别';
				$headArr['sex'][]='sex';
            }  
			
			if($field == 'dept_id'){  
                $headArr['dept_id'][]='部门';
				$headArr['dept_id'][]='dept_id';
            }
			
			if($field == 'sfzh'){  
                $headArr['sfzh'][]='身份证号'; 
				$headArr['sfzh'][]='sfzh';
            }
			
			
			if($field == 'xrzw'){  
                $headArr['xrzw'][]='现任职务';
				$headArr['xrzw'][]='xrzw';
            }
			
			if($field == 'xrzsj'){  
                $headArr['xrzsj'][]='现任职务时间';
				$headArr['xrzsj'][]='xrzsj';
            }
			
			
			if($field == 'birthday'){  
                $headArr['birthday'][]='出生日期';
				$headArr['birthday'][]='birthday';
            }
			
			
			if($field == 'minzu'){  
                $headArr['minzu'][]='民族';  
				$headArr['minzu'][]='minzu';
            }
			
			
			if($field == 'jiguan'){  
                $headArr['jiguan'][]='籍贯';
				$headArr['jiguan'][]='jiguan';
            }
			
			if($field == 'csd'){  
                $headArr['csd'][]='出生地';
				$headArr['csd'][]='csd';				
            }
			
			if($field == 'gzsj'){  
                $headArr['gzsj'][]='工作时间'; 
				$headArr['gzsj'][]='gzsj';
            }
			
			if($field == 'gongli'){  
                $headArr['gongli'][]='工龄';
				$headArr['gongli'][]='gongli';
            }
			
			if($field == 'bndkxsdxxjts'){  
                $headArr['bndkxsdxxjts'][]='本年度可享受带薪年休假天数';
				$headArr['bndkxsdxxjts'][]='bndkxsdxxjts';				
            }
			
			if($field == 'lzsj'){  
                $headArr['lzsj'][]='来站时间'; 
				$headArr['lzsj'][]='lzsj';	
            }
			
			if($field == 'csbjslynx'){  
                $headArr['csbjslynx'][]='从事本技术领域年限';
				$headArr['csbjslynx'][]='csbjslynx';
            }
			
			if($field == 'htlx'){  
                $headArr['htlx'][]='合同类型';  
				$headArr['htlx'][]='htlx';
            }
			
			
			if($field == 'pyhtqx'){  
                $headArr['pyhtqx'][]='聘用合同期限';
				$headArr['pyhtqx'][]='pyhtqx';
            }
			
			if($field == 'xpgw'){  
                $headArr['xpgw'][]='岗位';
				$headArr['xpgw'][]='xpgw';
            }
			
			if($field == 'gwsj'){  
                $headArr['gwsj'][]='岗位时间';
				$headArr['gwsj'][]='gwsj';
            }
			
			if($field == 'bgwnx'){  
                $headArr['bgwnx'][]='本岗位年限';
				$headArr['bgwnx'][]='bgwnx';
            }
			
			if($field == 'xpgw_type'){  
                $headArr['xpgw_type'][]='管理或技术';
				$headArr['xpgw_type'][]='xpgw_type';
            }
			
			if($field == 'gzjb'){  
                $headArr['gzjb'][]='工资级别'; 
				$headArr['gzjb'][]='gzjb';
            }
			
			if($field == 'gzjbsj'){  
                $headArr['gzjbsj'][]='工资级别时间';  
				$headArr['gzjbsj'][]='gzjbsj';
            }
			
			if($field == 'zzmm'){  
                $headArr['zzmm'][]='政治面貌';
				$headArr['zzmm'][]='zzmm';
            }
			
			
			if($field == 'rtsj'){  
                $headArr['rtsj'][]='入团时间';
				$headArr['rtsj'][]='rtsj';
            }
			
			if($field == 'jrdpsj'){  
                $headArr['jrdpsj'][]='入党时间';  
				$headArr['jrdpsj'][]='jrdpsj';
				
            }
			
			if($field == 'ypzyjszc'){  
                $headArr['ypzyjszc'][]='已评专业技术职称';
				$headArr['ypzyjszc'][]='ypzyjszc';
            }
			
			if($field == 'ypzyjszcsj'){  
                $headArr['ypzyjszcsj'][]='已评专业技术职称时间';
				$headArr['ypzyjszcsj'][]='ypzyjszcsj';
            }
			
			if($field == 'jsdj'){  
                $headArr['jsdj'][]='技术等级';
				$headArr['jsdj'][]='jsdj';				
            }
			
			if($field == 'xpzc'){  
                $headArr['xpzc'][]='现聘职称或级别';  
				$headArr['xpzc'][]='xpzc';
            }
			
			if($field == 'zcjbsrpr'){  
                $headArr['zcjbsrpr'][]='职称级别是否聘任'; 
				$headArr['zcjbsrpr'][]='zcjbsrpr';
            }
			
			if($field == 'xzchjbprsj'){  
                $headArr['xzchjbprsj'][]='现职称或级别聘任时间';
				$headArr['xzchjbprsj'][]='xzchjbprsj';
            }
			
			
			if($field == 'xzwjbpxsj'){  
                $headArr['xzwjbpxsj'][]='现职务级别评审时间'; 
				$headArr['xzwjbpxsj'][]='xzwjbpxsj';
            }
			
			if($field == 'qrxueli'){  
                $headArr['qrxueli'][]='全日制教育学历学位';  
				$headArr['qrxueli'][]='qrxueli';	
            }
			
			if($field == 'qryuanxiao'){  
                $headArr['qryuanxiao'][]='全日制教育毕业院校级专业';
				$headArr['qryuanxiao'][]='qryuanxiao';	
            }
			
			if($field == 'zzxueli'){  
                $headArr['zzxueli'][]='在职教育学历学位'; 
				$headArr['zzxueli'][]='zzxueli';				
            }
			
			if($field == 'zzyuanxiao'){  
                $headArr['zzyuanxiao'][]='在职教育毕业院校级专业';
				$headArr['zzyuanxiao'][]='zzyuanxiao';
            }
			
			if($field == 'zgxueli'){  
				$headArr['zgxueli'][]='最高学历';
                $headArr['zgxueli'][]='zgxueli';  
            }
			
			if($field == 'zgxuewei'){  
                $headArr['zgxuewei'][]='最高学位'; 
				$headArr['zgxuewei'][]='zgxuewei'; 				
            }
			
			
			if($field == 'biyeyuanxiao'){  
                $headArr['biyeyuanxiao'][]='毕业院校'; 
				$headArr['biyeyuanxiao'][]='biyeyuanxiao'; 				
            }
			
			
			if($field == 'biyesj'){  
                $headArr['biyesj'][]='毕业时间'; 
				$headArr['biyesj'][]='biyesj'; 				
            }
			
			if($field == 'xz'){  
                $headArr['xz'][]='学制'; 
				$headArr['xz'][]='xz'; 				
            }
			
			
			if($field == 'jtdz'){  
                $headArr['jtdz'][]='家庭住址'; 
				$headArr['jtdz'][]='jtdz'; 				
            }
			
			if($field == 'yzbm'){  
                $headArr['yzbm'][]='邮政编码'; 
				$headArr['yzbm'][]='yzbm'; 				
            }
			
			if($field == 'mobile_tel'){  
                $headArr['mobile_tel'][]='电话'; 
				$headArr['mobile_tel'][]='mobile_tel'; 				
            }
			
			if($field == 'beizhu'){  
                $headArr['beizhu'][]='备注'; 
				$headArr['beizhu'][]='beizhu'; 				
            }
			
			
			

        }  
          
        $filename="监测中心花名册-";//所导出的保存文件名称  
        $sss=$this->getExcel($filename,$headArr,$data);//调用导出引用方法  
          
          
    }  
  
  
    //导出引用方法  
        public  function getExcel($fileName,$headArr,$data)  
        {  
		
		
            //导入PHPExcel类库，因为PHPExcel没有用命名空间，只能inport导入  
			Vendor('Excel.PHPExcel');
            //Vendor('Excel.PHPExcel.Cell.php');
  
            $date = date("Y_m_d",time());  
            $fileName .= "{$date}.xls";  
  
            //创建PHPExcel对象，注意，不能少了\  
            $objPHPExcel = new \PHPExcel();  
            $objProps = $objPHPExcel->getProperties();  
  
            //设置表头  
            
			$key = ord("A");//A--65  
			$key2 = ord("@");//@--64 
			foreach($headArr as $k =>$v){ 

			
				if($key>ord("Z")){  
					$key2 += 1;  
					$key = ord("A");  
					$colum = chr($key2).chr($key);//超过26个字母时才会启用  dingling 20150626  
				}else{  
					if($key2>=ord("A")){  
						$colum = chr($key2).chr($key);  
					}else{  
						$colum = chr($key);  
					}  
				}
				$objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', $v[0]);  
				$key += 1;  
			}  
			  
			$column = 2;  
			$objActSheet = $objPHPExcel->getActiveSheet();  
			foreach($data as $key => $rows){ //行写入  
				$span = ord("A");  
				$span2 = ord("@");
				foreach($headArr as $k=>$v){  
					if($span>ord("Z")){  
						$span2 += 1;  
						$span = ord("A");  
						$j = chr($span2).chr($span);//超过26个字母时才会启用  dingling 20150626  
					}else{  
						if($span2>=ord("A")){  
							$j = chr($span2).chr($span);  
						}else{  
							$j = chr($span);  
						}  
					}
					
					//$j = chr($span);
					
					$objActSheet->setCellValue($j.$column, strip_tags($rows[$v[1]]));  
					$span++;
				} 
				
				
				$column++;  
			}
			
                 
            
  
            $fileName = iconv("utf-8", "gb2312", $fileName);  
  
            //重命名表  
            //$objPHPExcel->getActiveSheet()->setTitle('test');  
            //设置活动单指数到第一个表,所以Excel打开这是第一个表  
            $objPHPExcel->setActiveSheetIndex(0);  
            ob_end_clean();//清除缓冲区,避免乱码  
            header('Content-Type: application/vnd.ms-excel');  
            header("Content-Disposition: attachment;filename=\"$fileName\"");  
            header('Cache-Control: max-age=0');  
  
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  
            $objWriter->save('php://output'); //文件通过浏览器下载  
            exit;  
              
        }

    public function tj(){
    	// $now = strtotime("2018-08-01 00:00:00");
    	// $future = strtotime("2018-09-01 00:00:00");57 62 63 64 66
    	$map['type'] = array('in','57,62,63,64,66');
    	$map['step'] = array('eq',40);
    	$map['is_del'] = array('eq',0);
    	// $map['create_time'] = array('gt', $now);
    	// $map['create_time'] = array('lt', $future);
    	// $map['update_time'] = array('lt', $future);
    	$result = M('Flow')->where($map)->select();
    	foreach ($result as $key => $value) {
    		$result[$key]['create_time'] = date("Y-m-d H:i",$value['create_time']);
    		$result[$key]['update_time'] = date("Y-m-d H:i",$value['update_time']);
    		$result[$key]['diff'] = $this->diffBetweenTwoDays(date("Y-m-d",$value['create_time']),date("Y-m-d",$value['update_time']));
    	}
    	// $this -> assign("content", $result);
    	$model = M('Flow');
    	$this-> _folder_export($model, $map);
    }




    public function diffBetweenTwoDays($day1,$day2)
	{
	  $second1 = strtotime($day1);
	  $second2 = strtotime($day2);
	   
	  if ($second1 < $second2) {
	    $tmp = $second2;
	    $second2 = $second1;
	    $second1 = $tmp;
	  }
	  return ($second1 - $second2) / 86400;
	}
	
	public function aaa(){
		$day1="2013-07-27";
		$day2="2013-08-04";
		$diff = $this->diffBetweenTwoDays($day1,$day2);
		echo $diff."\n";
	}


	private function _folder_export($model, $map) {
            $list = $model -> where($map) -> select();
            $r = $model -> where($map) -> count();
            $model_flow_field = D("UdfField");
            if ($r <= 1000) {
            //导入thinkphp第三方类库
                Vendor('Excel.PHPExcel');

            //$inputFileName = "Public/templete/contact.xlsx";
            //$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
                $objPHPExcel = new \PHPExcel();

                $objPHPExcel -> getProperties() -> setCreator("OA") -> setLastModifiedBy("OA") -> setTitle("Office 2007 XLSX Test Document") -> setSubject("Office 2007 XLSX Test Document") -> setDescription("Test document for Office 2007 XLSX, generated using PHP classes.") -> setKeywords("office 2007 openxml php") -> setCategory("Test result file");
            // Add some data
                $i = 1;
            //dump($list);

            //编号，类型，标题，登录时间，部门，登录人，状态，审批，执行，传阅，审批情况，自定义字段
                $objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("A$i", "编号") -> setCellValue("B$i", "类型") -> setCellValue("C$i", "标题") -> setCellValue("D$i", "创建时间") -> setCellValue("E$i", "结束时间") -> setCellValue("F$i", "相隔天数") -> setCellValue("G$i", "部门");

                foreach ($list as $val) {
                    $i++;
                //dump($val);
                    $id = $val['id'];
                    $doc_no = $val["doc_no"];
                //编号
                    $name = $val["name"];
                //标题
                    $confirm_name = strip_tags($val["confirm_name"]);
                //审批
                    $consult_name = strip_tags($val["consult_name"]);
                //执行
                    $refer_name = strip_tags($val["refer_name"]);
                //执行
                    $type_name = $val["type"];
                //流程类型
                    $user_name = $val["user_name"];
                //登记人
                    $dept_name = $val["dept_name"];
                //部门
                    $create_time = $val["create_time"];

                    // $create_time = to_date($val["create_time"], 'Y-m-d H:i:s');

                    $create_time = date("Y-m-d",$val['create_time']);
    				$update_time = date("Y-m-d",$val['update_time']);
    				$diff = $this->diffBetweenTwoDays($create_time,$update_time);
                //创建时间
                    $step = show_step($val["step"]);

                //编号，类型，标题，登录时间，部门，登录人，状态，审批，执行，传阅，审批情况，自定义字段
                    $objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("A$i", $doc_no) -> setCellValue("B$i", $type_name) -> setCellValue("C$i", $name) -> setCellValue("D$i", $create_time) -> setCellValue("E$i", $update_time) -> setCellValue("F$i", $diff) -> setCellValue("G$i", $dept_name);
                    // $result = M("flow_log") -> where(array('flow_id' => $id)) -> select();
                    // $field_data = ''; -> setCellValue("H$i", $confirm_name) -> setCellValue("I$i", $consult_name) -> setCellValue("J$i", $refer_name);
                    // if (!empty($result)) {
                    //     foreach ($result as $field) {
                    //         // $field_data = $field_data . $field['user_name'] . ":" . $field['comment'] . ";" . date("Y-m-d H:i:s",$field['update_time']) . "\n";
                    //         $field_data = $field_data . $field['user_name'] . ":" . $field['comment'] . ";创建时间-修改时间" . date("Y-m-d H:i:s",$field['create_time'])."一".date("Y-m-d H:i:s",$field['update_time'])."步骤".$field['step']. "\n";
                    //     }
                    //     $objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("K$i", $field_data);
                    // }

                //     $field_list = $model_flow_field -> get_data_list($val["udf_data"]);
                // //	dump($field_list);
                //     $k = 'K';
                //     if (!empty($field_list)) {
                //         foreach ($field_list as $field) {
                //             $k++;
                //             $field_data = $field['name'] . ":" . $field['val'];
                //         // $location = get_cell_location("J", $i, $k);
                //             $objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("$k$i", $field_data);
                //         }
                //     }
                }
            // Rename worksheet
                $objPHPExcel -> getActiveSheet() -> setTitle('审批统计');

            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
                $objPHPExcel -> setActiveSheetIndex(0);
                $file_name = "审批统计.xlsx";
            // Redirect output to a client’s web browser (Excel2007)
                header("Content-Type: application/force-download");
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header("Content-Disposition:attachment;filename =" . str_ireplace('+', '%20', URLEncode($file_name)));
                header('Cache-Control: max-age=0');

                $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            //readfile($filename);
                $objWriter -> save('php://output');
                exit ;
            } else {
                header('Content-Type: application/vnd.ms-excel;charset=gbk');
                header('Content-Disposition: attachment;filename="审批统计.csv"');
                header('Cache-Control: max-age=0');

                $fp = fopen('php://output', 'a');
                $title = array('编号', '类型', '标题', '登录时间', '部门', '登录人', '状态', '审批', '执行', '传阅', '审批情况', '自定义字段');
                foreach ($title as $i => $v) {
                // CSV的Excel支持GBK编码，一定要转换，否则乱码
                    $title[$i] = iconv('utf-8', 'gbk', $v);
                }
                fputcsv($fp, $title);
                $cnt = 0;
                foreach ($list as $val) {
                    $cnt++;
                if (100000 == $cnt) {//刷新一下输出buffer，防止由于数据过多造成问题
                    ob_flush();
                    flush();
                    $cnt = 0;
                }
                //dump($val);
                $id = $val['id'];
                $doc_no = $val["doc_no"];
                //编号
                $name = $val["name"];
                //标题
                $confirm_name = strip_tags($val["confirm_name"]);
                //审批
                $consult_name = strip_tags($val["consult_name"]);
                //执行
                $refer_name = strip_tags($val["refer_name"]);
                //执行
                $type_name = $val["type_name"];
                //流程类型
                $user_name = $val["user_name"];
                //登记人
                $dept_name = $val["dept_name"];
                //部门

                $create_time = to_date($val["create_time"], 'Y-m-d H:i:s');
                //创建时间
                $step = show_step_type($val["step"]);

                $result_list = M("flow_log") -> where(array('flow_id' => $id)) -> select();
                $field_data = '';
                $result = '';
                if (!empty($result_list)) {
                    foreach ($result_list as $field) {

                        $field_data = $field_data . $field['user_name'] . ":" . $field['comment'] . ";创建时间-修改时间" . date("Y-m-d H:i:s",$field['create_time'])."一".date("Y-m-d H:i:s",$field['update_time'])."步骤".$field['step']. "\n";
                    }
                    $result = $field_data;

                }
                $r1 = array($doc_no, $type_name, $name, $create_time, $dept_name, $user_name, $step, $confirm_name, $consult_name, $refer_name, $result);

                $field_list = $model_flow_field -> get_data_list($val["udf_data"]);

                $t = 0;
                $r2 = array();
                if (!empty($field_list)) {

                    foreach ($field_list as $field) {
                        $r2[$t++] = $field['name'] . ":" . $field['val'];
                    }

                }
                $row = array_merge($r1, $r2);
                // dump($row);
                foreach ($row as $i => $v) {
                    // CSV的Excel支持GBK编码，一定要转换，否则乱码
                    $row[$i] = iconv('utf-8', 'gbk', $v);
                }
                fputcsv($fp, $row);
            }
            fclose($fp);
            exit ;
        }
    }


}



?>