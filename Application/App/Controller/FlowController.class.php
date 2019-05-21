<?php

namespace App\Controller; 
use Think\Controller;

class FlowController extends Controller {
	protected $config = array('app_type' => 'asst'); 
    /**
    * @desc 
    * 审批接口初始化
    * by dokey 2017-12-1
    */
    function _initialize(){
        //验证app_secret   
        // $app_secret = I('app_secret');
		$app_secret = $_POST['app_secret'];
		// var_dump($app_secret);
        if ($app_secret !== 'abc') {
            $return['s'] = 0;
            $return['err'] = '非法访问';
            app_return($return);   
        }                      
    }
    /**
    * @desc 
    * 创建新的审批项目
    * by dokey 2017-12-28
    * 参数：
    * $type：审批流程模型编号
    * $emp_no 发起人
    * $name 项目名称
    * 
    * $add_file 附件--暂时不用
    * $udf_data 表单--暂时不用
    */
    function FlowAdd(){
        //调用本类的数据插入
        $this -> _insert();
    }
	
	function FlowEdit(){
        //调用本类的数据插入
        $this -> _update();
    }
	
	function FlowDel(){
		$this -> _dele();
	}
	
	
	
	/**
	*待审核
	*参数：
	*$emp_no 审批人
	*
	*/
	function GetFlowCheck($emp_no=null){
			$emp_no = I('emp_no');
			if(!empty($emp_no)){
				$FlowLog = M("FlowLog");  //查看审核记录
                $where['emp_no'] = $emp_no;     //当前用户在审核记录表中
                $where['is_del'] = 0;
                $where['_string'] = "result is null";  //并且没有审核意见
                $log_list = $FlowLog -> where($where) -> field('flow_id') -> select();  //查询审核记录
				//dump(M("FlowLog")->getLastSql());die;
				$log_list = rotate($log_list);

                if (!empty($log_list)) { 
                    //$map['id'] = array('in', $log_list['flow_id']);  //如果已有当前用户的审核意见
                    $map1['id'] = array('in', $log_list['flow_id']);
                    $map1['step'] = array('neq', 100);
                    //$map1['step'] = array(array('gt', 10), array('neq', 40), 'and');
                    //解决发起人在Flow_log表中已有记录的问题    
                    $map2['emp_no'] = array('eq', $emp_no);
                    $map2['step'] = array('eq', 19);
                    //或条件查询    
                    $map['_complex'] = array(
                            $map1,
                            $map2,
                            '_logic' => 'or'   
                    );
					
                }else {
					//$modelflowlog = D("FlowLog");
					//$user_id = $modelflowlog -> get_user_id($emp_no);
                    $FlowLog_temp = M("FlowLog");     //当前用户在审核记录表中
                    $where_temp['is_del'] = 0;
                    $where_temp['step']= array('neq',100);
                    $where_temp['_string'] = "result is null";  //并且没有审核意见
                    $log_list_temp = $FlowLog_temp -> where($where_temp) -> field('flow_id') -> select();  //查询审核记录
                    $log_list_temp = rotate($log_list_temp);
                    //dump($log_list_temp);
                    $map['id'] = array('not in', $log_list_temp['flow_id']);
                    //$map['_string'] = '1=2';
                    $map['emp_no'] = array('eq', $emp_no);
                    $map['step'] = array('eq', 19);
					
                }
				
				$model = D('Flow');
				$flow = $model -> where($map) -> field('id,doc_no,name,type,confirm_name,consult_name as perform,emp_no,user_name,dept_id,dept_name,step as status,create_time,update_time,add_file') -> select();
				// var_dump($model->getLastSql());die;
				
				$len = count($flow);
				for($i = 0;$i<$len;$i++){
					$flows[$i]['flow_id'] = $flow[$i]['id'];
					$flows[$i]['type'] = M("Flow_type") -> where("id=".$flow[$i]['type'])-> getField('name');
					$flows[$i]['confirm'] = $flow[$i]['confirm_name'];
					// $flows[$i]['perform'] = $this -> get_confirm_string($flow[$i]['perform']);
					$flows[$i]['create_time'] = to_date($flow[$i]['create_time'],'Y-m-d H:i:s');
					$flows[$i]['update_time'] = to_date($flow[$i]['update_time  '],'Y-m-d H:i:s');
					$flows[$i]['status'] = show_step($flow[$i]['status']);
				}
				
                if(!empty($flow)){
					$return['s'] = '1'; 
					$return['data'] = $flows; 
				}else{
					$return['s'] = '0'; 
					$return['data'] = '查无数据'; 
				}
				
                app_return($return);
				
				}else{
            $return['s'] = '0'; 
            $return['err'] = "不正确的用户名";
            app_return($return);
        }
	}
	
	
	
	/**
	*经办事项
	*参数：
	*$emp_no 审批人
	*
	*/
	function GetFlowChecked($emp_no=null){
			if(!empty($emp_no)){
				$FlowLog = D("FlowLog");
				$name = $FlowLog->get_user_name($emp_no);
				//var_dump($name);die;
				//条件1：已经发布意见的审批记录
				$where1['emp_no'] = $emp_no;
				$where1['is_del'] = 0;
				$where1['_string'] = "result is not null";
				//条件2：已发布未回复的参阅记录
				$where2['from'] = $name;
				$where2['is_del'] = 0;
				$where2['_string'] = "comment is null";
				$where2['step'] = 100;
				//组合查询
				$whereOR['_complex'] = array(
							$where1,
							$where2,
							'_logic' => 'or'   
				);
				$log_list = $FlowLog -> where($whereOR) -> field('flow_id') -> select();
				$log_list = rotate($log_list);
				// dump($log_list);die;
				if (!empty($log_list)) {
					//$map['flow.id'] = array('in', $log_list['flow_id']);
					//$map['step'] = array(array('gt', 10), array('neq', 40), 'and');                     
					$map['id'] = array('in', $log_list['flow_id']);
					$map['step'] = array( array('gt', 10), 'and');
					//解决发起人在Flow_log表中已有记录的问题    
					// $map2['emp_no'] = array('eq', $emp_no);
					// $map2['step'] = array( array('gt', 10), 'and');                    
					//或条件查询
					// $map['_complex'] = array(
					// 		$map1,
					// 		$map2,
					// 		'_logic' => 'or'   
					// );
					
				} else {
					$FlowLog_temp = M("FlowLog");     //当前用户在审核记录表中
					$where_temp['is_del'] = 0;
					$where_temp['step']= array('neq',21);
					$where_temp['_string'] = "result is not null";  //并且已经有审核意见
					$log_list_temp = $FlowLog_temp -> where($where_temp) -> field('flow_id') -> select();  //查询审核记录
					$log_list_temp = rotate($log_list_temp);
					//dump($log_list_temp);
					$map['id'] = array('in', $log_list_temp['flow_id']);
					//$map['_string'] = '1=2';array('neq', 40),
					$map['emp_no'] = array('eq', $emp_no);
					$map['step'] = array( array('gt', 10),'and');
				}
				$modelflow = D("Flow");
				$flow = $modelflow -> where($map) -> field('id,doc_no,name,type,confirm_name,consult_name as perform,emp_no,user_name,dept_id,dept_name,step as status,create_time,update_time,add_file') -> select();
				// var_dump($modelflow->getLastSql());die;
				$len = count($flow);
				for($i = 0;$i<$len;$i++){
					$flows[$i]['flow_id'] = $flow[$i]['id'];
					$flows[$i]['type'] = M("Flow_type") -> where("id=".$flow[$i]['type'])-> getField('name');
					$flows[$i]['confirm'] = $flow[$i]['confirm_name'];
					$flows[$i]['perform'] = $this -> get_confirm_string($flow[$i]['perform']);
					$flows[$i]['create_time'] = to_date($flow[$i]['create_time'],'Y-m-d H:i:s');
					$flows[$i]['update_time'] = to_date($flow[$i]['update_time  '],'Y-m-d H:i:s');
					$flows[$i]['status'] = show_step($flow[$i]['status']);
				}
				//$return[$i]['data'] = $flow_data[$i];
				if(!empty($flows)){
					$return['s'] = '1'; 
					$return['data'] = $flows; 
				}else{
					$return['s'] = '0'; 
					$return['data'] = '查无数据'; 
				}
				 
				
				//dump($return);
				app_return($return);
		}else{
			$return['s'] = '0'; 
            $return['err'] = "不正确的用户名";
            app_return($return);
		}
	}
	
	
	/**
	*已办结
	*参数：
	*$emp_no 审批人
	*
	*/
	function GetFlowPassed($emp_no=null){
		if(!empty($emp_no)){
				$FlowLog = M("FlowLog");
                $where['emp_no'] = $emp_no;
                $where['is_del'] = 0;
                $where['_string'] = "result is not null";
                $log_list = $FlowLog -> where($where) -> field('flow_id') -> select();
                $log_list = rotate($log_list);
                if (!empty($log_list)) {
                    /*
                    $map['id'] = array('in', $log_list['flow_id']);
                    //$map['step'] = array('eq', 40);
                    $map['step'] = array( array('eq', 0),array('eq', 40), 'or'); */
                    
                    $map1['id'] = array('in', $log_list['flow_id']);
                    $map1['step'] = array( array('eq', 0),array('eq', 40), 'or');
                    //解决发起人在Flow_log表中已有记录的问题    
                    $map2['emp_no'] = array('eq', $emp_no);
                    $map2['step'] = array( array('eq', 0),array('eq', 40), 'or');
                    //或条件查询    
                    $map['_complex'] = array(
                            $map1,
                            $map2,
                            '_logic' => 'or'   
                    );
                    
                } else {
                    //$map['_string'] = '1=2';
                    $map['emp_no'] = array('eq', $emp_no);
                    //$map['step'] = array('eq', 40);
                    $map['step'] = array( array('eq', 0),array('eq', 40), 'or');
                }
				
				$modelflow = M("Flow");
				$flow = $modelflow -> where($map) -> field('id,doc_no,name,type,confirm_name,consult_name as perform,emp_no,user_name,dept_id,dept_name,step as status,create_time,update_time,add_file') -> select();
				$len = count($flow);
				for($i = 0;$i<$len;$i++){
					$flows[$i]['flow_id'] = $flow[$i]['id'];
					$flows[$i]['type'] = M("Flow_type") -> where("id=".$flow[$i]['type'])-> getField('name');
					$flows[$i]['confirm'] = $flow[$i]['confirm_name'];
					$flows[$i]['perform'] = $this -> get_confirm_string($flow[$i]['perform']);
					$flows[$i]['create_time'] = to_date($flow[$i]['create_time'],'Y-m-d H:i:s');
					$flows[$i]['update_time'] = to_date($flow[$i]['update_time  '],'Y-m-d H:i:s');
					$flows[$i]['status'] = show_step($flow[$i]['status']);
				}
				
				if(!empty($flow)){
					$return['s'] = '1'; 
					$return['data'] = $flows; 
				}else{
					$return['s'] = '0'; 
					$return['data'] = '查无数据'; 
				}
				
				app_return($return);
				}else{
			$return['s'] = '0'; 
            $return['err'] = "不正确的用户名";
            app_return($return);
		}
	}
	
