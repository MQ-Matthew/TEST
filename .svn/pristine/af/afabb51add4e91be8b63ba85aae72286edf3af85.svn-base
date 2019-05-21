<?php

namespace Weixin\Controller;
use Think\Controller;

class WeixinController extends Controller {
	protected $config = array('app_type' => 'public');
    public $weixin;
	protected $agent_id;
    
	/**
	 * 跳出页面-信息提示页面
	 */

	function _check_login() {

		$auth_id = session(C('USER_AUTH_KEY'));
		if (!isset($auth_id)) {
			//跳转到认证网关
			$this -> error('非法访问');
		}
	}

	public function message($msg = '') {
		$this -> assign('msg', $msg);
		$this -> display('Layout/message');
		exit();
	}

	/**
	 * 信息入库
	 * @param array $data 接收的数据
	 */
	private function add_log($data) {
		if ($data['MsgType'] == 'event') {
			M('weixin_log') -> data($data) -> add();
		} else {
			M('weixin_log') -> data($data) -> add();
		}
	}

	/**
	 * 主动发送消息
	 *
	 * @param string $content
	 *        	内容
	 * @param string $openid
	 *        	发送者用户名
	 * @param string $type
	 *        	类型
	 * @return array 返回的信息
	 */
	// function send($content, $openid = '', $type = 'text') {
	//	ignore_user_abort(true);
	//	$restr = $this -> weixin -> send_msg($content, $openid, $type);
	//	return $restr;
	//}

	function send($content, $openid, $type = 'news') {

		if (!is_array($content) && in_array($type, array('news', 'mpnews', 'video'))) {
			$content = json_decode($content, true);
		}
		if (!empty($content['action'])) {
			$msg['title'] = "{$content['type']}：{$content['action']}";
		} else {
			$msg['title'] = "{$content['type']}";
		}

		if (empty($content['title'])) {
			$msg['description'] = "【{$content['title']}】" . "\r\n" . $content['content'];
		} else {
			$msg['description'] = $content['content'];
		}

		$msg['url'] = $this -> get_weixin_auth_url($content['url']);

		$push_agent_id = get_push_agent_id($content['type']);
		$user_list = $this -> weixin -> get_user_list(1);

		$openid_list = array_filter(explode('|', $openid));

		$open_id = implode('|', array_intersect($user_list, $openid_list));
		$restr = $this -> weixin -> send_msg(array($msg), $open_id, $type, $push_agent_id);
		//\Think\Log::record($restr, 'WARN', true);
		return $restr;
	}

	public function _set_menu($data) {
		echo $this -> weixin -> set_menu($data, $this -> agent_id);
	}

	private function get_weixin_auth_url($url) {
		$site_url = get_system_config("weixin_site_url");
		$corpid = get_system_config("weixin_corp_id");
		$redirect_uri = urlencode($site_url . '/index.php?m=Weixin');
		$url = base64_encode($site_url . $url);
		$oauth_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$corpid}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_base&state={$url}#wechat_redirect";
		return $oauth_url;
	}

}
?>