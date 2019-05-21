<?php

namespace Home\Controller;

class ReportController extends HomeController {
	protected $config = array('app_type' => 'personal');
	//过滤查询字段
	function _search_filter(&$map) {
		if (!empty($_POST["keyword"])) {
			$map['name'] = array('like', "%" . $_POST['keyword'] . "%");
		}        
	}

	public function upload() {
		$this -> _upload();
	}
    
    /**
    * @desc 财务代发查询页面
    * 
    */
    public function daifa() {
        //初始化页面日期插件
        $plugin['date'] = true;
        $this -> assign("plugin", $plugin);
        //设置开始和结束时间
        $begin_time = '2017-01-01';
        $end_time = '2017-12-31';
        if (!empty($_POST)){
             $begin_time = $_POST['be_create_time'];
             $end_time = $_POST['en_create_time'];
        }
        //查询条件                
        $map['is_del'] = 0 ;
        $map['step'] = 40;  //已完成的审批项目
        $map['type'] = 72; //代发申请Flow_id
        //获取用户自定义数据
        $udf_data = M("Flow")->where($map)->getField('udf_data',true);
        //dump($udf_data);
        $list_array = '';
        $i = 0;   //数组索引
        foreach($udf_data as $key=>$value){
            $json_arr = json_decode($value,true);
            $json_data= json_decode($json_arr['326'],true);
            $tmp_date = $json_arr['323']; //代发年月

            //日期在查询日期范围内
            if(strtotime($begin_time) <= strtotime($tmp_date) && strtotime($tmp_date) <= strtotime($end_time)){
                foreach($json_data['data'] as $k=>$v){
                    $list_array[] = $v;
                    $list_array[$i]['wu_date'] = $tmp_date;
                    $i=$i+1;
                }
            }
        }
        //dump($list_array);
        $this -> assign('begin_time', $begin_time);
        $this -> assign('end_time', $end_time);          
        $this -> assign('list', $list_array);
        $this -> display();
    }
    /**
    * @desc 财务代发统计页面
    */
    public function daifatongji() {
        //初始化页面日期插件
        $plugin['date'] = true;
        $this -> assign("plugin", $plugin);
        //设置开始和结束时间
        $begin_time = '2017-01-01';
        $end_time = '2017-12-31';
        if (!empty($_POST)){
             $begin_time = $_POST['be_create_time'];
             $end_time = $_POST['en_create_time'];
        }
        
        $map['is_del'] = 0 ;
        $map['step'] = 40;
        $map['type'] = 72;
        $udf_data = M("Flow")->where($map)->getField('udf_data',true);
        //dump($udf_data);
        $list_array = '';
        $i = 0;
        foreach($udf_data as $key=>$value){
            $json_arr = json_decode($value,true);
            $json_data= json_decode($json_arr['326'],true);
            $tmp_date = $json_arr['323'];  //代发年月
            //判断查询日期
            if(strtotime($begin_time) <= strtotime($tmp_date) && strtotime($tmp_date) <= strtotime($end_time)){
                foreach($json_data['data'] as $k=>$v){
                    $list_array[] = $v;
                    $list_array[$i]['wu_date'] = $tmp_date;
                    $i=$i+1;
                }
            }
        }
        $tongji_array = Array();
        $tongji_array['在职人员']['奖金'] = 0;
        $tongji_array['在职人员']['绩效'] = 0;
        $tongji_array['在职人员']['劳务合作人员工资'] = 0;
        $tongji_array['在职人员']['抚恤金'] = 0;
        $tongji_array['在职人员']['丧葬费'] = 0;
        $tongji_array['在职人员']['补发工资'] = 0;
        $tongji_array['在职人员']['物业补贴'] = 0;
        $tongji_array['在职人员']['采暖补贴'] = 0;
        $tongji_array['在职人员']['公园年票'] = 0;
        $tongji_array['在职人员']['监测津贴'] = 0;
        $tongji_array['在职人员']['福利费'] = 0;
        $tongji_array['在职人员']['订报费'] = 0;
        $tongji_array['在职人员']['职称评审费'] = 0;
        $tongji_array['在职人员']['生育医疗费'] = 0;
        $tongji_array['在职人员']['节日补贴'] = 0;
        $tongji_array['在职人员']['其他'] = 0;
        $tongji_array['离休人员']['奖金'] = 0;
        $tongji_array['离休人员']['绩效'] = 0;
        $tongji_array['离休人员']['劳务合作人员工资'] = 0;
        $tongji_array['离休人员']['抚恤金'] = 0;
        $tongji_array['离休人员']['丧葬费'] = 0;
        $tongji_array['离休人员']['补发工资'] = 0;
        $tongji_array['离休人员']['物业补贴'] = 0;
        $tongji_array['离休人员']['采暖补贴'] = 0;
        $tongji_array['离休人员']['公园年票'] = 0;
        $tongji_array['离休人员']['监测津贴'] = 0;
        $tongji_array['离休人员']['福利费'] = 0;
        $tongji_array['离休人员']['订报费'] = 0;
        $tongji_array['离休人员']['职称评审费'] = 0;
        $tongji_array['离休人员']['生育医疗费'] = 0;
        $tongji_array['离休人员']['节日补贴'] = 0;
        $tongji_array['离休人员']['其他'] = 0;
        $tongji_array['退休人员']['奖金'] = 0;
        $tongji_array['退休人员']['绩效'] = 0;
        $tongji_array['退休人员']['劳务合作人员工资'] = 0;
        $tongji_array['退休人员']['抚恤金'] = 0;
        $tongji_array['退休人员']['丧葬费'] = 0;
        $tongji_array['退休人员']['补发工资'] = 0;
        $tongji_array['退休人员']['物业补贴'] = 0;
        $tongji_array['退休人员']['采暖补贴'] = 0;
        $tongji_array['退休人员']['公园年票'] = 0;
        $tongji_array['退休人员']['监测津贴'] = 0;
        $tongji_array['退休人员']['福利费'] = 0;
        $tongji_array['退休人员']['订报费'] = 0;
        $tongji_array['退休人员']['职称评审费'] = 0;
        $tongji_array['退休人员']['生育医疗费'] = 0;
        $tongji_array['退休人员']['节日补贴'] = 0;
        $tongji_array['退休人员']['其他'] = 0;            
               
        foreach ($list_array as $li){
            //dump($li);
            foreach($tongji_array as $ke => $vj){
                if($li['wu_type'] == $ke){
                    foreach($vj as $kkk => $jjj){
                        if($li['wu_class'] == $kkk){
                            $tongji_array[$ke][$kkk] +=  $li['wu_total'];
                        }                        
                    }
                }
            }
        }
        //计算分项合计
        foreach ($tongji_array as $k_total => $v_total){
            foreach($v_total as $v_total){
                $tongji_array[$k_total]['合计'] += $v_total;
            }
            
        }    
        //dump($tongji_array);
        $this -> assign('begin_time', $begin_time);
        $this -> assign('end_time', $end_time);        
        $this -> assign('list', $tongji_array);
        $this -> display();
    }
    