	function GetFlowPassedaaaaaaaaa($emp_no=null){
        if(!empty($emp_no)){
            $model = D("FlowLog");
            $where = array();
            $where['emp_no'] = $model -> get_emp_no();
			$where['comment'] = array('eq','同意');
            //$where['step'] = array('lt', 100);
            $where['is_del'] = array('eq', 0);
            //$where['_string'] = "result is not null";
            $flow_data = $model -> where($where) -> field('flow_id') -> select();
			$flow_data = rotate($flow_data);
			$modelflow = M("Flow");
            if(!empty($flow_data)){
				$map1['id'] = array('in', $flow_data['flow_id']);
                $map1['step'] = array('eq', 40);
				$flow = $modelflow -> where($map1) -> field('id,doc_no,name,type,confirm_name as confirm,consult_name as perform,emp_no,user_name,dept_id,dept_name,step as status,create_time,update_time,add_file') -> select();
				
				//foreach ($flow_data as $value) {
					//$return['data'] = $value;
				//}
				$len = count($flow);
				for($i = 0;$i<$len;$i++){
					
					$flow[$i]['type'] = M("Flow_type") -> where("id=".$flow[$i]['type'])-> getField('name');
					$flow[$i]['confirm'] = $this -> get_confirm_string($flow[$i]['confirm']);
					$flow[$i]['perform'] = $this -> get_confirm_string($flow[$i]['perform']);
					$flow[$i]['create_time'] = to_date($flow[$i]['create_time'],'Y-m-d H:i:s');
					$flow[$i]['update_time'] = to_date($flow[$i]['update_time  '],'Y-m-d H:i:s');
					$flow[$i]['status'] = show_step($flow[$i]['status']);
				}
                //$return[$i]['data'] = $flow_data[$i];
                
				$return['s'] = '1'; 
				
				
				//$return['sss'] = $len; 
				
					
				 $return['data'] = $flow; 
                
                //dump($return);
                app_return($return);
            }else{
                $return['s'] = '0'; 
                $return['err'] = "没有找到查询的流程数据";
                app_return($return);
            }
            
        }else{
            $return['s'] = '0'; 
            $return['err'] = "不正确的用户名";
            app_return($return);
        }
    }
	
	
	/**
	*审核否决
	*参数：
	*$emp_no 审批人
	*
	*/
	
	function GetFlowFail($emp_no=null){
        if(!empty($emp_no)){
            $model = D("FlowLog");
            $where = array();
            $where['emp_no'] = $model -> get_emp_no();
			//var_dump($where['emp_no']);die;
			$where['comment'] = array('eq','同意');
            //$where['step'] = array('lt', 100);
            $where['is_del'] = array('eq', 0);
            //$where['_string'] = "result is not null";
            $flow_data = $model -> where($where) -> field('flow_id') -> select();
			$flow_data = rotate($flow_data);
			$modelflow = M("Flow");
            if(!empty($flow_data)){
				$map1['id'] = array('in', $flow_data['flow_id']);
                $map1['step'] = array('eq', 0);
				$flow = $modelflow -> where($map1) -> field('id,doc_no,name,type,confirm_name,consult_name as perform,emp_no,user_name,dept_id,dept_name,step as status,create_time,update_time,add_file') -> select();
				
				//foreach ($flow_data as $value) {
					//$return['data'] = $value;
				//}
				$len = count($flow);
				for($i = 0;$i<$len;$i++){
					$flows[$i]['flow_id'] = $flow[$i]['id'];
					$flows[$i]['type'] = M("Flow_type") -> where("id=".$flow[$i]['type'])-> getField('name');
					$flows[$i]['confirm'] = $flow[$i]['confirm_name'];
					// $flows[$i]['perform'] = $this -> get_confirm_string($flow[$i]['perform']);
					$flows[$i]['create_time'] = to_date($flow[$i]['create_time'],'Y-m-d H:i:s');
					$flows[$i]['update_time'] = to_date($flow[$i]['update_time  '],'Y-m-d H:i:s');
					$flows[$i]['status'] = show_step($flow[$i]['status']);
				}
                //$return[$i]['data'] = $flow_data[$i];
                
				$return['s'] = '1'; 
				
				
				//$return['sss'] = $len; 
				
					
				 $return['data'] = $flows; 
                
                //dump($return);
                app_return($return);
            }else{
                $return['s'] = '0'; 
                $return['err'] = "没有找到查询的流程数据";
                app_return($return);
            }
            
        }else{
            $return['s'] = '0'; 
            $return['err'] = "不正确的用户名";
            app_return($return);
        }
    }
	
	/**
	*审核退回
	*参数：
	*$emp_no 审批人
	*
	*/
	
