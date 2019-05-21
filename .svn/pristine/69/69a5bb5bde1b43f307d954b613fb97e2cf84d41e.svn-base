<?php

namespace Home\Model;
use Think\Model\ViewModel;

class  WorkOrderLogViewModel extends ViewModel {
	public $viewFields=array(
		'WorkOrderLog'=>array('*'),
		'WorkOrder'=>array('name','content','request_arrive_time','order_type','is_del','status','_on'=>'WorkOrderLog.task_id=WorkOrder.id')
		);
}
?>