<?php
/*--------------------------------------------------------------------
 小微OA系统 - 让工作更轻松快乐

 Copyright (c) 2013 http://www.smeoa.com All rights reserved.

 Author:  jinzhu.yin<smeoa@qq.com>

 Support: https://git.oschina.net/smeoa/xiaowei
 --------------------------------------------------------------*/

namespace Home\Controller;

class GovDocController extends HomeController {
	protected $config = array('app_type' => 'master', 'admin' => 'add_doc,save_doc,edit_doc,save_readover');
	function _search_filter(&$where) {
		$where['is_del'] = array('eq', '0');
		$keyword = I('keyword');
		if (!empty($keyword)) {
			$where['title'] = array('like', "%" . $keyword . "%");
		}
	}

	public function index($state = unread) {
		$plugin['uploader'] = true;
		$this -> assign("plugin", $plugin);
		$plugin['date'] = true;
		$this -> assign("plugin", $plugin);
        //获取当前用户的权限
		$auth = $this -> judeg_auth(get_user_id());
		if($auth==null){
			
		}
		//文电拟办的权限
		if ($auth == 0) {
			$model = M('GovDoc');
			$where = $this -> _search($model);
			if (method_exists($this, '_search_filter')) {
				$this -> _search_filter($where);
			}
			if ($state == 'unread') {
				$where['is_read'] = 0;
			} else if ($state == 'read') {
				$where['is_read'] = 1;
			} else if ($state == 'unover') {
				$where['is_over'] = 0;
			} else if ($state == 'over') {
				$where['is_over'] = 1;
			} else if ($state == 'tuihui') {
				$where['is_read'] = 2;
			}

			$is_auth = 0;
        //领导权限
		} elseif ($auth == 1) {
			$model = D('GovDocView');

			$where = $this -> _search($model);
			if (method_exists($this, '_search_filter')) {
				$this -> _search_filter($where);
			}
			$where['user_id'] = get_user_id();
			if ($state == 'unread') {
				$where['state'] = 0;
			} else if ($state == 'read') {
				$where['state'] = 1;
			} else if ($state == 'unover') {
				$where['is_over'] = 0;
			} else if ($state == 'over') {
				$where['is_over'] = 1;
			}

			$is_auth = 1;
			$url = U('readover');
//部门负责人权限
		} elseif ($auth == 2) {
			$model = D('GovDocView');

			$where = $this -> _search($model);
			if (method_exists($this, '_search_filter')) {
				$this -> _search_filter($where);
			}
			$where['user_id'] = get_user_id();
			if ($state == 'unread') {
				$where['state'] = 0;
			} else if ($state == 'read') {
				$where['state'] = 1;
			} else if ($state == 'unover') {
				$where['is_over'] = 0;
			} else if ($state == 'over') {
				$where['is_over'] = 1;
			}

			$is_auth = 2;
			$url = U('readover2');
//普通人的权限
		} elseif ($auth == 3) {
			$model = D('GovDocView');

			$where = $this -> _search($model);
			if (method_exists($this, '_search_filter')) {
				$this -> _search_filter($where);
			}
			$where['user_id'] = get_user_id();
			if ($state == 'unread') {
				$where['state'] = 0;
			} else if ($state == 'read') {
				$where['state'] = 1;
			} else if ($state == 'unover') {
				$where['is_over'] = 0;
			} else if ($state == 'over') {
				$where['is_over'] = 1;
			}

			$is_auth = 3;
			$url = U('readover3');
		}
//打印用到的条件
		$_SESSION['report'] = $where;
		$this -> _list($model, $where);
		$this -> assign('is_auth', $is_auth);
		$this -> assign('state', $state);
		$this -> assign('url', $url);

		$this -> display();

	}

	//文电拟办
	public function add_doc() {
		$plugin['uploader'] = true;
		$plugin['date'] = true;
		$this -> assign("plugin", $plugin);
		$this -> get_ldbm_list();
		$this -> display();

	}

	//删除文电
	public function del_doc($id) {
		$gov_doc = M('gov_doc');
		$where['id'] = $id;
		$data['is_del'] = 1;
		$result = $gov_doc -> where($where) -> save($data);
		if ($result !== false) {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success("删除{$result}条!");
		} else {
			$this -> error('删除失败!');
		}
	}

