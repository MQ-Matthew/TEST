<?php

namespace Home\Model;
use Think\Model;

class  FlowModel extends CommonModel {
    // 自动验证设置
    protected $_validate = array( array('name', 'require', '标题必须', 1), array('content', 'require', '内容必须'), );

    function _before_insert(&$data, $options) {
        $type = $data["type"];
        if($type==93){
            $dept_id = $data['dept_id'];
        }else{
            $dept_id = get_dept_id();
            $data['dept_id'] = $dept_id;
            $data['dept_name'] = get_dept_name();
            $data['emp_no'] = get_emp_no();
        }
        

        $doc_no_format = M("FlowType") -> where("id=$type") -> getField("doc_no_format");
        $short_dept = M("Dept") -> where("id=$dept_id") -> getField('short');
        $short_flow = M("FlowType") -> where("id=$type") -> getField('short');

        $sql = "SELECT count(*) count FROM `" . $this -> tablePrefix . "flow` WHERE type=$type ";
        $sql .= " and year(FROM_UNIXTIME(create_time))>=year(now())";

        if (strpos($doc_no_format, "{DEPT}") !== false) {
            $sql .= " and dept_id=" . $dept_id;
        }
        $rs = $this -> db -> query($sql);
        $count = $rs[0]['count'] + 1;

        if (strpos($doc_no_format, "{DEPT}") !== false) {
            $doc_no_format = str_replace("{DEPT}", $short_dept, $doc_no_format);
        }

        if (strpos($doc_no_format, "{SHORT}") !== false) {
            $doc_no_format = str_replace("{SHORT}", $short_flow, $doc_no_format);
        }

        if (strpos($doc_no_format, "{YYYY}") !== false) {
            $doc_no_format = str_replace("{YYYY}", date('Y', mktime()), $doc_no_format);
        }

        if (strpos($doc_no_format, "{YY}") !== false) {
            $doc_no_format = str_replace("{YY}", date('y', mktime()), $doc_no_format);
        }

        if (strpos($doc_no_format, "{M}") !== false) {
            $doc_no_format = str_replace("{M}", date('m', mktime()), $doc_no_format);
        }

        if (strpos($doc_no_format, "{D}") !== false) {
            $doc_no_format = str_replace("{D}", date('d', mktime()), $doc_no_format);
        }

        if (strpos($doc_no_format, "{#}") !== false) {
            $doc_no_format = str_replace("{#}", str_pad($count, 1, "0", STR_PAD_LEFT), $doc_no_format);
        }

        if (strpos($doc_no_format, "{##}") !== false) {
            $doc_no_format = str_replace("{##}", str_pad($count, 2, "0", STR_PAD_LEFT), $doc_no_format);
        }

        if (strpos($doc_no_format, "{###}") !== false) {
            $doc_no_format = str_replace("{###}", str_pad($count, 3, "0", STR_PAD_LEFT), $doc_no_format);
        }

        if (strpos($doc_no_format, "{####}") !== false) {
            $doc_no_format = str_replace("{####}", str_pad($count, 4, "0", STR_PAD_LEFT), $doc_no_format);
        }

        if (strpos($doc_no_format, "{#####}") !== false) {
            $doc_no_format = str_replace("{#####}", str_pad($count, 5, "0", STR_PAD_LEFT), $doc_no_format);
        }

        if (strpos($doc_no_format, "{######}") !== false) {
            $doc_no_format = str_replace("{######}", str_pad($count, 6, "0", STR_PAD_LEFT), $doc_no_format);
        }

        $data['doc_no'] = $doc_no_format;

    }

    function _after_insert($data, $options) {
        $id = $data['id'];

        if ($data['step'] == 20) {

            $where_step['flow_type_id'] = array('eq', $data['type']);
            $where_step['is_del'] = array('eq', 0);

            $flow_step = M('FlowStep') -> where($where_step) -> order('sort') -> select();
            
            $rs = false;
            if (!empty($flow_step)) {
                foreach ($flow_step as $step) {
                    $rs = D('Flow') -> check_step($step['condition'], $data['udf_data']);
                    
                    if ($rs) {
                        $data['confirm_name'] = $step['confirm_name'];
                        $data['consult_name'] = $step['consult_name'];

                        $data['confirm'] = $this -> _conv_auditor($step['confirm'],$data['dept_id'],$data);
                        $data['consult'] = $this -> _conv_auditor($step['consult'],'',$data);
                        
                        break;
                    }
                }
            }

            // dump($data['confirm']);die;
            if (!$rs) {
                $data['confirm'] = $this -> _conv_auditor($data['confirm'],'',$data);
                $data['consult'] = $this -> _conv_auditor($data['consult'],'',$data);
            }
            
            $data['update_time'] = time();
            if($data['type'] == 93){

                $data['confirm'] = rtrim(str_replace("|","&",$data['confirm']),'&').'|';
                $pos = strrpos($data['confirm'],'&');
                $data['confirm'] = substr_replace($data['confirm'],'|',"$pos-1","1");
            }
            
            // dump($data);die;
            $list = M("Flow") -> save($data);

            $this -> next_step($data['id'], 20);
        }
    }

