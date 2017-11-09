<?php
namespace Api\Model;
use Api\Model\BaseModel;
/**
 * 
 * @author star7th      
 */
class VerifyCodeModel  {

    //次数加1
    public function _ins_times($key){
        // 初始化缓存
        S(array('type'=>'File','prefix'=>'think453434d','expire'=>60*60*24));
        $cache_times = S($key);
        $cache_times = intval($cache_times) ;
        $ret = S($key, $cache_times + 1 , 24*60*60);
        return $ret ;
    }

    public function _check_times($key , $max_times = 5 ){
        // 初始化缓存
        S(array('type'=>'File','prefix'=>'think453434d','expire'=>60*60*24));
        $cache_times = S($key);
        $cache_times = intval($cache_times) ;
        if ($cache_times) {
            if ($cache_times >= $max_times) {
                return false;
            }
        }
        return true ;
    }

}