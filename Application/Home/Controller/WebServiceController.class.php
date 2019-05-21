<?php


namespace Home\Controller;
use Think\Controller;

class WebServiceController extends Controller {
	protected $config = array('app_type' => 'public', 'public' => 'start,run');

	public function timer($token) {
		set_time_limit(0);
		session_write_close();
		$data_auth_key = C('DATA_AUTH_KEY');
		$host = $_SERVER['HTTP_HOST'];
		$post_data = think_encrypt(C('DATA_AUTH_KEY'));

		if ($data_auth_key == think_decrypt($token)) {
			$now = time();
			$where['is_del'] = array('eq', 0);
			$task_list = M('Timer') -> where($where) -> select();

			foreach ($task_list as $task) {
				echo 'start ' . $task['command'] . "\r\n";
				$interval = $task['interval'];
				$last_time = S('LAST_TIME_' . strtoupper($task['command']));
				if (empty($last_time)) {
					$last_time = 1;
				}

				if ($task['type'] == 'D') {
					$start_date = to_date($last_time, 'Y-m-d');
					$end_date = to_date(time(), 'Y-m-d');

					$date1 = date_create($start_date);
					$date2 = date_create($end_date);
					$diff = date_diff($date1, $date2);

					//	S('LAST_TIME_' . strtoupper($task['command']), time() - 3600 * 24 * 3);
					$diff = $diff -> days;
					if ($diff > $interval) {
						$time_start = date('Hi', time());
						$time_end = date('Hi', strtotime($task['time']));
						if ($time_start > $time_end) {
							$url = U('WebService/run', array('command' => think_encrypt($task['command']), 'data_key' => $post_data));
							$this -> async_request($url, $host);
							S('LAST_TIME_' . strtoupper($task['command']), time());
							continue;
						}
					}
				}
				if ($task['type'] == 'S') {
					$diff = time() - $last_time;
					if ($diff > $interval) {
						$url = U('WebService/run', array('command' => think_encrypt($task['command']), 'data_key' => $post_data));
						$this -> async_request($url, $host);
						S('LAST_TIME_' . strtoupper($task['command']), time());
						continue;
					}
				}
			}
			echo 'finish' . "\r\n";
		} else {
			die ;
			$this -> error('非法访问');
		};
	}

	function run($data_key, $command) {
		set_time_limit(0);
		session_write_close();
		if (think_decrypt($data_key) == C('DATA_AUTH_KEY')) {
			$command = think_decrypt($command);
			$data = I('data');
			$data = think_decrypt($data);
			$data = json_decode($data, TRUE);
			//\Think\Log::record(dump($data,false), 'WARN', true);
			echo 'run ' . $command;
			$this -> $command($data);
		} else {
			die ;
			$this -> error('非法访问');
		}
	}

	private function async_request($url) {
		$host = $_SERVER['HTTP_HOST'];
		$fp = fsockopen($host, 80, $errno, $errstr, 30);
		if (!$fp) {
			echo "$errstr ($errno)<br />\n";
		} else {
			$out = "GET {$url}  / HTTP/1.1\r\n";
			$out .= "Host: {$host}\r\n";
			$out .= "Connection: Close\r\n\r\n";
			fwrite($fp, $out);
			fclose($fp);
		}
	}

	function out_link($id, $result) {
		$id = think_decrypt($id);
		$id = think_decrypt($id);
		$where['id'] = $id;
		$where['is_del'] = array('eq', 0);
		$out_link = M('OutLink') -> where($where) -> find();
		if (!empty($out_link)) {
			$command = 'out_link_' . $out_link['app'];
			$emp_no = $out_link['emp_no'];
			$row_id = $out_link['row_id'];
			$this -> $command($row_id, $emp_no, $result);
			M('OutLink') -> delete($id);
		}
	}

	//-----------------------------------------------
	//完成在邮件中通过点击链接完成裁决
	//-----------------------------------------------

