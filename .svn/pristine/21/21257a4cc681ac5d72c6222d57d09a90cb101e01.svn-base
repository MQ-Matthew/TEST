<?php
namespace Home\Widget;
use Think\Controller;
class PageHeaderWidget extends Controller {
	protected $config = array('app_type' => 'public');

	public function simple($name) {
		$this -> assign('name', $name);
		$this -> display('Widget:PageHeader/simple');
	}
	
	public function asset_simple($name) {
		$this -> assign('name', $name);
		$this -> display('Widget:PageHeader/asset_simple');
	}

	public function search($name) {
		$this -> assign('name', $name);
		$this -> display('Widget:PageHeader/search');
	}

	public function adv_search($name) {
		$this -> assign('name', $name);
		// var_dump($name);die;
		$this -> display('Widget:PageHeader/adv_search');
	}

	public function local_search($name) {
		$this -> assign('name', $name);
		$this -> display('Widget:PageHeader/local_search');
	}
	
	public function popup($name) {
		$this -> assign('name', $name);
		$this -> display('Widget:PageHeader/popup');
	}
}
?>