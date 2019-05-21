<?php

class Ldap {

	private $connect;
	private $is_login;
	private $server;
	private $port;
	private $user;
	private $pwd;
	private $base_dn;
	public $status = true;
	public $info;

	function __construct($server, $port, $ldap_user, $ldap_pwd, $ldap_base_dn) {
		$this -> server = $server;
		$this -> port = $port;
		$this -> user = $ldap_user;
		$this -> pwd = $ldap_pwd;
		$this -> base_dn = $ldap_base_dn;
	}

	function connect() {
		$this -> connect = ldap_connect($this -> server, $this -> port);
		ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
	}

	function login() {
		$this -> is_login = ldap_bind($this -> connect, $this -> user, $this -> pwd) or die(ldap_error($this -> connect));
		//与服务器绑定
	}

	public function insert_ou($name, $id) {

		$this -> connect();
		$this -> login();

		$data["objectClass"] = "organizationalUnit";
		$data["ou"] = "$name";
		$data["name"] = "$name";
		$data["l"] = "$id";

		$this -> status = ldap_add($this -> connect, "OU={$name},{$this->base_dn}", $data);
		$this -> info = 'LDAP_INFO:' . ldap_error($this -> connect);
	}

	public function update_ou($name, $id) {
		$this -> connect();
		$this -> login();

		$data["objectClass"] = "organizationalUnit";
		$data["ou"] = "$name";
		$data["name"] = "$name";
		$data["l"] = "$id";

		$this -> status = ldap_modify($this -> connect, "OU={$name},{$this->base_dn}", $data);
		$this -> info = 'LDAP_INFO:' . ldap_error($this -> connect);
	}

	public function rename_ou($old_name, $new_name) {
		$this -> connect();
		$this -> login();

		$this -> status = ldap_rename($this -> connect, "OU={$old_name},{$this->base_dn}", "OU={$new_name}", $this -> base_dn, true);
		$this -> info = 'LDAP_INFO:' . ldap_error($this -> connect);
		//ldap_rename ($this->connect,"OU={$old_name},DC=ldap,DC=test","OU={$new_name}","DC=ldap,DC=test",true)or die(ldap_error($this->connect));
	}

	public function del_ou($name) {
		$this -> connect();
		$this -> login();

		$filter = "(objectClass=user)";
		$justthese = array("cn");
		$sr = ldap_search($this -> connect, "OU={$name},{$this->base_dn}", $filter, $justthese);

		$info = ldap_get_entries($this -> connect, $sr);

		if ($info['count'] == 0) {
			$this -> status = ldap_delete($this -> connect, "OU={$name},{$this->base_dn}");
			$this -> info(ldap_error($this -> connect));
		} else {
			$this -> status = false;
			$this -> info('AD部门不为空');
		}
	}

	public function insert_user($emp_no, $dept_name, $user_name, $password = null) {
		$this -> connect();
		$this -> login();

		//$password='Abc1234.';
		$data["objectClass"] = "user";
		$data['userAccountControl'] = 512;

		$data['name'] = $user_name;
		$data['displayName'] = $user_name;
		$data['cn'] = $emp_no;
		$data['userPrincipalName'] = $emp_no . '@ldap.test';
		$data['sAMAccountName'] = $emp_no;
		if (!empty($password)) {
			$data['unicodePwd'] = mb_convert_encoding('"' . $password . '"', 'utf-16le');
		}
		$this -> status = ldap_add($this -> connect, "CN={$emp_no},OU={$dept_name},{$this->base_dn}", $data);
		$this -> info = 'LDAP_INFO:1' . ldap_error($this -> connect);
	}

	public function del_user($emp_no,$dept_name) {
		$this -> connect();
		$this -> login();

		$data['userAccountControl'] = 2;
		$data['cn'] = $emp_no;
		$this -> status = ldap_modify($this -> connect, "CN={$emp_no},OU={$dept_name},{$this->base_dn}", $data);
		$this -> info = 'LDAP_INFO:' . ldap_error($this -> connect);
	}

	public function update_user($emp_no, $dept_name, $user_name, $is_del = 0) {
		$this -> connect();
		$this -> login();

		$data["objectClass"] = "user";
		if (!empty($is_del)) {
			$data['userAccountControl'] = 2;
		} else {
			$data['userAccountControl'] = 512;
		}

		$data['name'] = $user_name;
		$data['displayName'] = $user_name;
		$data['cn'] = $emp_no;
		$this -> status = ldap_modify($this -> connect, "CN={$emp_no},OU={$dept_name},{$this->base_dn}", $data);
		$this -> info = 'LDAP_INFO:' . ldap_error($this -> connect);
	}

	public function move_user($emp_no, $old_dept_name, $new_dept_name) {
		$this -> connect();
		$this -> login();

		$this -> status = ldap_rename($this -> connect, "CN={$emp_no},OU={$old_dept_name},{$this->base_dn}", "CN={$emp_no}", "OU={$new_dept_name},{$this->base_dn}", true);
		$hits -> info = 'LDAP_INFO:' . ldap_error($this -> connect);
	}

	public function reset_pwd($emp_no, $dept_name, $password) {
		$this -> connect();
		$this -> login();

		$data['unicodePwd'] = mb_convert_encoding('"' . $password . '"', 'utf-16le');

		$this -> status = ldap_modify($this -> connect, "CN={$emp_no},OU={$dept_name},{$this->base_dn}", $data);
		$this -> info = 'LDAP_INFO:' . ldap_error($this -> connect);
		//ldap_modify($this->connect,"CN={$emp_no},OU={$dept_name},DC=ldap,DC=test",$data)or die(ldap_error($this->connect));
	}
}
?>