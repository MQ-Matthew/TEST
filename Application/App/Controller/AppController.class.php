<?php

namespace App\Controller;
use Think\Controller;

class AppController extends Controller {
	protected $config = array('app_type' => 'asst');

	function _initialize() {
     
		$app_secret = I('app_secret');
		if ($app_secret !== 'abc') {
			$return['s'] = 0;
			$return['err'] = '非法访问';
            app_return($return);   
		}   
        /*        
		$user_id = think_decrypt(I('_u'));
		$time = think_decrypt(I('_t'));
		$token = think_decrypt(I('_k'));
                
		if (check_token($user_id, $time, $token) == true) {
			$map = array();
			$map['emp_no'] = array('eq', $user_id);
			$map["is_del"] = array('eq', 0);

			$model = M("User");
			$auth_info = $model -> where($map) -> find();
            
            if(!empty($auth_info)){
                session(C('USER_AUTH_KEY'), $auth_info['id']);
                session('emp_no', $auth_info['emp_no']);
                session('user_name', $auth_info['name']);
                session('user_pic', $auth_info['pic']);
                session('dept_id', $auth_info['dept_id']);
                session('token', $token);
                $return['s'] = 200;
                $return['data'] = $auth_info; 
                app_return($return);
            }else{
                $return['s'] = 0;
                $return['err'] = '用户错误'; 
                app_return($return);
            }
            
		} else {
			$return['s'] = 0;
            $return['err'] = '非法访问'; 
			app_return($return);
		} */
	}
    
    function version(){
        $return['url'] = 'http://115.28.53.28/download/bjsp201703.apk';
        $return['version'] = '0.3';
        app_return($return);        
    }

	function get_app_list() {
		$user_id = think_decrypt(I('_u'));
		$list = D('Node') -> get_top_menu($user_id);
		if ($list !== false) {
			$return['s'] = 1;
			$return['data'] = $list;
		} else {
			$return['s'] = 0;
			$return['error'] = '读取失败';
		}
		app_return($return);
	}

	function get_app_menu($app_id) {
		$user_id = think_decrypt(I('_u'));
		$list = D('Node') -> get_app_menu($user_id, $app_id);
		if ($list !== false) {
			$return['s'] = 1;
			$return['data'] = $list;
		} else {
			$return['s'] = 0;
			$return['error'] = '读取失败';
		}
		app_return($return);
	}
    
    function _search($model = null) {
        $map = array();
        //过滤非查询条件
        $request = array_filter(array_keys(array_filter($_REQUEST)), "filter_search_field");
        if (empty($model)) {
            $model = D(CONTROLLER_NAME);
        }
        $fields = get_model_fields($model);
        foreach ($request as $val) {
            $field = substr($val, 3);
            $prefix = substr($val, 0, 3);
            if (in_array($field, $fields)) {
                if ($prefix == "be_") {
                    if (isset($_REQUEST["en_" . $field])) {
                        if (strpos($field, "time") != false) {
                            $start_time = date_to_int(trim($_REQUEST[$val]));
                            $end_time = date_to_int(trim($_REQUEST["en_" . $field])) + 86400;
                            $map[$field] = array( array('egt', $start_time), array('elt', $end_time));
                        }
                        if (strpos($field, "date") != false) {
                            $start_date = trim($_REQUEST[$val]);
                            $end_date = trim($_REQUEST["en_" . substr($val, 3)]);
                            $map[$field] = array( array('egt', $start_date), array('elt', $end_date));
                        }
                    }
                }

                if ($prefix == "li_") {
                    $map[$field] = array('like', '%' . trim($_REQUEST[$val]) . '%');
                }
                if ($prefix == "eq_") {
                    $map[$field] = array('eq', trim($_REQUEST[$val]));
                }
                if ($prefix == "gt_") {
                    $map[$field] = array('egt', trim($_REQUEST[$val]));
                }
                if ($prefix == "lt_") {
                    $map[$field] = array('elt', trim($_REQUEST[$val]));
                }
            }
        }
        return $map;
    }

