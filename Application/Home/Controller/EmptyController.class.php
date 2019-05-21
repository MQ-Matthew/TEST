<?php

namespace Home\Controller;
use Think\Controller;

class EmptyController extends Controller {
	//过滤查询字段
	public function index() {
		echo("404");
		die;
	}
}
?>