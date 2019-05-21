<?php

namespace Home\Model;
use Think\Model\ViewModel;

class GovDocViewModel extends ViewModel {
	
	public $viewFields=array(
			'gov_doc'=>array('*','_type'=>'LEFT'), 
			'gov_doc_log'=>array('id'=>'gov_doc_log_id','user_id','type','remarks','state','_on'=>'gov_doc.id=gov_doc_log.gov_doc_id'),
	);
	
	

}
?>