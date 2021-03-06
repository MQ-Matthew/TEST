<?php
/**
* @desc 奖金发放管理
* 
*  2018-06-06
*/

namespace Home\Controller;

class KnotlistController extends HomeController {
	protected $config = array('app_type' => 'common', 'read' => 'flow_detail,fist_send_flow,second_send_flow,bonus_user_collect,bonus_user,confirm_sheet,create_flow_log');

	//过滤查询字段
	function _search_filter(&$map) {
		$map['user_id'] = array('eq', get_user_id());
		$map['is_del'] = array('eq', '0');
		if (!empty($_POST['tag'])) {
			$map['tag'] = $_POST['tag'];
		}
		$keyword = I('keyword');
		if (!empty($keyword)) {
			$where['name'] = array('like', "%" . $keyword . "%");
			$where['office_tel'] = array('like', "%" . $keyword . "%");
			$where['office_tel'] = array('like', "%" . $keyword . "%");
			$where['_logic'] = 'or';
			$map['_complex'] = $where;
		}
	}

	function index() {
		$model = M("Knotlist");
		// $where['is_del'] = 0;
		$list = $model -> where($where) -> order('create_time desc')-> select();
		$this -> assign('list', $list);

		$this -> display();
		return;
	}
    //新建奖金项目
	function add() {
		$plugin['date'] = true;
		$plugin['uploader'] = true;
		$this -> assign("plugin", $plugin);
        
       $this -> display();
       return;
   }
   // 创建flow
   
    function create_flow_log(){
        $id = $_POST['id'];
        $model = M('Knotlist');
        $vo = $model -> find($id);
        switch ($vo['type']) {
            case '1':
                $udf_data['411'] = '内部调动使用'; //计划完成日期
                $udf_data_json = json_encode($udf_data, JSON_UNESCAPED_UNICODE);
                break;
            case '2':
                $udf_data['411'] = '解除合同使用'; //计划完成日期
                $udf_data_json = json_encode($udf_data, JSON_UNESCAPED_UNICODE);
                break;
            case '3':
                $udf_data['411'] = '退休使用'; //计划完成日期
                $udf_data_json = json_encode($udf_data, JSON_UNESCAPED_UNICODE);
                break;
            case '4':
                $udf_data['411'] = '系统内部调出使用'; //计划完成日期
                $udf_data_json = json_encode($udf_data, JSON_UNESCAPED_UNICODE);
                break;
            case '5':
                $udf_data['411'] = '外派人员使用'; //计划完成日期
                $udf_data_json = json_encode($udf_data, JSON_UNESCAPED_UNICODE);
                break;
            
            default:
                $udf_data['411'] = '援疆援藏使用'; //计划完成日期
                $udf_data_json = json_encode($udf_data, JSON_UNESCAPED_UNICODE);
                break;
        }
        // dump($vo);die;
        $data['emp_no'] = $vo['emp_no'];
        $data['user_id'] = $vo['user_id'];
        $data['user_name'] = $vo['user_name'];
        $data['name'] = $vo['name'];
        $data['content'] = $_POST['content'];
        $data['step'] = '20';
        $data['type'] = 93;
        $data['dept_id'] = $vo['dept_id'];
        $data['dept_name'] = get_dept_name($vo['dept_id']);
        $data["create_time"] = time();
        $data["update_time"] = time();
        $data["udf_data"] = $udf_data_json;
        // dump($data);die;
        
        $Flow = D('Flow');
        // dump($data);die;
        $rs = $Flow->add($data);

        $l_map['id'] = $id;
        $l_data['flow_id'] = $rs;
        $l_data['step'] = 20;
        
        $rs = $model->where($l_map)->save($l_data);
        if($rs){
            // dump('aaaaaaa');
            $this -> success("结清单审批流程创建成功!",U('Knotlist/read','id='.$id));
        }else{
            $this -> error('结清单审批流程创建失败!',U('Knotlist/read','id='.$id));
        }
        
    }
   
    