	//自增涨文档编号
	public function get_doc_no() {
		$gov_doc = M('gov_doc');
		$count = $gov_doc -> count();
		if (empty($count)) {
			$doc_no = date('m-d') . '-01';
		} else {
			$list = $gov_doc -> order('create_time desc') -> limit(1) -> select();
			$arr = explode("-", $list[0]['doc_no']);
			$doc_no = date('m-d') . "-0" . ($arr[2] + 1);
		}
		return $doc_no;
	}

	//保存文电
	public function save_doc() {
		$gov_doc = M('gov_doc');
		if (!empty($_POST['gov_doc_id'])) {
			$map['id'] = $_POST['gov_doc_id'];
			$gov_doc -> where($map) -> delete();
		}
		$data['doc_no'] = $this -> get_doc_no();
		$data['title'] = $_POST['title'];
		$data['doc_type'] = $_POST['doc_type'];
		$data['add_file'] = $_POST['add_file'];
		$data['finish_time'] = $_POST['finish_time'];
		$data['create_time'] = time();
		$data['suggestion'] = $_POST['content'];
		$data['issuer'] = get_user_name();
		$insertId = $gov_doc -> add($data);
		$gov_doc_log = M('gov_doc_log');

		foreach ($_POST['ld_id'] as $k => $v) {
			if($k==0){
				$data1['is_first'] = 1;
			}else{
				$data1['is_first'] = 0;

			}
			$data1['gov_doc_id'] = $insertId;
			$data1['user_id'] = $v;
			$data1['type'] = "0";
			$data1['remarks'] = $_POST['content'];
			$data1['pishiren_id'] = get_user_id();
			$data1['pishiren_qianming'] = get_user_name();
			$data1['shenpi_time'] = time();
			$result = $gov_doc_log -> add($data1);
		}
		$push_data['type'] = '签批';
		$push_data['action'] = '';
		$push_data['title'] = $data['title'];
		$push_data['content'] = '发送人：' . get_dept_name() . "-" . get_user_name();
		$push_data['url'] = U("GovDoc/index?return_url=GovDoc/index");
		send_push($push_data, $_POST['ld_id']);

		$bm_user_id = array();

		foreach ($_POST['bm_id'] as $v) {
			$dept_list = $this -> get_dept_list($v);
			foreach ($dept_list as $v) {
				//判断是不是部门领导
				$is_auth = $this -> judeg_auth($v['id']);
				if ($is_auth == 2) {
					$bm_user_id[] = $v['id'];
					$data2['gov_doc_id'] = $insertId;
					$data2['user_id'] = $v['id'];
					$data2['type'] = "0";
					$data2['remarks'] = $_POST['content'];
					$data2['pishiren_id'] = get_user_id();
					$data2['pishiren_qianming'] = get_user_name();
					$data2['shenpi_time'] = time();
					$result = $gov_doc_log -> add($data2);
				}
			}

		}
		send_push($push_data, $bm_user_id);
		if ($result == false) {
			$this -> error('操作失败');
		} else {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('操作成功');
		}
	}

	//获取同一个部门的所有用户
	public function get_dept_list($id) {
		$User = D('UserView');
		$where['dept_id'] = $id;
		$list = $User -> where($where) -> select();
		return $list;
	}

	//修改文电
	public function edit_doc($id) {
		$plugin['uploader'] = true;
		$plugin['date'] = true;
		$this -> assign("plugin", $plugin);
		if ($_POST['state'] == '1') {

		} else {
			$gov_doc = M('gov_doc');
			$where['id'] = $id;
			$data = $gov_doc -> where($where) -> find();
			$this -> assign('data', $data);
			$this -> assign('gov_doc_id', $id);
			$this -> get_ldbm_list();

			$this -> display();
		}
	}

	//领导审批
	public function readover($id) {
		$gov_doc = M('gov_doc');
		$where['id'] = $id;
		$wendian = $gov_doc -> where($where) -> find();
		$this -> assign('wendian', $wendian);
		$this -> assign('gov_doc_id', $id);
		$this -> get_ldbm_list();
		$this -> get_useful_word();
		$this -> display();
	}

