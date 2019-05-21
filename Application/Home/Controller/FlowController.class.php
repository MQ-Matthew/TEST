<?php

namespace Home\Controller;

class FlowController extends HomeController {
    protected $config = array('app_type' => 'common', 'read' => 'transfer,approve,mark,field_manage,back_to,reject,send_refer,refer,consult_again,move_darft,import_material_plan,read_hetong,same_department_user,department_list,recall,get_financenumber,send_to_yzadmin,get_ulist,tongji,daishen');

    function _search_filter(&$map) {
        $map['is_del'] = array('eq', '0');
        $keyword = I('keyword');
        if (!empty($keyword)) {
            $where['Flow.name'] = array('like', '%' . $keyword . '%');
            $where['doc_no'] = array('like', '%' . $keyword . '%');

            $where['Flow.content'] = array('like', '%' . $keyword . '%');
            $where['udf_data'] = array('like', '%' . $keyword . '%');

            $where['_logic'] = 'or';
            $map['_complex'] = $where;
        }
    }

    function index() {
        $model = D('FlowTypeView');
        $where['is_del'] = 0;
        $user_id = get_user_id();
        $role_list = D("Role") -> get_role_list($user_id);
        $role_list = rotate($role_list);
        $role_list = $role_list['role_id'];

        $duty_list = D("Role") -> get_duty_list($role_list);
        $duty_list = rotate($duty_list);
        $duty_list = $duty_list['duty_id'];
        if (!empty($duty_list)) {
            $where['request_duty'] = array('in', $duty_list);
        } else {
            $where['_string'] = '1=2';
        }

        $list = $model -> where($where) -> order('sort') -> select();
        $this -> assign("list", $list);

        $model = D("SystemTag");
        $tag_list = $model -> get_tag_list('id,name', 'FlowType');
        $this -> assign("tag_list", $tag_list);

        $this -> display();
    }

    //待审计数
    public function jishu($type,$step,$gt,$lt){
        $Flow = M('Flow');
        $map['type'] = array('eq', $type);
        $map['is_del'] = array('eq', '0');
        $map['step'] = array('eq', $step);
        if($gt && $lt){
            $map['create_time'] = array(array('gt',$gt),array('lt',$lt));
        }
        $num = $Flow->where($map)->count();
        return $num;
    }
     
    