   // 结清单详情
   public function read(){
    $id = $_GET['id'];
    $model = M('Knotlist');
    $vo = $model -> find($id);
    $map['knotid'] = $id;
    // $map['content'] = array('neq',null);
    // $map['comment']  = array('exp',' is not NULL');
    $log_map['flow_id'] = $vo['flow_id'];
    $log_map['step'] = array('neq',20);
    $flow_map['id'] = $vo['flow_id'];
    if($vo['step']!=40){
        $step = M('Flow') -> where($flow_map) -> getfield('step');
        if($step == 40){
            $st['step'] = 40;
            $stepmap['id'] = $id;
            $model->where($stepmap)->save($st);
        }
    }
    $list = M('Knotcontent')->where($map)->select();
    
    $log = M('Flow_log')->where($log_map)->select();
    foreach ($log as $key => $value) {
        
        if(!empty($value['comment']) && $value['result'] == 1 && empty($list[$key]['comment'])){
            $map['id'] = $list[$key]['id'];
            $data['approver'] = $value['user_name'];
            $data['comment'] = $value['comment'];
            $data['flow_log_id'] = $value['id'];

            M('Knotcontent')->where($map)->save($data);
        }
    }
    
    $this -> assign('list', $list);
    $this -> assign('vo', $vo);
    $this -> assign('log', $log);
    $this -> display();
   }

   //保存清单
   function save(){
    // $_POST['user_id'] = get_user_id($_POST['agent']);
    // $_POST['user_name'] = get_user_name_empno($_POST['agent']);
    // $_POST['emp_no'] = $_POST['agent'];
    // $_POST['create_time'] = time();
    // unset($_POST['agent_name']);
    // unset($_POST['agent']);
    // $_POST['dept_id'] = get_dept_id($_POST['user_id']);
    // dump($_POST);die;
    $list_data['name'] = $_POST['name'];
    $list_data['title'] = $_POST['title'];
    $list_data['idcard'] = $_POST['idcard'];
    $list_data['place'] = $_POST['place'];
    $list_data['type'] = $_POST['type'];
    $list_data['user_id'] = get_user_id($_POST['agent']);
    $list_data['user_name'] = get_user_name_empno($_POST['agent']);
    $list_data['emp_no'] = $_POST['agent'];
    $list_data['create_time'] = time();
    $list_data['dept_id'] = get_dept_id($list_data['user_id']);
    // dump($_POST['matter']);die;
    // dump(get_dept_id());
    $model = M("Knotlist");
    $result = $model->add($list_data);
    if($result){
        if(empty($_POST['dept_name'][0])){
            $_POST['dept_id'][0] = get_dept_id($list_data['user_id']);
            $_POST['dept_name'][0] = get_dept_name($_POST['dept_id'][0]);
        }
        $data['knotid'] = $result;

        foreach ($_POST['dept_name'] as $key => $value) {
            $data['dept_name'] = $_POST['dept_name'][$key];
            $data['dept_id'] = $_POST['dept_id'][$key];
            $data['matter'] = $_POST['matter'][$key];
            
            // $data['approver'] = get_department_head_user_name_byid($_POST['dept_id'][$key]);
            M('Knotcontent')->add($data);
        }
        
    }
    
    
    
    $this->success('操作成功！',U('Knotlist/index'));
   }
   
   /**
    * @desc 
    * 复审进度详情页面
    */
   function flow_detail($id){
        //获取复审的审批项目id
    $list = M('Bonus')->where('id='.$id)->getField('second_flow_id');

    $map['id'] = array('in',$list);
    $map['is_del'] = 0;
    $flow_list = M('Flow')->where($map)->select();

    $this -> assign("id", $id);
    $this -> assign("list", $flow_list);
    $this -> display();
    return;        
}


function del($id) {
    $where['id'] = $id;
    $c_where['knotid'] = $id;
    $rs = M('Knotlist') -> where($where)->delete();
    $ct = M('Knotcontent') -> where($c_where)->delete();

 //    $count = $this -> _del($id, CONTROLLER_NAME, true);
 //    if ($count) {
 //     $model = D("SystemTag");
 //     $result = $model -> del_data_by_row($id);
 //    }

	// if ($count !== false) {//保存成功
	// 	$this -> assign('jumpUrl', get_return_url());
	// 	$this -> success("成功删除{$count}条!");
	// } else {
	// 	//失败提示
	// 	$this -> error('删除失败!');
	// }
    if($rs && $ct){
        $this -> assign('jumpUrl', get_return_url());
        $this -> success("成功删除!");
    }else{
        $this -> error('删除失败!');
    }
}

	