	public function save_readover() {

		$gov_doc_log = M('gov_doc_log');
		$push_user_list = array();
		if(empty($_POST['ld_id'])&&empty($_POST['ld2_id'])&&empty($_POST['bm_id'])&&empty($_POST['bm2_id'])){
			$data['gov_doc_id'] = $_POST['gov_doc_id'];
			$data['type'] = "1";
			$data['remarks'] = $_POST['content'];
			$data['pishiren_id'] = get_user_id();
			$data['pishiren_qianming'] = get_user_name();
			$data['shenpi_time'] = time();
			$gov_doc_log -> add($data);
		}
		foreach ($_POST['ld_id'] as $v) {
			$push_user_list[] = $v;
			$data1['gov_doc_id'] = $_POST['gov_doc_id'];
			$data1['user_id'] = $v;
			$data1['type'] = "1";
			$data1['remarks'] = $_POST['content'];
			$data1['pishiren_id'] = get_user_id();
			$data1['pishiren_qianming'] = get_user_name();
			$data1['shenpi_time'] = time();

			$is_user_repetition1 = $this -> is_user_repetition($_POST['gov_doc_id'], $v);
			if ($is_user_repetition1 == true) {
				$gov_doc_log -> add($data1);
			}

		}

		foreach ($_POST['ld2_id'] as $v) {
			$push_user_list[] = $v;
			$data2['gov_doc_id'] = $_POST['gov_doc_id'];
			$data2['user_id'] = $v;
			$data2['sort'] = "1";
			$data2['type'] = "1";
			$data2['remarks'] = $_POST['content'];
			$data2['pishiren_id'] = get_user_id();
			$data2['pishiren_qianming'] = get_user_name();
			$data2['shenpi_time'] = time();
			$is_user_repetition2 = $this -> is_user_repetition($_POST['gov_doc_id'], $v);
			if ($is_user_repetition2 == true) {
				$gov_doc_log -> add($data2);
			}
		}
		foreach ($_POST['bm_id'] as $v) {

			$dept_list = $this -> get_dept_list($v);
			foreach ($dept_list as $v) {
				$is_auth = $this -> judeg_auth($v['id']);
				if ($is_auth == 2) {
					$push_user_list[] = $v['id'];
					$data3['gov_doc_id'] = $_POST['gov_doc_id'];
					$data3['user_id'] = $v['id'];
					$data3['type'] = "1";
					$data3['remarks'] = $_POST['content'];
					$data3['pishiren_id'] = get_user_id();
					$data3['pishiren_qianming'] = get_user_name();
					$data3['shenpi_time'] = time();
					$is_user_repetition3 = $this -> is_user_repetition($_POST['gov_doc_id'], $v['id']);
					if ($is_user_repetition3 == true) {
						$gov_doc_log -> add($data3);
					}
				}
			}

		}

		foreach ($_POST['bm2_id'] as $v) {
			$dept_user_list = $this -> get_dept_list($v);
			foreach ($dept_user_list as $v) {
				$is_auth = $this -> judeg_auth($v['id']);
				if ($is_auth == 2) {
					$push_user_list[] = $v['id'];
					$data4['gov_doc_id'] = $_POST['gov_doc_id'];
					$data4['user_id'] = $v['id'];
					$data4['type'] = "1";
					$data4['remarks'] = $_POST['content'];
					$data4['pishiren_id'] = get_user_id();
					$data4['pishiren_qianming'] = get_user_name();
					$data4['shenpi_time'] = time();
					$is_user_repetition4 = $this -> is_user_repetition($_POST['gov_doc_id'], $v['id']);
					if ($is_user_repetition4 == true) {
						$gov_doc_log -> add($data4);
					}
				}
			}

		}
		$push_arr = array_unique($push_user_list);
		$push_data['type'] = '签批';
		$push_data['action'] = '';
		$push_data['title'] = $_POST['title'];
		$push_data['content'] = '发送人：' . get_dept_name() . "-" . get_user_name();
		$push_data['url'] = U("GovDoc/index");
		send_push($push_data, $push_arr);

		$gov_doc = M('gov_doc');
		$where['id'] = $_POST['gov_doc_id'];
		$state['is_read'] = 1;
		$state['is_agent'] = 1;
		if ($_POST['is_over'] == 'on') {
			$state['is_over'] = 1;
		}
		$gov_doc -> where($where) -> save($state);

		$gov_doc_log2 = M('gov_doc_log');
		$state2['state'] = 1;
		$where2['user_id'] = get_user_id();
		$where2['gov_doc_id'] = $_POST['gov_doc_id'];
		$gov_doc_log2 -> where($where2) -> save($state2);

		$this -> assign('jumpUrl', get_return_url());
		$this -> success('操作成功');

	}