    function tongji(){
        $Flow = M('Flow');
        $map['is_del'] = array('eq', '0');
        $map['step'] = array('eq', 20);
        $daishen = $Flow->where($map)->count();
        $this -> assign("daishen", $daishen);

        $map['step'] = array('eq', 30);
        $zhixing = $Flow->where($map)->count();
        $this -> assign("zhixing", $zhixing);

        $map['step'] = array('eq', 40);
        $wanjie = $Flow->where($map)->count();
        $this -> assign("wanjie", $wanjie);

        $map['step'] = array('eq', 19);
        $foujue = $Flow->where($map)->count();
        $this -> assign("foujue", $foujue);
        //奖金初审
        $gt17 = 1483200000;
        $lt17 = 1514736000;
        $gt18 = 1514736000;
        $lt18 = 1546272000;
        $gt19 = 1546272000;
        $lt19 = 1577808000;
        $s_jjcs = $this->jishu(1,20);
        $this -> assign("s_jjcs", $s_jjcs);

        $z_jjcs = $this->jishu(1,30);
        $this -> assign("z_jjcs", $z_jjcs);

        $f_jjcs = $this->jishu(1,40);
        $this -> assign("f_jjcs", $f_jjcs);

        $jjcs_17 = $this->jishu(1,40,$gt17,$lt17);
        $this -> assign("jjcs_17", $jjcs_17);

        $jjcs_18 = $this->jishu(1,40,$gt18,$lt18);
        $this -> assign("jjcs_18", $jjcs_18);

        $jjcs_19 = $this->jishu(1,40,$gt19,$lt19);
        $this -> assign("jjcs_19", $jjcs_19);

        


        //奖金复审
        $s_jjfs = $this->jishu(2,20);
        $this -> assign("s_jjfs", $s_jjfs);

        
        $z_jjfs = $this->jishu(2,30);
        $this -> assign("z_jjfs", $z_jjfs);

        $f_jjfs = $this->jishu(2,40);
        $this -> assign("f_jjfs", $f_jjfs);

        $jjfs_17 = $this->jishu(2,40,$gt17,$lt17);
        $this -> assign("jjfs_17", $jjfs_17);

        $jjfs_18 = $this->jishu(2,40,$gt18,$lt18);
        $this -> assign("jjfs_18", $jjfs_18);

        $jjfs_19 = $this->jishu(2,40,$gt19,$lt19);
        $this -> assign("jjfs_19", $jjfs_19);

        //消耗材料月计划
        $s_xhcl = $this->jishu(57,20);
        $this -> assign("s_xhcl", $s_xhcl);

        $z_xhcl = $this->jishu(57,30);
        $this -> assign("z_xhcl", $z_xhcl);

        $f_xhcl = $this->jishu(57,40);
        $this -> assign("f_xhcl", $f_xhcl);

        $xhcl_17 = $this->jishu(57,40,$gt17,$lt17);
        $this -> assign("xhcl_17", $xhcl_17);

        $xhcl_18 = $this->jishu(57,40,$gt18,$lt18);
        $this -> assign("xhcl_18", $xhcl_18);

        $xhcl_19 = $this->jishu(57,40,$gt19,$lt19);
        $this -> assign("xhcl_19", $xhcl_19);
        

        

        //资产维修
        $s_zcwx = $this->jishu(61,20);
        $this -> assign("s_zcwx", $s_zcwx);

        $z_zcwx = $this->jishu(61,20);
        $this -> assign("z_zcwx", $z_zcwx);

        $f_zcwx = $this->jishu(61,20);
        $this -> assign("f_zcwx", $f_zcwx);

        $zcwx_17 = $this->jishu(61,40,$gt17,$lt17);
        $this -> assign("zcwx_17", $zcwx_17);

        $zcwx_18 = $this->jishu(61,40,$gt18,$lt18);
        $this -> assign("zcwx_18", $zcwx_18);

        $zcwx_19 = $this->jishu(61,40,$gt19,$lt19);
        $this -> assign("zcwx_19", $zcwx_19);

        //运维保养与维修
        $s_ywby = $this->jishu(62,20);
        $this -> assign("s_ywby", $s_ywby);

        $z_ywby = $this->jishu(62,30);
        $this -> assign("z_ywby", $z_ywby);

        $f_ywby = $this->jishu(62,40);
        $this -> assign("f_ywby", $f_ywby);

        $ywby_17 = $this->jishu(62,40,$gt17,$lt17);
        $this -> assign("ywby_17", $ywby_17);

        $ywby_18 = $this->jishu(62,40,$gt18,$lt18);
        $this -> assign("ywby_18", $ywby_18);

        $ywby_19 = $this->jishu(62,40,$gt19,$lt19);
        $this -> assign("ywby_19", $ywby_19);

        //临时计划
        $s_lsjh = $this->jishu(63,20);
        $this -> assign("s_lsjh", $s_lsjh);

        $z_lsjh = $this->jishu(63,30);
        $this -> assign("z_lsjh", $z_lsjh);

        $f_lsjh = $this->jishu(63,40);
        $this -> assign("f_lsjh", $f_lsjh);

        $lsjh_17 = $this->jishu(63,40,$gt17,$lt17);
        $this -> assign("lsjh_17", $lsjh_17);

        $lsjh_18 = $this->jishu(63,40,$gt18,$lt18);
        $this -> assign("lsjh_18", $lsjh_18);

        $lsjh_19 = $this->jishu(63,40,$gt19,$lt19);
        $this -> assign("lsjh_19", $lsjh_19);


        //仪器设备采购
        $s_yqsb = $this->jishu(64,20);
        $this -> assign("s_yqsb", $s_yqsb);

        $z_yqsb = $this->jishu(64,30);
        $this -> assign("z_yqsb", $z_yqsb);

        $f_yqsb = $this->jishu(64,40);
        $this -> assign("f_yqsb", $f_yqsb);

        $yqsb_17 = $this->jishu(64,40,$gt17,$lt17);
        $this -> assign("yqsb_17", $yqsb_17);

        $yqsb_18 = $this->jishu(64,40,$gt18,$lt18);
        $this -> assign("yqsb_18", $yqsb_18);

        $yqsb_19 = $this->jishu(64,40,$gt19,$lt19);
        $this -> assign("yqsb_19", $yqsb_19);

        //办公用品采购
        $s_bgyp = $this->jishu(65,20);
        $this -> assign("s_bgyp", $s_bgyp);

        $z_bgyp = $this->jishu(65,30);
        $this -> assign("z_bgyp", $z_bgyp);

        $f_bgyp = $this->jishu(65,40);
        $this -> assign("f_bgyp", $f_bgyp);

        $bgyp_17 = $this->jishu(65,40,$gt17,$lt17);
        $this -> assign("bgyp_17", $bgyp_17);

        $bgyp_18 = $this->jishu(65,40,$gt18,$lt18);
        $this -> assign("bgyp_18", $bgyp_18);

        $bgyp_19 = $this->jishu(65,40,$gt19,$lt19);
        $this -> assign("bgyp_19", $bgyp_19);

        //技术服务类
        $s_jsfw = $this->jishu(66,20);
        $this -> assign("s_jsfw", $s_jsfw);

        $z_jsfw = $this->jishu(66,30);
        $this -> assign("z_jsfw", $z_jsfw);

        $f_jsfw = $this->jishu(66,40);
        $this -> assign("f_jsfw", $f_jsfw);

        $jsfw_17 = $this->jishu(66,40,$gt17,$lt17);
        $this -> assign("jsfw_17", $jsfw_17);

        $jsfw_18 = $this->jishu(66,40,$gt18,$lt18);
        $this -> assign("jsfw_18", $jsfw_18);

        $jsfw_19 = $this->jishu(66,40,$gt19,$lt19);
        $this -> assign("jsfw_19", $jsfw_19);

        //办公用品及设备领用
        $s_bgyp = $this->jishu(68,20);
        $this -> assign("s_bgyp", $s_bgyp);

        $z_bgyp = $this->jishu(68,30);
        $this -> assign("z_bgyp", $z_bgyp);

        $f_bgyp = $this->jishu(68,40);
        $this -> assign("f_bgyp", $f_bgyp);

        $bgyp_17 = $this->jishu(68,40,$gt17,$lt17);
        $this -> assign("bgyp_17", $bgyp_17);

        $bgyp_18 = $this->jishu(68,40,$gt18,$lt18);
        $this -> assign("bgyp_18", $bgyp_18);

        $bgyp_19 = $this->jishu(68,40,$gt19,$lt19);
        $this -> assign("bgyp_19", $bgyp_19);

        //办公电脑领用
        $s_dnly = $this->jishu(69,20);
        $this -> assign("s_dnly", $s_dnly);

        $z_dnly = $this->jishu(69,30);
        $this -> assign("z_dnly", $z_dnly);

        $f_dnly = $this->jishu(69,40);
        $this -> assign("f_dnly", $f_dnly);

        $dnly_17 = $this->jishu(69,40,$gt17,$lt17);
        $this -> assign("dnly_17", $dnly_17);

        $dnly_18 = $this->jishu(69,40,$gt18,$lt18);
        $this -> assign("dnly_18", $dnly_18);

        $dnly_19 = $this->jishu(69,40,$gt19,$lt19);
        $this -> assign("dnly_19", $dnly_19);

        //合同审批
        $s_htsp = $this->jishu(70,20);
        $this -> assign("s_htsp", $s_htsp);

        $z_htsp = $this->jishu(70,30);
        $this -> assign("z_htsp", $z_htsp);

        $f_htsp = $this->jishu(70,40);
        $this -> assign("f_htsp", $f_htsp);

        $htsp_17 = $this->jishu(70,40,$gt17,$lt17);
        $this -> assign("htsp_17", $htsp_17);

        $htsp_18 = $this->jishu(70,40,$gt18,$lt18);
        $this -> assign("htsp_18", $htsp_18);

        $htsp_19 = $this->jishu(70,40,$gt19,$lt19);
        $this -> assign("htsp_19", $htsp_19);

        //用章审批
        $s_yzsp = $this->jishu(71,20);
        $this -> assign("s_yzsp", $s_yzsp);

        $z_yzsp = $this->jishu(71,30);
        $this -> assign("z_yzsp", $z_yzsp);

        $f_yzsp = $this->jishu(71,40);
        $this -> assign("f_yzsp", $f_yzsp);

        $yzsp_17 = $this->jishu(71,40,$gt17,$lt17);
        $this -> assign("yzsp_17", $yzsp_17);

        $yzsp_18 = $this->jishu(71,40,$gt18,$lt18);
        $this -> assign("yzsp_18", $yzsp_18);

        $yzsp_19 = $this->jishu(71,40,$gt19,$lt19);
        $this -> assign("yzsp_19", $yzsp_19);

        //代发业务
        $s_dfyw = $this->jishu(72,20);
        $this -> assign("s_dfyw", $s_dfyw);

        $z_dfyw = $this->jishu(72,30);
        $this -> assign("z_dfyw", $z_dfyw);

        $f_dfyw = $this->jishu(72,40);
        $this -> assign("f_dfyw", $f_dfyw);

        $dfyw_17 = $this->jishu(72,40,$gt17,$lt17);
        $this -> assign("dfyw_17", $dfyw_17);

        $dfyw_18 = $this->jishu(72,40,$gt18,$lt18);
        $this -> assign("dfyw_18", $dfyw_18);

        $dfyw_19 = $this->jishu(72,40,$gt19,$lt19);
        $this -> assign("dfyw_19", $dfyw_19);

        //工资变动
        $s_gzbd = $this->jishu(73,20);
        $this -> assign("s_gzbd", $s_gzbd);

        $z_gzbd = $this->jishu(73,30);
        $this -> assign("z_gzbd", $z_gzbd);

        $f_gzbd = $this->jishu(73,40);
        $this -> assign("f_gzbd", $f_gzbd);

        $gzbd_17 = $this->jishu(73,40,$gt17,$lt17);
        $this -> assign("gzbd_17", $gzbd_17);

        $gzbd_18 = $this->jishu(73,40,$gt18,$lt18);
        $this -> assign("gzbd_18", $gzbd_18);

        $gzbd_19 = $this->jishu(73,40,$gt19,$lt19);
        $this -> assign("gzbd_19", $gzbd_19);

        //办公用品(非库存)领用
        $s_fkc = $this->jishu(74,20);
        $this -> assign("s_fkc", $s_fkc);

        $z_fkc = $this->jishu(74,30);
        $this -> assign("z_fkc", $z_fkc);

        $f_fkc = $this->jishu(74,40);
        $this -> assign("f_fkc", $f_fkc);

        $fkc_17 = $this->jishu(74,40,$gt17,$lt17);
        $this -> assign("fkc_17", $fkc_17);

        $fkc_18 = $this->jishu(74,40,$gt18,$lt18);
        $this -> assign("fkc_18", $fkc_18);

        $fkc_19 = $this->jishu(74,40,$gt19,$lt19);
        $this -> assign("fkc_19", $fkc_19);

        //结清单审批
        $s_jqd = $this->jishu(93,20);
        $this -> assign("s_jqd", $s_jqd);

        $z_jqd = $this->jishu(93,30);
        $this -> assign("z_jqd", $z_jqd);

        $f_jqd = $this->jishu(93,40);
        $this -> assign("f_jqd", $f_jqd);

        $jqd_17 = $this->jishu(93,40,$gt17,$lt17);
        $this -> assign("jqd_17", $jqd_17);

        $jqd_18 = $this->jishu(93,40,$gt18,$lt18);
        $this -> assign("jqd_18", $jqd_18);

        $jqd_19 = $this->jishu(93,40,$gt19,$lt19);
        $this -> assign("jqd_19", $jqd_19);

        //人事科章审批审批
        $s_rskz = $this->jishu(94,20);
        $this -> assign("s_rskz", $s_rskz);

        $z_rskz = $this->jishu(94,30);
        $this -> assign("z_rskz", $z_rskz);

        $f_rskz = $this->jishu(94,40);
        $this -> assign("f_rskz", $f_rskz);

        $rskz_17 = $this->jishu(94,40,$gt17,$lt17);
        $this -> assign("rskz_17", $rskz_17);

        $rskz_18 = $this->jishu(94,40,$gt18,$lt18);
        $this -> assign("rskz_18", $rskz_18);

        $rskz_19 = $this->jishu(94,40,$gt19,$lt19);
        $this -> assign("rskz_19", $rskz_19);

        //支出凭单审批
        $s_zcpd = $this->jishu(95,20);
        $this -> assign("s_zcpd", $s_zcpd);

        $z_zcpd = $this->jishu(95,30);
        $this -> assign("z_zcpd", $z_zcpd);

        $f_zcpd = $this->jishu(95,40);
        $this -> assign("f_zcpd", $f_zcpd);

        $zcpd_17 = $this->jishu(95,40,$gt17,$lt17);
        $this -> assign("zcpd_17", $zcpd_17);

        $zcpd_18 = $this->jishu(95,40,$gt18,$lt18);
        $this -> assign("zcpd_18", $zcpd_18);

        $zcpd_19 = $this->jishu(95,40,$gt19,$lt19);
        $this -> assign("zcpd_19", $zcpd_19);

        //劳务费审批
        $s_lwf = $this->jishu(97,20);
        $this -> assign("s_lwf", $s_lwf);

        $z_lwf = $this->jishu(97,30);
        $this -> assign("z_lwf", $z_lwf);

        $f_lwf = $this->jishu(97,40);
        $this -> assign("f_lwf", $f_lwf);

        $lwf_17 = $this->jishu(97,40,$gt17,$lt17);
        $this -> assign("lwf_17", $lwf_17);

        $lwf_18 = $this->jishu(97,40,$gt18,$lt18);
        $this -> assign("lwf_18", $lwf_18);

        $lwf_19 = $this->jishu(97,40,$gt19,$lt19);
        $this -> assign("lwf_19", $lwf_19);

        //专家咨询费审批
        $s_zjzx = $this->jishu(98,20);
        $this -> assign("s_zjzx", $s_zjzx);

        $z_zjzx = $this->jishu(98,30);
        $this -> assign("z_zjzx", $z_zjzx);

        $f_zjzx = $this->jishu(98,40);
        $this -> assign("f_zjzx", $f_zjzx);

        $zjzx_17 = $this->jishu(98,40,$gt17,$lt17);
        $this -> assign("zjzx_17", $zjzx_17);

        $zjzx_18 = $this->jishu(98,40,$gt18,$lt18);
        $this -> assign("zjzx_18", $zjzx_18);

        $zjzx_19 = $this->jishu(98,40,$gt19,$lt19);
        $this -> assign("zjzx_19", $zjzx_19);

        //差旅费用报销
        $s_clfy = $this->jishu(99,20);
        $this -> assign("s_clfy", $s_clfy);

        $z_clfy = $this->jishu(99,30);
        $this -> assign("z_clfy", $z_clfy);

        $f_clfy = $this->jishu(99,40);
        $this -> assign("f_clfy", $f_clfy);

        $clfy_17 = $this->jishu(99,40,$gt17,$lt17);
        $this -> assign("clfy_17", $clfy_17);

        $clfy_18 = $this->jishu(99,40,$gt18,$lt18);
        $this -> assign("clfy_18", $clfy_18);

        $clfy_19 = $this->jishu(99,40,$gt19,$lt19);
        $this -> assign("clfy_19", $clfy_19);

        
        $this -> display();

    }