	function GetFlowFailaaa($emp_no=null){
        if(!empty($emp_no)){
            $model = D("FlowLog");
            $where = array();
            $where['emp_no'] = $model -> get_emp_no();
			$where['comment'] = array('eq','同意');
            //$where['step'] = array('lt', 100);
            $where['is_del'] = array('eq', 0);
            //$where['_string'] = "result is not null";
            $flow_data = $model -> where($where) -> field('flow_id') -> select();
			$flow_data = rotate($flow_data);
			$modelflow = M("Flow");
            if(!empty($flow_data)){
				$map1['id'] = array('in', $flow_data['flow_id']);
                $map1['step'] = array('eq', 19);
				$flow = $modelflow -> where($map1) -> field('id,doc_no,name,type,confirm_name as confirm,consult_name as perform,emp_no,user_name,dept_id,dept_name,step as status,create_time,update_time,add_file') -> select();
				
				//foreach ($flow_data as $value) {
					//$return['data'] = $value;
				//}
				$len = count($flow);
				for($i = 0;$i<$len;$i++){
					
					$flow[$i]['type'] = M("Flow_type") -> where("id=".$flow[$i]['type'])-> getField('name');
					$flow[$i]['confirm'] = $this -> get_confirm_string($flow[$i]['confirm']);
					$flow[$i]['perform'] = $this -> get_confirm_string($flow[$i]['perform']);
					$flow[$i]['create_time'] = to_date($flow[$i]['create_time'],'Y-m-d H:i:s');
					$flow[$i]['update_time'] = to_date($flow[$i]['update_time  '],'Y-m-d H:i:s');
					$flow[$i]['status'] = show_step($flow[$i]['status']);
				}
                //$return[$i]['data'] = $flow_data[$i];
                
				$return['s'] = '1'; 
				
				
				//$return['sss'] = $len; 
				
					
				 $return['data'] = $flow; 
                
                //dump($return);
                app_return($return);
            }else{
                $return['s'] = '0'; 
                $return['err'] = "没有找到查询的流程数据";
                app_return($return);
            }
            
        }else{
            $return['s'] = '0'; 
            $return['err'] = "不正确的用户名";
            app_return($return);
        }
    }
	
	
	 /**
    * @desc 
    * 测试
    * by dokey 2018-04-02
    * 参数：
    * $id = 流程ID
    */
	public function GetNewOne(){
		$emp_no = I('jszl_emp_no');
		if(!empty($emp_no)){
			$dd = D('Flow');
			
			//$emp_noa = $dd->next_step();
			
			$return['data'] = $emp_noa;
			app_return($return);
		}
	}
	
	
	/**
    * @desc 
    * 撤销
    * by dokey 2018-04-13
    * 参数：
    * $id = 流程ID
    */
	
	//public function FlowDel
	
	
	
	
    /**
    * @desc 
    * 获取审批项目数据
    * by dokey 2017-12-28
    * 参数：
    * $id = 流程ID
    */
    public function GetFlowInfo($flow_id=null){
        if(!empty($flow_id)){
            $model = D("Flow");
            $where = array();
            $where['id'] = $flow_id;
            //$where['step'] = array('lt', 100);
            $where['is_del'] = array('eq', 0);
            //$where['_string'] = "result is not null";
			
			
            $flow_data = $model -> where($where) -> field('id,doc_no,name,type,confirm_name as confirm,consult_name as perform,emp_no,user_name,dept_id,dept_name,step as status,create_time,update_time,add_file,udf_data')-> order("id") -> find();
            // var_dump($flow_data);die;
            if(!empty($flow_data)){
                $flow_data['type'] = M("Flow_type") -> where("id=".$flow_data['type'])-> getField('name');
                // $aaa = json_decode($flow_data['confirm'],true);
                $flow_data['confirm'] = $flow_data['confirm'];
                $flow_data['perform'] = $this -> get_confirm_string($flow_data['perform']);
                $flow_data['create_time'] = to_date($flow_data['create_time'],'Y-m-d H:i:s');
                $flow_data['update_time'] = to_date($flow_data['update_time'],'Y-m-d H:i:s');
                $flow_data['status'] = $flow_data['status'];
				$flow_data['status_name'] = show_step($flow_data['status']);
				
                $return['s'] = '1'; 
                $return['data'] = $flow_data;
				$model1 = M("Flow_log");
				$where1['flow_id'] = $flow_id;
				$where1['is_del'] = array('eq', 0);
				$flow_data1 = $model1 -> where($where1) -> field('flow_id,emp_no,user_name,step,result,create_time,update_time,comment') -> order("id") -> select();
				foreach ($flow_data1 as $k => $v) {
					if(empty($v['result']) && empty($v['comment'])){
						$sp = $model->check_agent($v['emp_no']);
						$return['shenpi'] = $v['emp_no'];
						$return['agent'] = $sp;
						$return['step'] = $v['step'];
						$xiayi = $model -> next_step_info($flow_id,$v['step']);
						if($xiayi['emp_no'] == 'D_all'){
							$map['dept_id'] = array('eq',$flow_data['dept_id']);
							$map['peoplestatus'] = array('eq', 0);
							$return['nextshenpi'] = $xiayi['emp_no'];
							$nextstep = $v['step']+1;
							$return['nextstep'] = "$nextstep";
							$return['select_nextshenpi'] = M('User') -> where($map) -> field('id as user_id,emp_no,name') -> select();
						}else{
							// var_dump($xiayi);die;
							$nextsp = $model->check_agent($xiayi['emp_no']);
							
							$return['nextshenpi'] = $xiayi;
							$return['nextagent'] = $nextsp;
							
							
							
						}
						$shangyi = $model -> prev_step_info($flow_id,$v['step']);
						$return['prevagent'] = $model->check_agent($shangyi['emp_no']);
						$return['prevshenpi'] = $shangyi['emp_no'];
						$return['prevstep'] = $shangyi['step'];
						
					}
						
				}
				
				$return['log'] = $flow_data1;
				// $jsonarr = json_encode($return);
                //dump($return);
                app_return($return);
            }else{
                $return['s'] = '0'; 
                $return['err'] = "没有找到查询的流程数据";
                app_return($return);
            }
            
        }else{
            $return['s'] = '0'; 
            $return['err'] = "不正确的流程ID";
            app_return($return);
        }
    }
    
    /**
    * @desc 
    * 转换审批人字符
    * by dokey 2017-12-1
    */
    public function get_confirm_string($confirm){
            
        preg_match_all("/<B title=.*>(.*)<\/B>/isU", $confirm,$m_conf);
        //dump($m_conf[1]);
        $m_str = implode("=>",$m_conf[1]);
        //dump($m_str);
        return $m_str;
    }

	/**
    * @desc 附件上传接口
    * by dokey 2017-12-1
    */     
    public function upload() {
        /*
        if (C('CHUNK_UPLOAD')) {
            $files = $this -> _chunk_upload();
        } else {
            $files = $_FILES;
        }*/
        //接口不使用CHUNK_UPLOAD上传组件
        $files = $_FILES;

        //$return = array('status' => 1, 'info' => '上传成功', 'data' => '');
        /* 调用文件上传组件上传文件 */
        $File = D('File');
        $file_driver = C('DOWNLOAD_UPLOAD_DRIVER');
        $info = $File -> upload($files, C('DOWNLOAD_UPLOAD'), C('DOWNLOAD_UPLOAD_DRIVER'), C("UPLOAD_{$file_driver}_CONFIG"));
        /* 返回附件信息 */
        if ($info) {
            $return['s'] = 1; 
            if (!empty($info['file'])) {
                $return['data'] = $info['file'];
            }
            if (!empty($info['imgFile'])) {
                $return['data'] = $info['imgFile'];
                $return['url'] = $return['path'];
            }

        } else {
            $return['s'] = 0;
            $return['error'] = $File -> getError();
        }
        /* 返回JSON数据 */
        app_return($return);
    }

