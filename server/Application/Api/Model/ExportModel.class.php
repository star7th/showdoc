<?php
namespace Api\Model;
use Api\Model\BaseModel;
/**
 * 
 * @author star7th      
 */
class ExportModel  {

    Protected $autoCheckFields = false;  //一定要关闭字段缓存，不然会报找不到表的错误

    //把runapi的格式内容转换为markdown格式。如果不是runapi格式，则会返回原数据
    public function runapiToMd($content_json){
        $content_json = htmlspecialchars_decode($content_json) ;
        $content = json_decode($content_json , true) ;
        if(!$content || !$content['info'] || !$content['info']['url'] ){
            return $content_json ;
        }
        $new_content = "
##### 简要描述

 - ".($content['info']['description'] ? $content['info']['description'] :'无') ."

##### 请求URL

 - `{$content['info']['url']}`

##### 请求方式

 - {$content['info']['method']}
";

    if($content['request']['headers'] && $content['request']['headers'][0] && $content['request']['headers'][0]['name']){
        $new_content .= " 
##### Header 

|header|必选|类型|说明|
|:-----  |:-----|-----|
";
        foreach ($content['request']['headers'] as $key => $value) {
            $value['require'] = $value['require'] > 0 ? "是" : "否" ;
            $value['remark'] = $value['remark'] ? $value['remark'] : 无 ;
            $new_content .= "|{$value['name']}|  {$value['require']} |  {$value['type']} |  {$value['remark']} | \n";
        } 
    }

    $params = $content['request']['params'][$content['request']['params']['mode']];
    if ($params && is_array($params) && $params[0] && $params[0]['name']){
        $new_content .= " 
##### 请求参数

|参数名|必选|类型|说明|
|:-----  |:-----|-----|
";

    foreach ($params as $key => $value) {
        $value['require'] = $value['require'] > 0 ? "是" : "否" ;
        $value['remark'] = $value['remark'] ? $value['remark'] : 无 ;
        $new_content .= "|{$value['name']}|  {$value['require']} |  {$value['type']} |  {$value['remark']} | \n";
    }
    }
    //如果参数类型为json
    if($content['request']['params']['mode'] == 'json' && $params){
        $new_content .= " 
##### 请求参数示例  
```
{$params}

``` 
"; 
    }
        // json字段说明
        $jsonDesc = $content['request']['params']['jsonDesc'] ;
        if ($content['request']['params']['mode'] == 'json' && $jsonDesc && $jsonDesc[0] && $jsonDesc[0]['name']){
            $new_content .= " 
##### json字段说明

|字段名|必选|类型|说明|
|:-----  |:-----|-----|
";
    
        foreach ($jsonDesc as $key => $value) {
            $value['require'] = $value['require'] > 0 ? "是" : "否" ;
            $value['remark'] = $value['remark'] ? $value['remark'] : 无 ;
            $new_content .= "|{$value['name']}|  {$value['require']} |  {$value['type']} |  {$value['remark']} | \n";
        }
        }

        //返回示例
        if($content['response']['responseExample']){
            $new_content .= " 
##### 返回示例  
```
{$content['response']['responseExample']}

``` 
"; 
        }

        //返回示例说明
        if($content['response']['responseParamsDesc'] && $content['response']['responseParamsDesc'][0] && $content['response']['responseParamsDesc'][0]['name']){
            $new_content .= " 
##### 返回参数说明 

|参数名|类型|说明|
|:-----  |:-----|-----|
";
            foreach ($content['response']['responseParamsDesc'] as $key => $value) {
                $value['remark'] = $value['remark'] ? $value['remark'] : 无 ;
                $new_content .= "|{$value['name']}| {$value['type']} |  {$value['remark']} | \n";
            }
        }

        $new_content .= " 
##### 备注

 {$content['info']['remark']}
";

    

        return $new_content ;

    }

}