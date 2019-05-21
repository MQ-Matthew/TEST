<?php


namespace Home\Model;
use Think\Model\ViewModel;

class  BonusViewModel extends ViewModel {
	public $viewFields=array(
		'User'=>array('id','emp_no','name','letter','dept_id','position_id','email','duty','office_tel','mobile_tel','pic','birthday','sex','password','is_del'),
		'Position'=>array('name'=>'position_name','_on'=>'Position.id=User.position_id'),
		'Dept'=>array('name'=>'dept_name','_on'=>'Dept.id=User.dept_id'),
		'Group_user'=>array('group_id','_on'=>'Group_user.User_id=User.id'),
		'Group'=>array('amount','name'=>'group_name','_as'=>'Bgroup','_on'=>'Bgroup.id=Group_user.group_id'),
		);
}
?>