    /**
    * @desc 工资变动查询页面
    * 
    */
    public function gongzi() {
        //初始化页面日期插件
        $plugin['date'] = true;
        $this -> assign("plugin", $plugin);
        //设置开始和结束时间
        $begin_time = '2017-01-01';
        $end_time = '2017-12-31';
        if (!empty($_POST)){
             $begin_time = $_POST['be_create_time'];
             $end_time = $_POST['en_create_time'];
        }
        $map['is_del'] = 0;
        $map['step'] = 40;  //已完成的审批
        $map['type'] = 73; //Flow_id审批项目ID
        $udf_data = M("Flow")->where($map)->getField('udf_data',true);
        //dump($udf_data);
        $list_array = '';
        $i = 0;
        foreach($udf_data as $key=>$value){
            $json_arr = json_decode($value,true);
            $json_data= json_decode($json_arr['329'],true);
            $tmp_date = $json_arr['327']; //工资变动年月
            //dump($json_arr);
            if(strtotime($begin_time) <= strtotime($tmp_date) && strtotime($tmp_date) <= strtotime($end_time)){
                foreach($json_data['data'] as $k=>$v){
                    $list_array[] = $v;
                    $list_array[$i]['wu_date'] = $tmp_date;
                    $i=$i+1;
                }
            }
        }
        //dump($list_array);
        $this -> assign('begin_time', $begin_time);
        $this -> assign('end_time', $end_time);
        $this -> assign('list', $list_array);
        $this -> display();
    }
    /**
    * @desc 工资变动统计页面
    * 
    */
    public function gongzitongji() {
        //初始化页面日期插件
        $plugin['date'] = true;
        $this -> assign("plugin", $plugin);
        //设置开始和结束时间
        $begin_time = '2017-01-01';
        $end_time = '2017-12-31';
        if (!empty($_POST)){
             $begin_time = $_POST['be_create_time'];
             $end_time = $_POST['en_create_time'];
        }
        $map['is_del'] = 0;
        $map['step'] = 40;
        $map['type'] = 73;
        $udf_data = M("Flow")->where($map)->getField('udf_data',true);
        //dump($udf_data);
        $list_array = '';
        $i = 0;
        foreach($udf_data as $key=>$value){
            $json_arr = json_decode($value,true);
            $json_data= json_decode($json_arr['329'],true);
            $tmp_date = $json_arr['327'];
            if(strtotime($begin_time) <= strtotime($tmp_date) && strtotime($tmp_date) <= strtotime($end_time)){
                foreach($json_data['data'] as $k=>$v){
                    $list_array[] = $v;
                    $list_array[$i]['wu_date'] = $tmp_date;
                    $i=$i+1;
                }
            }
        }
        $tongji_array = Array();
        $tongji_array['在职人员']['增发工资'] = 0;
        $tongji_array['在职人员']['停发工资'] = 0;
        $tongji_array['在职人员']['岗位工资变动'] = 0;
        $tongji_array['在职人员']['薪级工资变动'] = 0;
        $tongji_array['在职人员']['岗位补贴变动'] = 0;
        $tongji_array['在职人员']['临时性补贴变动'] = 0;
        $tongji_array['在职人员']['托儿补变动'] = 0;
        $tongji_array['在职人员']['综合补助变动'] = 0;
        $tongji_array['在职人员']['站房租扣款变动'] = 0;
        $tongji_array['在职人员']['其他变动（需备注）'] = 0;
        $tongji_array['离休人员']['增发工资'] = 0;
        $tongji_array['离休人员']['停发工资'] = 0;
        $tongji_array['离休人员']['基本离休费变动'] = 0;
        $tongji_array['离休人员']['离休补贴变动'] = 0;
        $tongji_array['离休人员']['护理及高龄补助费变动'] = 0;
        $tongji_array['离休人员']['用车包干费变动'] = 0;
        $tongji_array['离休人员']['医疗补助费变动'] = 0;
        $tongji_array['离休人员']['站房租扣款变动'] = 0;
        $tongji_array['离休人员']['其他变动（需备注）'] = 0;
        $tongji_array['退休人员']['增发工资'] = 0;
        $tongji_array['退休人员']['停发工资'] = 0;
        $tongji_array['退休人员']['年龄补贴变动'] = 0;
        $tongji_array['退休人员']['劳模补贴变动'] = 0;
        $tongji_array['退休人员']['提租补贴变动'] = 0;
        $tongji_array['退休人员']['退休补贴变动'] = 0;
        $tongji_array['退休人员']['站房租扣款变动'] = 0;
        $tongji_array['退休人员']['所房租扣款变动'] = 0;
        $tongji_array['退休人员']['其他变动（需备注）'] = 0;          
        
       
        foreach ($list_array as $li){
            //dump($li);
            foreach($tongji_array as $ke => $vj){
                if($li['wu_type'] == $ke){
                    foreach($vj as $kkk => $jjj){
                        if($li['wu_class'] == $kkk){
                            $tongji_array[$ke][$kkk] +=  $li['wu_total'];
                        }                        
                    }
                }
            }
        }
        
        //计算分项合计
        foreach ($tongji_array as $k_total => $v_total){
            foreach($v_total as $v_total){
                $tongji_array[$k_total]['合计'] += $v_total;
            }
            
        }             
        //dump($tongji_array);
        $this -> assign('begin_time', $begin_time);
        $this -> assign('end_time', $end_time);        
        $this -> assign('list', $tongji_array);
        $this -> display();
    }
    
