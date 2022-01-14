<?php

namespace Qcloud\Cos\ImageParamTemplate;

/**
 * Parses default XML exception responses
 */
class ImageViewTemplate extends ImageTemplate
{
    private $mode;
    private $width;
    private $height;
    private $format;
    private $quality;


    public function __construct() {
        parent::__construct();
        $this->mode = "";
        $this->width = "";
        $this->height = "";
        $this->format = "";
        $this->quality = "";
    }

    public function setMode($value) {
        $this->mode = "/" . $value;
    }

    public function setWidth($value) {
        $this->width = "/w/" . $value;
    }

    public function setHeight($value) {
        $this->height = "/h/" . $value;
    }

    public function setFormat($value) {
        $this->format = "/format/" . $value;
    }

    public function setQuality($qualityType, $qualityValue, $force = 0) {
        if($qualityType == 1){
            $this->quality = "/q/$qualityValue" ;
            if($force){
                $this->quality .= "!";
            }
        }else if($qualityType == 2){
            $this->quality = "/rq/$qualityValue" ;
        }else if ($qualityType == 3){
            $this->quality = "/lq/$qualityValue" ;
        }
    }

    public function getMode() {
        return $this->mode;
    }

    public function getWidth() {
        return $this->width;
    }

    public function getHeight() {
        return $this->height;
    }

    public function getFormat() {
        return $this->format;
    }

    public function getQuality() {
        return $this->quality;
    }

    public function queryString() {
        $head = "imageView2";
        $res = "";
        if($this->mode) {
            $res .= $this->mode;
        }
        if($this->width) {
            $res .= $this->width;
        }
        if($this->height) {
            $res .= $this->height;
        }
        if($this->format) {
            $res .= $this->format;
        }
        if($this->quality) {
            $res .= $this->quality;
        }
        if($res) {
            $res = $head . $res;
        }
        return $res;
    }

    public function resetRule() {
        $this->mode = "";
        $this->width = "";
        $this->height = "";
        $this->format = "";
        $this->quality = "";
    }
}
