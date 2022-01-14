<?php

namespace Qcloud\Cos\ImageParamTemplate;

class ImageQrcodeTemplate extends ImageTemplate
{
    private $mode;

    public function __construct() {
        parent::__construct();
        $this->mode = "";
    }

    public function setMode($mode) {
        $this->mode = "/cover/" . $mode;
    }

    public function getMode() {
        return $this->mode;
    }

    public function queryString() {
        $head = "QRcode";
        $res = "";
        if($this->mode) {
            $res .= $this->mode;
        }
        if($res) {
            $res = $head . $res;
        }
        return $res;
    }

    public function resetRule() {
        $this->mode = "";
    }
}
