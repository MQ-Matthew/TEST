<?php

$basedir = dirname(__FILE__);
//require_once $basedir.'/Weixin/WXBizMsgCrypt.php';

include $basedir . '/Weixin/WXBizMsgCrypt.php';
class Weixin {

	/**
	 * 微信推送过来的数据或响应数据
	 * @var array
	 */
	private $data = array();

	/**
	 * 主动发送的数据
	 * @var array
	 */
	private $send = array();

	/*
	 *	配置信息和微信接口信息
	 */
	private $corp_id = '';
	private $corp_secret = "";
	private $token = '';
	private $encoding_aes_key = '';
	private $wxcpt;

	/**
	 * 构造函数
	 *实例化后自动获取变量
	 */
	public function __construct() {
		//读取配置数据
		$this -> corp_id = get_system_config('weixin_corp_id');
		$this -> corp_secret = get_system_config('weixin_secret');
		$this -> token = get_system_config('weixin_token');
		$this -> encoding_aes_key = get_system_config('weixin_encoding_aes_key');
		$this -> wxcpt = new WXBizMsgCrypt($this -> token, $this -> encoding_aes_key, $this -> corp_id);
	}

	/*获取推送数据*/
	public function request() {

		//获取接口参数
		$veryfy_msg = $_REQUEST["msg_signature"];
		$timestamp = $_REQUEST["timestamp"];
		$nonce = $_REQUEST["nonce"];
        
		if (!isset($_GET['echostr'])) {

			//接收到的xml包
			$postStr = file_get_contents('php://input');
			//解密
			$sMsg = "";
			// 解析之后的明文

			$errCode = $this -> wxcpt -> DecryptMsg($veryfy_msg, $timestamp, $nonce, $postStr, $sMsg);
            
			//转换成数组
			$xml = new SimpleXMLElement($sMsg);
			$xml || exit ;

			foreach ($xml as $key => $value) {
				$this -> data[$key] = strval($value);
			}
            
			return $this -> data;

		} else {
			//验证URL会出现此参数
			$msg_echostr = $_REQUEST["echostr"]; 
            
			$sEchoStr = "";
			$errCode = $this -> wxcpt -> VerifyURL($veryfy_msg, $timestamp, $nonce, $msg_echostr, $sEchoStr);
			if ($errCode == 0) {
				echo $sEchoStr;
			} else {
				print("ERR: " . $errCode . "\n\n");
			}
			exit ;
		}
	}

	/**
	 * * 被动响应微信发送的信息（自动回复）
	 * @param  string $to      接收用户名
	 * @param  string $from    发送者用户名
	 * @param  array  $content 回复信息，文本信息为string类型
	 * @param  string $type    消息类型
	 * @param  string $flag    是否新标刚接受到的信息
	 * @return string          XML字符串
	 */
	public function response($content, $type = 'text', $flag = 0) {
		$timestamp = $_REQUEST["timestamp"];
		$nonce = $_REQUEST["nonce"];
		/* 基础数据 */
		$this -> data = array('ToUserName' => $this -> data['FromUserName'], 'FromUserName' => $this -> data['ToUserName'], 'CreateTime' => NOW_TIME, 'MsgType' => $type, );

		/* 添加类型数据 */
		$this -> $type($content);

		/* 添加状态 */
		$this -> data['FuncFlag'] = $flag;

		/* 转换数据为XML */
		$xml = new SimpleXMLElement('<xml></xml>');
		$this -> data2xml($xml, $this -> data);
		//exit($xml->asXML());

		//加密数据
		$sEncryptMsg = '';
		$errCode = $this -> wxcpt -> EncryptMsg($xml -> asXML(), $timestamp, $nonce, $sEncryptMsg);

		print $sEncryptMsg;
		exit ;

		//加密并发送数据
	}