	protected function out_link_flow($id, $emp_no, $result) {
		if ($result = 0 || $result = 1) {
			//查找待审批
			$model = M("FlowLog");
			$where['emp_no'] = array('eq', $emp_no);
			$where['is_del'] = 0;
			$where['flow_id'] = array('eq', $id);
			$where['_string'] = "result is null";
			$flow_log = $model -> where($where) -> find();
			if (empty($flow_log)) {
				die ;
			}
			if (false === $model -> create($flow_log)) {
				$this -> error($model -> getError());
			}
			$model -> result = $result;
			$model -> user_id = get_user_id($emp_no);
			$model -> user_name = get_user_name($model -> user_id);
			$flow_id = $model -> flow_id;
			$step = $model -> step;
			//保存当前数据对象
			$list = $model -> save();

			//禁用其他用户审批条件
			$where['step'] = array('eq', $step);
			$where['flow_id'] = array('eq', $flow_id);
			$where['_string'] = 'result is null';
			$model -> where($where) -> setField('is_del', 1);

			if ($list !== false) {//保存成功
				D("Flow") -> next_step($flow_id, $step, $emp_no);
				echo('操作成功');
			} else {
				//失败提示
				echo('操作失败');
				//$this -> error('操作失败!');
			}
		}
	}

	protected function timer_push() {
		$where['time'] = array('lt', time());
		$where['status'] = array('eq', 3);
		$list = M('Push') -> where($where) -> select();
		M('Push') -> where($where) -> setField('status', 1);
		if (!empty($list)) {
			foreach ($list as $val) {
				$data = json_decode($val['data'], true);
				$user_list = array($val['user_id']);

				$ws_push_config = get_system_config('ws_push_config');
				if (!empty($ws_push_config)) {
					$ws_push_config = array_filter(explode(',', $ws_push_config));
					if (in_array($data['type'], $ws_push_config)) {
						@send_ws($data, $user_list);
					}
				}

				$sms_push_config = get_system_config('sms_push_config');
				if (!empty($sms_push_config)) {
					$sms_push_config = array_filter(explode(',', $sms_push_config));
					if (in_array($data['type'], $sms_push_config)) {
						//@send_sms($data, $user_list);
					}
				}

				$weixin_push_config = get_system_config('weixin_push_config');
				if (!empty($weixin_push_config)) {
					$weixin_push_config = array_filter(explode(',', $weixin_push_config));
					if (in_array($data['type'], $weixin_push_config)) {
						@send_weixin($data, $user_list);
					}
				}
			}
		}
	}

	//--------------------------------------------------------------------
	//  邮件推送
	//--------------------------------------------------------------------

	protected function send_mail($data) {
		$mail_account['mail_id'] = C('PUSH_MAIL_ID');
		$mail_account['mail_pwd'] = C('PUSH_MAIL_PWD');
		$mail_account['smtpsvr'] = C('PUSH_SMTPSVR');
		$mail_account['mail_name'] = C('PUSH_MAIL_NAME');

		$title = $data['title'];
		$body = $data['body'];
		$to = $data['to'];

		import("@.ORG.Util.send");
		//从PHPMailer目录导入class.send.php类文件
		$mail = new \PHPMailer(true);
		// the true param means it will throw exceptions on errors, which we need to catch
		$mail -> IsSMTP();
		// telling the namespace Home\Controller;

		try {
			$mail -> Host = $mail_account['smtpsvr'];
			//"smtp.qq.com"; // SMTP server 部分邮箱不支持SMTP，QQ邮箱里要设置开启的
			$mail -> SMTPDebug = false;
			// 改为2可以开启调试
			$mail -> SMTPAuth = true;
			// enable SMTP authentication
			$mail -> Port = 25;
			// set the SMTP port for the GMAIL server
			$mail -> CharSet = "UTF-8";
			// 这里指定字符集！解决中文乱码问题
			$mail -> Encoding = "base64";

			$mail -> Username = $mail_account['mail_id'];
			// SMTP account username
			$mail -> Password = $mail_account['mail_pwd'];
			// SMTP account password
			$mail -> SetFrom($mail_account['mail_id'], $mail_account['mail_name']);
			//发送者邮箱
			$mail -> AddReplyTo($mail_account['mail_id'], $mail_account['mail_name']);
			//回复到这个邮箱

			// 收件人
			$arr_to = array_filter(explode(';', $to));
			foreach ($arr_to as $item) {
				if (strpos($item, "dept_") !== false) {
					$arr_tmp = array_filter(explode('|', $item));
					$dept_id = str_replace("dept_", '', $arr_tmp[2]);
					$mail_list = $this -> get_mail_list_by_dept_id($dept_id);
					foreach ($mail_list as $val) {
						$mail -> AddAddress($val["mail_id"], $val["name"]);
						// 收件人
					}
				} else {
					$arr_tmp = explode('|', $item);
					$mail -> AddAddress($arr_tmp[1], $arr_tmp[0]);
				}
			}

			$mail -> Subject = "=?UTF-8?B?" . base64_encode($title) . "?=";

			$mail -> MsgHTML($body);

			if ($mail -> Send()) {
				return true;
			} else {
				return false;
			};
		} catch (phpmailerException $e) {
			echo $e -> errorMessage();
			//Pretty error messages from PHPMailer
		} catch (Exception $e) {
			echo $e -> getMessage();
			//Boring error messages from anything else!
		}
	}