    function _before_update(&$data, $options) {
        $flow_type = M("FlowType") -> find($data['type']);
        if (($flow_type['is_lock']) && ($data['step'] > 20)) {
            unset($data['confirm']);
            unset($data['consult']);
            unset($data['refer']);
        }
    }

    function _after_update($data, $options) {
        $id = $data['id'];
        $step = $data['step'];
        if ($data['step'] <= 20 && $data['step'] != '') {
            $where_step['flow_type_id'] = array('eq', $data['type']);
            $where_step['is_del'] = array('eq', 0);
            $flow_step = M('FlowStep') -> where($where_step) -> order('sort') -> select();

            $rs = false;
            if (!empty($flow_step)) {
                foreach ($flow_step as $val) {
                    $rs = D('Flow') -> check_step($val['condition'], $data['udf_data']);

                    if ($rs) {

                        $data['confirm_name'] = $val['confirm_name'];
                        $data['consult_name'] = $val['consult_name'];
                        $data['confirm'] = $this -> _conv_auditor($val['confirm']);
                        $data['consult'] = $this -> _conv_auditor($val['consult']);
                        break;
                    }
                }
            }

            if (!$rs) {

                $data['confirm'] = $this -> _conv_auditor($data['confirm']);
                $data['consult'] = $this -> _conv_auditor($data['consult']);
            }

            $list = M("Flow") -> save($data);
            $this -> next_step($data['id'], $step);
        }
    }

    function _get_dept($dept_id, $dept_grade) {
        $model = M("Dept");
        $dept = $model -> find($dept_id);
        if ($dept['dept_grade_id'] == $dept_grade) {
            return $dept_id;
        } else {
            if ($dept['pid'] != 0) {
                return $this -> _get_dept($dept['pid'], $dept_grade);
            }
        }
        return false;
    }
    /****
     * 函数：查找审核人
     * 修改日期：2017-2-23
     * 修改原因：为适应多用户身份，修改原查找User表dept_id，position_id字段，为查找identity字段
     *
     * @desc by Dokey
     */
    function _conv_auditor($val,$dp_id,$data) {
        // $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
        //             $txt = $maps['name'];
        //             fwrite($myfile, print_r($data['udf_data']));
        //             fclose($myfile);
        $arr_auditor = array_filter(explode("|", $val));
        $str_auditor = '';
        $dept_id = $dp_id;
        // dump($dp_id);die;
        foreach ($arr_auditor as $auditor) {
            //部门级别-职位
            if (strpos($auditor, "dgp") !== false) {

                $temp = explode("_", $auditor);
                $dept_grade = $temp[1];
                $position = $temp[2];
                if(!empty($dept_id)){
                    // $dept_id = $dept_id;
                }else{
                    $dept_id = $this -> _get_dept(get_dept_id(), $dept_grade);
                }
                
                $model = M("User");
                $where = array();
                //$where['dept_id'] = $dept_id;
                //$where['position_id'] = $position;
                if($position==99){
                    $where['emp_no'] = get_emp_no(); //发起人
                }else{
                    $where['identity'] =  array('like','%dp_'.$dept_id.'_'.$position.'|%');
                }
                $where['is_del'] = 0;
                
                $emp_list = $model -> where($where) -> select();
                //授权代理人替换
                // $emp_list = $this->check_agent($emp_list);
                // dump($emp_list);die;
                $emp_list = rotate($emp_list);

                if (!empty($emp_list)) {
                    $str_auditor .= implode(",", $emp_list['emp_no']) . "|";
                }
            }
            //部门—职位
            if (strpos($auditor, "dp") !== false) {
                $temp = explode("_", $auditor);
                $dept = $temp[1];
                $position = $temp[2];

                $model = M("User");
                $where = array();
                //$where['dept_id'] = $dept;
                //$where['position_id'] = $position;
                $where['identity'] =  array('like','%dp_'.$dept.'_'.$position.'|%');
                $where['is_del'] = 0;
                $emp_list = $model -> where($where) -> select();
                if($emp_list[0]['emp_no'] == 'keti'){
                    //课题人替换
                    $emp_list = $this->check_keti($data);
                    
                }
                //代理人替换
                // $emp_list = $this->check_agent($emp_list);
                // dump($emp_list);
                $emp_list = rotate($emp_list);

                if (!empty($emp_list)) {
                    $str_auditor .= implode(",", $emp_list['emp_no']) . "|";
                }
            }
            //部门
            if (strpos($auditor, "dept") !== false) {
                $temp = explode("_", $auditor);
                $dept = $temp[1];

                $model = M("User");
                $where = array();
                $where['dept_id'] = $dept;
                $where['is_del'] = 0;
                $emp_list = $model -> where($where) -> select();
                $emp_list = rotate($emp_list);
                if (!empty($emp_list)) {
                    $str_auditor .= implode(",", $emp_list['emp_no']) . "|";
                }
            }
            //个人
            if (strpos($auditor, "emp") !== false) {
                $temp = explode("_", $auditor);
                $emp = $temp[1];
                /*
                $myfile = fopen("log.txt", "w");
                fwrite($myfile, var_export($emp, true));
                fclose($myfile); */
                //$emp =  $this->check_agent($emp);
                $str_auditor .= $emp . "|";
            }

            if (strpos($auditor, "_") == false) {
                $str_auditor .= $auditor . "|";
            }
        }
        // die;
        return $str_auditor;
    }
    /***********
     * 代理人替换
     *
     * @desc
     */
    public function check_agent($a) {
        if (is_array($a)) {
            foreach($a as $k => $v){
                if(!empty($v['agent'])){
                    $a[$k]['emp_no'] = $v['agent'];
                }
            }
        }else{
            $map['emp_no'] = $a;
            $agent =  M("User")->where($map)->getField('agent');
            if(!empty($agent)){
                $a = $agent;
            }
        }
        return $a;
    }