	public function readover2($id) {
		$gov_doc = M('gov_doc');
		$where['id'] = $id;
		$wendian = $gov_doc -> where($where) -> find();
		$this -> assign('wendian', $wendian);
		$this -> assign('gov_doc_id', $id);
		$this -> get_deptuser_list();
		$this -> get_useful_word();
		$this -> display();
	}

	public function save_readover2() {
		$gov_doc_log = M('gov_doc_log');
		if(empty($_POST['bm_id'])&&empty($_POST['bm2_id'])){
			$data['gov_doc_id'] = $_POST['gov_doc_id'];
			$data['user_id'] = $v;
			$data['type'] = "2";
			$data['remarks'] = $_POST['content'];
			$data['pishiren_id'] = get_user_id();
			$data['pishiren_qianming'] = get_user_name();
			$data['shenpi_time'] = time();
			$gov_doc_log -> add($data);
		}
		$push_user_list = array();
		foreach ($_POST['bm_id'] as $v) {
			$push_user_list[] = $v;
			$data3['gov_doc_id'] = $_POST['gov_doc_id'];
			$data3['user_id'] = $v;
			$data3['type'] = "2";
			$data3['remarks'] = $_POST['content'];
			$data3['pishiren_id'] = get_user_id();
			$data3['pishiren_qianming'] = get_user_name();
			$data3['shenpi_time'] = time();
			$is_user_repetition = $this -> is_user_repetition($_POST['gov_doc_id'], $v);
			if ($is_user_repetition == true) {
				$gov_doc_log -> add($data3);
			}
		}

		foreach ($_POST['bm2_id'] as $v) {
			$push_user_list[] = $v;
			$data4['gov_doc_id'] = $_POST['gov_doc_id'];
			$data4['user_id'] = $v;
			$data4['type'] = "2";
			$data4['remarks'] = $_POST['content'];
			$data4['pishiren_id'] = get_user_id();
			$data4['pishiren_qianming'] = get_user_name();
			$data4['shenpi_time'] = time();
			$is_user_repetition2 = $this -> is_user_repetition($_POST['gov_doc_id'], $v);
			if ($is_user_repetition2 == true) {
				$gov_doc_log -> add($data4);
			}
		}
		$push_arr = array_unique($push_user_list);
		$push_data['type'] = '签批';
		$push_data['action'] = '';
		$push_data['title'] = $_POST['title'];
		$push_data['content'] = '发送人：' . get_dept_name() . "-" . get_user_name();
		$push_data['url'] = U("GovDoc/index");
		send_push($push_data, $push_arr);

		$gov_doc = M('gov_doc');
		$where['id'] = $_POST['gov_doc_id'];
		if ($_POST['is_save'] == 'on') {
			$state['is_save'] = 1;
		}
		if ($_POST['is_over'] == 'on') {
			$state['is_over'] = 1;
		}
		$gov_doc -> where($where) -> save($state);

		$where2['gov_doc_id'] = $_POST['gov_doc_id'];
		$where2['user_id'] = get_user_id();
		$state2['state'] = 1;
		$gov_doc_log -> where($where2) -> save($state2);
		$this -> assign('jumpUrl', get_return_url());
		$this -> success('操作成功');

	}

	//普通人員阅办
	public function readover3($id) {
		$gov_doc = M('gov_doc');
		$where['id'] = $id;
		$wendian = $gov_doc -> where($where) -> find();
		$this -> assign('wendian', $wendian);
		$this -> assign('gov_doc_id', $id);
		$this -> get_deptuser_list();
		$this -> display();
	}

	public function save_readover3() {
		$gov_doc_log = M('gov_doc_log');
		$data['gov_doc_id'] = $_POST['gov_doc_id'];
		$data['remarks'] = $_POST['content'];
        $data['type'] = "2";
		$data['pishiren_id'] = get_user_id();
		$data['pishiren_qianming'] = get_user_name();
		$data['shenpi_time'] = time();
		$gov_doc_log -> where($where) -> add($data);

		$gov_doc = M('gov_doc');
		$where['id'] = $_POST['gov_doc_id'];
		$state['is_over'] = 1;
		$gov_doc -> where($where) -> save($state);

		$where2['gov_doc_id'] = $_POST['gov_doc_id'];
		$where2['user_id'] = get_user_id();
		$state2['state'] = 1;
		$gov_doc_log -> where($where2) -> save($state2);
		
		$this -> assign('jumpUrl', get_return_url());
		$this -> success('操作成功');

	}

