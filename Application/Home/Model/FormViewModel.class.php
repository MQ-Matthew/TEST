<?php

namespace Home\Model;
use Think\Model\ViewModel;

class  FormViewModel extends ViewModel {
	public $viewFields=array(
		'Form'=>array('*'),
		'SystemFolder'=>array('name'=>'folder_name','_on'=>'Form.folder=SystemFolder.id')
		);
}
?>