	protected function _insert($name = 'Bonus') {
		$model = D('Bonus');
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		//$model -> letter = get_letter($model -> name);
		//保存当前数据对象
		$list = $model -> add();
		if ($list !== false) {//保存成功
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('新增成功!');
		} else {
			//失败提示
			$this -> error('新增失败!');
		}
	}

	protected function _update($name = 'Bonus') {
		$id = $_POST['id'];
		$model = D("Bonus");
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		//$model -> letter = get_letter($model -> name);

		// 更新数据
		$list = $model -> save();
		if (false !== $list) {
			//成功提示
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('编辑成功!');
		} else {
			//错误提示
			$this -> error('编辑失败!');
		}
	}

	
    /**
     * 获取分组部门人员合计
     * @param  [type] $group_id [组ID]
     * @param  [type] $dept_id  [部门ID]
     * @return [type] [返回人员数量]
     */
    private function get_group_dept($group_id,$dept_id){
    	//$map['is_del'] = 0; 
    	$map['group_id'] = $group_id;
    	$map['dept_id'] = $dept_id;    
    	$user_count = M("Group_user")->where($map)->count();        
        //dump($user_count);
    	return $user_count;
    }

    private function get_group_amount($group_id,$dept_id){
        if($dept_id != false){
            $map['dept_id'] = $dept_id; 
        }         
        $map['group_id'] = $group_id;   
        $sum = M("Group_user")->where($map)->sum('amount');        
        //dump($sum);
        return $sum;
    }

    private function get_group_conut($group_id){
        //$map['is_del'] = 0; 
        $map['group_id'] = $group_id;  
        $user_count = M("Group_user")->where($map)->count();       
        //dump($sum);
        return $user_count;
    }

    private function get_group_username($group_id){
        //$map['is_del'] = 0; 
        $map['group_id'] = $group_id;  
        $userlist= M("Group_user")->where($map)->Field('user_name,user_id')->select();       
        //dump($userlist);die();
        return $userlist;
    }

    //获取特殊人员名单
    private function get_special_username($dept_id){
    	//$map['is_del'] = 0; 
        $map['group_id'] = array('neq',4); //不计算普通人员
        $map['dept_id'] = $dept_id;    
        $user_list = M("Group_user")->where($map)->field('user_name,group_id')->select();
        //dump($user_list);
        foreach($user_list as $uval){
        	$m = $uval['user_name'].'('.$this->get_groupname($uval['group_id']).'),'.$m;
        }
        //dump($m);
        return $m;
    }

    private function get_groupname($group_id){           
        $groupname = M("Group")->where('id='.$group_id)->getfield('name');        
        return $groupname;
    }