	//代批
	public function agent_readover($id) {
		$gov_doc = M('gov_doc');
		$where['id'] = $id;
		$wendian = $gov_doc -> where($where) -> find();
		$this -> assign('wendian', $wendian);
		$this -> assign('gov_doc_id', $id);
		$this -> get_ldbm_list($id);
		$this -> get_useful_word();

		$User = M(User);
		$lingdao_user_list = $this -> get_user_list(1);
		if (empty($lingdao_user_list)) {
			$where2['_string'] = "1=2";
		} else {
			$where2['id'] = array('in', $lingdao_user_list);
		}
		$lingdaolist = $User -> where($where2) -> select();
		$this -> assign('lingdaolist', $lingdaolist);
		$this -> display();
	}

	//代批保存
	public function save_agent_readover() {

		$gov_doc_log = M('gov_doc_log');
		$map['gov_doc_id']=$_POST['gov_doc_id'];
		$map['is_first']=1;
		$pishiren=$gov_doc_log->where($map)->find();
		$user=M('User');
		$map2['id']= $pishiren['user_id'];
		$qianming=$user->where($map2)->find();
		
		if(empty($_POST['ld_id'])&&empty($_POST['ld2_id'])&&empty($_POST['bm_id'])&&empty($_POST['bm2_id'])){
			$data['gov_doc_id'] = $_POST['gov_doc_id'];
			$data['type'] = "1";
			$data['remarks'] = $_POST['content'];
			$data['pishiren_id'] = $pishiren['user_id'];
			$data['pishiren_qianming'] = $qianming['nickname'];
			$data['shenpi_time'] = time();
			$gov_doc_log -> add($data);
		}
		
		$push_user_list = array();
		foreach ($_POST['ld_id'] as $v) {
			$push_user_list[] = $v;
			$data1['gov_doc_id'] = $_POST['gov_doc_id'];
			$data1['user_id'] = $v;
			$data1['type'] = 1;
			$data1['remarks'] = $_POST['content'];
			$data1['pishiren_id'] = $pishiren['user_id'];
			$data1['pishiren_qianming'] = $qianming['nickname'];
			$data1['shenpi_time'] = time();
			$is_user_repetition = $this -> is_user_repetition($_POST['gov_doc_id'], $v);
			if ($is_user_repetition == true) {
				$result = $gov_doc_log -> add($data1);
			}
		}
		foreach ($_POST['ld2_id'] as $v) {
			$push_user_list[] = $v;
			$data2['gov_doc_id'] = $_POST['gov_doc_id'];
			$data2['user_id'] = $v;
			$data2['type'] = 1;
			$data2['remarks'] = $_POST['content'];
			$data2['pishiren_id'] = $pishiren['user_id'];
			$data2['pishiren_qianming'] = $qianming['nickname'];
			$data2['shenpi_time'] = time();
			$is_user_repetition2 = $this -> is_user_repetition($_POST['gov_doc_id'], $v);
			if ($is_user_repetition2 == true) {
				$gov_doc_log -> add($data2);
			}
		}
		foreach ($_POST['bm_id'] as $v) {
			$dept_list = $this -> get_dept_list($v);
			foreach ($dept_list as $v) {
				$is_auth = $this -> judeg_auth($v['id']);
				if ($is_auth == 2) {
					$push_user_list[] = $v['id'];
					$data3['gov_doc_id'] = $_POST['gov_doc_id'];
					$data3['user_id'] = $v['id'];
					$data3['type'] = 1;
					$data3['remarks'] = $_POST['content'];
					$data3['pishiren_id'] = $pishiren['user_id'];
					$data3['pishiren_qianming'] = $qianming['nickname'];
					$data3['shenpi_time'] = time();
					$is_user_repetition3 = $this -> is_user_repetition($_POST['gov_doc_id'], $v['id']);
					if ($is_user_repetition3 == true) {
						$gov_doc_log -> add($data3);
					}
				}
			}

		}
		foreach ($_POST['bm2_id'] as $v) {
			$dept_list = $this -> get_dept_list($v);
			foreach ($dept_list as $v) {
				$is_auth = $this -> judeg_auth($v['id']);
				if ($is_auth == 2) {
					$push_user_list[] = $v['id'];
					$data4['gov_doc_id'] = $_POST['gov_doc_id'];
					$data4['user_id'] = $v['id'];
					$data4['type'] = 1;
					$data4['remarks'] = $_POST['content'];
					$data4['pishiren_id'] = $pishiren['user_id'];
					$data4['pishiren_qianming'] = $qianming['nickname'];
					$data4['shenpi_time'] = time();
					$is_user_repetition4 = $this -> is_user_repetition($_POST['gov_doc_id'], $v['id']);
					if ($is_user_repetition4 == true) {
						 $gov_doc_log -> add($data4);
					}
				}
			}

		}
		$push_arr = array_unique($push_user_list);
		$push_data['type'] = '签批';
		$push_data['action'] = '';
		$push_data['title'] = $_POST['title'];
		$push_data['content'] = '发送人：' . get_dept_name() . "-" . get_user_name();
		$push_data['url'] = U("GovDoc/index");
		send_push($push_data, $push_arr);

		$gov_doc = M('gov_doc');
		$where['id'] = $_POST['gov_doc_id'];
		$state['is_agent'] = 1;
		$state['is_read'] = 1;
		$gov_doc -> where($where) -> save($state);

		$where2['gov_doc_id'] = $_POST['gov_doc_id'];
		$where2['user_id'] = get_user_id();
		$state2['state'] = 1;
		$gov_doc_log -> where($where2) -> save($state2);
		//如果是代执就把领导状态改成已批
		$where3['gov_doc_id'] = $_POST['gov_doc_id'];
		$where3['is_first'] = 1;
		$state3['state'] = 1;
		$gov_doc_log -> where($where3) -> save($state3);
		
		$this -> assign('jumpUrl', get_return_url());
		$this -> success('操作成功');

	}
    //退回
    public function tuihui($id){
    	$gov_doc=M('gov_doc');
		$where['id']=$id;
		$where['user_id']=get_user_id();
		$data['is_read']=2;
		$data['is_agent']=1;
		$gov_doc->where($where)->save($data);
		
		$gov_doc_log=M('gov_doc_log');
		$where2['gov_doc_id']=$id;
		$data2['state']=2;
		$gov_doc_log->where($where2)->save($data2);
		$this -> success('操作成功');
    }
	//修改批语
	public function edit_readover($id){
		if($_POST['state']!=true){
			$gov_doc_log=M('gov_doc_log');
			$where['gov_doc_id']=$id;
			$where['pishiren_id']=get_user_id();
			$list=$gov_doc_log->where($where)->group('pishiren_id')->find();
			$this->assign('remarks',$list['remarks']);
			$this->assign('id',$id);
			$this->display();
		}else{
			$gov_doc_log=M('gov_doc_log');
			$where['gov_doc_id']=$id;
			$where['pishiren_id']=get_user_id();
			$data['remarks']=$_POST['content'];
			$gov_doc_log->where($where)->save($data);
			$this->success('修改成功！');
		}
	}
	public function is_user_repetition($gov_doc_id, $user_id) {
		$gov_doc_log = M('gov_doc_log');
		$where['gov_doc_id'] = $gov_doc_id;
		$where['user_id'] = $user_id;
		$list = $gov_doc_log -> where($where) -> count();
		if ($list == 0) {
			return ture;
		} else {
			return false;
		}
	}