	protected function un_finish_work() {
		$user_list = M('User') -> where('is_del=0') -> select();
		foreach ($user_list as $user) {
			$user_id = $user['id'];
			$task_count = $this -> _badge_count_no_finish_task($user_id) + $this -> _badge_count_dept_task($user_id);
			$flow_count = $this -> _badge_count_flow_todo($user_id);
			$info_count = $this -> _badge_count_info($user_id);
			$todo_count = $this -> _badge_count_todo($user_id);
			$schedule_count = $this -> _badge_count_schedule($user_id);

			if (!empty($task_count)) {
				$content = "您有{$task_count}条【任务】需要处理" . "<br>\r\n";
			}
			if (!empty($flow_count)) {
				$content .= "您有{$flow_count}条【审批】需要处理" . "<br>\r\n";
			}
			if (!empty($info_count)) {
				$content .= "您有{$info_count}条【信息】需要处理" . "<br>\r\n";
			}
			if (!empty($todo_count)) {
				$content .= "您有{$todo_count}条【个人待办】需要处理" . "<br>\r\n";
			}
			if (!empty($schedule_count)) {
				$content .= "您有{$schedule_count}条【计划】需要处理" . "<br>\r\n";
			}

			$total = (int)$task_count + (int)$flow_count + (int)$info_count + (int)$todo_count + (int)$schedule_count;
			$push_data['type'] = "提醒";
			$push_data['action'] = "{$total}条未完成工作";
			$push_data['title'] = '';
			$push_data['content'] = $content;
			$push_data['url'] = U("Index/index");
			send_push($push_data, $user_id);
		}
	}

	private function _badge_count_no_finish_task($user_id) {
		//等我接受的任务
		$where = array();
		$where_log['type'] = 1;
		$where_log['status'] = array('lt', 20);
		$where_log['executor'] = $user_id;
		$task_list = M("TaskLog") -> where($where_log) -> getField('task_id', true);
		$task_todo_count = 0;
		if (!empty($task_list)) {
			$where['id'] = array('in', $task_list);
			$where['is_del'] = array('eq', 0);
			$task_todo_count = M("Task") -> where($where) -> count();
		}
		return $task_todo_count;
	}

	private function _badge_count_dept_task($user_id) {

		//我部门任务
		$where = array();
		$auth = D("Role") -> get_auth("Task", $user_id);
		if ($auth['admin']) {
			$where_log['type'] = 2;
			$where_log['executor'] = get_dept_id($user_id);
			$where_log['status'] = array('eq', '0');
			$task_list = M("TaskLog") -> where($where_log) -> getField('task_id', TRUE);
			if (!empty($task_list)) {
				$where['id'] = array('in', $task_list);
				$where['is_del'] = array('eq', 0);
			} else {
				return 0;
			}
		} else {
			return 0;
		}

		$task_dept_count = M("Task") -> where($where) -> count();
		return $task_dept_count;
	}

	private function _badge_count_flow_todo($user_id) {
		//获取待裁决
		$where = array();
		$FlowLog = M("FlowLog");

		$emp_no = get_emp_no($user_id);
		$where['emp_no'] = $emp_no;
		$where['is_del'] = array('eq', 0);
		$where['_string'] = "result is null";
		$log_list = $FlowLog -> where($where) -> field('flow_id') -> select();
		$log_list = rotate($log_list);

		$new_confirm_count = 0;
		if (!empty($log_list)) {
			$map['id'] = array('in', $log_list['flow_id']);
			$new_confirm_count = M("Flow") -> where($map) -> count();
		}
		return $new_confirm_count;
	}

