<?php
/*---------------------------------------------------------------------------
 小微OA系统 - 让工作更轻松快乐

 Copyright (c) 2013 http://www.smeoa.com All rights reserved.

 Author:  jinzhu.yin<smeoa@qq.com>

 Support: https://git.oschina.net/smeoa/xiaowei
 -------------------------------------------------------------------------*/

namespace Home\Model;
use Think\Model\ViewModel;

class GovDocViewModel extends ViewModel {
	
	public $viewFields=array(
			'gov_doc'=>array('*','_type'=>'LEFT'), 
			'gov_doc_log'=>array('id'=>'gov_doc_log_id','user_id','type','remarks','state','_on'=>'gov_doc.id=gov_doc_log.gov_doc_id'),
	);
	
	

}
?>