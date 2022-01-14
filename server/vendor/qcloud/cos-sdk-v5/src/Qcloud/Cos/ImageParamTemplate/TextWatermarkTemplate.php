<?php

namespace Qcloud\Cos\ImageParamTemplate;

/**
 * Parses default XML exception responses
 */
class TextWatermarkTemplate extends ImageTemplate
{
    private $text;
    private $font;
    private $fontsize;
    private $fill;
    private $dissolve;
    private $gravity;
    private $dx;
    private $dy;
    private $batch;
    private $degree;

    public function __construct() {
        parent::__construct();
        $this->text = "";
        $this->font = "";
        $this->fontsize = "";
        $this->fill = "";
        $this->dissolve = "";
        $this->gravity = "";
        $this->dx = "";
        $this->dy = "";
        $this->batch = "";
        $this->degree = "";
    }

    public function setText($value) {
        $this->text = "/text/" . $this->ciBase64($value);
    }

    public function setFont($value) {
        $this->font = "/font/" . $this->ciBase64($value);
    }

    public function setFontsize($value) {
        $this->fontsize = "/fontsize/" . $value;
    }

    public function setFill($value) {
        $this->fill = "/fill/" . $this->ciBase64($value);
    }

    public function setDissolve($value) {
        $this->dissolve = "/dissolve/" . $value;
    }

    public function setGravity($value) {
        $this->gravity = "/gravity/" . $value;
    }

    public function setDx($value) {
        $this->dx = "/dx/" . $value;
    }

    public function setDy($value) {
        $this->dy = "/dy/" . $value;
    }

    public function setBatch($value) {
        $this->batch = "/batch/" . $value;
    }

    public function setDegree($value) {
        $this->degree = "/degree/" . $value;
    }

    public function getText() {
        return $this->text;
    }

    public function getFont() {
        return $this->font;
    }

    public function getFontsize() {
        return $this->fontsize;
    }

    public function getFill() {
        return $this->fill;
    }

    public function getDissolve() {
        return $this->dissolve;
    }

    public function getGravity() {
        return $this->gravity;
    }

    public function getDx() {
        return $this->dx;
    }

    public function getDy() {
        return $this->dy;
    }

    public function getBatch() {
        return $this->batch;
    }

    public function getDegree() {
        return $this->degree;
    }

    public function queryString() {
        $head = "watermark/2";
        $res = "";
        if($this->text) {
            $res .= $this->text;
        }
        if($this->font) {
            $res .= $this->font;
        }
        if($this->fontsize) {
            $res .= $this->fontsize;
        }
        if($this->fill) {
            $res .= $this->fill;
        }
        if($this->dissolve) {
            $res .= $this->dissolve;
        }
        if($this->gravity) {
            $res .= $this->gravity;
        }
        if($this->dx) {
            $res .= $this->dx;
        }
        if($this->dy) {
            $res .= $this->dy;
        }
        if($this->batch) {
            $res .= $this->batch;
        }
        if($this->degree) {
            $res .= $this->degree;
        }
        if($res) {
            $res = $head . $res;
        }
        return $res;
    }

    public function resetRule() {
        $this->text = "";
        $this->font = "";
        $this->fontsize = "";
        $this->fill = "";
        $this->dissolve = "";
        $this->gravity = "";
        $this->dx = "";
        $this->dy = "";
        $this->batch = "";
        $this->degree = "";
    }
}