    function flowcount($map){
        
    }

    function _flow_auth_filter($folder, &$map) {
        $emp_no = get_emp_no();
        $user_id = get_user_id();
        switch ($folder) {
            case 'confirm' :  //待办文档列表
            $this -> assign("folder_name", '待办事项');
                $FlowLog = M("FlowLog");  //查看审核记录
                $where['emp_no'] = $emp_no;     //当前用户在审核记录表中
                $where['is_del'] = 0;
                $where['_string'] = "result is null";  //并且没有审核意见
                $log_list = $FlowLog -> where($where) -> field('flow_id') -> select();  //查询审核记录
				//dump(M("FlowLog")->getLastSql());die;
                $log_list = rotate($log_list);

                if (!empty($log_list)) { 
                    //$map['id'] = array('in', $log_list['flow_id']);  //如果已有当前用户的审核意见
                    $map1['Flow.id'] = array('in', $log_list['flow_id']);
                    $map1['step'] = array('neq', 100);
                    //$map1['step'] = array(array('gt', 10), array('neq', 40), 'and');
                    //解决发起人在Flow_log表中已有记录的问题    
                    $map2['user_id'] = array('eq', $user_id);
                    $map2['step'] = array('eq', 19);
                    //或条件查询    
                    $map['_complex'] = array(
                        $map1,
                        $map2,
                        '_logic' => 'or'   
                    );

                } else {
                    $FlowLog_temp = M("FlowLog");     //当前用户在审核记录表中
                    $where_temp['is_del'] = 0;
                    $where_temp['step']= array('neq',100);
                    $where_temp['_string'] = "result is null";  //并且没有审核意见
                    $log_list_temp = $FlowLog_temp -> where($where_temp) -> field('flow_id') -> select();  //查询审核记录
                    $log_list_temp = rotate($log_list_temp);
                    //dump($log_list_temp);
                    $map['id'] = array('not in', $log_list_temp['flow_id']);
                    //$map['_string'] = '1=2';
                    $map['user_id'] = array('eq', $user_id);
                    $map['step'] = array('eq', 19);

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
            //经办
                case 'jingban' :
                $this -> assign("folder_name", '经办事项');
                $FlowLog = M("FlowLog");
                //条件1：已经发布意见的审批记录
                $where1['emp_no'] = $emp_no;
                $where1['is_del'] = 0;
                $where1['_string'] = "result is not null";
                //条件2：已发布未回复的参阅记录
                $where2['from'] = get_user_name();
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
                
                if (!empty($log_list)) {
                    //$map['flow.id'] = array('in', $log_list['flow_id']);
                    //$map['step'] = array(array('gt', 10), array('neq', 40), 'and');                     
                    $map1['Flow.id'] = array('in', $log_list['flow_id']);
                    //$map1['step'] = array( array('gt', 10),array('neq', 40), 'and');
                    $map1['step'] = array('neq', 40);  //2018-5-10  解决草稿箱中文件无法显示
                    //dump($map1);
                    //解决发起人在Flow_log表中已有记录的问题    
                    $map2['user_id'] = array('eq', $user_id);
                    //$map2['step'] = array( array('gt', 10),array('neq', 40), 'and');
                    $map2['step'] = array('neq', 40);  //2018-5-10  解决草稿箱中文件无法显示                  
                    //或条件查询    
                    $map['_complex'] = array(
                        $map1,
                        $map2,
                        '_logic' => 'or'   
                    );    
                } else {
                    $FlowLog_temp = M("FlowLog");     //当前用户在审核记录表中
                    $where_temp['is_del'] = 0;
                    $where_temp['step']= array('neq',100);
                    $where_temp['_string'] = "result is null";  //并且已经有审核意见
                    $log_list_temp = $FlowLog_temp -> where($where_temp) -> field('flow_id') -> select();  //查询审核记录
                    $log_list_temp = rotate($log_list_temp);
                    //dump($log_list_temp);
                    $map['id'] = array('in', $log_list_temp['flow_id']);
                    //$map['_string'] = '1=2';
                    $map['user_id'] = array('eq', $user_id);
                    $map['step'] = array('neq', 40);
                }
                break;

                case 'finish' :
                $this -> assign("folder_name", '办结事项');
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
                    
                    $map1['Flow.id'] = array('in', $log_list['flow_id']);
                    $map1['step'] = array( array('eq', 0),array('eq', 40), 'or');
                    //解决发起人在Flow_log表中已有记录的问题    
                    $map2['user_id'] = array('eq', $user_id);
                    $map2['step'] = array( array('eq', 0),array('eq', 40), 'or');
                    //或条件查询    
                    $map['_complex'] = array(
                        $map1,
                        $map2,
                        '_logic' => 'or'   
                    );
                    
                } else {
                    //$map['_string'] = '1=2';
                    $map['user_id'] = array('eq', $user_id);
                    //$map['step'] = array('eq', 40);
                    $map['step'] = array( array('eq', 0),array('eq', 40), 'or');
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

            $dept_list = M("Dept") -> where('is_del=0') -> order('sort asc') -> getField('id,name');
            $this -> assign('dept_list', $dept_list);

            $this -> assign("flow_type_list", $flow_type_list);

            $map = $this -> _search();

            if (method_exists($this, '_search_filter')) {
                $this -> _search_filter($map);
            }
            $folder = $fid;

            $this -> assign("folder", $folder);

            $this -> _flow_auth_filter($folder, $map);

        //$model = D("FlowView");
            $model = D("FlowView");
            if (I('mode') == 'export') {

                $this -> _folder_export($model, $map);
            } else {
            ///dump($map);
                $this -> _list($model, $map, 'update_time desc');

           // dump($this -> _list($model, $map, 'update_time desc'));

            //$list = M('Flow')->where($map)->order('update_time desc')->select();

            }
            $this -> display();
        }

        private function _folder_export($model, $map) {
            $list = $model -> where($map) -> select();
            $r = $model -> where($map) -> count();
            $model_flow_field = D("UdfField");
            if ($r <= 1000) {
            //导入thinkphp第三方类库
                Vendor('Excel.PHPExcel');

            //$inputFileName = "Public/templete/contact.xlsx";
            //$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
                $objPHPExcel = new \PHPExcel();

                $objPHPExcel -> getProperties() -> setCreator("OA") -> setLastModifiedBy("OA") -> setTitle("Office 2007 XLSX Test Document") -> setSubject("Office 2007 XLSX Test Document") -> setDescription("Test document for Office 2007 XLSX, generated using PHP classes.") -> setKeywords("office 2007 openxml php") -> setCategory("Test result file");
            // Add some data
                $i = 1;
            //dump($list);

            //编号，类型，标题，登录时间，部门，登录人，状态，审批，执行，传阅，审批情况，自定义字段
                $objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("A$i", "编号") -> setCellValue("B$i", "类型") -> setCellValue("C$i", "标题") -> setCellValue("D$i", "登录时间") -> setCellValue("E$i", "部门") -> setCellValue("F$i", "登录人") -> setCellValue("G$i", "状态") -> setCellValue("H$i", "审批") -> setCellValue("I$i", "执行") -> setCellValue("J$i", "传阅") -> setCellValue("K$i", "审批情况");

                foreach ($list as $val) {
                    $i++;
                //dump($val);
                    $id = $val['id'];
                    $doc_no = $val["doc_no"];
                //编号
                    $name = $val["name"];
                //标题
                    $confirm_name = strip_tags($val["confirm_name"]);
                //审批
                    $consult_name = strip_tags($val["consult_name"]);
                //执行
                    $refer_name = strip_tags($val["refer_name"]);
                //执行
                    $type_name = $val["type_name"];
                //流程类型
                    $user_name = $val["user_name"];
                //登记人
                    $dept_name = $val["dept_name"];
                //部门
                    $create_time = $val["create_time"];

                    $create_time = to_date($val["create_time"], 'Y-m-d H:i:s');
                //创建时间
                    $step = show_step($val["step"]);

                //编号，类型，标题，登录时间，部门，登录人，状态，审批，执行，传阅，审批情况，自定义字段
                    $objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("A$i", $doc_no) -> setCellValue("B$i", $type_name) -> setCellValue("C$i", $name) -> setCellValue("D$i", $create_time) -> setCellValue("E$i", $dept_name) -> setCellValue("F$i", $user_name) -> setCellValue("G$i", $step) -> setCellValue("H$i", $confirm_name) -> setCellValue("I$i", $consult_name) -> setCellValue("J$i", $refer_name);
                    $result = M("flow_log") -> where(array('flow_id' => $id)) -> select();
                    $field_data = '';
                    if (!empty($result)) {
                        foreach ($result as $field) {
                            // $field_data = $field_data . $field['user_name'] . ":" . $field['comment'] . ";" . date("Y-m-d H:i:s",$field['update_time']) . "\n";
                            $field_data = $field_data . $field['user_name'] . ":" . $field['comment'] . ";创建时间-修改时间" . date("Y-m-d H:i:s",$field['create_time'])."一".date("Y-m-d H:i:s",$field['update_time'])."步骤".$field['step']. "\n";
                        }
                        $objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("K$i", $field_data);
                    }

                    $field_list = $model_flow_field -> get_data_list($val["udf_data"]);
                //	dump($field_list);
                    $k = 'K';
                    if (!empty($field_list)) {
                        foreach ($field_list as $field) {
                            $k++;
                            $field_data = $field['name'] . ":" . $field['val'];
                        // $location = get_cell_location("J", $i, $k);
                            $objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("$k$i", $field_data);
                        }
                    }
                }
            // Rename worksheet
                $objPHPExcel -> getActiveSheet() -> setTitle('审批统计');

            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
                $objPHPExcel -> setActiveSheetIndex(0);
                $file_name = "审批统计.xlsx";
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
                header('Content-Disposition: attachment;filename="审批统计.csv"');
                header('Cache-Control: max-age=0');

                $fp = fopen('php://output', 'a');
                $title = array('编号', '类型', '标题', '登录时间', '部门', '登录人', '状态', '审批', '执行', '传阅', '审批情况', '自定义字段');
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
                //dump($val);
                $id = $val['id'];
                $doc_no = $val["doc_no"];
                //编号
                $name = $val["name"];
                //标题
                $confirm_name = strip_tags($val["confirm_name"]);
                //审批
                $consult_name = strip_tags($val["consult_name"]);
                //执行
                $refer_name = strip_tags($val["refer_name"]);
                //执行
                $type_name = $val["type_name"];
                //流程类型
                $user_name = $val["user_name"];
                //登记人
                $dept_name = $val["dept_name"];
                //部门

                $create_time = to_date($val["create_time"], 'Y-m-d H:i:s');
                //创建时间
                $step = show_step_type($val["step"]);

                $result_list = M("flow_log") -> where(array('flow_id' => $id)) -> select();
                $field_data = '';
                $result = '';
                if (!empty($result_list)) {
                    foreach ($result_list as $field) {

                        $field_data = $field_data . $field['user_name'] . ":" . $field['comment'] . ";创建时间-修改时间" . date("Y-m-d H:i:s",$field['create_time'])."一".date("Y-m-d H:i:s",$field['update_time'])."步骤".$field['step']. "\n";
                    }
                    $result = $field_data;

                }
                $r1 = array($doc_no, $type_name, $name, $create_time, $dept_name, $user_name, $step, $confirm_name, $consult_name, $refer_name, $result);

                $field_list = $model_flow_field -> get_data_list($val["udf_data"]);

                $t = 0;
                $r2 = array();
                if (!empty($field_list)) {

                    foreach ($field_list as $field) {
                        $r2[$t++] = $field['name'] . ":" . $field['val'];
                    }

                }
                $row = array_merge($r1, $r2);
                // dump($row);
                foreach ($row as $i => $v) {
                    // CSV的Excel支持GBK编码，一定要转换，否则乱码
                    $row[$i] = iconv('utf-8', 'gbk', $v);
                }
                fputcsv($fp, $row);
            }
            fclose($fp);
            exit ;
        }
    }

    function add() {

        $plugin['date'] = true;
        $plugin['uploader'] = true;
        $plugin['editor'] = true;
        $this -> assign("plugin", $plugin);

        $type_id = I('type');
        $model = M("FlowType");
        $flow_type = $model -> find($type_id);
        $this -> assign("flow_type", $flow_type);
        $model_flow_field = D("UdfField");
        $field_list = $model_flow_field -> get_field_list($type_id);
        //查找下一节点审核人
        $str_confirm = D("Flow") -> _conv_auditor($model -> confirm);
        if(strpos($str_confirm,'|')){
            $auditorArray = explode("|", $str_confirm);
            //$auditor = $auditorArray[0];
            $auditor = D("Flow")->check_agent($auditorArray[0]); //查找代理人
            $auditorName = get_user_name_empno($auditor);
        }
        $this -> assign("auditorName", $auditorName);
        $on_edit = 'add';
        $this -> assign("on_edit", $on_edit);  //指示模板文件
        $this -> assign("field_list", $field_list);
        $this -> display();
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
            $this -> error("系统错误");
        }
        $this -> assign("emp_no", $vo['emp_no']);
        $this -> assign("user_name", $vo['user_name']);
        $this -> assign('vo', $vo);
        //如果当前用户为发起人，并且审批已通过，可以再次发起执行申请
        if($vo['emp_no'] == get_emp_no() && $vo['step'] == '40'){
            $consult_again = 'yes';
            $this -> assign('consult_again',$consult_again);
        }
        $field_list = D("UdfField") -> get_data_list($vo['udf_data']);
        $this -> assign("field_list", $field_list);
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
        //审核日志当前step
        $map = "";
        $map['flow_id'] = $id;
        $map['emp_no'] = get_emp_no();
        $map['step'] = array('lt', 100);
        $currentStep = M('Flow_log')->where($map)->order('step desc')-> getField("step");
        //查找下个审核人
        //dump($currentStep);
        $auditorInfo =D("Flow") -> next_step_info($id,$currentStep);
        $auditorName = get_user_name_empno($auditorInfo['emp_no']);
        $next_setp = $auditorInfo['step'];
        $this -> assign("auditorName", $auditorName);
        $this -> assign("next_setp", $next_setp);
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
        $where['step'] = array('neq', 100);
        $to_confirm = $model -> where($where) -> find();
        $this -> assign("to_confirm", $to_confirm);

     ///   dump($to_confirm);
        $where = array();
        $where['flow_id'] = $id;
        $where['emp_no'] = get_emp_no();
        $where['is_del'] = array('eq', 0);
        $where['step'] = array('eq', 100);
        $where['_string'] = "comment is null";//参阅人如果还没有意见，可以输入意见
        $to_refer = $model -> where($where) -> find();
        $is_read = $model -> where($where) -> setField('is_read', 1);
        $this -> assign("to_refer", $to_refer);

        //$role_is_edit = $this->role_is_edit($flow_type_id,$vo['emp_no']);
        if (!empty($role_is_edit)) {
            $this -> assign("is_edit", $role_is_edit);
        } else {
            $is_edit = $flow_type['is_edit'];
            $this -> assign("is_edit", $is_edit);
        }
        //输出退回人员列表
        $where = array();
        $where['flow_id'] = $id;
        $where['step'] = array('lt',$currentStep);
        $where['_string'] = "result is not null";
        $where['emp_no'] = array('neq', $vo['emp_no']);
        $confirmed = $model -> Distinct(true) -> where($where) -> field('emp_no,user_name') -> order('step')-> select();
        //dump($confirmed);
        $this -> assign("confirmed", $confirmed);

        //判断是否移动到草稿箱
        $map_temp['flow_id'] = $id;
        $map_temp['_string'] = 'result is null';
        $map_temp['step']    = array('neq',100);
        $map_temp['is_del'] = 0;
        $is_move =  M("Flow_log")->where($map_temp)->getField('id');

        $this -> assign("is_move", $is_move);

        

        $this -> display();
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
        //查找审核人
        $str_confirm = D("Flow") -> _conv_auditor($model -> confirm);
        if(strpos($str_confirm,'|')){
            $auditorArray = explode("|", $str_confirm);
            $auditor = $auditorArray[0];
            $auditorName = get_user_name_empno($auditor);
        }
        $this -> assign("auditorName", $auditorName);

        $field_list = D("UdfField") -> get_data_list($vo['udf_data']);

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

        $on_edit = 'edit';
        $this -> assign("on_edit", $on_edit);

        $this -> assign("confirm", $confirm[0]);
        $this -> display();
    }