	function _flow_auth_filter($folder, &$map) {
		$emp_no = get_emp_no();
		$user_id = get_user_id();
		switch ($folder) {
			case 'confirm' :
				$this -> assign("folder_name", '待审批');
				$FlowLog = M("FlowLog");
				$where['emp_no'] = $emp_no;
				$where['is_del'] = 0;
				$where['_string'] = "result is null";
				$log_list = $FlowLog -> where($where) -> field('flow_id') -> select();
				$log_list = rotate($log_list);

				if (!empty($log_list)) {
					$map['id'] = array('in', $log_list['flow_id']);
				} else {
					$map['_string'] = '1=2';
				}
				break;

			case 'darft' :
				$this -> assign("folder_name", '草稿箱');
				$map['user_id'] = $user_id;
				$map['step'] = 10;
				break;

			case 'submit' :
				$this -> assign("folder_name", '已提交');
				$map['user_id'] = array('eq', $user_id);
				$map['step'] = array( array('gt', 10), array('eq', 0), 'or');

				break;

			case 'finish' :
				$this -> assign("folder_name", '已审批');
				$FlowLog = M("FlowLog");
				$where['emp_no'] = $emp_no;
				$where['is_del'] = 0;
				$where['_string'] = "result is not null";
				$log_list = $FlowLog -> where($where) -> field('flow_id') -> select();
				$log_list = rotate($log_list);
				if (!empty($log_list)) {
					$map['id'] = array('in', $log_list['flow_id']);
				} else {
					$map['_string'] = '1=2';
				}
				break;

			case 'receive' :
				$this -> assign("folder_name", '参阅箱');
				$FlowLog = M("FlowLog");
				$where['emp_no'] = $emp_no;
				$where['step'] = 100;
				$where['is_del'] = 0;
				$log_list = $FlowLog -> where($where) -> field('flow_id') -> select();
				$log_list = rotate($log_list);
				if (!empty($log_list)) {
					$map['id'] = array('in', $log_list['flow_id']);
				} else {
					$map['_string'] = '1=2';
				}
				break;

			case 'receive_read' :
				$this -> assign("folder_name", '已参阅');
				$FlowLog = M("FlowLog");
				$where['emp_no'] = $emp_no;
				$where['step'] = 100;
				$where['is_del'] = 0;
				$where['_string'] = "comment is not null";
				$log_list = $FlowLog -> where($where) -> field('flow_id') -> select();
				$log_list = rotate($log_list);
				if (!empty($log_list)) {
					$map['id'] = array('in', $log_list['flow_id']);
				} else {
					$map['_string'] = '1=2';
				}
				break;

			case 'receive_unread' :
				$this -> assign("folder_name", '未参阅');
				$FlowLog = M("FlowLog");
				$where['emp_no'] = $emp_no;
				$where['step'] = 100;
				$where['is_del'] = 0;
				$where['_string'] = "comment is null";
				$log_list = $FlowLog -> where($where) -> field('flow_id') -> select();
				$log_list = rotate($log_list);
				if (!empty($log_list)) {
					$map['id'] = array('in', $log_list['flow_id']);
				} else {
					$map['_string'] = '1=2';
				}
				break;

			case 'report' :
				$this -> assign("folder_name", '统计报告');
				$role_list = D("Role") -> get_role_list($user_id);
				$role_list = rotate($role_list);
				$role_list = $role_list['role_id'];

				$duty_list = D("Role") -> get_duty_list($role_list);
				$duty_list = rotate($duty_list);
				$duty_list = $duty_list['duty_id'];

				if (!empty($duty_list)) {
					$map['report_duty'] = array('in', $duty_list);
					$map['step'] = array('gt', 10);
				} else {
					$this -> error("没有权限");
				}
				break;
		}
	}

	function folder($fid) {
		$plugin['date'] = true;
		$this -> assign("plugin", $plugin);

		$emp_no = get_emp_no();
		$user_id = get_user_id();

		$flow_type_where['is_del'] = array('eq', 0);

		$flow_type_list = M("FlowType") -> where($flow_type_where) -> getField("id,name");
		$this -> assign("flow_type_list", $flow_type_list);

		$map = $this -> _search();
		if (method_exists($this, '_search_filter')) {
			$this -> _search_filter($map);
		}
		$folder = $fid;
		$this -> assign("folder", $folder);

		$this -> _flow_auth_filter($folder, $map);
		$model = D("FlowView");

		if (I('mode') == 'export') {
			$this -> _folder_export($model, $map);
		} else {
			$vo = $this -> _list($model, $map, 'id desc');

			if (!empty($vo)) {
				foreach ($vo as $k => $v) {
					$flow_id[] = $v['id'];
                    $data[$k]['id'] = $v['id'];        
                    $data[$k]['doc_no'] = $v['doc_no'];
                    $data[$k]['name'] = $v['name']; 
                    $data[$k]['add_file'] = $this->view_add_file($v['add_file']); 
                    $data[$k]['user_id'] = $v['user_id'];
                    $data[$k]['user_name'] = $v['user_name'];
                    $data[$k]['dept_id'] = $v['dept_id'];
                    $data[$k]['dept_name'] = $v['dept_name'];
                    $data[$k]['confirm'] = $v['confirm'];
                    $data[$k]['create_time'] = $v['create_time'];
                    $data[$k]['step'] = $v['step'];
                    $data[$k]['udf_data'] = $v['udf_data'];
                    $data[$k]['flow_log'] = $this->get_flowlog($v['id']);
				}
				$where_flow['flow_id'] = array('in', $flow_id);
				$where_flow['is_del'] = array('eq', 0);
				//$where_flow['_string'] = "result is null";
				$flow_log = M('FlowLog') -> where($where_flow) -> getField('flow_id,emp_no,user_name,step,result,comment');
                
				//$this -> assign('arr_emp', $flow_log);
			}else{
                $return['s'] = 1;
                $return['data'] = ''; 
                app_return($return);
            }
		}
        
        $return['s'] = 1;
        $return['data'] = $data;
        app_return($return);
		//$this -> display('list');
	}
    
    
    function get_flowlog($id){
        //审批日志
        $model = M("FlowLog");
        $where = array();
        $where['flow_id'] = $id;
        $where['step'] = array('lt', 100);
        $where['is_del'] = array('eq', 0);                          
        $where['_string'] = "result is not null";
        $flow_log = $model -> where($where) -> order("id") -> select();
        //$this -> assign("flow_log", $flow_log);
        return $flow_log;
        
    }
	

	function read($id) {
		$plugin['date'] = true;
		$plugin['uploader'] = true;
		$plugin['editor'] = true;
		$this -> assign("plugin", $plugin);

		$fid = I("fid");
		$this -> _flow_auth_filter($fid, $map);

		$model = D("Flow");
		$where['id'] = array('eq', $id);
		$where['_logic'] = 'and';
		$map['_complex'] = $where;

		$vo = $model -> where($map) -> find();
		if (empty($vo)) {
			//$this -> error("系统错误");
            $return['s'] = 0;
            $return['err'] = '数据错误';
            app_return($return);
		}
        
		$this -> assign("emp_no", $vo['emp_no']);
		$this -> assign("user_name", $vo['user_name']);
		$this -> assign('vo', $vo);

		//$field_list = D("UdfField") -> get_data_list($vo['udf_data']);
		//dump($field_list);
		//$this -> assign("field_list", $field_list);

		$flow_type_id = $vo['type'];
		$model = M("FlowType");
		$flow_type = $model -> find($flow_type_id);
		$this -> assign("flow_type", $flow_type);

		//审批日志
		$model = M("FlowLog");
		$where = array();
		$where['flow_id'] = $id;
		$where['step'] = array('lt', 100);
		$where['is_del'] = array('eq', 0);
		$where['_string'] = "result is not null";
		$flow_log = $model -> where($where) -> order("id") -> select();
		$this -> assign("flow_log", $flow_log);
        
		//参阅日志
		$model = M("FlowLog");
		$where = array();
		$where['flow_id'] = $id;
		$where['step'] = array('eq', 100);
		$model -> where($where) -> setField('is_read', 1);
		$refer_flow_log = $model -> where($where) -> order("id") -> select();
		$this -> assign("refer_flow_log", $refer_flow_log);

		//当前审批信息
		$where = array();
		$where['flow_id'] = $id;
		$where['emp_no'] = get_emp_no();
		$where['is_del'] = array('eq', 0);
		$where['_string'] = "result is null";
		$to_confirm = $model -> where($where) -> find();
		//$this -> assign("to_confirm", $to_confirm);
        
        //附件
        $to_confirm['add_file'] = $this->view_add_file($to_confirm['add_file']);
        
        $return['s'] = 1;
        $return['data'] = $to_confirm;
        app_return($return);
        
        
		$where = array();
		$where['flow_id'] = $id;
		$where['emp_no'] = get_emp_no();
		$where['is_del'] = array('eq', 0);
		$where['step'] = array('eq', 100);
		$to_refer = $model -> where($where) -> find();
		$is_read = $model -> where($where) -> setField('is_read', 1);
		$this -> assign("to_refer", $to_refer);

		if (!empty($to_confirm)) {
			$is_edit = $flow_type['is_edit'];
			$this -> assign("is_edit", $is_edit);
		} else {
			$is_edit = $flow_type['is_edit'];
			$this -> assign("is_edit", 0);
		}

		$where = array();
		$where['flow_id'] = $id;
		$where['_string'] = "result is not null";
		$where['emp_no'] = array('neq', $vo['emp_no']);
		$confirmed = $model -> Distinct(true) -> where($where) -> field('emp_no,user_name') -> select();
		$this -> assign("confirmed", $confirmed);
		//$this -> display();
	}
    

