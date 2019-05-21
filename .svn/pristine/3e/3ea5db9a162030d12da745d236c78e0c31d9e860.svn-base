<?php

namespace Home\Model;
use Think\Model;

class  BonusModel extends CommonModel {
	// 自动验证设置
	protected $_validate	 =	 array(
		array('name','require','标题必须！',1),
		array('total','number','总计必须为数字格式！',2),
        array('date','require','日期必须！',1),
		);
   //自动补齐     
    protected $_auto = array(
        array('is_del', '0', self::MODEL_INSERT),
        array('create_time', 'time', self::MODEL_INSERT, 'function'),
        array('update_time', 'time', self::MODEL_UPDATE, 'function'),
        array('user_id', 'get_user_id', self::MODEL_INSERT, 'callback'),
        array('emp_no', 'get_emp_no', self::MODEL_INSERT, 'function'),
        array('user_name', 'get_user_name', self::MODEL_INSERT, 'callback'),
     );
     
     public function  UserDeptView(){
         //dump("okok");
         $Model = new Model(); 
         $sql = "SELECT
        a.id AS user_id,
        a.emp_no,
        a. NAME,
        a.dept_id,
        d. NAME AS dept_name,
        a.position_id,
        e. NAME AS position_name,
        b.id AS group_id,
        b. NAME AS group_name,
        b.amount,
        b.remark
        FROM
            oa_user a
        JOIN oa_group b
        JOIN oa_group_user c
        JOIN oa_dept d
        JOIN oa_position e
        WHERE
            a.dept_id = d.id
        AND a.position_id = e.id
        AND a.id = c.user_id
        AND b.id = c.group_id

        ORDER BY
            b.sort,dept_id ";
         $list  = $Model->query($sql);
         dump($list);
    } 
}
?>