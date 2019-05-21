<?php

namespace Home\Controller; 

//采购申请待办提示

class TishiController extends HomeController {

    protected function _initialize() {
        C('ALLOW_VISIT', array_merge(C('ALLOW_VISIT'), array('Tishi/tishi2'))); //允许访问的url地址
        C('ALLOW_VISIT', array_merge(C('ALLOW_VISIT'), array('Tishi/tishi')));
        //parent::_initialize();
        $this->sso_login(); 
        $auth_id = session(C('USER_AUTH_KEY'));  
    }

    //采购申请   待办事项
    public function tishi() {
        
        $emp_no = get_emp_no();
        $user_id = get_user_id();
        //待办事项
        $where = array();
        $FlowLog = M("FlowLog");

        $emp_no = get_emp_no();
        $where['emp_no'] = $emp_no;
        $where['is_del'] = array('eq', 0);
        $where['_string'] = "result is null";
        $log_list = $FlowLog -> where($where) -> field('flow_id') -> select();
        $log_list = rotate($log_list);
        $new_confirm_count = 0;
        if (!empty($log_list)) {
            $map['id'] = array('in', $log_list['flow_id']);
            $map['is_del'] = array('eq', 0);
            //$new_confirm_count = M("Flow") -> where($map) -> count();
            $new_confirm_count = M("Flow") -> where($map) -> select();
        }else{
            $map['emp_no'] = array('eq', $emp_no);
            $map['step'] = array('eq', 19);
            $map['is_del'] = array('eq', 0);
            $new_confirm_count = M("Flow") -> where($map) -> select();;
        }
        $list_arr = Array();
        
        foreach($new_confirm_count as $key=>$val){
             $list_arr[] = M('Flow_type')->where('id='.$val['type'])->getField('name');
        }        
        $list_count = array_count_values($list_arr);
        //待参阅事项
        $re_count = badge_count_flow_receive();
        if($re_count != 0){
            //dump($re_count);
            $list_count['参阅事项'] =  $re_count;
        }
        
        $this->assign('list',$list_count); 
        //$this->assign('shuliang',$shuliang);
        $this->assign('emp_no',$emp_no);
        $this->display();
    }
    
    public function tishi2() {
        $shuliang = '';
        $emp_no = '';
        $this->assign('shuliang',$shuliang);
        $this->assign('emp_no',$emp_no);
        $this->display();
    }

	
}