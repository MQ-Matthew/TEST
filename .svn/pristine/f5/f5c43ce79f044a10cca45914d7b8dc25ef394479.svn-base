<?php
/**
* @desc 合同模块
* 
* 
*/
namespace Home\Controller;

class AgreementController extends HomeController {
	//过滤查询字段
	//protected $config = array('app_type' => 'common', 'admin' => 'set_tag,tag_manage');
    protected $config = array('app_type' => 'personal');
	function _search_filter(&$map) {
        $keyword = I('keyword');
        if (!empty($keyword)) {
            $map['name|code_number'] = array('like', "%" . $keyword . "%");
        }else{
		    $map['name'] = array('like', "%" . $_POST['name'] . "%");
		    $map['code_number'] = array('like', "%" . $_POST['code_number'] . "%");
            $map['user_name'] = array('like', "%" . $_POST['user_name'] . "%");
		    $map['is_del'] = array('eq', '0');
        }
		if (!empty($_POST['tag'])) {
			$map['group'] = $_POST['tag'];
		}
		$map['is_del'] = array('eq', '0');
	}

	function index() {
		$plugin['date'] = true;
        $this -> assign("plugin", $plugin);
        
        $map = $this -> _search();
        if (method_exists($this, '_search_filter')) {
            $this -> _search_filter($map);
        }         
        $model = D("Agreement");
        $this -> _list($model, $map, 'approve_time desc');
		$this -> display();		 
	}
    
    function mydoc() {
        $plugin['date'] = true;
        $this -> assign("plugin", $plugin);
        
        $map = $this -> _search();
        if (method_exists($this, '_search_filter')) {
            $this -> _search_filter($map);
        }
        $map['emp_no'] = get_emp_no();
        //dump($map);         
        $model = D("Agreement");
        $this -> _list($model, $map, 'approve_time desc');
        $this -> display();         
    }

	function export() {
		$model = M("Agreement");
		$where['is_del'] = 0;
		$list = $model -> where($where) -> select();

		Vendor('Excel.PHPExcel');
		//导入thinkphp第三方类库

		// $inputFileName = "Public/templete/Supplier.xlsx";
		// $objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
		$objPHPExcel = new \PHPExcel();
		$objPHPExcel -> getProperties() -> setCreator("smeoa") -> setLastModifiedBy("smeoa") -> setTitle("Office 2007 XLSX Test Document") -> setSubject("Office 2007 XLSX Test Document") -> setDescription("Test document for Office 2007 XLSX, generated using PHP classes.") -> setKeywords("office 2007 openxml php") -> setCategory("Test result file");
		// Add some data
		$i = 1;
		//dump($list);
		foreach ($list as $val) {
			$i++;
			$objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("A$i", $val["name"]) -> setCellValue("B$i", $val["short"]) -> setCellValue("C$i", $val["account"]) -> setCellValue("D$i", $val["tax_no"]) -> setCellValue("E$i", $val["payment"]) -> setCellValue("F$i", $val["address"]) -> setCellValue("G$i", $val["contact"]) -> setCellValue("H$i", $val["email"]) -> setCellValue("I$i", $val["office_tel"]) -> setCellValue("J$i", $val["mobile_tel"]) -> setCellValue("J$i", $val["fax"]) -> setCellValue("L$i", $val["im"]) -> setCellValue("M$i", $val["remark"]);
		}
		// Rename worksheet
		$objPHPExcel -> getActiveSheet() -> setTitle('Supplier');

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel -> setActiveSheetIndex(0);

		$file_name = "customer.xlsx";
		// Redirect output to a client’s web browser (Excel2007)
		header("Content-Type: application/force-download");
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header("Content-Disposition:attachment;filename =" . str_ireplace('+', '%20', URLEncode($file_name)));
		header('Cache-Control: max-age=0');

		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter -> save('php://output');
		exit ;
	}
    /**
    * @desc 
    * 导入合同审批表中的完成项目
    */
	public function import() {
		$opmode = $_POST["opmode"];        
		if ($opmode == "import") {
           $id = I('id');
           if(!empty($id)){
               foreach ($id as $val){
                   $ag_data = array();
                   $Flow_list = M("Flow")->where("id=".$val)->find();
                   //dump($list);
                   $ag_data['doc_no'] = $Flow_list['doc_no'];
                   $ag_data['name'] = $Flow_list['name'];               
                   //合同附件取最后两个文件
                   $m_arr = explode(";",substr($Flow_list['add_file'],0,strlen($Flow_list['add_file'])-1));
                   $ag_data['add_file'] = implode(";",array_slice($m_arr, -2)).implode(";",array_slice($m_arr, -1));
                   //填入数据
                   $ag_data['user_id'] = $Flow_list['user_id'];
                   $ag_data['emp_no'] = $Flow_list['emp_no'];
                   $ag_data['user_name'] = $Flow_list['user_name'];
                   $ag_data['dept_id'] = $Flow_list['dept_id'];
                   $ag_data['dept_name'] = $Flow_list['dept_name'];
                   $ag_data['approve_time'] = $Flow_list['update_time'];
                   $ag_data['create_time'] = $Flow_list['create_time'];
                   $ag_data['update_time'] = $Flow_list['update_time'];
                   $ag_data['flow_id'] = $Flow_list['id'];
                   //解析udf_data字段
                   $ag_array = json_decode($Flow_list['udf_data'],true);               
                   $ag_data['type'] = $ag_array['305'];  //合同类型
                   $ag_data['purchase_type'] = $ag_array['306']; //采购方式（一般采购、政府采购）               
                   $ag_data['start_date'] = $ag_array['307']; //合同开始日期
                   $ag_data['end_date'] = $ag_array['308'];  //合同截止日期
                   $ag_data['supplier'] = $ag_array['309']; //供应商 
                   $ag_data['quality'] = $ag_array['310'];  //质保期
                   $ag_data['amount'] = $ag_array['311'];  //合同金额
                   $ag_data['paytype'] = $ag_array['312'];  //支付方 
                   $ag_data['bond'] = $ag_array['313'];     //保证金                             
                   $ag_data['sources'] = $ag_array['314'];  //经费来源
                   $ag_data['code_number'] = $ag_array['315']; //合同编号
                   $ag_data['stamp'] = $ag_array['331'];     // 印章次数
                   //dump($ag_data);
                   $rs = M("Agreement") -> add($ag_data);
               }
                $this -> success('导入成功111！');
           }else{
                $this -> error('没有合同被导入！');
		   }		
		} else {
            //查出合同表中已有的合同编号
            $map['is_del'] = 0;
            $map['_string'] =  "code_number is not null";
            $list_code_number = M("Agreement")->where($map)->field('code_number') -> select();
            $list_code_number = rotate($list_code_number);
            //dump($list_code_number);
            //查出审批表中未归档的记录
            $model = M("Flow");
            $where['is_del'] = 0;
            $where['step'] = 40;
            $where['type'] = 70;
            $list = M("Flow")->where($where)-> select();

            //判断合同表中是否已有该合同编号
            foreach ($list as $key=>$val){
                $udf_arr = json_decode($val['udf_data'],true);
                if(in_array($udf_arr['315'],$list_code_number['code_number'])){
                    unset($list[$key]);  //不显示已有的合同编号记录
                }
            }
            $this -> assign('list', $list);
			$this -> display();
		}
	}