    /**
    * @desc 财务代发科研绩效查询
    * 
    */
    public function keyanjixiao() {
        //初始化页面日期插件
        $plugin['date'] = true;
        $this -> assign("plugin", $plugin);
        //设置开始和结束时间
        $begin_time = '2017-01-01';
        $end_time = '2017-12-31';
        if (!empty($_POST)){
             $begin_time = $_POST['be_create_time'];
             $end_time = $_POST['en_create_time'];
        }
        //查询条件                
        $map['is_del'] = 0 ;
        $map['step'] = 40;  //已完成的审批项目
        $map['type'] = 74; //代发申请Flow_id
        //获取用户自定义数据
        $udf_data = M("Flow")->where($map)->getField('udf_data',true);
        //dump($udf_data);
        $list_array = '';
        $i = 0;   //数组索引
        foreach($udf_data as $key=>$value){
            $json_arr = json_decode($value,true);
            $json_data= json_decode($json_arr['334'],true);
            $tmp_date = $json_arr['332']; //代发年月

            //日期在查询日期范围内
            if(strtotime($begin_time) <= strtotime($tmp_date) && strtotime($tmp_date) <= strtotime($end_time)){
                foreach($json_data['data'] as $k=>$v){
                    $list_array[] = $v;
                    $list_array[$i]['wu_date'] = $tmp_date;
                    $i=$i+1;
                }
            }
        }
        //dump($list_array);
        $this -> assign('begin_time', $begin_time);
        $this -> assign('end_time', $end_time);          
        $this -> assign('list', $list_array);
        $this -> display();
    }
	