	/**
	 * 添加用户
	 * @param  string $data    json数据
	 * @return string          微信返回信息
	 */
	public function add_user($id, $name, $mobile) {
		$access_token = $this -> get_token();
		$url = "https://qyapi.weixin.qq.com/cgi-bin/user/create?access_token={$access_token}";
		$data['userid'] = $id;
		$data['name'] = $name;
		$data['mobile'] = $mobile;
		$data['department'] = 1;
		$sendjson = json_encode($data, JSON_UNESCAPED_UNICODE);
		$restr = $this -> http_post($url, $sendjson);
		return $restr;
	}

	/**
	 * 更新用户信息
	 * @param  string $data    json数据
	 * @return string          微信返回信息
	 */
	public function update_user($id, $name, $mobile, $enable) {
		$access_token = $this -> get_token();
		$url = "https://qyapi.weixin.qq.com/cgi-bin/user/create?access_token={$access_token}";
		$data['userid'] = $id;
		$data['name'] = $name;
		$data['mobile'] = $mobile;
		if ($enable == 0) {
			$enable = 1;
		} else {
			$enable = 0;
		}
		$data['enable'] = $enable;
		$sendjson = json_encode($data, JSON_UNESCAPED_UNICODE);
		$restr = $this -> http_post($url, $sendjson);
		return $restr;
	}

	/**
	 * 删除用户
	 * @param  string $data    json数据
	 * @return string          微信返回信息
	 */
	public function del_user($user_id) {
		$access_token = $this -> get_token();
		$url = "https://qyapi.weixin.qq.com/cgi-bin/user/delete?access_token={$access_token}&userid={$user_id}";
		$restr = $this -> http_get($url);
		return $restr;
	}

	public function get_user_list($status) {
		$access_token = $this -> get_token();
		$url = "https://qyapi.weixin.qq.com/cgi-bin/user/simplelist?access_token={$access_token}&department_id=1&status={$status}&fetch_child=1";
		$restr = $this -> http_get($url);
		$data = json_decode($restr, true);
		$user_list = $data['userlist'];
		$user_list = rotate($user_list);
		$user_list = $user_list['userid'];
		return $user_list;
	}

	/**
	 * 批量删除用户
	 * @param  string $data    json数据
	 * @return string          微信返回信息
	 */
	public function del_user_list($data = NULL) {
		$access_token = $this -> get_token();
		$url = "https://qyapi.weixin.qq.com/cgi-bin/user/batchdelete?access_token={$access_token}";
		$sendjson = json_encode(array('useridlist' => $data), JSON_UNESCAPED_UNICODE);
		$restr = $this -> http_post($url, $sendjson);
		return $restr;
	}

	/**
	 * * 获取微信用户的基本资料 不完善
	 *
	 * @param string $openid   	发送者用户名
	 * @return array 用户资料
	 */

	public function get_user_info($code = '') {
		if ($code) {
			header("Content-type: text/html; charset=utf-8");
			$access_token = $this -> get_token();
			$url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token={$access_token}&code={$code}";
			$httpstr = $this -> http_get($url);
			$harr = json_decode($httpstr, true);
			return $harr;
		} else {
			return false;
		}
	}

	public function get_user_id($code = '') {
		if ($code) {
			header("Content-type: text/html; charset=utf-8");
			$access_token = $this -> get_token();
			$url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token={$access_token}&code={$code}";
			$httpstr = $this -> http_get($url);
			$harr = json_decode($httpstr, true);
			return $harr['UserId'];
		} else {
			return false;
		}
	}

	/**
	 * 生成菜单
	 * @param  string $data 菜单的str
	 * @return string  返回的结果；
	 */
	public function set_app($data = NULL) {
		$access_token = $this -> get_token();
		$url = "https://qyapi.weixin.qq.com/cgi-bin/agent/set?access_token={$access_token}";
		$menustr = $this -> http_post($url, $data);
		return $menustr;
	}