    /***********
     * 课题人替换
     *
     * @desc
     */
    public function check_keti($data) {
        $maps['folder'] = 1;
        $ketiname = json_decode($data['udf_data'],true);
        
        if (!empty($ketiname[369])) {
            $maps['name'] = substr($ketiname[369],strpos($ketiname[369],'-')+1);
            // $maps['name'] = $ketiname[369];
        }else{
            $maps['name'] = substr($ketiname[380],strpos($ketiname[380],'-')+1);
            // $maps['name'] = $ketiname[380];
        }
        
        $val = M('Form')->where($maps)->find();
        $vals = json_decode($val['udf_data'],true);
        $kt_where['is_del'] = 0;
        $kt_where['emp_no'] = $vals['370'];
        $emp_list = M('User') -> where($kt_where) -> select();
        return $emp_list;
    }

    public function back_to($flow_id, $emp_no) {

        $model = D("FlowLog");
        $where['flow_id'] = $flow_id;
        $where['emp_no'] = $emp_no;
        $data['step'] = D("FlowLog") -> where($where) -> getField('step');
        if (empty($data['step'])) {
            $data['step'] = 19;
        }

        //设置当前审核业务为退回标记（19）
        M("Flow") -> where(array('id' => $flow_id)) -> setField('step', 19);
        $map['id'] = $flow_id;
        $user_emp_no =  M("Flow") ->where($map)-> getField('emp_no');
        //在审核记录表中增加下个审核人的记录,如果下个审核人是发起人，则不写记录
        //dump($emp_no.'--'.$user_emp_no);
        if($emp_no !== $user_emp_no){
            $data['flow_id'] = $flow_id;
            $data['emp_no'] = $emp_no;

            $model -> create($data);
            $model -> add();
        }
        $flow = M("Flow") -> find($flow_id);
        //发送通知消息
        $push_data['type'] = '审批';
        $push_data['action'] = '被退回';
        $push_data['title'] = $flow['name'];
        $push_data['content'] = '审核人：' . get_dept_name() . "-" . get_user_name();
        $push_data['url'] = U('Flow/read', "id={$flow_id}&return_url=Flow/index");

        $user_id = M("User") -> where(array('emp_no' => $emp_no)) -> getField("id");
        send_push($push_data, $user_id);
    }
    /**
     * @desc 下个节点处理函数
     * 参数 $flow_id 审核业务ID
     * 参数 $step 当前审核节点
     */
    public function next_step($flow_id, $step) {
        //首先查出审核人列表
        $confirm = M("Flow") -> where(array('id' => $flow_id)) -> getField("confirm");
        /*
		if (!empty($confirm) && ($step == 20)) {  //判断当前审核节点为审核步骤
			$confirm_list = array_filter(explode("|", $confirm));

            $is_include_presenter = array_search(get_emp_no(), $confirm_list);  //当前用户是否在审核人列表中
			//$is_include_presenter = 0;
			if ($is_include_presenter !== false) {
				$step = $step + 1 + $is_include_presenter;  //审核节点加1
			}
		} */
        
        $FlowLog = M('FlowLog');
        $emp_no = $this->duty_emp_no($flow_id, $step);
        // dump($emp_no);die;
        $arr = explode(',', $emp_no);
        // dump($arr);die;
        if(count($arr) > 1)
        {
            $where['flow_id'] = array('eq', $flow_id);
            $where['step'] = array('eq', $step);
            $where['_string'] = 'result is null';
            $FlowLog->where($where)->setField('is_del', 1);//只要一个人通过其他人不能看到这个申请
        }
        

        //处理会签功能，一般是有多个人，如果选择会签只有一个人，那这个人同意就是下个节点，如果是选择多个人会签，那就是需要多个人全部同意才能走下一个节点
        $arr = explode('&', $emp_no);
        if(count($arr) > 1)
        {
            foreach($arr as $val)
            {
                $val = $this->check_agent($val);
                $result = M('FlowLog')->where(array('flow_id'=>$flow_id, 'step'=>$step, 'emp_no'=>$val, 'is_del'=>0))->order('id desc')->getField('result');//为什么取最后一个，因为里面存在退回一个审核，里面同一个人会存在多条记录

                if($result != 1){
                    
                     return false;
                }

                   
            }
        }

        // dump('dsadsas');die;
        $model = D("Flow");

        //当前为审核步骤
        if (substr($step, 0, 1) == 2) {
            if ($this -> is_last_confirm($flow_id)) { //判断是否是最后一个审核人
                $model -> where(array('id' => $flow_id)) -> setField(array('step'=>'30','update_time'=>time()));

                $step = 30;
            } else {

                $step++;
                //$model -> where(array('id' => $flow_id)) -> setField('step', $step);
            }
        }
        //当前为执行步骤
        if (substr($step, 0, 1) == 3) {
            if ($this -> is_last_consult($flow_id)) { //判断是否是最后一个执行人
                $step = 40;
            } else {
                $step++;
                //$model -> where(array('id' => $flow_id)) -> setField('step', $step);
            }
        }

        if ($step == 40) {  //审核工作已结束
            $model -> where(array('id' => $flow_id)) -> setField(array('step'=>'40','update_time'=>time())); //审核表step节点为结束40标志

            //发通知消息
            $flow = M("Flow") -> find($flow_id);
            $push_data['type'] = '审批';
            $push_data['action'] = '审核通过';
            $push_data['title'] = $flow['name'];
            $push_data['content'] = '审核人：' . get_dept_name() . "-" . get_user_name();
            $push_data['url'] = U('Flow/read', "id={$flow_id}&return_url=Flow/index");

            send_push($push_data, $flow['user_id']);

        } else {   //审核工作未结束
            $data['flow_id'] = $flow_id;
            $data['step'] = $step;
            $data['emp_no'] = $this -> duty_emp_no($flow_id, $step);
            //下一个节点有多个审核人
            if (strpos($data['emp_no'], ",") !== false) {
                $emp_list = explode(",", $data['emp_no']);
                foreach ($emp_list as $emp) {
                    $data['emp_no'] = $emp;
                    $model = D("FlowLog");                   
                    $data['emp_no'] = $this->check_agent($data['emp_no']);  //替换代理人
                    $model -> create($data); //创建多个审核记录
                    $model -> add();
                }
            }
            elseif (strpos($data['emp_no'], "&") !== false)//一个节点多个人审核，是并且关系
            {
                $emp_list = explode("&", $data['emp_no']);
                foreach ($emp_list as $emp) {
                    $data['emp_no'] = $emp;
                    $model = D("FlowLog");
                    $data['emp_no'] = $this->check_agent($data['emp_no']);  //替换代理人 
                    $model -> create($data); //创建多个审核记录
                    $model -> add();
                }
            }
            else { //下一个节点只有一位审核人
                $model = D("FlowLog");
                $data['emp_no'] = $this->check_agent($data['emp_no']); //替换代理人
                $model -> create($data); //创建一个审核记录
                $model -> add();
            }
        }
    }
    /**
     * @desc
     * 返回下一步审核信息
     */
    public function next_step_info($flow_id, $step) {

        $confirm = M("Flow") -> where(array('id' => $flow_id)) -> getField("confirm");

        if (!empty($confirm) && ($step == 20)) {  //判断当前审核节点为审核步骤
            $confirm_list = array_filter(explode("|", $confirm));
            $is_include_presenter = array_search(get_emp_no(), $confirm_list);  //当前用户是否在审核人列表中

            //$is_include_presenter = 0;
            if ($is_include_presenter !== false) {
                $step = $step + 1 + $is_include_presenter;  //审核节点加1
            }
        }

        $model = D("Flow");
        if (substr($step, 0, 1) == 2) {
            if ($this -> is_last_confirm($flow_id)) {
                //$model -> where(array('id' => $flow_id)) -> setField('step', 30);
                $step = 30;
            } else {
                $step++;
            }
        }

        if (substr($step, 0, 1) == 3) {
            if ($this -> is_last_consult($flow_id)) {
                $step = 40;
            } else {
                $step++;
            }
        }


        if ($step == 40) {
            
            $data['flow_id'] = $flow_id;
            $data['step'] = 40;
            $data['emp_no'] = "";  
            return $data;
        } else {
            $data['flow_id'] = $flow_id;
            $data['step'] = $step;
            $data['emp_no'] = $this -> duty_emp_no($flow_id, $step);
            if (strpos($data['emp_no'], ",") !== false) {
                $emp_list = explode(",", $data['emp_no']);
                foreach ($emp_list as $emp) {
                    //$data['emp_no'] = $emp;
                    $data['emp_no'] = $this->check_agent($emp);  //替换代理人 
                    return $data;
                }
            } else {
                $data['emp_no'] = $this->check_agent($data['emp_no']);  //替换代理人
                return $data;
            }
        }
    }
    /**
     * 判定是否为最后一个审批人
     * 参数：$flow_id 流程ID
     * @desc
     *  2018-5-22 增加代理人验证
     */
    function is_last_confirm($flow_id) {
        $confirm = M("Flow") -> where(array('id' => $flow_id)) -> getField("confirm");
        if (empty($confirm)) {
            return true;
        }
        //审批人列表
        $confirm_list = array_filter(explode("|", $confirm));
        //找出最后一个审批人
        $last_confirm_emp_no = end($confirm_list);
        //是否有代理人
        $last_confirm_emp_no = $this->check_agent($last_confirm_emp_no);
        //如果最后一个审批人与当前用户一致
        if (strpos($last_confirm_emp_no, get_emp_no()) !== false) {
            // By Dokey 修改审批跳步的问题，查出审批记录中当前用户的节点step
            $map['flow_id'] = $flow_id;
            $map['emp_no'] = get_emp_no();
            $currentStep = M('Flow_log')->where($map)->order('step desc')-> getField("step");
            $endStep = 20 + count($confirm_list);
            //dump($endStep.'--'.$currentStep);die();
            if($endStep == $currentStep){
                return true;
            }else{
                return false;
            }
        }
        return false;
    }

