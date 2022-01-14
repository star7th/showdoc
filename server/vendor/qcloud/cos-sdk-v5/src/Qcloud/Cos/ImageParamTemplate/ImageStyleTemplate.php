<?php

namespace Qcloud\Cos\ImageParamTemplate;

class ImageStyleTemplate extends ImageTemplate
{
    private $style;

    public function __construct() {
        parent::__construct();
        $this->style = "";
    }

    public function setStyle($styleName) {
        $this->style = "style/" . $styleName;
    }

    public function getStyle() {
        return $this->style;
    }

    public function queryString() {
        $res = "";
        if($this->style) {
            $res = $this->style;
        }
        return $res;
    }

    public function resetRule() {
        $this->style = "";
    }
}
