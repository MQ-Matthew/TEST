<?php
/*---------------------------------------------------------------------------
 小微OA系统 - 让工作更轻松快乐

 Copyright (c) 2013 http://www.smeoa.com All rights reserved.

 Author:  jinzhu.yin<smeoa@qq.com>

 Support: https://git.oschina.net/smeoa/xiaowei
 -------------------------------------------------------------------------*/

namespace Weixin\Controller;

class IndexController extends WeixinController {

	protected $config = array('app_type' => 'public');

	function _initialize() {	
		$agent_id = get_system_config("oa_agent_id");
		if (empty($agent_id)) {
			$this -> error('没有找到OA_AGENT_ID');
		}
		
		$this -> agent_id = $agent_id;
		import("@.ORG.Util.Weixin");
		$this -> weixin = new \Weixin();
	}

	public function index() {
		if (!is_weixin()) {
			$this -> error('参数错误');
		}
		$id = I('id');
		if ($id) {
			$this -> weixin_link($id);
		} else {
			$code = I("code");
			$state = I("state");
			$this -> login($code, $state);
		}
	}

	private function weixin_link($id) {

		$site_url = get_system_config("weixin_site_url");
		$redirect_uri = urlencode($site_url . '/index.php?m=Weixin');

		$raw_url = get_system_config("weixin_link_{$id}");
		
		$arr_raw_url = explode('/', $raw_url);
		$return_url = ucfirst($arr_raw_url['1']) . '/index';
		$top_menu_id = get_top_menu_id($return_url);
		cookie('top_menu', $top_menu_id);

		$url = base64_encode(U($raw_url));
		$corpid = get_system_config("weixin_corp_id");
		$oauth_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$corpid&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_base&state={$url}#wechat_redirect";
		redirect($oauth_url);
	}

	public function login($code, $state) {
		if (empty($code)) {
			$this -> error('参数错误');
		}
		if (empty($state)) {
			$this -> error('参数错误');
		}

		$openid = $this -> weixin -> get_user_id($code);
		if (empty($openid)) {
			$this -> error('参数错误');
		}

		$model = M("User");
		$where['openid'] = array('eq', $openid);
		$where['westatus'] = array('eq', 1);
		$where['is_del'] = array('eq', 0);

		$auth_info = $model -> where($where) -> find();

		// 查到userid
		if ($auth_info) {
			session(C('USER_AUTH_KEY'), $auth_info['id']);
			session('emp_no', $auth_info['emp_no']);
			session('email', $auth_info['email']);
			session('user_name', $auth_info['name']);
			session('user_pic', $auth_info['pic']);
			session('dept_id', $auth_info['dept_id']);
			$url = base64_decode($state);
			redirect($url);
		} else {
			redirect(U('Weixin/Oa/bind', array('openid' => $openid)));
		}
	}

}
