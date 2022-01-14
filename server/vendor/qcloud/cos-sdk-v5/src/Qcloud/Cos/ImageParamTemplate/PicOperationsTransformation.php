<?php

namespace Qcloud\Cos\ImageParamTemplate;

class PicOperationsTransformation {
    private $isPicInfo;
    private $rules;

    public function __construct() {
        $this->isPicInfo = 0;
        $this->rules = array();
    }

    public function setIsPicInfo($value) {
        $this->isPicInfo = $value;
    }

    public function addRule(ImageTemplate $template, $fileid = "", $bucket = "") {
        $rule = $template->queryString();
        if($rule){
            $item = array();
            $item['rule'] = $rule;
            if($fileid){
                $item['fileid'] = $fileid;
            }
            if($bucket) {
                $item['bucket'] = $bucket;
            }
            $this->rules[] = $item;
        }
    }

    public function getIsPicInfo() {
        return $this->isPicInfo;
    }

    public function getRules() {
        return $this->rules;
    }

    public function queryString() {
        $res = "";
        $picOperations = array();
       if($this->isPicInfo){
           $picOperations['is_pic_info'] = $this->isPicInfo;
       }
       if($this->rules){
           $picOperations['rules'] = $this->rules;
       }
       if($picOperations){
           $res = json_encode($picOperations);
       }
       return $res;
    }

    public function resetRule() {
        $this->isPicInfo = 0;
        $this->rules = array();
    }
}
