<?php

namespace Qcloud\Cos\ImageParamTemplate;

class BlindWatermarkTemplate extends ImageTemplate {
    private $markType;
    private $type;
    private $image;
    private $text;
    private $level;

    public function __construct() {
        parent::__construct();
        $this->markType = 3;
        $this->type = "";
        $this->image = "";
        $this->text = "";
        $this->level = "";

    }

    public function setPick() {
        $this->markType = 4;
    }

    public function setType($value) {
        $this->type = "/type/" . $value;
    }

    public function setImage($value) {
        $this->image = "/image/" . $this->ciBase64($value);
    }

    public function setText($value) {
        $this->text = "/text/" . $this->ciBase64($value);
    }

    public function setLevel($value) {
        $this->level = "/level/" . $value;
    }

    public function getType() {
        return $this->type;
    }

    public function getImage() {
        return $this->image;
    }

    public function getText() {
        return $this->text;
    }

    public function getLevel() {
        return $this->level;
    }


    public function queryString() {
        $head = "watermark/$this->markType";
        $res = "";
        if($this->type){
            $res .= $this->type;
        }
        if($this->image){
            $res .= $this->image;
        }
        if($this->text){
            $res .= $this->text;
        }
        if($this->level){
            $res .= $this->level;
        }
        if($res){
            $res = $head . $res;
        }
        return $res;
    }

    public function resetRule() {
        $this->markType = 3;
        $this->type = "";
        $this->image = "";
        $this->text = "";
        $this->level = "";
    }
}
