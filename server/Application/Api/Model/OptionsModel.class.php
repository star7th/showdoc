<?php

namespace Api\Model;

use Api\Model\BaseModel;

class OptionsModel extends BaseModel
{

    //
    public function get($option_name)
    {
        $res = $this->where(" option_name = '%s' ", array($option_name))->find();
        if ($res) {
            return $res['option_value'];
        }
        return false;
    }

    //
    public function set($option_name, $option_value)
    {
        $return = M('options')->add(array(
            "option_name" => $option_name,
            "option_value" => $option_value,
        ), NULL, true);
        return $return;
    }
}