	function edit($id) {
		$plugin['date'] = true;
		$plugin['uploader'] = true;
		$plugin['editor'] = true;
		$this -> assign("plugin", $plugin);

		$folder = I('fid');
		$this -> assign("folder", $folder);

		if (empty($folder)) {
			$this -> error("系统错误");
		}
		$this -> _flow_auth_filter($folder, $map);

		$model = D("Flow");
		$where['id'] = array('eq', $id);
		$where['_logic'] = 'and';
		$map['_complex'] = $where;
		$vo = $model -> where($map) -> find();
		if (empty($vo)) {
			$this -> error("系统错误");
		}
		$this -> assign('vo', $vo);

		$field_list = D("UdfField") -> get_data_list($vo['udf_data']);
		//dump($field_list);
		$this -> assign("field_list", $field_list);

		$model = M("FlowType");
		$type = $vo['type'];
		$flow_type = $model -> find($type);
		$this -> assign("flow_type", $flow_type);

		$model = M("FlowLog");
		$where['flow_id'] = $id;
		$where['_string'] = "result is not null";
		$flow_log = $model -> where($where) -> select();

		$this -> assign("flow_log", $flow_log);
		$where = array();
		$where['flow_id'] = $id;
		$where['emp_no'] = get_emp_no();
		$where['_string'] = "result is null";
		$confirm = $model -> where($where) -> select();

		$this -> assign("confirm", $confirm[0]);
		$this -> display();
	}

	function del($id) {
		$this -> _del($id);
	}

	//转交
	function transfer() {
		$emp_no = I('addr_id');
		//被转交人的EMP_NO
		$flow_id = I('id');
		//审批id
		$step = I('step');
		$where['id'] = $flow_id;
		//当获取STEP 大于21小于30时为审批人
		if ($step >= 21 && $step < 30) {
			$flow = M('Flow') -> where($where) -> getField('confirm');
			//查询原数据
			if (strpos($flow, ',') == false) {
				$flow_array = explode('|', substr($flow, 0, -1));
			} else {
				$flow_array = explode(',', substr($flow, 0, -1));
			}
			//如果获取的confirm 里面有，的话就以，分割字符串，反之|分割字符
			$i = $step - 21;
			$flow_array[$i] = $flow_array[$i] . ',' . $emp_no;
            //step -21 代表这条审批的位置，然后更改这个位置的数据
			//step -21 代表这条审批的位置，然后更改这个位置的数据
			if (strpos($flow, ',') == false) {
				$confirm = implode('|', $flow_array);
			} else {
				$confirm = implode(',', $flow_array);
			}
			//如果confirm 里面有，就以，好将数组转换成字符，反之|转换，并保存
			$data['confirm'] = $confirm;
			//当获取STEP 大于30小于40时为执行
		} else if ($step >= 30 && $step <= 39) {
			$flow = M('Flow') -> where($where) -> getField('consult');
			//查询原数据
			if (strpos($flow, ',') == false) {
				$flow_array = explode('|', substr($flow, 0, -1));
			} else {
				$flow_array = explode(',', substr($flow, 0, -1));
			}
			$i = $step - 30;
			$flow_array[$i] = $flow_array[$i] . ',' . $emp_no;
			if (strpos($flow, ',') == false) {
				$consult = implode('|', $flow_array);
			} else {
				$consult = implode(',', $flow_array);
			}
			$data['consult'] = $consult;
		}
		//新的confirm
		M('Flow') -> where($where) -> save($data);
		//更新

		$where_user['emp_no'] = $emp_no;
		$user = M('User') -> where($where_user) -> field('id,name') -> select();

		$flow_log['emp_no'] = $emp_no;
		$flow_log['step'] = $step;
		$flow_log['flow_id'] = $flow_id;
		$flow_log['create_time'] = time();
		$flow_log['user_id'] = $user[0]['id'];
		$flow_log['user_name'] = $user[0]['name'];
		M('FlowLog') -> add($flow_log);
		//被转交人的审批情况

		$flow_log1['emp_no'] = get_emp_no();
		$flow_log1['step'] = $step;
		$flow_log1['flow_id'] = $flow_id;
		$flow_log1['create_time'] = time();
		$flow_log1['update_time'] = time();
		$flow_log1['user_id'] = get_user_id();
		$flow_log1['user_name'] = get_user_name();
		$flow_log1['result'] = '6';
		$flow_log1['comment'] = get_user_name() . '转交给' . $user[0]['name'];
		M('FlowLog') -> add($flow_log1);
		//转交人的审批情况

		$push_data['type'] = "审批";
		$push_data['action'] = '转交审批';
		$push_data['title'] = '请审批';
		$push_data['content'] = '发送人：' . get_dept_name() . "-" . get_user_name();
		$push_data['url'] = U("Flow/read?id=$flow_id");
		send_push($push_data, $user[0]['id']);

		$this -> success('转交成功', U('Flow/read?id=$flow_id'));
	}
	
	
	

	/**  
    * 插入新新数据接口
    * 2017-12-28
    * 接口参数：
    * app_secret: 访问密钥
    * type: 审批类型
    * emp_no: 用户名
    * name: 申请标题
    * step：审批步骤
    **/
	protected function _insert() {
		if(empty(I('name'))){
			$return['s'] = '0'; 
            $return['error'] = '申请标题不能为空';
            app_return($return);
		}
		
		if(empty(I('emp_no'))){
			$return['s'] = '0'; 
            $return['error'] = '用户名不能为空';
            app_return($return);
		}
		if(!empty(I('jszl_emp_no'))){
			$map1['emp_no'] = I('jszl_emp_no');
			$map1['is_del'] = array('eq',0);
			// $map1['peoplestatus'] = array('eq',0);
			// $map1['dept_id'] = array('eq',16);
			// // $map2['_complex'] = $map1;
			// $map2['emp_no'] = I('jszl_emp_no');
			// $map2['position_id'] = array('in','1,2,3');
			// $map1['peoplestatus'] = array('in','0,1');
			// $map2['is_del'] = array('eq',0);
			// $map2['_logic'] = 'and';
			
			// $final['_logic']='or';
			// $map['_complex'] = array(
   //                          $map1,
   //                          $map2,
   //                          '_logic' => 'or'   
   //                  );
			
			$mod = M('User') -> where($map1) -> field('id,emp_no,name') -> find();
			// var_dump(M('User')->getLastSql());die;
			// var_dump($mod);die;
			if(!empty($mod)){
				
			}else{
				$return['s'] = '0'; 
				$return['error'] = '该用户名无权限发起';
				app_return($return);
			}
			// var_dump($mod);die;
		}
		
		
		
		$whe['emp_no'] = I('emp_no');
		$whe['is_del'] = array('eq',0);
		$mod = M('User') -> where($whe) -> field('id') -> find();

		if($mod){
		
			$typemodel = D('FlowTypeView');
	        $typewhere['is_del'] = 0;
	        $user_id = $mod['id'];
	        $role_list = D("Role") -> get_role_list($user_id);
	        $role_list = rotate($role_list);
	        $role_list = $role_list['role_id'];

	        $duty_list = D("Role") -> get_duty_list($role_list);
	        $duty_list = rotate($duty_list);
	        $duty_list = $duty_list['duty_id'];
	        if (!empty($duty_list)) {
	            $typewhere['request_duty'] = array('in', $duty_list);
	        } else {
	            $typewhere['_string'] = '1=2';
	        }

	        $typelist = $typemodel -> where($typewhere) -> field('id') -> order('sort') -> select();
			// var_dump($typemodel->getLastSql());die;
			// var_dump($typelist);die;
			$typelist = rotate($typelist);
			
			// var_dump($typelist);die;
		}else{
			$return['s'] = '0'; 
            $return['error'] = '请输入有效的用户名';
            app_return($return);
		}
		
		
		
		
		$model = D("Flow");
		// var_dump($model->data());die;
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		//$model -> udf_data = D('UdfField') -> get_field_data();
        $model -> udf_data = "";
		//$where_step['flow_type_id'] = array('eq', $model -> type);
		//$where_step['is_del'] = array('eq', 0);
		//$flow_step = M('FlowStep') -> where($where_step) -> order('sort') -> select();
		$rs = false;
        //dump($flow_step); die;
		/*查找分支审批人，暂时不判断分支
        if (!empty($flow_step)) {
			foreach ($flow_step as $step) {
				$rs = D('Flow') -> check_step($step['condition'], $model -> udf_data);
                dump("rs=".$rs);
				if ($rs) {
                    //查找审核人
					$str_confirm = D("Flow") -> _conv_auditor($step['confirm'],$model->emp_no);
					$str_consult = D("Flow") -> _conv_auditor($step['consult'],$model->emp_no);
					break;
				}
			}
		} */ 
		if(in_array(I('flow_type_id'),$typelist['id'])){
				$type_id = I('flow_type_id');
			}else{
				$return['s'] = '0'; 
				$return['error'] = '申请的类型不对';
				app_return($return);
			}
		//var_dump($type_id);die;
        $flow_type = M("FlowType") -> find($type_id);
        // var_dump($flow_type);die;
        //查找下一节点审核人
		// var_dump($flow_type["confirm"]);die;
        $str_confirm = D("Flow") -> _conv_auditor($flow_type["confirm"],$model->emp_no);
        
		if (!$rs) {
            $str_confirm = D("Flow") -> _conv_auditor($flow_type["confirm"],$model->emp_no);
            $str_consult = D("Flow") -> _conv_auditor($flow_type["consult"],$model->emp_no);
		}
		$str_auditor = $str_confirm . $str_consult;
		// dump($str_confirm);die;
        if (empty($str_auditor)) {
			//$this -> error('没有找到任何审核人');
            $return['s'] = '0'; 
            $return['error'] = '没有找到任何审核人';
            app_return($return); 
		}
		// var_dump($str_confirm);die;
        //审批人和执行人
        $model-> confirm = $str_confirm;
        $model-> confirm_name = $flow_type["confirm_name"];
        $model-> consult = $str_consult;
        $model-> consult_name = $flow_type["consult_name"];
        //发起人和部门
        $model-> user_id = $model -> get_user_id($model->emp_no);
		$model-> user_name = $model -> get_user_name($model->emp_no);
        $model-> dept_id = $model -> get_dept_id($model->emp_no);
        $model-> dept_name = $model -> get_dept_name($model->emp_no);
		//$model-> dept_name = $dept_name;$model -> get_dept_name($model->emp_no);
		// var_dump($model-> dept_name);die;
		
		$model-> type = $type_id;
		$model-> step = 20;
        
		
        $list = $model -> add();
		// dump($model->getLastSql());die;
        
		if ($list !== false) {//保存成功
			//$flow_filed = D("UdfField") -> set_field($list);;
            $return['s'] = '1'; 
            $return['data']['Flow_id'] = $list;
            app_return($return);
		} else {
			//$this -> error('新增失败!');
			$return['s'] = '0'; 
            $return['error'] = '新增失败';
            app_return($return);
		}    
	}
	
	