    function is_last_consult($flow_id) {
        $consult = M("Flow") -> where(array('id' => $flow_id)) -> getField("consult");
        if (empty($consult)) {
            return true;
        }

        $consult_list = array_filter(explode("|", $consult));
        $last_consult_emp_no = end($consult_list);
        //是否有代理人 
        $last_consult_emp_no = $this->check_agent($last_consult_emp_no);
        if (strpos($last_consult_emp_no, get_emp_no()) !== false) {
            return true;
        }
        return false;
    }

    function duty_emp_no($flow_id, $step) {
        if (substr($step, 0, 1) == 2) {
            $confirm = M("Flow") -> where(array('id' => $flow_id)) -> getField("confirm");
            $arr_confirm = array_filter(explode("|", $confirm));
            // dump($arr_confirm[fmod($step, 10) - 1]);
            return $arr_confirm[fmod($step, 10) - 1];
        }

        if (substr($step, 0, 1) == 3) {
            $consult = M("Flow") -> where(array('id' => $flow_id)) -> getField("consult");
            $arr_consult = array_filter(explode("|", $consult));
            return $arr_consult[fmod($step, 10) - 1];
        }
    }
    //参阅
    function send_to_refer($flow_id, $emp_list) {
        $data['flow_id'] = $flow_id;
        $data['result'] = 1;
        $data['step'] = 100;
        $data['create_time'] = time();
        $data['from'] = get_user_name();

        foreach ($emp_list as $key => $val) {
            $data['emp_no'] = $val;
            $where_flow_log['flow_id'] = array('eq', $flow_id);
            $where_flow_log['emp_no'] = array('eq', $val);

            $flow_log = M("FlowLog") -> where($where_flow_log) -> select();
            if (!$flow_log) {
                D("FlowLog") -> add($data);
            } else {
                unset($emp_list[$key]);
            }
        }
        //设置审批步骤为100参阅
        M('Flow') -> where(array('id' => $flow_id)) -> setField(array('step'=>'100','update_time'=>time()));         
        /* 2017-12-19去掉交办合同管理员处理，在流程执行中加入合同管理员
        $flow_info = M('Flow')->field('type,consult')->where(array('id'=>$flow_id))->find();
        if($flow_info['type'] == 70)
        {
            $arr = explode('|', $flow_info['consult']);
            $arr[0] = implode(',', $emp_list);
            M('Flow')->where(array('id'=>$flow_id))->save(array('consult'=>implode('|', $arr)));//修改合同审批执行的时候还是同一个人
        } */ 
        $flow = M("Flow") -> find($flow_id);
        $push_data['type'] = '审批';
        $push_data['action'] = '需要您参阅';
        $push_data['title'] = $flow['name'];
        $push_data['content'] = '转发人：' . get_dept_name() . "-" . get_user_name();
        $push_data['url'] = U('Flow/read', "id={$flow_id}&return_url=Flow/index");

        $where_user_list['emp_no'] = array('in', $emp_list);
        $user_list = M("User") -> where($where_user_list) -> getField("id", true);
        send_push($push_data, $user_list);
    }

