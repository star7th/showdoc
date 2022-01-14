<?php

namespace Qcloud\Cos\ImageParamTemplate;

class CIParamTransformation extends ImageTemplate{

    private $tranParams;
    private $tranString;
    private $spilt;

    public function __construct($spilt = "|") {
        parent::__construct();
        $this->spilt = $spilt;
        $this->tranParams = array();
        $this->tranString = "";
    }

    public function addRule(ImageTemplate $template) {
        if($template->queryString()){
            $this->tranParams[] = $template->queryString();
        }
    }

    public function queryString() {
        if($this->tranParams) {
            $this->tranString = implode($this->spilt, $this->tranParams);
        }
        return $this->tranString;
    }

    public function resetRule() {
        $this->tranParams = array();
        $this->tranString = "";
    }

    public function defineRule($value) {
        $this->tranParams[] = $value;
    }
}