	public function get_useful_word() {
		$model = M('SystemConfig');
		$where['code'] = 'USEFUL_WROD';
		$useful_word_list = $model -> where($where) -> select();
		$this -> assign('useful_word_list', $useful_word_list);
	}

	//批示单
	public function doc_order($id) {

		$gov_doc_log = M('gov_doc_log');
		$where['gov_doc_id'] = $id;
		$where['type'] = 0;
		$list = $gov_doc_log -> where($where) -> find();
		$this -> assign('list', $list);

		$list2 = D('GovDocOrderView') -> get_gov_doc_list('a.gov_doc_id=' . $id . ' and a.type=1 and ');
		$this -> assign('list2', $list2);

		$where['type'] = 2;
		$list3 = $gov_doc_log -> where($where) -> group('pishiren_id')->order('shenpi_time') ->select();
		$this -> assign('list3', $list3);
		$this -> display();
	}

	//获取阅办阅知的人员
	public function get_ldbm_list() {
		$User = M('user');
		//获取领导
		$lingdao_user_list = $this -> get_user_list(1);
		if (empty($lingdao_user_list)) {
			$where['_string'] = "1=2";
		} else {
			$where['id'] = array('in', $lingdao_user_list);
		}
		$list = $User -> where($where) -> select();
		$this -> assign('list', $list);

		$User2 = D('UserView');
		//获取部门
		$bumen_user_list = $this -> get_user_list(2);
		if (empty($bumen_user_list)) {
			$where2['_string'] = "1=2";
		} else {
			$where2['id'] = array('in', $bumen_user_list);
		}
		$list2 = $User2 -> where($where2) -> group('dept_name') -> select();
		$this -> assign('list2', $list2);

	}