	/**
	 * 生成菜单
	 * @param  string $data 菜单的str
	 * @return string  返回的结果；
	 */
	public function set_menu($data = NULL, $agent_id) {
		$access_token = $this -> get_token();
		$url = "https://qyapi.weixin.qq.com/cgi-bin/menu/create?access_token={$access_token}&agentid={$agent_id}";
		$menustr = $this -> http_post($url, $data);
		return $menustr;
	}

	/**
	 * * 主动发送消息
	 *
	 * @param string $content   内容
	 * @param string $openid   	发送者用户名
	 * @param string $type   	类型
	 * @return array 返回的信息
	 */

	public function send_msg($content, $openid = '', $type = 'text', $agent_id) {
		/* 基础数据 */
		$this -> send['touser'] = $openid;
		$this -> send['msgtype'] = $type;
		$this -> send['agentid'] = $agent_id;

		/* 添加类型数据 */
		$sendtype = 'send_' . $type;
		$this -> $sendtype($content);
		/* 发送 */
		$sendjson = json_encode($this -> send, JSON_UNESCAPED_UNICODE);
		$restr = $this -> send($sendjson);
		return $restr;
	}

	/**
	 * 发送文本消息
	 *
	 * @param string $content
	 *        	要发送的信息
	 */
	private function send_text($content) {
		$this -> send['text'] = array('content' => $content);
	}

	/**
	 * 发送图片消息
	 *
	 * @param string $content
	 *        	要发送的信息
	 */
	private function send_image($content) {
		$this -> send['image'] = array('media_id' => $content);
	}

	/**
	 * 发送视频消息
	 * @param  string $content 要发送的信息
	 */
	private function send_video($video) {
		list($video['media_id'], $video['title'], $video['description']) = $video;

		$this -> send['video'] = $video;
	}

	/**
	 * 发送语音消息
	 *
	 * @param string $content
	 *        	要发送的信息
	 */
	private function send_voice($content) {
		$this -> send['voice'] = array('media_id' => $content);
	}

	/**
	 * 发送音乐消息
	 *
	 * @param string $content
	 *        	要发送的信息
	 */
	private function send_music($music) {
		list($music['title'], $music['description'], $music['musicurl'], $music['hqmusicurl'], $music['thumb_media_id']) = $music;

		$this -> send['music'] = $music;
	}

	/**
	 * 发送图文消息
	 * @param  string $news 要回复的图文内容
	 */
	private function send_news($news) {
		$articles = array();
		foreach ($news as $key => $value) {
			// 下面的写法测试有问题|Terry
			// list($articles[$key]['title'], $articles[$key]['description'], $articles[$key]['url'], $articles[$key]['picurl']) = $value;
			// 改为下面
			$articles[$key] = $value;
			if ($key >= 9) {
				break;
			} //最多只允许10调新闻
		}
		$this -> send['news']['articles'] = $articles;
	}

	/**
	 * 回复文本信息
	 * @param  string $content 要回复的信息
	 */
	private function text($content) {
		$this -> data['Content'] = $content;
	}

	/**
	 * 回复音乐信息
	 * @param  string $content 要回复的音乐
	 */
	private function music($music) {
		list($music['Title'], $music['Description'], $music['MusicUrl'], $music['HQMusicUrl']) = $music;
		$this -> data['Music'] = $music;
	}

	/**
	 * 回复图文信息
	 * @param  string $news 要回复的图文内容
	 */
	private function news($news) {
		$articles = array();
		foreach ($news as $key => $value) {
			$articles[$key] = $value;
			if ($key >= 9) {
				break;
			} //最多只允许10调新闻
		}
		$this -> data['ArticleCount'] = count($articles);
		$this -> data['Articles'] = $articles;
	}

	/**
	 * 主动发送的信息
	 * @param  string $data    json数据
	 * @return string          微信返回信息
	 */
	private function send($data = NULL) {
		$access_token = $this -> get_token();
		$url = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token={$access_token}";
		$restr = $this -> http_post($url, $data);
		return $restr;
	}