	public function report() {
		$plugin['date'] = true;
		$this -> assign("plugin", $plugin);
		$this -> assign('user_id', get_user_id());

		$auth = $this -> config['auth'];
		$this -> assign('auth', $auth);

		if ($auth['admin']) {
			$menu = array();
			$dept_menu = M("Dept") -> field('id,pid,name') -> where('is_del=0') -> order('sort asc') -> select();
			$dept_tree = list_to_tree($dept_menu, 0);
			$count = count($dept_tree);
			if (empty($count)) {
				/*获取部门列表*/
				$html = '';
				$html = $html . "<option value='{$dept_id}'>{$dept_name}</option>";
				$this -> assign('dept_list', $html);

				/*获取人员列表*/
				$where['is_del'] = array('eq', 0);
				$emp_list = D("User") -> where($where) -> getField('id,name');
				$this -> assign('emp_list', $emp_list);
			} else {
				/*获取部门列表*/
				$this -> assign('dept_list', select_tree_menu($dept_tree));
				$dept_list = tree_to_list($dept_tree);
				$dept_list = rotate($dept_list);
				$dept_list = $dept_list['id'];

				/*获取人员列表*/
				$where['is_del'] = array('eq', 0);
				$emp_list = D("User") -> where($where) -> getField('id,name');
				$this -> assign('emp_list', $emp_list);
			}
		} else {
			$dept_id = get_dept_id();
			$dept_name = get_dept_name();
			$menu = array();
			$dept_menu = M("Dept") -> field('id,pid,name') -> where("is_del=0") -> order('sort asc') -> select();
			$dept_tree = list_to_tree($dept_menu, $dept_id);
			$count = count($dept_tree);
			if (empty($count)) {
				/*获取部门列表*/
				$html = '';
				$html = $html . "<option value='{$dept_id}'>{$dept_name}</option>";
				$this -> assign('dept_list', $html);

				/*获取人员列表*/
				$where['dept_id'] = array('eq', $dept_id);
				$where['is_del'] = array('eq', 0);
				$emp_list = D("User") -> where($where) -> getField('id,name');
				$this -> assign('emp_list', $emp_list);
			} else {
				/*获取部门列表*/
				$this -> assign('dept_list', select_tree_menu($dept_tree));
				$dept_list = tree_to_list($dept_tree);
				$dept_list = rotate($dept_list);
				$dept_list = $dept_list['id'];

				/*获取人员列表*/
				$where['dept_id'] = array('in', $dept_list);
				$where['is_del'] = array('eq', 0);
				$emp_list = D("User") -> where($where) -> getField('id,name');
				$this -> assign('emp_list', $emp_list);
			}
		}

		if ($auth['admin']) {
			// if (empty($map['dept_id'])) {
			// if (!empty($dept_list)) {
			// $map['dept_id'] = array('in', array_merge($dept_list, array($dept_id)));
			// } else {
			// $map['dept_id'] = array('eq', $dept_id);
			// }
			// }
		} else {
			$map['user_id'] = get_user_id();
		}

		$model = D("SignView");
		$map = $this -> _search($model);
		if (method_exists($this, '_search_filter')) {
			$this -> _search_filter($map);
		}
		if (I('mode') == 'export') {
			$this -> _report_export($model, $map);
		} else {
			$this -> _list($model, $map);
		}
		$this -> display();
	}

