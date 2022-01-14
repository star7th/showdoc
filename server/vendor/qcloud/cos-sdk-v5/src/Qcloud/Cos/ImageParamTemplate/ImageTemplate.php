<?php

namespace Qcloud\Cos\ImageParamTemplate;

class ImageTemplate
{

    public function __construct() {
    }

    public function queryString() {
        return "";
    }

    public function ciBase64($value) {
        return  str_replace("/", "_", str_replace("+", "-", base64_encode($value)));
    }
}
