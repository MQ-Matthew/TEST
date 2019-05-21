<?php

namespace Weixin\Controller;
use Home\Controller;
class SignController extends WeixinController {

	protected $config = array('app_type' => 'public');

	function _initialize() {
		$agent_id = get_system_config("sign_agent_id");
		if (empty($agent_id)) {
			$this -> error('没有找到SIGN_AGENT_ID');
		}
		$this -> agent_id = $agent_id;
		import("@.ORG.Util.Weixin");
		$this -> weixin = new \Weixin();
	}

	public function set_menu() {

		$sub = array();
		$data = array();
		$subs = array();

		$corpid = get_system_config("weixin_corp_id");
		$site_url = get_system_config("weixin_site_url");

		$redirect_uri = urlencode($site_url . U('Weixin/Sign/add'));

		$sign_in_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$corpid&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_base&state=sign_in#wechat_redirect";

		$sign_out_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$corpid&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_base&state=sign_out#wechat_redirect";

		$outside_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$corpid&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_base&state=outside#wechat_redirect";

		$sub1 = array('type' => 'view', 'name' => '签到', 'url' => $sign_in_url);
		$sub2 = array('type' => 'view', 'name' => '签退', 'url' => $sign_out_url);
		$sub3 = array('type' => 'view', 'name' => '外勤', 'url' => $outside_url);

		$data['button'][] = $sub1;
		$data['button'][] = $sub2;
		$data['button'][] = $sub3;

		$data = json_encode($data, JSON_UNESCAPED_UNICODE);
		$this -> _set_menu($data);
	}

	public function index() {

		/* 获取请求信息 */
		$data = $this -> weixin -> request();
		/* 获取回复信息 */
		list($content, $type) = $this -> reply($data);
		/* 响应当前请求 */
		$this -> weixin -> response($content, $type);
	}

	function add() {
		header("Pragma: no-cache ");
		header(" Expires: Mon, 26 Jul 1970 05:00:00 GMT ");
		if (IS_POST) {

		}

		vendor('WeiXin.jssdk');
		$corp_id = get_system_config('weixin_corp_id');
		$secret = get_system_config('weixin_secret');
		$jssdk = new \JSSDK($corp_id, $secret);
		$signPackage = $jssdk -> GetSignPackage();

		$this -> assign('signPackage', $signPackage);
		$this -> assign('is_weixin', is_weixin());

		$state = I('state');
		$this -> assign('state', $state);
		$this -> display();
	}

	/**
	 * 定制响应信息
	 * @param array $data 接收的数据
	 * @return array; 响应的数据
	 */
	private function reply($data) {
		// 消息类型
		switch ($data ['MsgType']) {
			case 'event' :
				// 类型是事件的
				// 事件类型
				switch ($data ['Event']) {
					case 'LOCATION' :
						// 上报地理位置事件
						$reply = $this -> record_location($data);
						break;
				}

				break;
			default :
				$reply = array('没有相关消息类型', 'text');
				break;
		}
		return $reply;
	}

	function save() {
		$data['type'] = I('state');
		$data['ip'] = getenv('REMOTE_ADDR');
		$data['emp_no'] = get_emp_no();
		$data['user_id'] = get_user_id();
		$data['create_time'] = time();
		$data['latitude'] = I('lat');
		$data['longitude'] = I('lng');
		$data['is_real_time'] = 0;
		$data['sign_date'] = to_date(time());
		$data['location'] = I('location');
		$data['content'] = I('content');

		$result = M('Sign') -> add($data);
		$lng = I('lng');
		$lat = I('lat');
		$state = I('state');

		switch ($state) {
			case 'sign_in' :
				$sign_type = '签到';
				$rs = $this -> qiandao($lat, $lng, $result, $state);
				break;
			case 'sign_out' :
				$sign_type = '签退';
				$rs = $this -> qiandao($lat, $lng, $result, $state);
				break;
			case 'outside' :
				$sign_type = '外勤';
				$rs['error'] = 0;
				break;
			default :
				break;
		}

		if ($rs['error'] == 0) {
			$return['info'] = "{$sign_type}成功";
			$return['status'] = 1;
			$return['time'] = $data['sign_date'];
			$this -> ajaxReturn($return);
		} else {
			$return['info'] = $rs['msg'];
			$return['status'] = 1;
			$return['time'] = $data['sign_date'];
			$this -> ajaxReturn($return);
		}
	}

	public function shangbao() {
		if (IS_POST) {
			$data['type'] = 'sign_report';
			$data['ip'] = getenv('REMOTE_ADDR');
			$data['emp_no'] = get_emp_no();
			$data['user_id'] = get_user_id();
			$data['create_time'] = time();
			$data['latitude'] = I('lat');
			$data['longitude'] = I('lng');
			$data['is_real_time'] = 0;
			$data['sign_date'] = to_date(time());
			$data['sign_name'] = '上报位置';
			$data['report_id'] = $_POST['id'];
			$data['location'] = I('location');
			$data['content'] = I('content');

			$result = M('sign_shangbao') -> add($data);
			$lng = I('lng');
			$lat = I('lat');
			if ($rs['error'] == 0) {
				$return['info'] = "{$sign_type}成功";
				$return['status'] = 1;
				$return['time'] = $data['sign_date'];
				$this -> ajaxReturn($return);
			} else {
				$return['info'] = $rs['msg'];
				$return['status'] = 1;
				$return['time'] = $data['sign_date'];
				$this -> ajaxReturn($return);
			}
		}
		vendor('WeiXin.jssdk');
		$corp_id = get_system_config('weixin_corp_id');
		$secret = get_system_config('weixin_secret');
		$jssdk = new \JSSDK($corp_id, $secret);
		$signPackage = $jssdk -> GetSignPackage();

		$this -> assign('signPackage', $signPackage);
		$this -> assign('is_weixin', is_weixin());

		$state = I('state');
		$this -> assign('state', $state);
		$this -> display();
	}