	private function _badge_count_todo($user_id) {
		//获取待办事项
		$where = array();
		$where['user_id'] = $user_id;
		$where['status'] = array("in", "1,2");
		$new_todo_count = M("Todo") -> where($where) -> count();
		return $new_todo_count;
	}

	private function _badge_count_info($user_id) {
		$model = M("Info");
		$map['is_del'] = array('eq', '0');
		$map['create_time'] = array("egt", time() - 3600 * 24 * 30);

		$where_scope['user_id'] = array('eq', $user_id);
		$scope_list = M("InfoScope") -> where($where_scope) -> getField('info_id', TRUE);

		$readed_info = M("UserConfig") -> where("id=$user_id") -> getField('readed_info');
		$readed_info = array_filter(explode(',', $readed_info));

		$un_read_list = array_diff($scope_list, $readed_info);

		if (!empty($un_read_list)) {
			$map['id'] = array('in', $un_read_list);
		} else {
			$map['_string'] = '1=2';
		}

		$info_list = M('Info') -> where($map) -> getField('id,folder', true);

		return count($info_list);
	}

	private function _badge_count_schedule($user_id) {
		$where_company['type'] = array('eq', 1);
		$where_company['start_time'] = array('egt', date('Y-m-d'));
		$gs = M("Schedule") -> where($where_company) -> count();

		$where_dept['dept_id'] = get_dept_id();
		$where_dept['type'] = array('eq', 2);
		$where_dept['start_time'] = array('egt', date('Y-m-d'));
		$bm = M("Schedule") -> where($where_dept) -> count();

		$where_private['type'] = array('eq', 3);
		$where_private['start_time'] = array('egt', date('Y-m-d'));
		$where_private['user_id'] = $user_id;
		$gr = M("Schedule") -> where($where_private) -> count();

		$max = $gs + $bm + $gr;
		return $max;
	}

	//--------------------------------------------------------------------
	//   接收邮件
	//--------------------------------------------------------------------

	protected function recevie_mail() {
		set_time_limit(0);
		$where['is_del'] = array('eq', 0);
		$mail_account_list = D("MailAccountView") -> where($where) -> select();
		foreach ($mail_account_list as $account) {
			$this -> _receve_by_user($account['id']);
			sleep(1);
		}
		sleep(1);
	}

	private function _receve_by_user($user_id) {
		$mail_account = $this -> _get_mail_account($user_id);
		$new = 0;
		import("@.ORG.Util.receve");
		$mail_list = array();
		$mail = new \receiveMail();
		$connect = $mail -> connect($mail_account['pop3svr'], '110', $mail_account['mail_id'], $mail_account['mail_pwd'], 'INBOX', 'pop3/novalidate-cert');
		if (!$connect) {
			$connect = $mail -> connect($mail_account['pop3svr'], '995', $mail_account['mail_id'], $mail_account['mail_pwd'], 'INBOX', 'pop3/ssl/novalidate-cert');
		}
		$mail_count = $mail -> mail_total_count();
		if ($connect) {
			for ($i = 1; $i <= $mail_count; $i++) {
				$mail_id = $mail_count - $i + 1;
				$mail_header = $mail -> mail_header($mail_id);
				$where['mid'] = $mail_header['mid'];
				$where['user_id'] = array('eq', $user_id);
				$count = M('Mail') -> where($where) -> count();
				if ($count == 0) {
					$model = M("Mail");
					$model -> create($mail_header);
					if ($model -> create_time < strtotime(date('y-m-d H:i:s')) - 86400 * 30) {
						$mail -> close_mail();
						if ($new > 0) {
							$push_data['type'] = '邮件';
							$push_data['action'] = '';
							$push_data['title'] = '收到' . $new . '封邮件';
							$push_data['content'] = '';
							$push_data['url'] = U('Mail/folder', 'fid=inbox&return_url=Mail/index');

							send_push($push_data, $user_id);
						}
						return;
					}

					$new++;
					$model -> user_id = $user_id;
					$model -> read = 0;
					$model -> folder = 1;
					$model -> is_del = 0;
					$str = $mail -> get_attach($mail_id);

					$model -> add_file = $this -> _receive_file($str, $model);
					$this -> _organize($model, $user_id);
					$model -> add();

				} else {
					$mail -> close_mail();
				}
			}
			if ($new > 0) {
				$push_data['type'] = '邮件';
				$push_data['action'] = '';
				$push_data['title'] = '收到' . $new . '封邮件';
				$push_data['content'] = '';
				$push_data['url'] = U("Mail/folder?fid=inbox&return_url=Mail/index");

				send_push($push_data, $user_id);
			}
		}
		$mail -> close_mail();
	}