    function check_step($condition, $udf_data) {
        $condition = json_decode($condition, true);
        extract($condition);
        $udf_data = json_decode($udf_data, true);
        if (!empty($val_11)) {
            $rs_1 = $this -> check_condition($udf_data[$key_11], $filter_11, $val_11);
        }

        if (!empty($val_12)) {
            $rs_1 = $this -> check_condition($udf_data[$key_12], $filter_12, $val_12, $logic_12, $rs_1);
        }

        if (!empty($val_13)) {
            $rs_1 = $this -> check_condition($udf_data[$key_13], $filter_13, $val_13, $logic_12, $rs_1);
        }

        if (!empty($val_21)) {
            $rs_2 = $this -> check_condition($udf_data[$key_21], $filter_21, $val_21);
        }
        if (!empty($val_22)) {
            $rs_2 = $this -> check_condition($udf_data[$key_22], $filter_22, $val_22, $logic_22, $rs_2);
        }
        if (!empty($val_23)) {
            $rs_2 = $this -> check_condition($udf_data[$key_23], $filter_23, $val_23, $logic_23, $rs_2);
        }

        if (isset($rs_1)) {
            $rs = $rs_1;
        }

        if (isset($rs_2)) {
            if ($logic_group == 'and') {
                $rs = $rs and $rs_2;
            }
            if ($logic_group == 'or') {
                $rs = $rs or $rs_2;
            }
        }
        return $rs;
    }

    function check_condition($key, $filter, $val, $logic = 'and', $logic_val = true) {
        switch ($filter) {
            case 'eq' :
                $query = ($key == $val);
                break;
            case 'neq' :
                $query = ($key !== $val);
                break;             
            case 'gt' :
                $query = ($key > $val);
                break;
            case 'egt' :
                $query = ($key >= $val);
                break;
            case 'lt' :
                $query = ($key < $val);
                break;
            case 'elt' :
                $query = ($key <= $val);
                break;
            case 'like' :
                $query = (strpos($key, $val) !== false);
                break;
            case 'nolike' :
                $query = (strpos($key, $val) == false);
                break;
            case 'isnull' :
                $query = ($key == '');
                break;
            default :
                break;
        }
        if ($logic == 'and') {
            $query = ($query and $logic_val);
        }

        if ($logic == 'or') {
            $query = ($query or $logic_val);
        }

        return $query;
    }

}
?>