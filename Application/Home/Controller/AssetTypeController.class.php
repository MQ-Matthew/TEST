<?php


// 后台用户模块
namespace Home\Controller;
/**
 * 资产管理分类管理控制器
 *
 */
class AssetTypeController extends HomeController {

    protected $config = array('app_type' => 'personal');

    public function index() {
        $node = M("Asset_type");

        $menu = array();
        $menu = $node -> field('id,pid,name') -> order('sort asc') -> select();
        $tree = list_to_tree($menu);

        $model = M("Asset_type");
        $list = $model -> order('sort asc') -> getField('id,name');
        //dump($tree);

        $this -> assign('node_list', $list);
        $this -> assign('menu', popup_tree_menu($tree));
        $this->assign('tree',$tree);
        //dump(popup_tree_menu($tree));

        $this -> display();
    }

    protected function _insert($name='Asset_type') {
        $model = D('Asset_type');
        if (false === $model -> create()) {
            $this -> error($model -> getError());
        }
        if (strpos($model -> url, '##') !== false) {
            $model -> sub_folder = ucfirst(get_controller(str_replace("##", "", $model -> url))) . "Folder";
        } else {
            $model -> sub_folder = '';
        }
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

    protected function _update($name='Asset_type'){
        $id = $_POST['id'];
        $model = D("Asset_type");
        if (false === $model -> create()) {
            $this -> error($model -> getError());
        }
        if (strpos($model -> url, '##') !== false) {
            $model -> sub_folder = ucfirst(get_controller(str_replace("##", "", $model -> url))) . "Folder";
        } else {
            $model -> sub_folder = '';
        }
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

    function winpop() {
        $menu = D("Asset_type") -> order('sort asc') -> select();
        $tree = list_to_tree($menu);
        $this -> assign('menu', popup_tree_menu($tree));
        $this -> display();
    }

    function del($id){
        $where['pid']=array('eq',$id);
        $list=M("Asset_type")->where($where)->select();

        if($list){
            $this->error('有子节点不能删除');
        }
        $model = M("RoleNode");
        $where['node_id'] = $id;
        $model -> where($where) -> delete();
        $this -> _destory($id);
    }





}

?>