    function del($id) {
        $this -> _del($id);
    }

    //转交
    public function transfer() {
        //$emp_no = I('addr_id');
        $emp_no = I('name');
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

    /** 插入新新数据  **/
    protected function _insert($name = CONTROLLER_NAME) {
		
        $model = D($name);
		
        if (false === $model -> create()) {
            $this -> error($model -> getError());
        }
      
        $model -> udf_data = D('UdfField') -> get_field_data();

        $where_step['flow_type_id'] = array('eq', $model -> type);
        $where_step['is_del'] = array('eq', 0);
        $flow_step = M('FlowStep') -> where($where_step) -> order('sort') -> select();
        $rs = false;
        if (!empty($flow_step)) {
            foreach ($flow_step as $step) {
                $rs = D('Flow') -> check_step($step['condition'], $model -> udf_data);

                if ($rs) {
                    //查找审核人
                    $str_confirm = D("Flow") -> _conv_auditor($step['confirm']);
                    $str_consult = D("Flow") -> _conv_auditor($step['consult']);
                    break;
                }
            }
        }

        if (!$rs) {
            $str_confirm = D("Flow") -> _conv_auditor($model -> confirm);
            $str_consult = D("Flow") -> _conv_auditor($model -> consult);
        }

        $str_auditor = $str_confirm . $str_consult;
        if (empty($str_auditor)) {
            $this -> error('没有找到任何审核人');
        }
		
        $list = $model -> add();
        if ($list !== false) {//保存成功
            //$flow_filed = D("UdfField") -> set_field($list);
            $this -> assign('jumpUrl', get_return_url());
            $this -> success('新增成功!');
        } else {
            $this -> error('新增失败!');
            //失败提示
        }
    }

    /* 更新数据  */
    protected function _update($name = CONTROLLER_NAME) {
        $opmode = I('opmode');
        if ($opmode == 'edit') {
            $model = D($name);
            if (false === $model -> create()) {
                $this -> error($model -> getError());
            }
            $flow_id = $model -> id;
            $model -> udf_data = D('UdfField') -> get_field_data();

            if ($model -> step == 20) {
                $where_step['flow_type_id'] = array('eq', $model -> type);
                $where_step['is_del'] = array('eq', 0);
                $flow_step = M('FlowStep') -> where($where_step) -> order('sort') -> select();
                $rs = false;
                if (!empty($flow_step)) {
                    foreach ($flow_step as $step) {
                        $rs = D('Flow') -> check_step($step['condition'], $model -> udf_data);
                        if ($rs) {
                            $str_confirm = D("Flow") -> _conv_auditor($step['confirm']);
                            $str_consult = D("Flow") -> _conv_auditor($step['consult']);
                            break;
                        }
                    }
                }
                if (!$rs) {
                    $str_confirm = D("Flow") -> _conv_auditor($model -> confirm);
                    $str_consult = D("Flow") -> _conv_auditor($model -> consult);
                }
                $str_auditor = $str_confirm . $str_consult;
                if (empty($str_auditor)) {
                    $this -> error('没有找到任何审核人');
                }
            }

            $list = $model -> save();
            if (false !== $list) {
                $this -> assign('jumpUrl', get_return_url());
                $this -> success('更新成功!');
                //成功提示
            } else {
                $this -> error('更新失败!');
                //错误提示
            }
        }
    }
    //审批同意
    public function approve() {
        $model = D("FlowLog");
        if (false === $model -> create()) {
            $this -> error($model -> getError());
        }
        $model -> result = 1;

        $flow_id = $model -> flow_id;
        $step = $model -> step;


        //保存当前数据对象
        $list = $model -> save();

        if ($list !== false) {//保存成功
            D("Flow") -> next_step($flow_id, $step);
            $this -> assign('jumpUrl', U('flow/folder', 'fid=confirm'));
            $this -> success('审批成功!');
        } else {
            //失败提示
            $this -> error('审批失败!');
        }
    }
   //审批否决
    public function reject() {
        $model = D("FlowLog");
        if (false === $model -> create()) {
            $this -> error($model -> getError());
        }
        $model -> result = 0;
        if (in_array('user_id', $model -> getDbFields())) {
            $model -> user_id = get_user_id();
        };
        if (in_array('user_name', $model -> getDbFields())) {
            $model -> user_name = get_user_name();
        };

        $flow_id = $model -> flow_id;
        $step = $model -> step;
        $list = $model -> save();

        //可以裁决的人有多个人的时候，一个人评价完以后，禁止其他人重复裁决。
        $model = D("FlowLog");
        $where['step'] = array('eq', $step);
        $where['flow_id'] = array('eq', $flow_id);
        $where['_string'] = 'result is null';
        $model -> where($where) -> setField('is_del', 1);

        if ($list !== false) {//保存成功

            M("Flow") -> where("id=$flow_id") -> setField('step', 0);
            $flow = M("Flow") -> find($flow_id);

            $push_data['type'] = '审批';
            $push_data['action'] = '被否决';
            $push_data['title'] = $flow['name'];
            $push_data['content'] = '审核人：' . get_dept_name() . "-" . get_user_name();
            $push_data['url'] = U('Flow/read', "id={$flow['id']}&fid=submit&return_url=Flow/index");
            send_push($push_data, $flow['user_id']);

            $this -> assign('jumpUrl', U('flow/folder', 'fid=confirm'));
            $this -> success('操作成功!');
        } else {
            //失败提示
            $this -> error('操作失败!');
        }
    }

    function back_to($emp_no) {
        $model = D("FlowLog");
        if (false === $model -> create()) {
            $this -> error($model -> getError());
        }

        $model -> result = 2;
        if (in_array('user_id', $model -> getDbFields())) {
            $model -> user_id = get_user_id();
        };
        if (in_array('user_name', $model -> getDbFields())) {
            $model -> user_name = get_user_name();
        };

        $flow_id = $model -> flow_id;
        $step = $model -> step;
		//var_dump($step);die;
        //保存当前数据对象
        $list = $model -> save();
        if ($list !== false) {//保存成功
            //可以裁决的人有多个人的时候，一个人评价完以后，禁止其他人重复裁决。
            $where['step'] = array('eq', $step);
            $where['flow_id'] = array('eq', $flow_id);
            $where['_string'] = 'result is null';
            $model -> where($where) -> setField('is_del', 1);

            D("Flow") -> back_to($flow_id, $emp_no);
            $this -> assign('jumpUrl', U('flow/folder?fid=confirm'));
            $this -> success('退回成功!');
        } else {
            //失败提示
            $this -> error('退回失败!');
        }
        return;
    }

    public function refer() {
        $model = D("FlowLog");
        if (false === $model -> create()) {
            $this -> error($model -> getError());
        }
        //保存当前数据对象
        $list = $model -> save();
        
        if ($list !== false) {//保存成功
            $flow_id = I('post.flow_id');
            //更新项目step
            M('Flow') -> where(array('id' => $flow_id)) -> setField(array('step'=>'20','update_time'=>time())); 

            $this -> assign('jumpUrl', U('flow/folder', 'fid=receive_unread'));
            $this -> success('意见提交成功!');
        } else {
            //失败提示
            $this -> error('操作失败!');
        }
    }
    //发送参阅请求
    function send_refer($flow_id, $emp_list) {
        $emp_list = array_filter(explode(",", $emp_list));
        $model = D("Flow") -> send_to_refer($flow_id, $emp_list);
        $this -> success('发送成功');
    }

    function down($attach_id) {
        $this -> _down($attach_id);
    }

    public function upload() {
        $this -> _upload();
    }

    public function field_manage($row_type) {
        $this -> assign("folder_name", "自定义字段管理");
        $this -> _field_manage($row_type);
    }
    /**
     * @desc
     * 再次发起执行申请，Step=30为待执行
     *
     */
    function consult_again($id){
        $where['id']= $id;
        M("Flow") -> where($where) -> setField('step', 30);
        D("Flow") -> next_step($id,'30');
        $this -> assign('jumpUrl', U('flow/folder?fid=submit'));
        $this -> success('已成功申请再次执行');
    }
    /**
     * @desc
     * 移动到草稿箱
     * step=10
     */
    function move_darft($id){
        $where['id']= $id;
        M("Flow") -> where($where) -> setField('step', 10);
        $this -> assign('jumpUrl', U('flow/folder?fid=darft'));
        $this -> success('已成功移至草稿箱，可再次编辑！');
    }

    //功能：导入消耗材料月计划
    function import_material_plan()
    {

        //dump($_FILES);die;

        Vendor('Excel.PHPExcel');
        $objPHPExcel = \PHPExcel_IOFactory::load($_FILES['file']['tmp_name']);
        $sheet_data = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

       /// dump($sheet_data);die;

        $data = array();
        for($i = 2; $i <= count($sheet_data); $i++)
        {
            $data[] = array('wu_class'=>$sheet_data[$i]['A'], 'wu_name'=>$sheet_data[$i]['B'], 'wu_type'=>$sheet_data[$i]['C'], 'wu_unit'=>$sheet_data[$i]['D'], 'wu_amount'=>$sheet_data[$i]['E'],
                'wu_price'=>$sheet_data[$i]['F'], 'wu_total'=>$sheet_data[$i]['G'], 'wu_brand'=>$sheet_data[$i]['H'], 'wu_remark'=>$sheet_data[$i]['I']);
        }

        $result['ret'] = 0;
        $result['msg'] = '导入成功';
        $result['data'] = $data;
        exit(json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    /**
     * @desc 判断当前用户是否有编辑权限
     *
     */
    public function role_is_edit($flow_type_id,$current_emp_no) {
        $fid = I("fid");
        $is_edit = '';
        //只有审批待办和参阅才可以编辑
        if($fid == 'confirm' || $fid == 'receive'){
            //获取角色id
            $user_id = get_user_id();
            $role_list = D("Role") -> get_role_list($user_id);
            $role_list = rotate($role_list);
            $role_list = $role_list['role_id'];
            $duty_list = D("Role") -> get_duty_list($role_list);
            $duty_list = rotate($duty_list);
            $duty_list = $duty_list['duty_id'];

            if (!empty($duty_list)) {
                $map_duty['id'] = $flow_type_id;
                $map_duty['report_duty'] = array('in', $duty_list);
                $is_duty = M('FlowType')->where($map_duty)->find();
            }
            //如果是合同管理员，或发起人，可编辑
            if (!empty($is_duty) || $current_emp_no == get_emp_no()){
                $is_edit = 1;    //设置为可编辑
            }
        }
        return $is_edit;
    }
    
    //获取相同部门
    function same_department_user()
    {
        $user_id = get_user_id();
        $User = M('User');
        $dept_id = $User->where(array('id'=>$user_id))->getField('dept_id');
        $user_list = $User->field('id,name')->where(array('dept_id'=>$dept_id))->select();
        exit(json_encode($user_list, JSON_UNESCAPED_UNICODE));
    }

    //获取部门列表（包括这个部门主任信息）
    function department_list()
    {
        $Dept = M('Dept');
        $User = M('User');
        $dept_list = $Dept->field('id,name AS dept_name')->where(array('pid'=>1))->select();
        foreach($dept_list as $key=>$val)
        {
            $user_info = $User->field('emp_no,name AS nickname')->where("identity LIKE '%dp_".$val['id']."_3|%'")->find();
            $dept_list[$key]['emp_no'] = $user_info['emp_no'];
            $dept_list[$key]['nickname'] = $user_info['nickname'];
            if(!$dept_list[$key]['emp_no'])
                unset($dept_list[$key]);
        }
        
        $arr = array();
        foreach ($dept_list as $val)//必须要转换成数组下标为0,1,2这样的，不能的话js不能for循环
        {
            $arr[] = $val;
        }
        exit(json_encode($arr, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * @desc
     * 撤回到上个节点
     * fid审批项目id
     */
    function recall($id){
        //找到最后的审批步骤
        $where['flow_id']= $id;
        $where['step'] = array('lt', 100);
        $where['is_del'] = 0;
        $CurrentStep = M("Flow_log") -> where($where) -> max('step'); 
        
        if(!empty($CurrentStep)){
            //删除当前审批记录
            $where['step'] = $CurrentStep;
            M("Flow_log")->where($where)->setField('is_del',1);             
            //删除上个审批记录意见
            $BeforeStep = $CurrentStep - 1;
            
            if( $BeforeStep == 30 ){  //
                $where['step'] = array('lt', 100);
                $BeforeStep = M("Flow_log") -> where($where) -> max('step');
                $where['step'] = $BeforeStep;               
                $res = M("Flow_log")->where($where)->setField('result',null);
                //审批表的step
                M("Flow")->where('id='.$id)->setField('step',20); 
            }else{
                if( $BeforeStep == 20 ){
                    M("Flow")->where('id='.$id)->setField('step',19); //
                    $this -> assign('jumpUrl', U('flow/index'));
                    $this -> success('撤回成功！');
                }else{
                    $where['step'] = $BeforeStep; 
                    $res = M("Flow_log")->where($where)->setField('result',null);
                }                
            }          
            if($res){
                $this -> assign('jumpUrl', U('flow/index'));
                $this -> success('撤回成功！');
            }
        }else{
            $this -> assign('jumpUrl', U('flow/index'));
            $this -> success('撤回失败！');
        }       
    }
    /**
    * 获取合同编号
    * @desc 
    */
    function get_financenumber(){
        $map['type'] = 70;
        $map['is_del'] = 0;
        $map['create_time'] = array('egt',mktime(0,0,0,1,1,date("Y",time()))); 
        $result =  M("Flow")->where($map)->select();
        $LastNumber = 0;
        foreach ($result as $value){
            $arr = json_decode($value['udf_data'],1);
            //取出已有的合同编号            
            $FinanceNumber = $arr['315'];
            if(!empty($FinanceNumber)){
                $tempNumber = substr($FinanceNumber, -3);
                if($tempNumber > $LastNumber){
                    $LastNumber = $tempNumber ;
                }                
            }
        }
        $LastNumber = $LastNumber + 1;
        $newNumber = substr(strval($LastNumber+1000),1,3); //补齐3位
        exit(json_encode($newNumber, JSON_UNESCAPED_UNICODE));
    }
    
    /**
    * 发送合同审批业务到印章管理员
    * @desc 2017.11.16 
    * 参数$fid流程flow_id
    * （1）首先复制本流程数据，变为一个新的用章流程，type=71
    * （2）复制Flow_log审批记录，变为新的审批记录；
    * （3）插入一条印章管理员审批意见
    * （4）更新印章发送字段，避免重复发送
    */
    function send_to_yzadmin($id){
        $where['id'] = $id;
        $where['is_del'] = 0;
        $where['type'] = 70; //只有合同流程才可以转
        $htFlow = M("Flow")->where($where)->find();
        //dump($htFlow);
        if(!empty($htFlow)){
            $data['doc_no'] = $this->get_yz_doc();
            $data['name'] = $htFlow['name'];
            $data['confirm'] = $htFlow['confirm'];
            $data['confirm_name'] = '<span id="dgp_2_3" data="dgp_2_3"><nobr><b title="科室主任">科室主任</b></nobr><b><i class="fa fa-arrow-right"></i></b></span><span id="dgp_2_2" data="dgp_2_2"><nobr><b title="科室主管中心副主任">科室主管中心副主任</b></nobr><b><i class="fa fa-arrow-right"></i></b></span><span id="dp_23_1" data="dp_23_1"><nobr><b title="中心主任">中心主任</b></nobr><b><i class="fa"></i></b></span>';
            $data['consult'] = $htFlow['emp_no'].'|nihanjun,chenc|'; //发起人+印章管理员
            $data['consult_name'] = '<span data="dgp_2_99" id="dgp_2_99"><nobr><b title="发起人">发起人</b></nobr><b><i class="fa fa-arrow-right"></i></b></span><span data="dp_10_100" id="dp_10_100"><nobr><b title="印章管理员">印章管理员</b></nobr><b><i class="fa"></i></b></span>';
            $data['type'] = 71;
            $data['add_file'] = end(explode(";",substr($htFlow['add_file'],0,strlen($htFlow['add_file'])-1))).";"; //取最后一个合同附件
            $data['user_id'] = $htFlow['user_id'];
            $data['emp_no'] = $htFlow['emp_no'];
            $data['user_name'] = $htFlow['user_name'];
            $data['dept_id'] = $htFlow['dept_id'];
            $data['dept_name'] = $htFlow['dept_name'];
            $data['create_time'] = $htFlow['create_time'];
            $data['update_time'] = time();
            $data['step'] = 30;
            $data['is_del'] = 0;
            $data['udf_data'] = $this->udf_to_data($htFlow['udf_data']);            
            $new_flow_id = M("Flow") -> add($data); //返回新申请的用章审批ID
            //申请成功
            if ($new_flow_id !== false) {
                //设置原合同审批，已转发印章管理员字段
                $htFlow['udf_data'] = $this->set_send_yz($htFlow['udf_data']);
                $rs_flow = M("Flow") ->where('id='.$id)-> save($htFlow);
                //处理审批记录
                $map['flow_id'] = $id; //原合同审批ID
                $map['is_del'] = 0;
                $map['step'] = array('between','20,30');
                $map['result'] = 1;
                $htFlow_log = M("Flow_log")->where($map)->select();
                //印章审批记录
                $yzFlow_log = array();
                foreach ($htFlow_log as $key=>$val){
                    //根据发起人判断审批人意见是否转印章审批意见 
                    $new_flow_step = $this->is_yz_flowlog($val['emp_no']); 
                    if(!empty($new_flow_step)){
                        $yzFlow_log[$key]['flow_id'] = $new_flow_id;  //新的审批Flow_id
                        $yzFlow_log[$key]['emp_no'] = $val['emp_no'];
                        $yzFlow_log[$key]['user_id'] = $val['user_id'];
                        $yzFlow_log[$key]['user_name'] = $val['user_name'];
                        $yzFlow_log[$key]['step'] = $new_flow_step;
                        $yzFlow_log[$key]['result'] = $val['result'];
                        $yzFlow_log[$key]['create_time'] = $val['create_time'];
                        $yzFlow_log[$key]['update_time'] = $val['update_time'];
                        $yzFlow_log[$key]['comment'] = $val['comment'];
                        $yzFlow_log[$key]['is_read'] = $val['is_read'];
                        $yzFlow_log[$key]['from'] = $val['from'];
                        $yzFlow_log[$key]['is_del'] = $val['is_del'];
                        $yzFlow_log[$key]['sign'] = $val['sign'];
                        //dump($data_array);
                        //写入Flow_log表
                        //$new_flowlog_id = M("Flow_log") -> add($data_array);
                    }                    
                }
                //增加发起人记录
                array_push($yzFlow_log,array("id"=>"",
                    "flow_id"=>$new_flow_id,
                    "emp_no"=>$htFlow['emp_no'],
                    "user_id"=>$htFlow['user_id'],
                    "user_name"=>$htFlow['user_name'],
                    "step"=>"31",
                    "result"=>"1",
                    "create_time"=>time(),
                    "update_time"=>time(),
                    "comment"=>"由合同审批系统审批结束后，转发印章管理员",
                    "is_read"=>"0",
                    "from"=>"",
                    "is_del"=>"0",
                    "sign"=>""));
                //增加合同管理员记录,两个印章管理员
                array_push($yzFlow_log,array("id"=>"",
                    "flow_id"=>$new_flow_id,
                    "emp_no"=>"nihanjun",
                    "user_id"=>"38",
                    "user_name"=>"倪菡君",
                    "step"=>"32",
                                            //"result"=>"",
                    "create_time"=>time(),
                    "update_time"=>"",
                    "comment"=>"",
                    "is_read"=>"0",
                    "from"=>"",
                    "is_del"=>"0",
                    "sign"=>""));
                array_push($yzFlow_log,array("id"=>"",
                    "flow_id"=>$new_flow_id,
                    "emp_no"=>"guoyanjiao",
                    "user_id"=>"271",
                    "user_name"=>"郭彦娇",
                    "step"=>"32",
                                            //"result"=>"",
                    "create_time"=>time(),
                    "update_time"=>"",
                    "comment"=>"",
                    "is_read"=>"0",
                    "from"=>"",
                    "is_del"=>"0",
                    "sign"=>""));
                //dump($yzFlow_log);
                foreach ($yzFlow_log as $v){
                 $new_flowlog_id = M("Flow_log") -> add($v);                     
             }               

             $this -> assign('jumpUrl', get_return_url());
             $this -> success('转发印章管理员成功!');  

         }else {
            $this -> error('转发印章管理员失败!');
                //错误提示
        }

    }else {
        $this -> error('转发印章管理员失败!');
            //错误提示
    }
}
    /**
    * @desc 
    * 合同自定义字段转为印章自定义字段
    * 318：用章类型 中心章|办公室章
    * 319：用章分类 证明|说明|协议|其他
    * 320：用章事由 
    * 321：用章次数
    * 322：实际用章次数
    */
    function udf_to_data($udf_data){
        $yz_udf_data = "";
        $yz_array = array();
        $yz_array['318'] = "中心章";
        $yz_array['319'] = "合同";      

        $ht_array = json_decode($udf_data,true);
        foreach ($ht_array as $key=>$val){
            //dump($key.'---'.$val);
            switch ($key){
                case 331:  //用章次数
                $yz_array['321'] = $val;
                break; 
                case 315:  //用章事由(合同编号)
                $yz_array['320'] = "此申请由合同审批系统转发，合同编号：".$val;
                break;               
                default:
                  $yz_array['322'] = "";//实际用章次数
              }            
          }
        //dump($yz_array); 
          $yz_udf_data = json_encode($yz_array,JSON_UNESCAPED_UNICODE);       
          return $yz_udf_data;
      }
    /**
    * @desc 
    * 设置合同审批字段，已发送印章管理员
    * 自定义字段333
    */
    function set_send_yz($udf_data){
        $new_udf_data = "";
        $new_array = array();                
        $new_array = json_decode($udf_data,true);
        $new_array["333"] = "1";     
        $new_udf_data = json_encode($new_array,JSON_UNESCAPED_UNICODE);     
        return $new_udf_data;        
    }
    /**
    * @desc 
    * 获取印章doc_no
    * 
    */
    function get_yz_doc(){
        $doc_no = "";
        $sql = "SELECT count(*) count FROM `oa_flow` WHERE type=71 ";
        $sql .= " and year(FROM_UNIXTIME(create_time))>=year(now())";
        $rs = M('Flow')->query($sql);
        
        $count = $rs[0]['count'] + 1;
        
        $doc_no = "YZ".date('Y', mktime()).str_pad($count, 4, "0", STR_PAD_LEFT);
        return $doc_no;
    }
    /**
    * @desc 
    * 印章审批流程判断
    * 角色：发起人科室主任，主管站长，站长
    */
    function is_yz_flowlog($confirm){
        //$step = "";
        $str_confirm = D("Flow") -> _conv_auditor("dgp_2_3|dgp_2_2|dp_23_1|");
        //dump($confirm);
        $arr_confirm = explode("|",$str_confirm);
        switch ($confirm)
        {
            case $arr_confirm[0]:
            $step = 21;
            break;
            case $arr_confirm[1]:
            $step = 22;
            break;
            case $arr_confirm[2]:
            $step = 23;
            break;
            default:
            $step = "";
        }
        return $step;        
    }

    /**
     * 按部门获取用户列表
     * @return [JSON] [description]
     */
    function get_ulist()
    {
        $Dept = M('Dept');
        $User = M('User');

        $dept_list = $Dept->field('id,name AS dept_name')->where(array('pid'=>1))->select();
        $uList = array();

        foreach($dept_list as $key=>$val)
        {
            $where['dept_id'] = $val['id'];
            $where['position_id'] = array('egt',3);
            $where['is_del'] = 0;
            $UserList = $User-> field('emp_no,name AS nickname,position_id')->where($where)->order('position_id')->select();
            
            $dept_list[$key]['dept_id'] = $val['id'];
            $dept_list[$key]['dept_name'] = $val['dept_name'];
            $dept_list[$key]['user'] = $UserList;

            //删除没有人员的部门
            if(!$dept_list[$key]['user'])
                unset($dept_list[$key]);
        }
        
        $arr = array();
        foreach ($dept_list as $v)//必须要转换成数组下标为0,1,2这样的，不能的话js不能for循环
        {
            $arr[] = $v;
        }
        //dump($arr);die();
        exit(json_encode($arr, JSON_UNESCAPED_UNICODE));
    }

    // public function 
    
}