<?php

namespace Home\Controller;

class PositionController extends HomeController {
	protected $config = array('app_type' => 'master');

	function _search_filter(&$map) {
		$keyword = I('keyword');
		if (!empty($keyword)) {
			$map['position_no|name'] = array('like', "%" . $keyword . "%");
		}
	}
    /** 插入新新数据  **/
    protected function _insert($name = CONTROLLER_NAME) {

        $model = D($name);
        if (false === $model -> create()) {
            $this -> error($model -> getError());
        }

        /*保存当前数据对象 */
        $list = $model -> add();
        if ($list !== false) {//保存成功
            $this -> assign('jumpUrl', get_return_url());
            syncdate_log('A','Position',$list); //记录更新日志
            $this -> success('新增成功!');            
        } else {
            $this -> error('新增失败!');
            //失败提示
        }
    }
    
    /* 更新数据  */
    protected function _update($name = CONTROLLER_NAME) {
        $model = D($name);
        if (false === $model -> create()) {
            $this -> error($model -> getError());
        }
        $list = $model -> save();
        if (false !== $list) {
            syncdate_log('U','Position',I('id')); //记录更新日志
            $this -> assign('jumpUrl', get_return_url());
            $this -> success('编辑成功!');
            //成功提示
        } else {
            $this -> error('编辑失败!');
            //错误提示
        }
    }
    
	function del() {
		$id = $_POST['id'];
        syncdate_log('D','Position',$id); //记录更新日志
		$this -> _destory($id);
	}


}
?>