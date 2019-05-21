<?php

namespace Home\Model;
use Think\Model\ViewModel;

class GovDocOrderViewModel extends ViewModel {
	
public function get_gov_doc_list($where){
 	$gov_doc_log = M('gov_doc_log');
	return $gov_doc_log->table($this->tablePrefix.'gov_doc_log a,'.$this->tablePrefix.'user b,'.$this->tablePrefix.'position c')->
	where($where.'a.pishiren_id=b.id and b.position_id=c.id')
	->group('pishiren_id')->order('c.sort')->select();
 	
 }
	
	

}
?>