    function _list($model, $map, $sort = '') {
        //排序字段 默认为主键名
        if (isset($_REQUEST['_sort'])) {
            $sort = $_REQUEST['_sort'];
        } else if (in_array('sort', get_model_fields($model))) {
            $sort = "sort asc";
        } else if (empty($sort)) {
            $sort = "id desc";
        }

        //取得满足条件的记录数
        $count_model = clone $model;
        //取得满足条件的记录数
        $count = $count_model -> where($map) -> count();

        if ($count > 0) {
            //创建分页对象
            if (!empty($_REQUEST['list_rows'])) {
                $list_rows = $_REQUEST['list_rows'];
            } else {
                $list_rows = get_user_config('list_rows');
            }
            import("@.ORG.Util.Page");
            //$p = new \Page($count, $list_rows);
            //分页查询数据
            $vo_list = $model -> where($map) -> order($sort) -> limit($p -> firstRow . ',' . $p -> listRows) -> select();

            //echo $model->getlastSql();
            $p -> parameter = $this -> _search($model);
            //分页显示
            //$page = $p -> show();
            if ($vo_list) {
                $this -> assign('list', $vo_list);
                $this -> assign('sort', $sort);
                $this -> assign("page", $page);
                return $vo_list;
            }
        }
        return FALSE;
    }
    
    function User(){
        $emp_no =  I('login_name'); 
        $name =  I('name');                
        if(!empty($emp_no) || !empty($name)){
            switch ($emp_no){
              case 'all': // 
                   $map['emp_no'] = array('not in','admin');
                   $data = M('User')->where($map)->field('emp_no as login_name,name,sex,is_del as status,sort,identity')->order('sort')->select();
                   break;
              case 'admin': //管理员
                    $data = '';
                    break;
              default: 
                   $map['emp_no'] = $emp_no;
                   $map['name'] = $name;
                   $map['_logic'] = 'OR';
                   $data = M('User')->where($map)->field('emp_no as loginname,name,sex,is_del as status,sort,identity')->order('id')->select(); 
                   break;
             }
             
             if(empty($data)){
                $return['s'] = '0'; 
                $return['data'] = 'error';                
             }else{
                 //用户循环

                 foreach($data as $key=>$val){
                    //身份循环                     
                    if(!empty($val['identity'])){
                        $identity_array = explode('|', substr($val['identity'], 0, -1));                       
                        $identity_array_new = array();
                        foreach($identity_array as $k=>$v){                            
                            //部门职位
                            $identity_base_array = explode('_', $v);
                            $dept_id =  $identity_base_array[1];      //部门id
                            $position_id =  $identity_base_array[2];  //职位id
                            $dept_name = M(dept)->where('id='.$dept_id)-> getField('name');
                            $position_name = M(position)->where('id='.$position_id)-> getField('name');
                            
                            $identity_array_new[$k]['dept_id'] = $dept_id;
                            $identity_array_new[$k]['position_id'] = $position_id;
                            $identity_array_new[$k]['dept_name'] = $dept_name;
                            $identity_array_new[$k]['position_name'] = $position_name;                            
                            
                        }
                        $data[$key]['identity'] = $identity_array_new;                       
                        
                    }                     
                }
                $return['s'] = '1'; 
                $return['data'] = $data; 
             }
             app_return($return);
             
        }
    }
    
    function Dept(){
        $Dept_id =  I('Dept_id'); 
        $Dept_name =  I('Dept_name');               
        if(!empty($Dept_id) || !empty($Dept_name)){
            switch ($Dept_id){
              case 'all': // 全部
                   $map['id_del'] = 0;
                   $data = M('Dept')->where($map)->field('id,name,pid')->order('sort')->select();
                   break;
              default: 
                   $map['id'] = $Dept_id;
                   $map['name'] = $Dept_name;
                   $map['_logic'] = 'OR';
                   $data = M('Dept')->where($map)->field('id,name,pid')->order('sort')->select(); 
                   break;
             }
             if(empty($data)){
                $return['s'] = '0'; 
                $return['data'] = 'error';                
             }else{
                $return['s'] = '1'; 
                $return['data'] = $data; 
             }                         
            app_return($return);
        }
        
    }
    
    function Position(){
        $Position_id =  I('Position_id'); 
        $Position_name =  I('Position_name');               
        if(!empty($Position_id) || !empty($Position_name)){
            switch ($Position_id){
              case 'all': // 全部
                   $map['id_del'] = 0;
                   $data = M('Position')->where($map)->field('id,name')->order('sort')->select();
                   break;
              default: 
                   $map['id'] = $Position_id;
                   $map['name'] = $Position_name;
                   $map['_logic'] = 'OR';
                   $data = M('Position')->where($map)->field('id,name')->order('sort')->select(); 
                   break;
             }
             if(empty($data)){
                $return['s'] = '0'; 
                $return['data'] = 'error';                
             }else{
                $return['s'] = '1'; 
                $return['data'] = $data; 
             }                         
            app_return($return);
        }
        
    }
    