	//获取当前用户权限
	public function get_auth() {
		$model = M('gov_doc_auth');
		$auth = $model -> select();
		if (empty($auth)) {
			$this -> redirect('GovDocAuth/index');
		}
		$where['user_id'] = get_user_id();
		$list = $model -> where($where) -> find();
		return $list['set_auth'];
	}

	//判断用户权限
	public function judeg_auth($user_id) {
		$model = M('gov_doc_auth');
		$where['user_id'] = $user_id;
		$list = $model -> where($where) -> find();
		return $list['set_auth'];
	}

	//获取某个部门的人员
	public function get_deptuser_list() {
		$User = M('User');
		$where['id'] = get_user_id();
		$list = $User -> where($where) -> find();
		$UserView = D('UserView');
		$where2['dept_id'] = $list['dept_id'];
		$list2 = $UserView -> where($where2) -> select();
		$this -> assign('list2', $list2);

	}

	//获取某个权限的所有用户id
	public function get_user_list($num) {
		$model = M('gov_doc_auth');
		$where['set_auth'] = $num;
		$list = $model -> field('user_id') -> where($where) -> select();
		$arr = array();
		foreach ($list as $v) {
			$arr[] = $v['user_id'];
		}
		return $arr;

	}

	function export() {
		$model = M('GovDoc');
		$list = $model -> where($_SESSION['report']) -> select();
		$this -> exportexcel($list, array('文档编号', '标题', '发起时间', '类型', '批示情况', '完成情况', '存档','拟办人'), 'report');
	}

	function exportexcel($data = array(), $title = array(), $filename = 'report') {
		header("Content-type:application/octet-stream");
		header("Accept-Ranges:bytes");
		header("Content-type:application/vnd.ms-excel");
		header("Content-Disposition:attachment;filename=" . $filename . ".xls");
		header("Pragma: no-cache");
		header("Expires: 0");
		//导出xls 开始
		if (!empty($title)) {
			foreach ($title as $k => $v) {
				$title[$k] = iconv("UTF-8", "GB2312", $v);
			}
			$title = implode("\t", $title);
			echo "$title\n";
		}
		$gov_doc_log = M('gov_doc_log');
		if (!empty($data)) {
			foreach ($data as $key => $val) {
				$where['gov_doc_id'] = $val['id'];
				$list = $gov_doc_log -> where($where)->group('type')-> select();
				$pishiqingkuang = "";
				foreach ($list as $v) {
					$pishiqingkuang .= $v['pishiren_qianming'].':'.$v['remarks'];
				}
				$data2[$key]['doc_no'] = iconv("UTF-8", "GB2312", "'" . $val['doc_no']);
				$data2[$key]['title'] = iconv("UTF-8", "GB2312", $val['title']);
				$data2[$key]['create_time'] = iconv("UTF-8", "GB2312", date('Y-m-d', $val['create_time']));
				$data2[$key]['doc_type'] = iconv("UTF-8", "GB2312", $val['doc_type'] ? "红头文件" : "一般文件");
				$data2[$key]['pishiqingkuang'] = iconv("UTF-8", "GB2312", $pishiqingkuang);
				$data2[$key]['is_over'] = iconv("UTF-8", "GB2312", $val['is_over'] ? "已完成" : "未完成");
				$data2[$key]['is_save'] = iconv("UTF-8", "GB2312", $val['is_save'] ? "已存档" : "未存档");
				$data2[$key]['issuer'] = iconv("UTF-8", "GB2312", $val['issuer'] );
				$data2[$key] = implode("\t", $data2[$key]);
			}
			echo implode("\n", $data2);
		}
	}
    public function download($file){
    	$this->assign('file',$file);
    	$this->display();
    }
	public function upload() {
		$this -> _upload();
	}

	function down($attach_id) {
		$this -> _down($attach_id);
	}

}