	public function _report_export($model, $map) {
		$list = $model -> where($map) -> select();
		$r = $model -> where($map) -> count();
		if ($r <= 1000) {
			//导入thinkphp第三方类库
			Vendor('Excel.PHPExcel');

			$objPHPExcel = new \PHPExcel();

			$objPHPExcel -> getProperties() -> setCreator("OA") -> setLastModifiedBy("OA") -> setTitle("Office 2007 XLSX Test Document") -> setSubject("Office 2007 XLSX Test Document") -> setDescription("Test document for Office 2007 XLSX, generated using PHP classes.") -> setKeywords("office 2007 openxml php") -> setCategory("Test result file");
			// Add some data
			$i = 1;

			$objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("A$i", "部门") -> setCellValue("B$i", "姓名") -> setCellValue("C$i", "职位") -> setCellValue("D$i", "类型") -> setCellValue("E$i", "时间") -> setCellValue("F$i", "经度") -> setCellValue("G$i", "纬度") -> setCellValue("H$i", "IP") -> setCellValue("I$i", "地理位置")-> setCellValue("J$i", "备注");

			foreach ($list as $val) {
				$i++;

				$dept_name = $val['dept_name'];
				$name = $val['name'];
				$position_name = $val['position_name'];
				$type = sign_type($val['type']);
				$sign_date = $val['sign_date'];
				$latitude = $val['latitude'];
				$longitude = $val['longitude'];
				$ip = $val['ip'];
				$location = $val['location'];
				$content = $val['content'];

				$objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("A$i", $dept_name) -> setCellValue("B$i", $name) -> setCellValue("C$i", $position_name) -> setCellValue("D$i", $type) -> setCellValue("E$i", $sign_date) -> setCellValue("F$i", $latitude) -> setCellValue("G$i", $longitude) -> setCellValue("H$i", $ip) -> setCellValue("I$i", $location)-> setCellValue("J$i", $content);
			}

			// Rename worksheet
			$objPHPExcel -> getActiveSheet() -> setTitle('考勤统计');

			// Set active sheet index to the first sheet, so Excel opens this as the first sheet
			$objPHPExcel -> setActiveSheetIndex(0);
			$file_name = "考勤统计.xlsx";

			// Redirect output to a client’s web browser (Excel2007)
			header("Content-Type: application/force-download");
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header("Content-Disposition:attachment;filename =" . str_ireplace('+', '%20', URLEncode($file_name)));
			header('Cache-Control: max-age=0');

			$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			//readfile($filename);
			$objWriter -> save('php://output');
			exit ;
		} else {
			header('Content-Type: application/vnd.ms-excel;charset=gbk');
			header('Content-Disposition: attachment;filename="考勤统计.csv"');
			header('Cache-Control: max-age=0');

			$fp = fopen('php://output', 'a');
			$title = array('部门', '姓名', '职位', '类型', '时间', '经度', '纬度', 'IP', '地理位置', '备注');
			foreach ($title as $i => $v) {
				// CSV的Excel支持GBK编码，一定要转换，否则乱码
				$title[$i] = iconv('utf-8', 'gbk', $v);
			}
			fputcsv($fp, $title);
			$cnt = 0;
			
			foreach ($list as $val) {
				$cnt++;
				if (100000 == $cnt) {//刷新一下输出buffer，防止由于数据过多造成问题
					ob_flush();
					flush();
					$cnt = 0;
				}

				$dept_name = $val['dept_name'];
				$name = $val['name'];
				$position_name = $val['position_name'];
				$type = sign_type($val['type']);
				$sign_date = $val['sign_date'];
				$latitude = $val['latitude'];
				$longitude = $val['longitude'];
				$ip = $val['ip'];
				$location = $val['location'];
				$content = $val['content'];
				

				$row = array($dept_name, $name, $position_name, $type, $sign_date, $latitude, $longitude, $ip, $location, $content);

				foreach ($row as $i => $v) {
					$row[$i] = iconv('utf-8', 'gbk', $v);
				}
				fputcsv($fp, $row);
			}
			fclose($fp);
			exit ;
		}
	}
    

	function json() {
		header("Cache-Control: no-cache, must-revalidate");
		header("Content-Type:text/html; charset=utf-8");

		$start_date = $_REQUEST["start_date"];
		$end_date = $_REQUEST["end_date"];

		$where['user_id'] = get_user_id();
		$where['sign_date'] = array( array('egt', $start_date), array('elt', $end_date));

		$list = M("Sign") -> where($where) -> order('sign_date') -> select();
		exit(json_encode($list));
	}

}
?>