    /**
     * [奖金发放初审流程发起]
     * @param  [type] $id [奖金项目ID]
     * @return [type]     [description]
     */
    function fist_send_flow($id,$flow_type){
    	//dump($flow_type);die();
    	$Bonus = M('Bonus');
    	$bs = $Bonus->where('id='.$id)->find();
    	//审批流程(奖金初审typeid)
    	$Flow_type = M('Flow_type')->where('id='.$flow_type)->find();
    	//dump($Flow_type);die();
    	$data["name"] = $bs['name'];
    	$data["type"] = $flow_type; //初审的流程ID
    	$data["content"] = $bs['tb_content']; //申请单    	
    	$data["user_id"] = get_user_id();
    	$data["user_name"] = get_user_name();
    	$data["emp_no"] = get_emp_no();
    	$data["dept_id"] = get_dept_id();
    	$data["dept_name"] = get_dept_name();
    	$data["create_time"] = time();
    	$data["update_time"] = time();
    	$data["step"] = "20"; //审批中
    	$data["confirm"] = $Flow_type['confirm']; //审批人
    	$data["confirm_name"] = $Flow_type['confirm_name']; //审批人列表
    	$data["consult"] = $Flow_type['consult']; //执行人
    	$data["consult_name"] = $Flow_type['consult_name']; //执行人列表

    	$Flow = D('Flow');
    	$rs = $Flow->add($data);
    	//dump($rs);die();
    	if($rs){
    		$ls = $Bonus->where('id='.$id)->setField('fist_flow_id',$rs); //更新
    		if ($ls !== false) {
    			$this -> assign('jumpUrl', get_return_url());
    			$this -> success('提交审核成功!');
    		} else {
    			$this -> error('提交审核失败!');
    		}
    	}

    }


