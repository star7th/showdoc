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


	//删除目录以及下面的所有页面/子目录
	public function deleteCat($cat_id){
		if (!$cat_id) {
			return false;
		}
		$cat_id = intval($cat_id) ;
		//如果有子目录的话，递归把子目录清了
		$cats = $this->where(" parent_cat_id = '$cat_id' ")->select();
		if ($cats) {
			foreach ($cats as $key => $value) {
				$this->deleteCat($value['cat_id']);
			}
		}
		//获取当前目录信息
		$cat = $this->where(" cat_id = '$cat_id' ")->find();
		$item_id = $cat['item_id'];
		$all_pages = D("Page")->where("item_id = '$item_id' and is_del = 0 ")->field("page_id,cat_id")->select();
        $pages = array() ;
        if ($all_pages) {
            foreach ($all_pages as $key => $value) {
                if ($value['cat_id'] == $cat_id) {
                    $pages[] = $value ;
                }
            }
        }
        
        if ($pages) {
        	foreach ($pages as $key => $value) {
        		D("Page")->softDeletePage($value['page_id']);
        	}
        }
        $this->where(" cat_id = '$cat_id' ")->delete();

        return true ;

	}

	//根据用户目录权限来过滤目录数据
	public function filteMemberCat($uid  , $catData){
		if(!$catData || !$catData[0]['item_id']){
			return $catData ;
		}
		$item_id = $catData[0]['item_id'] ;
		$cat_id = 0 ;
		//首先看是否被添加为项目成员
		$itemMember = D("ItemMember")->where("uid = '%d' and item_id = '%d' ", array($uid ,$item_id ))->find() ;
		if($itemMember && $itemMember['cat_id'] > 0 ){
				$cat_id = $itemMember['cat_id'] ;
		}
		//再看是否添加为团队-项目成员
		$teamItemMember = D("TeamItemMember")->where("member_uid = '%d' and item_id = '%d' ",array($uid ,$item_id ))->find() ;
		if($teamItemMember && $teamItemMember['cat_id'] > 0 ){
				$cat_id = $teamItemMember['cat_id'] ;
		}
		//开始根据cat_id过滤
		if($cat_id > 0 ){
			foreach ($catData as $key => $value) {
					if( $value['cat_id'] != $cat_id){
							unset($catData[$key]);
					}
			}
			$catData = array_values($catData);
		}

		return $catData ;
	}

	//复制目录
	// old_cat_id 原目录id
	// new_p_cat_id 复制完目录后，挂在哪个父目录下。这里是父目录id。可为0。默认使用old_cat_id的父目录id
	// $to_item_id 要复制到的项目id。可以是同一个项目，可以是跨项目。默认是同一个项目
	public function copy($uid , $old_cat_id , $new_p_cat_id = 0 , $to_item_id = 0 ){
		$userInfo = D("User")->userInfo($uid);
		$old_cat_ary = $this->where("cat_id = '%d' ",array($old_cat_id) )->find() ;
		$to_item_id = $to_item_id ? $to_item_id : $cat_ary['item_id'] ;
	
		//这里需要读取目录下的页面以及子目录信息
		$old_cat_data = $this->getCat($old_cat_id) ;
		$catalogs[] =$old_cat_data ;
		//获取$level.先初始化$level = 2 ;
		$level = 2 ;
		if($new_p_cat_id){
			$p_cat_ary = $this->where("cat_id = '%d' " ,array($new_p_cat_id)  )->find() ;
			$level = $p_cat_ary['level'] + 1 ;
		}
		//插入
		$res =  $this->insertCat($to_item_id , $catalogs , $userInfo , $new_p_cat_id ,  $level ) ;
		return $res ;
	}

	//获取某个目录下的页面和子目录
	public function getCat($cat_id){
			$cat_id = intval($cat_id) ;
			$cat_ary = $this->where("cat_id = '$cat_id' ")->find() ;
			$item_id = $cat_ary['item_id'] ;
			//获取项目下所有页面信息
			$all_pages = D("Page")->where("item_id = '$item_id' and is_del = 0 ")->order(" s_number asc , page_id asc ")->field($page_field)->select();
			//获取项目下所有目录信息
			$all_catalogs = $this->where(" item_id = '%d' ",array($item_id) )->order(" s_number, cat_id asc  ")->select();

			return D("Item")->getCat($cat_ary , $all_pages , $all_catalogs) ;
		}

		//插入一个目录下的所有页面和子目录
	public function insertCat($item_id , $catalogs , $userInfo , $parent_cat_id = 0  ,  $level = 2 ){
			return D("Item")->insertCat($item_id , $catalogs , $userInfo , $parent_cat_id  ,  $level );
		}
	

}
