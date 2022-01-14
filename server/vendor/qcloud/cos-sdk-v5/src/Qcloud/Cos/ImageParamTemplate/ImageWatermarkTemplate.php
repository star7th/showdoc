<?php

namespace Qcloud\Cos\ImageParamTemplate;

class ImageWatermarkTemplate extends ImageTemplate
{

    private $image;
    private $gravity;
    private $dx;
    private $dy;
    private $blogo;
    private $scatype;
    private $spcent;

    public function __construct() {
        parent::__construct();
        $this->image = "";
        $this->gravity = "";
        $this->dx = "";
        $this->dy = "";
        $this->blogo = "";
        $this->scatype = "";
        $this->spcent = "";
    }

    public function setImage($value) {
        $this->image = "/image/" . $this->ciBase64($value);
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

    public function setBlogo($value) {
        $this->blogo = "/blogo/" . $value;
    }

    public function setScatype($value) {
        $this->scatype = "/scatype/" . $value;
    }

    public function setSpcent($value) {
        $this->spcent = "/spcent/" . $value;
    }

    public function getImage() {
        return $this->image;
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

    public function getBlogo() {
        return $this->blogo;
    }

    public function getScatype() {
        return $this->scatype;
    }

    public function getSpcent() {
        return $this->spcent;
    }

    public function queryString() {
        $head = "watermark/1";
        $res = "";
        if($this->image) {
            $res .= $this->image;
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
        if($this->blogo) {
            $res .= $this->blogo;
        }
        if($this->scatype) {
            $res .= $this->scatype;
        }
        if($this->spcent) {
            $res .= $this->spcent;
        }
        if($res) {
            $res = $head . $res;
        }
        return $res;
    }

    public function resetRule() {
        $this->image = "";
        $this->gravity = "";
        $this->dx = "";
        $this->dy = "";
        $this->blogo = "";
        $this->scatype = "";
        $this->spcent = "";
    }
}
