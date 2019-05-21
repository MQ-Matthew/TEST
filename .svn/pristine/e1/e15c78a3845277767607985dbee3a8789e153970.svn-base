<?php


// 后台用户模块
namespace Home\Controller;
use Think\Controller;
/**
 * 资产管理分类管理控制器
 *
 */
class AssetLookController extends Controller{ 
protected $config=array('app_type'=>'public');

    //查看
    public function detail()
    {
        $id = $_GET['id'];
        $map['id'] = $id ;
        $info = M('Asset')->where($map)->find();
        $this->assign('info',$info);
        $this->display();
    }



    //二维码
    public function img_code()
    {
        $id = $_GET['id'];
        $map['id'] = $id ;
        $info = M('Asset')->where($map)->find();
        $code = $info['code'];
        if(empty($code)){
            return false;
        }
        //获取当前url
        $server_name=$_SERVER['SERVER_NAME'];
        $url = 'http://'.$server_name.U('AssetLook/detail',['id'=>$id]);
        Vendor("phpqrcode");
        $object=new \QRcode();
        //$this->assign('object',$object);
        $path = "./Uploads/code/$code.png";
        $object->png($url,$path,L,5,2);
    }

}