	/* 更新数据  
	* 2017-12-28
    * 接口参数：
    * app_secret: 访问密钥
	* flow_id: ID          必填
    * type: 审批类型
    * emp_no: 用户名
    * name: 申请标题       必填
    * step：审批步骤
	*/
	protected function _update($name = CONTROLLER_NAME) {
		$model = D($name);
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}  
		
		$where_fl['id'] = array('eq', I('flow_id'));
		$where_fl['step'] = array('eq', 19);
		
		$rs = false;
			if(!empty(I('flow_id'))){
            if ($model -> where($where_fl) -> select()) {

					$flow_type = M("FlowType") -> find($data['type']);
					//var_dump(M("FlowType")->getLastSql());die;
					 //查找下一节点审核人
					$str_confirm = D("Flow") -> _conv_auditor($flow_type["confirm"],$model->emp_no);
					//dump($str_confirm); 
					if (!$rs) {
						$str_confirm = D("Flow") -> _conv_auditor($flow_type["confirm"],$model->emp_no);
						$str_consult = D("Flow") -> _conv_auditor($flow_type["consult"],$model->emp_no);
					}        
					$str_auditor = $str_confirm . $str_consult;
					if (empty($str_auditor)) {
						//$this -> error('没有找到任何审核人');
						$return['s'] = '0'; 
						$return['error'] = '没有找到任何审核人';
						app_return($return); 
					}
					//审批人和执行人
					$model-> confirm = $str_confirm;
					$model-> confirm_name = $flow_type["confirm_name"];
					$model-> consult = $str_consult;
					$model-> consult_name = $flow_type["consult_name"];
					//发起人和部门
					$model-> user_id = $model -> get_user_id($model->emp_no);
					$model-> user_name = $model -> get_user_name($model->emp_no);
					$model-> dept_id = $model -> get_dept_id($model->emp_no);
					$model-> dept_name = $model -> get_dept_name($model->emp_no);
					if(!empty(I('flow_id'))){
						//$model-> type = I('flow_id');
					}else{
						$return['error'] = '参数错误';
						app_return($return);
					}
					
					//if(!empty(I('flow_type_id'))){
					//	$model-> type = I('flow_type_id');
					//}
					
					if(!empty(I('emp_no'))){
						$model-> emp_no = I('emp_no');
					}
					
					if(!empty(I('flow_username'))){
						$model-> user_name = I('flow_username');
					}
					
					if(!empty(I('name'))){
						$model-> name = I('name');
					}else{
						$return['error'] = '参数错误';
						app_return($return);
					}
					
					$model-> step = 20;
					//dump($model);
					$modellog = D("FlowLog");
					$datalog['is_del'] = 1;
					$wherelog['flow_id'] = array('eq', I('flow_id'));
					$wherelog['result'] = array('EXP','is NULL');
					//if($modellog -> where($wherelog) -> save($datalog)){
						$modellog -> where($wherelog) -> save($datalog);
						$list = $model -> where($where_fl)->save();
						if($list == true){
							
							$return['s'] = '1'; 
							//$return['data']['Flow_id'] = $list;
							$return['data'] = '修改成功';
							app_return($return);
						} else {
							$return['s'] = '0'; 
							$return['error'] = '修改失败';
							app_return($return);
						}
					/*}else{
						$return['s'] = '0'; 
						$return['error'] = '修改失败';
						app_return($return);
					}*/
					
					
					
				
			}else{
				//$this -> error($model -> getError());
				$return['s'] = '0'; 
						$return['error'] = 'ID不存在或未退回不允许修改';
						app_return($return);
			}
			}else{
				$return['s'] = '0'; 
							$return['error'] = 'ID为空';
							app_return($return);
			}
		