	//--------------------------------------------------------------------
	//   接收邮件附件
	//--------------------------------------------------------------------
	private function _receive_file($str, &$model) {

		$ar = array_filter(explode("?", $str));
		$files = array();
		if (!empty($ar)) {
			foreach ($ar as $key => $value) {
				$ar2 = explode("|", $value);
				$cid = $ar2[0];
				$inline = $ar2[1];
				$file_name = $ar2[2];
				$tmp_name = $ar2[3];

				$files[0]['name'] = $file_name;
				$files[0]['tmp_name'] = $tmp_name;
				$files[0]['size'] = filesize($tmp_name);
				$files[0]['is_move'] = true;
				if (!empty($files)) {
					$File = D('File');
					$file_driver = C('DOWNLOAD_UPLOAD_DRIVER');
					$info = $File -> upload($files, C('DOWNLOAD_UPLOAD'), C('DOWNLOAD_UPLOAD_DRIVER'), C("UPLOAD_{$file_driver}_CONFIG"));
					if ($inline == "INLINE") {
						$model -> content = str_replace("cid:" . $cid, $info[0]['path'], $model -> content);
					} else {
						$add_file = $add_file . think_encrypt($info[0]['id']) . ';';
					}
				}
			}
		}
		return $add_file;
	}

	private function _organize(&$model, $user_id) {
		$where['user_id'] = array('eq', $user_id);
		$where['is_del'] = array('eq', 0);
		$list = M("MailOrganize") -> where($where) -> order('sort') -> select();

		foreach ($list as $val) {
			//包含

			if (($val['sender_check'] == 1) && ($val['sender_option'] == 1) && (strpos($model -> from, $val['sender_key']) !== false)) {
				$model -> folder = $val['to'];
				return;
			}
			//不包含
			if (($val['sender_check'] == 1) && ($val['sender_option'] == 0) && (strpos($model -> from, $val['sender_key']) == false)) {
				$model -> folder = $val['to'];
				return;
			}

			//包含
			if (($val['domain_check'] == 1) && ($val['domain_option'] == 1) && (strpos($model -> from, $val['domain_key']) !== false)) {
				$model -> folder = $val['to'];
				return;
			}

			//不包含
			if (($val['domain_check'] == 1) && ($val['domain_option'] == 0) && (strpos($model -> from, $val['domain_key']) == false)) {
				$model -> folder = $val['to'];
				return;
			}

			//包含
			if (($val['recever_check'] == 1) && ($val['recever_option'] == 1) && (strpos($model -> to, $val['recever_key']) !== false)) {
				$model -> folder = $val['to'];
				return;
			}
			//不包含
			if (($val['recever_check'] == 1) && ($val['recever_option'] == 0) && (strpos($model -> to, $val['recever_key']) == false)) {
				$model -> folder = $val['to'];
				return;
			}

			//包含
			if (($val['title_check'] == 1) && ($val['title_option'] == 1) && (strpos($model -> name, $val['title_key']) !== false)) {
				$model -> folder = $val['to'];
				return;
			}
			//不包含
			if (($val['title_check'] == 1) && ($val['title_option'] == 0) && (strpos($model -> name, $val['title_key']) == false)) {
				$model -> folder = $val['to'];
				return;
			}
		}
	}

	//--------------------------------------------------------------------
	//  读取邮箱用户数据
	//--------------------------------------------------------------------
	private function _get_mail_account($user_id = null) {
		if (empty($user_id)) {
			$user_id = get_user_id();
		}
		$model = M('MailAccount');
		$list = $model -> field('mail_name,email,pop3svr,smtpsvr,mail_id,mail_pwd') -> find($user_id);
		if (empty($list['mail_name']) || empty($list['email']) || empty($list['pop3svr']) || empty($list['smtpsvr']) || empty($list['mail_id']) || empty($list['mail_pwd'])) {
			$this -> assign('jumpUrl', U('MailAccount/index'));
			cookie('current_node', null);
			$this -> error("请设置邮箱帐号");
			die ;
		} else {
			return $list;
		}
	}

}
?>