    /**
     * 提交科室主任复审流程
     * 
     */
    function second_send_flow($id,$flow_type){

        $bs = M('Bonus')->where('id='.$id)->find();
        //分组列表
        $GroupList = M('Group')->field('id,name,amount')->order('sort')->select();
    	//部门列表
        $deptList = M('Dept')->where('pid=1 and id!=23')->field('id,name')->order('sort')->select();
        
        $second_flow_id = '';
        foreach($deptList as $dval){
                //审批流程(奖金复审) 
            $Flow_type = M('Flow_type')->where('id='.$flow_type)->find();
            $flowdata["name"] = $bs['name'] . '('. $dval['name'].')';
                $flowdata["type"] = $flow_type; //复审的流程ID
                //$flowdata["content"] = 'XXOO'; //申请单        
                $flowdata["user_id"] = get_user_id();
                $flowdata["user_name"] = get_user_name();
                $flowdata["emp_no"] = get_emp_no();
                $flowdata["dept_id"] = get_dept_id();
                $flowdata["dept_name"] = get_dept_name();
                $flowdata["create_time"] = time();
                $flowdata["update_time"] = time();
                $flowdata["step"] = "20"; //审批中
                $flowdata["confirm"] = $Flow_type['confirm']; //审批人
                $flowdata["confirm_name"] = $Flow_type['confirm_name']; //审批人列表
                $flowdata["consult"] = $Flow_type['consult']; //执行人
                $flowdata["consult_name"] = $Flow_type['consult_name']; //执行人列表
                $flowdata["udf_data"] = $this->json_udf_data($id,$dval['id'],4,$flow_type); //普通用户奖金表
                //dump($flowdata);die();
                $Flow = D('Flow');
                $flow_id = $Flow->add($flowdata); //保存审批项目
                $second_flow_id = $flow_id .','. $second_flow_id;                 
            }
            //dump($second_flow_id);
            if($second_flow_id){
                   $rs = M('Bonus')->where('id='.$id)->setField('second_flow_id',$second_flow_id); //更新

                   $this -> assign('jumpUrl', get_return_url());
                   $this -> success('提交复审成功!'); 
               }

           }
    /**
    * @desc  组合自定义字段JSON数据
    * $bonus 奖金项目ID
    * $dept 部门ID
    * $group 奖金分组ID
    * $flow_type 审批流程类型ID
    */
    function json_udf_data($bonus,$dept,$group,$flow_type){
        //获取奖金项目信息
        $bs = M('Bonus')->where('id='.$bonus)->find();

        //获取分组人员列表
        //$map['is_del'] = 0; 
        $map['group_id'] = $group;
        $map['dept_id'] = $dept;    
        $user_list = D("Group_user")->where($map)->select();
        
        //dump($user_list);die();
        $dept_total = '';
        $uarray = array();
        $user_array =  array();
        foreach($user_list as $key=> $val){
            $uarray[$key]['wu_no'] = $key + 1;
            $uarray[$key]['wu_name'] = $val['user_name'];
            $uarray[$key]['wu_amount'] = $val['amount'];
            $uarray[$key]['wu_remark'] = $this->get_groupname($val['group_id']);
            $uarray[$key]['wu_empno'] = $val['emp_no'];
            $dept_total = $val['amount'] + $dept_total;           
        }
        //dump($uarray);die();
        $user_array['data'] = $uarray;
        $user_json = json_encode($user_array, JSON_UNESCAPED_UNICODE);
        //dump($user_json);
        $udf_data = array();
        $udf_data['1'] = $bs['date'];  //奖金发放日期
        $udf_data['2'] = get_dept_name($dept); //部门名称
        $udf_data['3'] = $user_json;  //员工奖金表
        $udf_data['4'] = $dept_total; //合计 
        $udf_data['5'] = $bs['plan_date']; //计划完成日期
        $udf_data_json = json_encode($udf_data, JSON_UNESCAPED_UNICODE);
        //dump($udf_data_json);
        return $udf_data_json;
    }
    /**
     * 用户奖金汇总
     * @return [type] [description]
     */
    function bonus_user_collect($id){
        //奖金项目
        $blist = M('Bonus')->where('id='.$id)->find();
        //初审特殊人员汇总
        $sql['group_id'] = array('not in','4');
        $vlist = D('Group_user')->where($sql)->select();
        //dump($vlist);die();
        foreach ($vlist as $val) {
            $data = array();
            $data['bonus_id'] = $blist['id'];
            $data['date'] = $blist['date'];
            $data['emp_no'] = $val['emp_no'];
            $data['user_name'] = $val['user_name'];
            $data['user_id'] = $val['user_id'];
            $data['dept_id'] = $val['dept_id'];
            $data['dept_name'] = get_dept_name($val['dept_id']);
            $data['amount'] = $val['amount'];
            $data['remark'] = $this->get_groupname($val['group_id']);
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['sort'] = $val['sort'];
            //dump($data); die();           
            $where['bonus_id'] = $data['bonus_id'];
            $where['emp_no'] = $data['emp_no'];
            $rs = M('Bonus_user')->where($where)->find();
                //查看是否已有记录                
            if(!$rs){               
                $list = M('Bonus_user') -> add($data);
                //$this -> success($data['user_name'].'工资添加成功!'); 
            }
        }
        
        //复审普通人员汇总
        $map['id'] = array('in',$blist['second_flow_id']);
        $map['is_del'] = 0;
        $map['step'] = 40; //只列出审批完成的项目
        $flow_list = M('Flow')->where($map)->select();
        //dump($flow_list);
        foreach ($flow_list as $value) {
            //$data['bonus_id'] = $id; //奖金项目ID
            //$darray = array();
            $darray = json_decode($value['udf_data'],true);            
            $uarray = json_decode($darray['3'],true); //人员列表
            //dump($uarray);die();
            foreach ($uarray['data'] as $v) {
                $data = array();
                //部门人员列表
                $data['bonus_id'] = $blist['id'];
                $data['date'] = $blist['date'];
                $data['emp_no'] = $v['wu_empno'];
                $data['user_name'] = $v['wu_name'];
                $data['user_id'] = get_user_id($data['emp_no']);
                $data['dept_id'] = $this->get_dept_id($data['user_id']);
                $data['dept_name'] = get_dept_name($data['dept_id']);
                $data['amount'] = $v['wu_amount'];
                $data['remark'] = $v['wu_remark'];
                $data['create_time'] = time();
                $data['update_time'] = time();
                $data['sort'] = $this->get_user_sort($data['user_id']);
                //dump($data);
                $where['bonus_id'] = $data['bonus_id'];
                $where['emp_no'] = $data['emp_no'];
                $rs = M('Bonus_user')->where($where)->find();
                //查看是否已有记录                
                if(!$rs){
                    $list = M('Bonus_user') -> add($data);
                    //$this -> success($data['user_name'].'工资添加成功!'); 
                }
            }

        }
        $this -> success('人员工资汇总完成!');

    }
    /**
     * 查看人员奖金列表
     * @param  $id [奖金项目ID]
     */
    function bonus_user($id){

        $map['bonus_id'] = $id;

        $model = D("Bonus_user");
        if (I('mode') == 'export') {
            $this -> _bonus_user_export($model, $map,'sort');
        } else {
            $this -> _list($model, $map, 'sort');
        }
        $this -> display();
    }

