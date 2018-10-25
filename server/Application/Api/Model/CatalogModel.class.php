<?php
namespace Api\Model;
use Api\Model\BaseModel;
/**
 * 
 * @author star7th      
 */
class CatalogModel extends BaseModel {

	//获取目录列表。如果isGroup参数为true，则按分组返回
	public function getList($item_id,$isGroup = false ){
        if ($item_id > 0 ) {
            $ret = $this->where(" item_id = '%d' ",array($item_id))->order(" s_number, cat_id asc  ")->select();
        }
        if ($ret) {
	        foreach ($ret as $key => $value) {
	            $ret[$key]['addtime'] = date("Y-m-d H:i:s",$value['addtime']) ;
	        }

	        if ($isGroup) {
	        	$ret2 = array() ;
		        foreach ($ret as $key => $value) {
		            if ($value['parent_cat_id']) {
		            	//跳过
		            	//
		            }else{
		            	$value['sub'] = $this->_getChlid($value['cat_id'],$ret);
		            	$ret2[] = $value ;
		            }
		        }
		        $ret = $ret2 ;
	        }

           return $ret ;
        }else{
           return array();
        }
	}

	//获取某个目录的子   （如果存在的话）  此private方法只给本类内调用
	private function _getChlid($cat_id,$item_data){
		$return = array() ;
		if ($item_data && $cat_id) {
			foreach ($item_data as $key => $value) {
				if ($value['parent_cat_id'] == $cat_id ) {
					$value['sub'] = $this->_getChlid($value['cat_id'],$item_data);
					$return[] = $value ;
				}
			}
		}

		return $return;
	}

	//获取某id下的子目录列表（此public方法暴露出去给其他地方调用）
	public function getChlid($item_id,$cat_id){
		$return = array() ;
		$ret = $this->getList($item_id , true) ;
        if ($ret) {
	        foreach ($ret as $key => $value) {
	            if ($value['cat_id'] == $cat_id) {
	            	$return = $value['sub'] ;
	            }

	            if ($value['sub']) {
	            	foreach ($value['sub'] as $key2 => $value2) {
			            if ($value2['cat_id'] == $cat_id) {
			            	$return = $value2['sub'] ;
			            }
			            if ($value2['sub']) {
			            	foreach ($value2['sub'] as $key3 => $value3) {
					            if ($value3['cat_id'] == $cat_id) {
					            	$return = $value3['sub'] ;
					            }
			            	}
			            }

	            	}
	            }
	        }
        }

        return $return ;

	}

	//获取某个层级的目录列表。例如 获取二级目录列表
	public function getListByLevel($item_id , $level = 2){
		$return = array() ;
		$ret = $this->getList($item_id) ;
		if ($ret) {
			foreach ($ret as $key => $value) {
				if ($value['level'] == $level) {
					$return[] = $value ;
				}
			}
		}

		return $return ;
	}
	
}