    //*****************
   //数据同步API接口 Post方法
   //参数格式 http://127.0.0.1/API/SyncDate/
   //参数：datetime ：2017-3-15 12:00:00
   //函数根据日期返回该日期后的全部更新数据
   //By Dokey 2017-3-16
   //*****************
   function SyncDate() {
        $datetime =  I('datetime');
        $map['datetime']= array('gt',$datetime); 
        $syncdate = M('Syncdate')->where($map)->order('datetime desc')->select();
        //dump(M('Syncdate')->getLastSQL());die(); 
     foreach($syncdate as $k=>$val){
        $syncdate[$k]['syncdata'] = json_decode($val['syncdata'],true);  
        unset($syncdate[$k]['tableid']);
     }
     if(empty($syncdate)){
                $return['s'] = '0'; 
                $return['data'] = 'error';                
             }else{
                $return['s'] = '1'; 
                $return['data'] = $syncdate; 
     }                         
     app_return($return);     
     
   }

   function UserInfo(){
        $emp_no =  I('login_name'); 
        $name =  I('name');                
        if(!empty($emp_no) || !empty($name)){
            switch ($emp_no){
              case 'all': // 
                   $map['emp_no'] = array('not in','admin');
                   $data = M('User')->where($map)->field('emp_no as login_name,name,sex,mobile_tel,office_tel,is_del as status,sort,identity')->order('sort')->select();
                   break;
              case 'admin': //管理员
                    $data = '';
                    break;
              default: 
                   $map['emp_no'] = $emp_no;
                   $map['name'] = $name;
                   $map['_logic'] = 'OR';
                   $data = M('User')->where($map)->field('emp_no as loginname,name,sex,mobile_tel,office_tel,is_del as status,sort,identity')->order('id')->select(); 
                   break;
             }
             
             if(empty($data)){
                $return['s'] = '0'; 
                $return['data'] = 'error';                
             }else{
                 //用户循环

                 foreach($data as $key=>$val){
                    //身份循环                     
                    if(!empty($val['identity'])){
                        $identity_array = explode('|', substr($val['identity'], 0, -1));                       
                        $identity_array_new = array();
                        foreach($identity_array as $k=>$v){                            
                            //部门职位
                            $identity_base_array = explode('_', $v);
                            $dept_id =  $identity_base_array[1];      //部门id
                            $position_id =  $identity_base_array[2];  //职位id
                            $dept_name = M(dept)->where('id='.$dept_id)-> getField('name');
                            $position_name = M(position)->where('id='.$position_id)-> getField('name');
                            
                            $identity_array_new[$k]['dept_id'] = $dept_id;
                            $identity_array_new[$k]['position_id'] = $position_id;
                            $identity_array_new[$k]['dept_name'] = $dept_name;
                            $identity_array_new[$k]['position_name'] = $position_name;                            
                            
                        }
                        $data[$key]['identity'] = $identity_array_new;                       
                        
                    }                     
                }
                $return['s'] = '1'; 
                $return['data'] = $data; 
             }
             app_return($return);
             
        }
    }
   
   
   //待办接口
   function mytask() {
        $emp_no =  I('login_name');
        $user_map['emp_no'] = $emp_no;
        $rs = M('User')->where($user_map)->find(); 

        if(!empty($rs)){
        
        $FlowLog = M("FlowLog");  //查看审核记录

        $where['emp_no'] = $emp_no;     //当前用户在审核记录表中
        $where['is_del'] = 0;
        $where['_string'] = "result is null";  //并且没有审核意见
        $log_list = $FlowLog -> where($where) -> field('flow_id') -> select();  //查询审核记录

        // $FlowLog_temp = M("FlowLog");     //当前用户在审核记录表中
        // $where['emp_no'] = $emp_no;
        // $where['step'] = 100;
        // $where['_string'] = "comment is null";

        // $log_list = M("FlowLog") -> where($where) -> field('flow_id') -> select();
        // dump($log_list);die;
        $log_list = rotate($log_list);
        // dump($log_list);die;
        if(!empty($log_list)){
            $map['id'] = array('not in', $log_list['flow_id']);
        }
        
        //$map['_string'] = '1=2';
        $map['emp_no'] = array('eq', $emp_no);
        $map['step'] = array('eq', 19);
        $num = M('Flow')->where($map)->count();
        //将两种查询到的数目整合
        $nums = $num + count($log_list['flow_id']);
                               
        $return['s'] = '1'; 
        $return['num'] = $nums;
        }else{
            $return['s'] = '0'; 
            $return['data'] = 'login_name为空或者错误';
        }
     app_return($return);     
     
   }

   //tishi
   public function tishi() {
        
        $emp_no =  I('emp_no'); 
        // $user_id = get_user_id();
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
        // dump($new_confirm_count);die;
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
        
    }
}
?>