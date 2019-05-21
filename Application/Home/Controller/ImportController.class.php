<?php

namespace Home\Controller;

class ImportController extends HomeController {
    //protected $config = array('app_type' => 'common', 'read' => 'import_material_plan');

    /**
    * @desc 
    * 导入办公用品领用（非库存）
    * 
    */
    public function import_bangongcaigoufkc()
    {
        //dump($_FILES);die; 
        Vendor('Excel.PHPExcel');
        $objPHPExcel = \PHPExcel_IOFactory::load($_FILES['file']['tmp_name']);
        $sheet_data = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

        //dump($sheet_data);die;

        $data = array();
        for($i = 6; $i <= count($sheet_data); $i++)
        {
            if(empty($sheet_data[$i]['A'])){
                break;
            }
            $data[] = array('wu_name'=>$sheet_data[$i]['A'], 
            'wu_type'=>$sheet_data[$i]['B'], 
            'wu_unit'=>$sheet_data[$i]['C'], 
            'wu_amount'=>$sheet_data[$i]['D'],
            'wu_price'=>$sheet_data[$i]['E'], 
            'wu_total'=>$sheet_data[$i]['F'], 
            'wu_brand'=>$sheet_data[$i]['G'],
            'wu_contact'=>$sheet_data[$i]['H'], 
            'wu_remark'=>$sheet_data[$i]['I']);
        }

        $result['ret'] = 0;
        $result['msg'] = '导入成功';
        $result['data'] = $data;
        exit(json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    
}