<?php

namespace Home\Model;
use Think\Model\ViewModel;

class  FlowViewModel extends ViewModel {
	public $viewFields=array(
		'Flow'=>array('*'),
		'FlowType'=>array('name'=>'type_name','_on'=>'FlowType.id=Flow.type')
		);
}
?>