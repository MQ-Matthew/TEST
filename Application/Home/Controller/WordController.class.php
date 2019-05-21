<?php

namespace Home\Controller;

use Common\Common\PHPZip;

//use Think\Controller;
//采购申请Word下载控制器
class WordController extends \Think\Controller {

    protected $config = array('app_type' => 'public');

    //采购申请生成word文档并且下载
    public function word($id) {


///获取内容
        $model = D("Flow");
        $where['id'] = array('eq', $id);
        $where['_logic'] = 'and';
        $map['_complex'] = $where;

        $vo = $model -> where($map) -> find();
        if (empty($vo)) {
            $this -> error("系统错误");
        }
        //审批人数组
        $str = $vo['confirm_name'];
        $pa = '%<b title.*?>(.*?)</b>%si';
        preg_match_all($pa,$str,$match);
        // dump($match);die;
        //dump($match[1]);
        //dump($vo);
        $this -> assign('list', $vo);

        $field_list = D("UdfField") -> get_data_list($vo['udf_data']);
        // dump($field_list);die;
        $this -> assign("field_list", $field_list);

        ///dump($field_list);

///导出正常模板的内容 start
        for($i=0;$i<count($field_list);$i++){
                if(!empty($field_list[$i]['val'])){
                    $data['name'.$i]= $field_list[$i]['val'];
                }else{
                    $data['name'.$i]='';
                }

        }

      // dump($data);

        $daochu=array();

        foreach($data  as $k=>$v){
            $daochu[0]['list'][$k] =$v;

        }

      // dump($daochu);

        $this->assign('daochu',$daochu);///导出正常模板的数据(大数组)

       // dump($data);die;
        $this->assign('data',$data);///导出正常模板的数据

        foreach( $field_list  as $K=>$v){
            $arr[]= $v[2];
        }


        // dump($field_list[3]['val']);die;
/////导出添加的json值 start

        $json = $field_list[2]['val'];
        // dump($field_list[2]['val']);die;
        $json = trim($json);

        $json= preg_replace('/\s(?=\s)/', '', $json);

        $json = preg_replace('/[\n\r\t]/', ' ', $json);

        $arrry = json_decode($json,true);

       // dump($arrry['data']);die;

        $this->assign('array',$arrry['data']);



/////导出添加的json值 end


        $flow_type_id = $vo['type'];
        $model = M("FlowType");
        $flow_type = $model -> find($flow_type_id);
        $this -> assign("flow_type", $flow_type);
        //dump($flow_type);
        
        // $str = M("Flow")->where('id='.$position_id)-> getField('name');
        // $pregStr = '/<b[^>].*>(.*)<\/b>/isU';
        // preg_match_all($pregStr, $str, $matchObj);


  //审批日志
        $model = M("FlowLog");
        $where = array();
        $where['flow_id'] = $id;
        $where['step'] = array('between',array(20,30));        
        $where['result'] = array('eq', 1);
        $where['is_del'] = array('eq', 0);
        $where['_string'] = "result is not null";
        $flow_log = $model->where($where)->order('step')->select();
        // $flow_log = $model->Distinct(true) -> where($where) -> field('step,max(update_time)')->select();
       /// $flow_log = $model->group('step')->where($where)->order('step')->select();  //利用group方法去重(这儿还有个问题,取的不是时间最大的一个时间)

    //  dump($flow_log);

////审核记录去除重复(退回再审核审核)的值,并保留重复中的时间最大的那个值(已解决)
        ///$arrsss = [];
        $count = count($flow_log);

        foreach($flow_log as $k=>$v){


            for($i=$k+1;$i<$count;$i++){

                if($flow_log[$k]['step'] == $flow_log[$i]['step'] ){    ////一直不走这个区间
                    //echo 1;
                    if($v['update_time'] > $flow_log[$i]['update_time']  ){
                        ///$arrsss[]= $v;
                        //echo 2;
                        unset($flow_log[$i]);

                    }else{
                       /// echo 3;
                        unset($flow_log[$k]);

                    }

                }
            }
        }

        $flow_log =  array_merge($flow_log);
        
        foreach($flow_log as $mkey=>$mval){
            $flow_log[$mkey]['confirm_name'] = $match[1][$mkey]; //插入审批人身份            
        }
       // dump($flow_log);die();
       // var_dump($match[1][0]);die();
        $this -> assign("match", $match[1]);
        $this -> assign("flow_log", $flow_log);


        if($flow_type['daochu_tpl']){
            $html = $this -> fetch($flow_type['daochu_tpl']);   ///用fetch()加载 会特别慢
          //  $html = $this -> display($flow_type['daochu_tpl']);   ///不能用display()
        }else{

            $this->error('没找到模板,导出失败');
           /// $thml = $this -> fetch('');///加载单独的模板
        }

        //echo $html;exit();

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
        $pdf->SetAutoPageBreak(TRUE, 25);

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
                $pdf->AddPage(); ////设置页数的
            }

            $pdf->writeHTML($chunks[$i], true, false, true, false, '');
        }


         $pdf->lastPage();

      //  $order_number = date('Ymd', $vo['create_time']) . '-' . $vo['id'];
        $order_number =  $vo['doc_no'];
       $order_number.=".pdf";

    ///  echo $order_number;exit();

        ob_end_clean();

      //  echo '8888';
//输出PDF
        $pdf->Output($order_number, 'D');
        //$pdf->Output('t.pdf', 'I');
        exit();
    }




}