	function get_location($lat, $lng) {
		//$location = conv_baidu_map($lat, $lng);
		$data['location'] = get_location($lat,$lng);
		$data['lat'] = $lat;
		$data['lng'] = $lng;
		$data['time'] = to_date(time());
		$data['status'] = 1;
		$this -> ajaxReturn($data);
	}

	private function record_location($data) {
		$where['is_real_time'] = 1;
		$where['emp_no'] = $data['FromUserName'];
		$lasted_location = M("Sign") -> where($where) -> find();

		if ($lasted_location != false) {
			$lasted_location['latitude'] = $data['Latitude'];
			$lasted_location['longitude'] = $data['Longitude'];
			$lasted_location['precision'] = $data['Precision'];
			$lasted_location['create_time'] = time();
			M('Sign') -> save($lasted_location);
		} else {
			$new_data['latitude'] = $data['Latitude'];
			$new_data['longitude'] = $data['Longitude'];
			$new_data['precision'] = $data['Precision'];
			$new_data['emp_no'] = $data['FromUserName'];
			$new_data['is_real_time'] = 1;
			$new_data['create_time'] = time();

			$where['emp_no'] = array('eq', $data['FromUserName']);
			$user_id = M("User") -> where($where) -> getField('id');
			$new_data['user_id'] = $user_id;
			M('Sign') -> add($new_data);
		}
	}

	public function qiandao($lat, $lng, $id, $sign_type) {
		$where['user_id'] = get_user_id();
		$sign_member = M('SignMember');
		$sid_list = $sign_member -> where($where) -> getField('sid', true);

		if (empty($sid_list)) {
			$return['error'] = 1;
			$return['msg'] = '没有分配考勤规则';
			return $return;
		}

		$where1['id'] = array('in', $sid_list);
		$where1['sign_type'] = array('eq', $sign_type);
		$where1['is_del'] = array('eq', 0);
		$rule_list = M('SignRule') -> where($where1) -> order('sort') -> select();

		if (empty($rule_list)) {
			$return['error'] = 2;
			$return['msg'] = '没有找到对应考勤规则';
			return $return;
		}
		$count = 0;
		foreach ($rule_list as $rule) {
			//查询签到用户的规则的详细内容
			$rule_lng = $rule['longitude'];
			//规则的经度
			$rule_lat = $rule['latitude'];
			
			//dump($rule);
			//规则的纬度
			$rule_radius = (int)($rule['radius']);
			//规则的范围
			$rule_start = $rule['start_time'];
			$rule_end = $rule['end_time'];
			//规则的签到时间
			$start_time = (int)(str_replace(':', '', $rule_start));
			//讲规则中的时间转换为INT 进行比较
			$end_time = (int)(str_replace(':', '', $rule_end));
			$hour = date('H:i', time());
			//将时间单位去掉: 转换为INT 类型，进行比较
			$now = (int)(str_replace(':', '', $hour));

			$a1 = $this -> zhuanhuan($lng, $lat);
			//调用转化方法，将微信获取的经纬度，转换为百度经纬度
			$lng1 = $a1['lng'];
			$lat1 = $a1['lat'];
			
			$jl = $this -> getDistance($lat,$lng,$rule_lat, $rule_lng);
			//然后将转换后的经纬度，以及个人的规则经纬度，传到计算距离的方法内
			if (($now >= $start_time) && ($now <= $end_time)) {
				if ($jl < $rule_radius) {
					$count++;
					$status['status'] = 1;
					$status['sign_name'] = $rule['name'];
					break;
				} else {
					$count++;
					$status['status'] = 2;
					$status['sign_name'] = $rule['name'];
					continue;
				}
			}
		}
		if ($count == 0) {
			$return['error'] = 3;
			$return['msg'] = '没有找到对应考勤规则';
			return $return;
		} else {
			$where2['id'] = array('eq', $id);
			M('sign') -> where($where2) -> save($status);
		}
	}

	//计算两个经纬度之间距离的方法,并将状态保存到表中
	public function getDistance($lat1,$lng1,$lat2,$lng2) {
		$earthRadius = 6367000;
		$lat1 = ($lat1 * pi()) / 180;
		$lng1 = ($lng1 * pi()) / 180;

		$lat2 = ($lat2 * pi()) / 180;
		$lng2 = ($lng2 * pi()) / 180;

		$calcLongitude = $lng2 - $lng1;
		$calcLatitude = $lat2 - $lat1;
		$stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
		$stepTwo = 2 * asin(min(1, sqrt($stepOne)));
		$calculatedDistance = $earthRadius * $stepTwo;
		$jl = round($calculatedDistance);
		return $jl;
		//计算完后，将距离返回到签到方法中，进行处理
	}

	//微信经纬度，转换为百度经纬度
	public function zhuanhuan($lng, $lat) {
		$url = "http://api.map.baidu.com/geoconv/v1/?coords=" . $lng . "," . $lat . "&from=1&to=5&ak=EE6745c36d96321e90b7015f3de4a4ee";
		$result = json_decode(file_get_contents($url));
		$data['lat'] = $result -> result[0] -> x;
		$data['lng'] = $result -> result[0] -> y;
		return $data;
	}

}