        /*
       	if($model->where($where_fl)->save($data)){
					$return['s'] = '1'; 
					$return['data']['Flow_id'] = I('flow_id');
					app_return($return);
				}
		*/
        
		
	}
	
	
	/**
    * @desc 删除申请
    */
	public function _dele(){
		$model = M('Flow');
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}  
		
		$where_fl['id'] = array('eq', I('flow_id'));
		
		$rs = false;
			if(!empty(I('flow_id')) && !empty(I('emp_no'))){
			// var_dump($model -> where($where_fl) -> field('emp_no') -> find()['emp_no']);die;
			if($model -> where($where_fl) -> field('emp_no') -> find()['emp_no'] != I('emp_no')){
				$return['s'] = '0'; 
				$return['error'] = '发起人不对';
				app_return($return);
			}
			//查询当前申请状态
			$result = $model -> where($where_fl) -> field('step') -> find();
			
			$status = array( 10, 19);
			//判断该申请状态是否为退回或草稿
            if (in_array($result['step'], $status)) {
					$modellog = M("FlowLog");
					$datalog['is_del'] = 1;
					$wherelog['flow_id'] = array('eq', I('flow_id'));
					if($modellog -> where($wherelog) -> save($datalog)){
						$list = $model -> where($where_fl)->save($datalog);
						if($list == true){
							$return['s'] = '1'; 
							$return['data']['Flow_id'] = $list;
							app_return($return);
						} else {
							$return['s'] = '0'; 
							$return['error'] = '参数错误';
							app_return($return);
						}
					}else{
						$return['s'] = '0'; 
						$return['error'] = '修改失败';
						app_return($return);
					}
					
					
					
				
			}else{
				//$this -> error($model -> getError());
				$return['s'] = '0'; 
						$return['error'] = '该申请未被退回';
						app_return($return);
			}
			}else{
				$return['s'] = '0'; 
							$return['error'] = 'ID与发起人不匹配';
							app_return($return);
			}
	}
	//测试next_step
	// public function nextstep(){
		// D("Flow") -> next_step(1633, 21);
	// }
	
	
    /**
    * @desc 用户同意
    */
	public function approve() {

		$flow_id = I('flow_id');
		$emp_no = I('emp_no');
		$comment = I('comment');
		$zd_emp_no = I('zd_emp_no');
		if(!empty($zd_emp_no)){
			$user = M('flow')->where('id='.$flow_id)->field('emp_no')->find();
			// var_dump('aaa');die;
			$dept_id = M('user')->where($user)->field('dept_id')->find();
			
			$mapuser['dept_id'] = array('eq',$dept_id['dept_id']);
			$mapuser['peoplestatus'] = array('eq',0);
			$lists = M('user')->where($mapuser)->field('emp_no')->select();
			$lists = rotate($lists);
			
			if(in_array($zd_emp_no, $lists['emp_no'])){
				// $return['s'] = 1;
				// $return['secess'] = '选择指定人chenggong'; 
				// app_return($return);
			}else{
				$return['s'] = 0;
				$return['err'] = '选择指定人错误'; 
				app_return($return);
			}
			
			
		}

        if(empty($flow_id)){
			$return['s'] = 0;
			$return['err'] = 'ID不能为空'; 
			app_return($return);
		}else{
			$modelid = M("Flow");
			$whereid['id'] = $flow_id;
			$whereid['is_del'] = array('eq', 0);
			if($modelid -> where($whereid) -> select()){
				if(empty($emp_no)){
					$return['s'] = 0;
					$return['err'] = '审批人不能为空'; 
					app_return($return);
				}else{
					$model1 = M("Flow_log");
					$where1['flow_id'] = $flow_id;
					$where1['result'] = array('EXP','IS NULL');
					$where1['is_del'] = array('eq', 0);
					$flow_data1 = $model1 -> where($where1) -> field('flow_id','emp_no','user_name','step','result','create_time','update_time','comment') -> order("id") -> select();
					// dump($flow_data1);die;
					$shenpiren[] = '';
					$i=0;
					foreach ($flow_data1 as $k => $v) {
							$shenpiren[$i]['emp_no'] = $v['emp_no'];
							$i++;
							if($v['emp_no']== $emp_no){
								// var_dump($v['emp_no']);
								$step = $v['step'];
								break;	
							}
							
					}
					foreach ($shenpiren as $k => $v) {
						if($emp_no == $v['emp_no']){
							$a = 'a';
						}
					}
					if($a!='a'){
						$return['s'] = 0;
						$return['err'] = '没权限'; 
						app_return($return);
					}
					// var_dump('aaa');die;
				}
			}else{
				$return['s'] = 0;
				$return['err'] = '当前id有误'; 
				app_return($return);
				
			}
		}
		//app_return('判断好多了');
		if(empty($comment)){
			$return['s'] = 0;
			$return['err'] = '需要填写审核意见'; 
			app_return($return);
			
		}
		$model = D("FlowLog");
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		$model -> result = 1;
		$model -> comment = $comment;
		//$model -> data['emp_no'] = $emp_no;
		//$flow_id = $model -> flow_id;
		//$step = $model -> step;
		
		$model -> step = $step;
		$model -> emp_no = $emp_no;
		$model -> user_id = $model -> get_user_id($emp_no);
		$model -> user_name = $model -> get_user_name($emp_no);
        $model -> update_time = time(); 
        //dump($step);die();
		//保存当前数据对象
		//$list = $model -> save();
		$appmap['flow_id'] = array('eq', $flow_id);
		$appmap['emp_no'] = array('eq', $emp_no);
		$appmap['result'] = array('EXP','IS NULL');
		$appmap['is_del'] = array('eq', 0);
		//dump($model);
		$list = $model -> where($appmap) -> save();
		// app_return($model->getLastSql());
		//app_return($list);
		$modelflow = M("Flow");
		$data['id'] = $flow_id;
		$data['update_time'] = time();
		$modelflow->save($data);

		//保存当前数据对象  同级审批人 禁止重复审批
		$where['step'] = array('eq', $step);
		$where['flow_id'] = array('eq', $flow_id);
		$where['_string'] = 'result is null';
		$model -> where($where) -> setField('is_del', 1);

		if ($list !== false) {//保存成功

			D("Flow") -> next_step($flow_id, $step);
			
            $return['s'] = 1;
            $return['data'] = '操作成功!'; 
            app_return($return);
		} else {
			//失败提示
			//$this -> error('操作失败!');
            $return['s'] = 0;
            $return['err'] = '操作失败'; 
            app_return($return);
		}
		
	
	}
	
	
    /**
    * 用户否决接口
    * @desc 
    * 
    */
	public function reject() {
		$flow_id = I('flow_id');
		$emp_no = I('emp_no');
		$comment = I('comment');
        if(empty($flow_id)){
			$return['s'] = 0;
			$return['err'] = 'ID不能为空'; 
			app_return($return);
		}else{
			$modelid = M("Flow");
			$whereid['id'] = $flow_id;
			$whereid['is_del'] = array('eq', 0);
			if($modelid -> where($whereid) -> select()){
				if(empty($emp_no)){
					$return['s'] = 0;
					$return['err'] = '审批人不能为空'; 
					app_return($return);
		}else{
				$model1 = M("Flow_log");
				$where1['flow_id'] = $flow_id;
				$where1['result'] = array('EXP','IS NULL');
				$where1['is_del'] = array('eq', 0);
				$flow_data1 = $model1 -> where($where1) -> field('flow_id','emp_no','user_name','step','result','create_time','update_time','comment') -> order("id") -> select();
				//dump($flow_data1);die;
				
				$shenpiren[] = '';
					$i=0;
					foreach ($flow_data1 as $k => $v) {
							$shenpiren[$i]['emp_no'] = $v['emp_no'];
							$i++;
							if($v['emp_no']== $emp_no){
								// var_dump($v['emp_no']);
								$step = $v['step'];
								break;	
							}
							
					}
					foreach ($shenpiren as $k => $v) {
						if($emp_no == $v['emp_no']){
							$a = 'a';
						}
					}
					if($a!='a'){
						$return['s'] = 0;
						$return['err'] = '没权限'; 
						app_return($return);
					}
					// var_dump('aaa');die;
				
		}
			}else{
				$return['s'] = 0;
				$return['err'] = '当前id有误'; 
				app_return($return);
				
			}
		}
		//app_return('判断好多了');
		if(empty($comment)){
			$return['s'] = 0;
			$return['err'] = '需要填写审核意见'; 
			app_return($return);
			
		}
		$model = D("FlowLog");
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		/*
		if (in_array('user_id', $model -> getDbFields())) {
			$model -> user_id = $model -> get_user_id();
		};
		if (in_array('user_name', $model -> getDbFields())) {
			$model -> user_name = $model -> get_user_name();
		};*/
		$model -> result = 0;
		$model -> comment = $comment;
		
		$model -> user_id = $model -> get_user_id($emp_no);
		$model -> user_name = $model -> get_user_name($emp_no);
		$model -> flow_id = $flow_id;
		$model -> step = $step;
		
        $model -> update_time = time();
		$appmap['flow_id'] = array('eq', $flow_id);
		$appmap['emp_no'] = array('eq', $emp_no);
		$appmap['result'] = array('EXP','IS NULL');
		$appmap['is_del'] = array('eq', 0);
		$list = $model -> where($appmap) -> save();
        //var_dump($model->getLastSql());die;
		//可以裁决的人有多个人的时候，一个人评价完以后，禁止其他人重复裁决。
		$model = D("FlowLog");
		$where['step'] = array('eq', $step);
		$where['flow_id'] = array('eq', $flow_id);
		$where['_string'] = 'result is null';
		$model -> where($where) -> setField('is_del', 1);
		// var_dump($model->getLastSql());die;
        //dump($list); die();
		if ($list !== 0) {//保存成功

			M("Flow") -> where("id=$flow_id") -> setField('step', 0);
			$flow = M("Flow") -> find($flow_id);
            
            //返回审核结果
            $return['s'] = 1;
            $return['data'] = '否决成功!'; 
            app_return($return);
			//$this -> assign('jumpUrl', U('flow/folder', 'fid=confirm'));            
			//$this -> success('操作成功!');
		} else {
			//失败提示
			//$this -> error('操作失败!');
            $return['s'] = 0;
            $return['data'] = '否决失败!'; 
            app_return($return);
		}
	}
    /**
    * 退回接口
    * @desc 
    * 
    */
	function back_to($emp_no) {
		$flow_id = I('flow_id');
		$emp_no = I('emp_no');
		$comment = I('comment');
		$back_step = I('step');
		$back_emp_no = I('back_emp_no');
		
		//判断flowid是否为空 
        if(empty($flow_id)){
			$return['s'] = 0;
			$return['err'] = 'ID不能为空'; 
			app_return($return);
		}else{
			//判断flowid是否存在
			$modelid = M("Flow");
			$whereid['id'] = $flow_id;
			$whereid['is_del'] = array('eq', 0);
			if($modelid -> where($whereid) -> select()){
				//判断申请人是否为空
				if(empty($emp_no)){
					$return['s'] = 0;
					$return['err'] = '审批人不能为空'; 
					app_return($return);
				}else{
						//查询log记录获取step
						$model1 = M("Flow_log");
						$where1['flow_id'] = $flow_id;
						$where1['result'] = array('EXP','IS NULL');
						$where1['is_del'] = array('eq', 0);
						$flow_data1 = $model1 -> where($where1) -> field('flow_id','emp_no','user_name','step','result','create_time','update_time','comment') -> order("id") -> select();
						//dump($flow_data1);die;
						
						
						$shenpiren[] = '';
						$i=0;
						foreach ($flow_data1 as $k => $v) {
								$shenpiren[$i]['emp_no'] = $v['emp_no'];
								$i++;
								if($v['emp_no']== $emp_no){
									// var_dump($v['emp_no']);
									$step = $v['step'];
									break;	
								}
								
						}
						
						foreach ($shenpiren as $k => $v) {
							if($emp_no == $v['emp_no']){
								$a = 'a';
							}
						}
						if($a!='a'){
							$return['s'] = 0;
							$return['err'] = '没权限'; 
							app_return($return);
						}
				}
				
				if(empty($back_emp_no)){
					$return['s'] = 0;
					$return['err'] = '退回人不能为空'; 
					app_return($return);
				}else{
						$model2 = M("Flow_log");
						$where2['flow_id'] = $flow_id;
						$where2['result'] = array('EXP','IS NOT NULL');
						$where2['is_del'] = array('eq', 0);

						$flow_data2 = $model2 -> where($where2) -> field('flow_id','emp_no','user_name','user_id','step') -> order("id") -> select();
						//dump($flow_data2);die;
						
						foreach ($flow_data2 as $k2 => $v2) {
							$shenpi[] = $v2['emp_no'];
								/*
								if($v2['emp_no']!= $emp_no){
									$return['s'] = 0;
									$return['err'] = '当前人员没有权限'; 
									app_return($return);
								}else{
									$step = $v['step'];
									//var_dump($step);die;
								}*/
								
						}
						//var_dump($shenpi);die;
						$where3['id'] = array('eq', $flow_id);
						$where3['is_del'] = array('eq', 0);
						$shenqingren = $modelid -> where($where3) -> field('emp_no') -> find();
						//var_dump($shenqingren['emp_no']);die;
						//$shenpi[] = $shenqingren['emp_no'];
						//var_dump($shenpi);die;
						if(in_array($back_emp_no,$shenpi) || $back_emp_no == $shenqingren['emp_no']){
							
						}else{
							$return['s'] = 0;
							$return['err'] = '退回人信息有误'; 
							app_return($return);
						}
				}
			}else{
				$return['s'] = 0;
				$return['err'] = '当前id有误'; 
				app_return($return);
				
			}
		}

		//判断审核意见是否为空
		if(empty($comment)){
			$return['s'] = 0;
			$return['err'] = '需要填写审核意见'; 
			app_return($return);
			
		}
		//判断审核步骤是否为空
		if(empty($back_step)){
			$return['s'] = 0;
			$return['err'] = '需要填写退回步骤'; 
			app_return($return);
			
		}else{
			$whereback['flow_id'] = array('eq', $flow_id);
			$whereback['step'] = array('eq', $back_step);
			$whereback['emp_no'] = array('eq', $back_emp_no);
			$whereback['result'] = array('EXP','IS NOT NULL');
			$whereback['is_del'] = array('eq', 0);
			$model_log = M('flow_log')->where($whereback)->select();
			if(empty($model_log)){
				$return['s'] = 0;
				$return['err'] = '退回步骤与退回人不匹配'; 
				app_return($return);
			}
		}

		//退回
		$model = D("FlowLog");
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}

		/*
		if (in_array('user_id', $model -> getDbFields())) {
			$model -> user_id = get_user_id();
		};
		if (in_array('user_name', $model -> getDbFields())) {
			$model -> user_name = get_user_name();
		};*/
		
		$model -> user_id = $model -> get_user_id($emp_no);
		$model -> user_name = $model -> get_user_name($emp_no);

		$model -> flow_id = $flow_id;
		$model -> step = $step;
		
		$model -> result = 2;
		$model -> comment = $comment;
		//保存当前数据对象
		$appmap['flow_id'] = array('eq', $flow_id);
		$appmap['emp_no'] = array('eq', $emp_no);
		$appmap['result'] = array('EXP','IS NULL');
		$appmap['is_del'] = array('eq', 0);
		$list = $model -> where($appmap) -> save();
		if ($list !== false) {//保存成功
            //可以裁决的人有多个人的时候，一个人评价完以后，禁止其他人重复裁决。
            $where['step'] = array('eq', $step);
            $where['flow_id'] = array('eq', $flow_id);
            $where['_string'] = 'result is null';
            $model -> where($where) -> setField('is_del', 1);
			// var_dump($model->getLastSql());die;
            D("Flow") -> back_to($flow_id, $back_emp_no,$back_step);
            $return['s'] = 1;
            $return['data'] = '退回成功!'; 
            app_return($return);
        } else {
            //失败提示
            $return['s'] = 0;
            $return['data'] = '退回失败!'; 
            app_return($return);
        }
		
		
	}

	public function refer() {
		$model = D("FlowLog");
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		//保存当前数据对象
		$list = $model -> save();

		if ($list !== false) {//保存成功
			$this -> assign('jumpUrl', U('flow/folder', 'fid=receive_unread'));
			$this -> success('操作成功!');
		} else {
			//失败提示
			$this -> error('操作失败!');
		}
	}

	function send_refer($flow_id, $emp_list) {
		$emp_list = array_filter(explode(",", $emp_list));
		$model = D("Flow") -> send_to_refer($flow_id, $emp_list);
		$this -> success('发送成功');
	}

	function down($attach_id) {
		$this -> _down($attach_id);
	}
    
    

	public function field_manage($row_type) {
		$this -> assign("folder_name", "自定义字段管理");
		$this -> _field_manage($row_type);
	}
    
    public function get_field_list($type_id){
        $where['type_id']=array('eq',$type_id);
        $where['is_del']=0;
        $list = $this -> where($where) -> order('sort asc') -> select();
        return $list;
    }
    
    public function get_data_list($flow_id){
        $model=M("FlowFieldData");
        $where = "flow_id=$flow_id";
        $join = 'join ' . $this -> tablePrefix . 'flow_field field on field_id=field.id';
        $list = $model -> join($join) -> where($where) ->  order('sort asc') ->select();
        return $list;
    }
    
    public function view_add_file($add_file) {
        if (!empty($add_file)) {
            $files = array_filter(explode(';', $add_file));
            $files = array_map('think_decrypt', $files);

            $where['id'] = array('in', $files);
            $model = M("File");
            //$file_list = $model -> where($where) -> select();
            $file_list = $model -> where($where)-> field('name,savename,savepath')-> select();

            foreach ($file_list as $key => &$value){
                $return[$key]['name'] = $value['name'];
                $return[$key]['path'] = __ROOT__ . '/Uploads/Download/'. $value['savepath'] . $value['savename'];
            }            
            return  $return;
        }
        return $add_file;
    }
    
    /** 保存操作  **/
    function save() {
        $this -> _save();
    }
    
    protected function _save($name = CONTROLLER_NAME) {
        $opmode = "add";
        switch($opmode) {
            case "add" :
                $this -> _insert($name);
                break;
            case "edit" :
                $this -> _update($name);
                break;
            default :
                $this -> error("非法操作");
        }
    }

}
