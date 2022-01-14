<?php

namespace Qcloud\Cos\ImageParamTemplate;

class ImageMogrTemplate extends ImageTemplate
{
    private $tranParams;
    private $tranString;

    public function __construct() {
        parent::__construct();
        $this->tranParams = array();
        $this->tranString = "";
    }

    public function thumbnailByScale($widthScale) {
        $this->tranParams[] = "/thumbnail/!" . $widthScale . "p";
    }

    public function thumbnailByWidthScale($heightScale) {
        $this->tranParams[] = "/thumbnail/!" . $heightScale . "px";
    }

    public function thumbnailByHeightScale($scale) {
        $this->tranParams[] = "/thumbnail/!x" . $scale . "p";
    }

    public function thumbnailByWidth($width) {
        $this->tranParams[] = "/thumbnail/" . $width . "x";
    }

    public function thumbnailByHeight($height) {
        $this->tranParams[] = "/thumbnail/x" . $height;
    }

    public function thumbnailByMaxWH($maxW, $maxH) {
        $this->tranParams[] = "/thumbnail/" . $maxW . "x" . $maxH;
    }

    public function thumbnailByMinWH($minW, $minH) {
        $this->tranParams[] = "/thumbnail/!" . $minW . "x" . $minH . "r" ;
    }

    public function thumbnailByWH($width, $height) {
        $this->tranParams[] = "/thumbnail/" . $width  . "x" . $height . "!";
    }

    public function thumbnailByPixel($pixel) {
        $this->tranParams[] = "/thumbnail/" . $pixel . "@";
    }

    public function cut($width, $height, $dx, $dy) {
        $this->tranParams[] = "/cut/" . $width . "x" . "$height" . "x" . $dx . "x" . $dy;
    }

    public function cropByWidth($width, $gravity = "") {
        $temp = "/crop/" . $width . "x";
        if($gravity){
            $temp .= "/gravity/" . $gravity;
        }
        $this->tranParams[] = $temp;
    }

    public function cropByHeight($height, $gravity = "") {
        $temp = "/crop/x" . $height;
        if($gravity){
            $temp .= "/gravity/" . $gravity;
        }
        $this->tranParams[] = $temp;
    }

    public function cropByWH($width, $height, $gravity = "") {
        $temp = "/crop/" . $width . "x" . $height;
        if($gravity){
            $temp .= "/gravity/" . $gravity;
        }
        $this->tranParams[] = $temp;
    }

    public function iradius($radius) {
        $this->tranParams[] = "/iradius/" . $radius;
    }

    public function rradius($radius) {
        $this->tranParams[] = "/rradius/" . $radius;
    }

    public function scrop($width, $height) {
        $this->tranParams[] = "/scrop/" . $width . "x" . $height;
    }

    public function rotate($degree) {
        $this->tranParams[] = "/rotate/" . $degree;
    }

    public function autoOrient() {
        $this->tranParams[] = "/rotate/auto-orient";
    }

    public function format($format) {
        $this->tranParams[] = "/format/" . $format;
    }

    public function gifOptimization($frameNumber) {
        $this->tranParams[] = "/cgif/" . $frameNumber;
    }

    public function jpegInterlaceMode($mode) {
        $this->tranParams[] = "/interlace/" . $mode;
    }

    public function quality($value, $force = 0) {
        $temp = "/quality/" . $value;
        if($force){
            $temp .= "!";
        }
        $this->tranParams[] = $temp;
    }

    public function lowestQuality($value) {
        $this->tranParams[] = "/lquality/" . $value;
    }

    public function relativelyQuality($value) {
        $this->tranParams[] = "/rquality/" . $value;
    }

    public function blur($radius, $sigma) {
        $this->tranParams[] = "/blur/" . $radius . "x" . $sigma;
    }

    public function bright($value) {
        $this->tranParams[] = "/bright/" . $value;
    }

    public function contrast($value) {
        $this->tranParams[] = "/contrast/" . $value;
    }

    public function sharpen($value) {
        $this->tranParams[] = "/sharpen/" . $value;
    }

    public function strip() {
        $this->tranParams[] = "/strip";
    }

    public function queryString() {
        if($this->tranParams) {
            $this->tranString = "imageMogr2" . implode("", $this->tranParams);
        }
        return $this->tranString;
    }

    public function resetRule() {
        $this->tranString = "";
        $this->tranParams = array();
    }
}