	/**
	 * 数据XML编码
	 * @param  object $xml  XML对象
	 * @param  mixed  $data 数据
	 * @param  string $item 数字索引时的节点名称
	 * @return string
	 */
	private function data2xml($xml, $data, $item = 'item') {
		foreach ($data as $key => $value) {
			/* 指定默认的数字key */
			is_numeric($key) && $key = $item;

			/* 添加子元素 */
			if (is_array($value) || is_object($value)) {
				$child = $xml -> addChild($key);
				$this -> data2xml($child, $value, $item);
			} else {
				if (is_numeric($value)) {
					$child = $xml -> addChild($key, $value);
				} else {
					$child = $xml -> addChild($key);
					$node = dom_import_simplexml($child);
					$node -> appendChild($node -> ownerDocument -> createCDATASection($value));
				}
			}
		}
	}

	/**
	 * 获取保存的accesstoken
	 */
	private function get_token() {
		// access_token 应该全局存储与更新，以下代码以写入到文件中做示例
		//$data = json_decode(file_get_contents("access_token.json"));
		$data = S('S_TOKEN');
		if ($data['expire_time'] < time()) {

			$corp_id = $this -> corp_id;
			$app_secret = $this -> corp_secret;
			$url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corp_id}&corpsecret={$app_secret}";
			$res = json_decode($this -> http_get($url), true);
			$access_token = $res['access_token'];
			if ($access_token) {
				$data['expire_time'] = time() + 7000;
				$data['access_token'] = $access_token;
				S('S_TOKEN', $data);
			}
		} else {
			$access_token = $data['access_token'];
		}
		return $access_token;
	}

	private function http_get($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 500);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_URL, $url);

		$res = curl_exec($curl);
		curl_close($curl);

		return $res;
	}

	private function http_post($url, $data) {
		$curl = curl_init();

		//curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: text/html; charset=utf-8"));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 50);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_URL, $url);

		$res = curl_exec($curl);
		curl_close($curl);

		return $res;
	}

	public function add_file($file) {

		$access_token = $this -> get_token();
		$type = 'file';
		$url = "https://qyapi.weixin.qq.com/cgi-bin/media/upload?access_token={$access_token}&type={$type}";
		$ch1 = curl_init();
		$timeout = 5;
 
		$real_path = "{$file['rootpath']}{$file['savepath']}{$file['savename']}";
		
		$data['media'] = "@{$real_path};type=file;filename={$file['name']}";
 
		//curl_setopt($ch1, CURLOPT_SAFE_UPLOAD, false);
		curl_setopt($ch1, CURLOPT_URL, $url);
		curl_setopt($ch1, CURLOPT_POST, 1);
		curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch1, CURLOPT_POSTFIELDS, $data);

		$result = curl_exec($ch1);

		//print_r(curl_getinfo($ch1));
		curl_close($ch1);
		if (curl_errno() == 0) {
			$result = json_decode($result, true);
			return $result['media_id'];
		} else {
			echo curl_error($ch1);
			return false;
		}
	}

	/**
	 * 主动发送的信息
	 * @param  string $data    json数据
	 * @return string          微信返回信息
	 */
	function send_file($media_id,$open_id) {
		$data['touser'] = $open_id;
		$data['file']['media_id'] = $media_id;
		$agent_id = get_system_config('oa_agent_id');
		$data['agentid'] = $agent_id;

		$data['msgtype'] = 'file';

		$data = json_encode($data, JSON_UNESCAPED_UNICODE);
		$access_token = $this -> get_token();
		$url = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token={$access_token}";
		$restr = $this -> http_post($url, $data);
		return $restr;
	}

	function get_agent_list() {
		$access_token = $this -> get_token();
		$url = "https://qyapi.weixin.qq.com/cgi-bin/agent/list?access_token={$access_token}";
		$restr = $this -> http_get($url);
		$restr = json_decode($restr, true);
		if($restr['errcode']==0){
			return $restr['agentlist'];	
		}else{
			echo($restr['errmsg']);
			die;
		}
	}

}
