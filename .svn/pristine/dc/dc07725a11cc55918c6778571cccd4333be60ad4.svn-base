<?php

namespace App\Model;
use Think\Model;

class CommonModel extends Model {
	
    protected $_auto = array(
		array('is_del', '0', self::MODEL_INSERT),
		array('create_time', 'time', self::MODEL_INSERT, 'function'),
		array('update_time', 'time', self::MODEL_UPDATE, 'function'),
		array('user_id', 'get_user_id', self::MODEL_INSERT, 'callback'),
		array('user_name', 'get_user_name', self::MODEL_INSERT, 'callback'),
		array('dept_id', 'get_dept_id', self::MODEL_INSERT, 'callback'),
		array('dept_name', 'get_dept_name', self::MODEL_INSERT, 'callback'),
	 );  
    function get_emp_no(){
        return I('emp_no');
    }
    
	function get_user_id($emp_no = null) {
		if (empty($emp_no)) {
            $user_id = session(C('USER_AUTH_KEY'));
            return isset($user_id) ? $user_id : 0;
        } else {
            $where['emp_no'] = array('eq', $emp_no);
            return M("User") -> where($where) -> getField('id');
        }
        
	}

	function get_user_name($emp_no = null) {
		if (empty($emp_no)) {
            $user_name = session('user_name');
            return isset($user_name) ? $user_name : 0;
        } else {
            $where['emp_no'] = array('eq', $emp_no);
            return M("User") -> where($where) -> getField('name');
        }        
	}

	function get_dept_id($emp_no = null) {
        // dump($emp_no."99");
        if (empty($emp_no)) {
            return session('dept_id');
        } else {
            $where['emp_no'] = array('eq', $emp_no);
            return M("User") -> where($where) -> getField('dept_id');
        }
    }

	function get_dept_name($emp_no = null) {
		if (empty($emp_no)) {
            $result = M("Dept") -> find(session("dept_id"));
            return $result['name'];
        } else {
            $where['id'] = array('eq', $this->get_dept_id($emp_no));            
            return  M("Dept") -> where($where) -> getField('name');
        }
	}
}
?>