    /**
     * 导出奖金发放Excel文件
     */
    private function _bonus_user_export($model, $map,$sort) {

        $list = $model -> where($map) -> order($sort)->select();
        $date = $list[0]['date'];

        Vendor('Excel.PHPExcel');
        //导入thinkphp第三方类库

        // $inputFileName = "Public/templete/customer.xlsx";
        // $objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel -> getProperties() -> setCreator("综合审批") -> setLastModifiedBy("综合审批") -> setTitle("Office 2007 XLSX Test Document") -> setSubject("Office 2007 XLSX Test Document") -> setDescription("Test document for Office 2007 XLSX, generated using PHP classes.") -> setKeywords("office 2007 openxml php") -> setCategory("Test result file");
        // Add some data
        $i = 2;
        //dump($list);
        $objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("A1", "监测中心".$date."奖金发放表"); 
        $objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("A2", "序号") ->  setCellValue("B2", "部门") -> setCellValue("C2", "姓名") -> setCellValue("D2", "金额") ;
        foreach ($list as $val) {
            $i++;
            $objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("A$i", $i-2) -> setCellValue("B$i", $val["dept_name"]) -> setCellValue("C$i", $val["user_name"]) -> setCellValue("D$i", $val["amount"]);
        }
        // Rename worksheet
        $objPHPExcel -> getActiveSheet() -> setTitle($date.'奖金');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel -> setActiveSheetIndex(0);

        $file_name = $date."奖金发放.xlsx";
        // Redirect output to a client’s web browser (Excel2007)
        header("Content-Type: application/force-download");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition:attachment;filename =" . str_ireplace('+', '%20', URLEncode($file_name)));
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter -> save('php://output');
        exit ;
    }


    //奖金审批单
    function confirm_sheet($id){
        $list = M('Knotlist')->where('id='.$id)->find();
        $content = M('Knotcontent')->where('knotid='.$id)->select();
        $this -> assign("content", $content);
        $this -> assign("list", $list);
        //$this -> display();
        $html = $this -> fetch("confirm_sheet");
        //echo($html);die();
        import("Org.Tcpdf.Fpdf");

        $pdf = new \MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        // 设置页眉和页脚字体
        $pdf->setHeaderFont(Array('stsongstdlight', '', '10'));
        $pdf->setFooterFont(Array('helvetica', '', '8'));
        //$pdf->AddPage(); 
        // 设置默认等宽字体
        $pdf->SetDefaultMonospacedFont('courier');
        // 设置间距
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);
        // 设置分页
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setFontSubsetting(true);
        //设置字体
        $pdf->SetFont('stsongstdlight', '', 14);
        //$pdf->AddPage();
        $pdf->setprotection(array('modify', 'copy', 'annot-forms'), '');
        $delimiter = '<hr>';
        $chunks = explode($delimiter, $html);
        $cnt = count($chunks);
        for ($i = 0; $i < $cnt; $i++) {

            if ($i < $cnt) {
                $pdf->AddPage(); //设置页数的
            }

            $pdf->writeHTML($chunks[$i], true, false, true, false, '');
        }
        
        $pdf->lastPage();

        //  $order_number = date('Ymd', $vo['create_time']) . '-' . $vo['id'];
        $order_number =  'jieqing';
        $order_number.=".pdf";

        //  echo $order_number;exit();
        ob_end_clean();
        //输出PDF
        $pdf->Output($order_number, 'D');
        //$pdf->Output('t.pdf', 'I');
        exit();
    }
    
    function get_user_sort($uid){
        $rs = M('Group_user')->where('user_id='.$uid)->getField('sort');
        return $rs;
    }
    function get_dept_id($uid){
        $rs = M('Group_user')->where('user_id='.$uid)->getField('dept_id');
        return $rs;
    }
    
    
}
?>