	function del($id) {
		$count = $this -> _del($id, CONTROLLER_NAME, true);

		if ($count) {
			$model = D("SystemTag");
			$result = $model -> del_data_by_row($id);
		}

		if ($count !== false) {//保存成功
			$this -> assign('jumpUrl', get_return_url());
			$this -> success("成功删除{$count}条!");
		} else {
			//失败提示
			$this -> error('删除失败!');
		}
	}

	function read($id) {
		$model = M('Agreement');
		$vo = $model -> getById($id);
		$this -> assign('vo', $vo);
		$this -> display();
	}

	function tag_manage() {
		$this -> _system_tag_manage("分组管理");
	}

	function set_tag() {
		$id = $_POST['id'];
		$tag = $_POST['tag'];
		$new_tag = $_POST['new_tag'];
		if (!empty($id)) {
			$model = D("SystemTag");
			$model -> del_data_by_row($id);
			if (!empty($_POST['tag'])) {
				$result = $model -> set_tag($id, $tag);
			}
		};

		if (!empty($new_tag)) {
			$model = D("SystemTag");
			$model -> controller = CONTROLLER_NAME;
			$model -> name = $new_tag;
			$model -> is_del = 0;
			$model -> user_id = get_user_id();
			$new_tag_id = $model -> add();
			if (!empty($id)) {
				$result = $model -> set_tag($id, $new_tag_id);
			}
		};
		if ($result !== false) {//保存成功
			if ($ajax || IS_AJAX)
				$this -> assign('jumpUrl', get_return_url());
			$this -> success('操作成功!');
		} else {
			//失败提示
			$this -> error('操作失败!');
		}
	}

	function json() {
		header("Content-Type:text/html; charset=utf-8");
		$key = $_REQUEST['key'];

		$model = M("Agreement");
		$where['name'] = array('like', "%" . $key . "%");
		$where['letter'] = array('like', "%" . $key . "%");
		$where['_logic'] = 'or';
		$map['_complex'] = $where;
		$map['status'] = 1;
		$list = $model -> where($map) -> field('id,name') -> select();
		exit(json_encode($list));
	}

	public function winpop() {
		$node = M("Agreement");
		$menu = array();
		$menu = $node -> where($where) -> field('id,name') -> select();
		$tree = list_to_tree($menu);
		//dump($node);
		$this -> assign('menu', popup_tree_menu($tree));
		$this -> display();
	}

	protected function _insert($name = 'Supplier') {
		$model = D('Supplier');
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		$model -> __set('letter', get_letter($model -> __get('name')));
		$model -> __set('user_id', get_user_id());
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

	protected function _update($name = 'Supplier') {
		$id = $_POST['id'];
		$model = D("Agreement");
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		$model -> __set('letter', get_letter($model -> __get('name')));
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

	protected function _assign_tag_list() {
		$model = D("SystemTag");
		$tag_list = $model -> get_tag_list('id,name');
		$this -> assign("tag_list", $tag_list);
	}

}
?>