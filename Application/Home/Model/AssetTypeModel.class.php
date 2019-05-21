<?php

// 角色模型
namespace Home\Model;
use Think\Model;

class  AssetTypeModel extends CommonModel {
    public $_validate = array( array('name', 'require', '名称必须'), );
    /**
     * 获取分类详细信息
     * @param  milit   $id 分类ID或标识
     * @param  boolean $field 查询字段
     * @return array     分类信息
     */
    public function info($id, $field = true)
    {
        /* 获取分类信息 */
        $map = array();
        if(is_numeric($id))//通过ID查询
            $map['id'] = $id;
        else//通过标识查询
            $map['name'] = $id;

        return $this->field($field)->where($map)->find();
    }

    /**
     * 获取分类树，指定分类则返回指定分类极其子分类，不指定则返回所有分类树
     * @param  integer $id    分类ID
     * @param  boolean $field 查询字段
     * @return array          分类树
     */

    public function getTree($id = 0, $field = true)
    {

        /* 获取当前分类信息 */
        if($id)
        {
            $info = $this->info($id);
            $id   = $info['id'];
        }

        /* 获取所有分类 */
        $list = $this->field($field)->order('sort')->select();


        $list = $this->list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_', $root = $id);
        ///dump($list);die;
        /* 获取返回数据 */
        if(isset($info))//指定分类则返回当前分类极其子分类
            $info['_'] = $list;
        else//否则返回所有分类
            $info = $list;

        return $info;
    }

    /**
     * 获取指定分类的同级分类
     * @param  integer $id    分类ID
     * @param  boolean $field 查询字段
     * @return array
     */
    public function getSameLevel($id, $field = true)
    {
        $info = $this->info($id, 'pid');
        $map = array('pid' => $info['pid']);
        return $this->field($field)->where($map)->order('sort')->select();
    }

    /**
     * 更新分类信息
     * @return boolean 更新状态
     */
    public function update()
    {
        $data = $this->create();
        //print_r($data);die;
        if(!$data)//数据对象创建错误
            return false;

        /* 添加或更新数据 */
        if(empty($data['id']))
            $res = $this->add();
        else
            $res = $this->save();

        //更新分类缓存
        S('sys_category_list', null);
        //记录行为
        //action_log('update_category', 'category', $data['id'] ? $data['id'] : $res, UID);
        return $res;
    }

    /**
     * 把返回的数据集转换成Tree
     * @param array $list 要转换的数据集
     * @param string $pid parent标记字段
     * @param string $level level标记字段
     * @return array
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    function list_to_tree($list, $pk='id', $pid = 'pid', $child = '_child', $root = 0) {
        // 创建Tree
        $tree = array();
        if(is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId =  $data[$pid];
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                }else{
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }

}
?>