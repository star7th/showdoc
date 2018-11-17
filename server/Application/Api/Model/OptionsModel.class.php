<?php
namespace Api\Model;
use Api\Model\BaseModel;

class OptionsModel extends BaseModel {

    //
    public function get($option_name){
        $res = $this->where(" option_name = '%s' " ,array($option_name))->find();
        if ($res) {
            return $res['option_value'] ;
        }
        return false;
    }

    //
    public function set($option_name,$option_value){
        $sql = " replace into  options (option_name , option_value ) values ('$option_name' , '$option_value')";
        return $this->execute($sql);;
    }    
    
}