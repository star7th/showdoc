<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Template\TagLib;
use Think\Template\TagLib;
/**
 * CX标签库解析类
 */
class Cx extends TagLib {

    // 标签定义
    protected $tags   =  array(
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'php'       =>  array(),
        'volist'    =>  array('attr'=>'name,id,offset,length,key,mod','level'=>3,'alias'=>'iterate'),
        'foreach'   =>  array('attr'=>'name,item,key','level'=>3),
        'if'        =>  array('attr'=>'condition','level'=>2),
        'elseif'    =>  array('attr'=>'condition','close'=>0),
        'else'      =>  array('attr'=>'','close'=>0),
        'switch'    =>  array('attr'=>'name','level'=>2),
        'case'      =>  array('attr'=>'value,break'),
        'default'   =>  array('attr'=>'','close'=>0),
        'compare'   =>  array('attr'=>'name,value,type','level'=>3,'alias'=>'eq,equal,notequal,neq,gt,lt,egt,elt,heq,nheq'),
        'range'     =>  array('attr'=>'name,value,type','level'=>3,'alias'=>'in,notin,between,notbetween'),
        'empty'     =>  array('attr'=>'name','level'=>3),
        'notempty'  =>  array('attr'=>'name','level'=>3),
        'present'   =>  array('attr'=>'name','level'=>3),
        'notpresent'=>  array('attr'=>'name','level'=>3),
        'defined'   =>  array('attr'=>'name','level'=>3),
        'notdefined'=>  array('attr'=>'name','level'=>3),
        'import'    =>  array('attr'=>'file,href,type,value,basepath','close'=>0,'alias'=>'load,css,js'),
        'assign'    =>  array('attr'=>'name,value','close'=>0),
        'define'    =>  array('attr'=>'name,value','close'=>0),
        'for'       =>  array('attr'=>'start,end,name,comparison,step', 'level'=>3),
        );

    /**
     * php标签解析
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string
     */
    public function _php($tag,$content) {
        $parseStr = '<?php '.$content.' ?>';
        return $parseStr;
    }

    /**
     * volist标签解析 循环输出数据集
     * 格式：
     * <volist name="userList" id="user" empty="" >
     * {user.username}
     * {user.email}
     * </volist>
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string|void
     */
    public function _volist($tag,$content) {
        $name  =    $tag['name'];
        $id    =    $tag['id'];
        $empty =    isset($tag['empty'])?$tag['empty']:'';
        $key   =    !empty($tag['key'])?$tag['key']:'i';
        $mod   =    isset($tag['mod'])?$tag['mod']:'2';
        // 允许使用函数设定数据集 <volist name=":fun('arg')" id="vo">{$vo.name}</volist>
        $parseStr   =  '<?php ';
        if(0===strpos($name,':')) {
            $parseStr   .= '$_result='.substr($name,1).';';
            $name   = '$_result';
        }else{
            $name   = $this->autoBuildVar($name);
        }
        $parseStr  .=  'if(is_array('.$name.')): $'.$key.' = 0;';
        if(isset($tag['length']) && '' !=$tag['length'] ) {
            $parseStr  .= ' $__LIST__ = array_slice('.$name.','.$tag['offset'].','.$tag['length'].',true);';
        }elseif(isset($tag['offset'])  && '' !=$tag['offset']){
            $parseStr  .= ' $__LIST__ = array_slice('.$name.','.$tag['offset'].',null,true);';
        }else{
            $parseStr .= ' $__LIST__ = '.$name.';';
        }
        $parseStr .= 'if( count($__LIST__)==0 ) : echo "'.$empty.'" ;';
        $parseStr .= 'else: ';
        $parseStr .= 'foreach($__LIST__ as $key=>$'.$id.'): ';
        $parseStr .= '$mod = ($'.$key.' % '.$mod.' );';
        $parseStr .= '++$'.$key.';?>';
        $parseStr .= $this->tpl->parse($content);
        $parseStr .= '<?php endforeach; endif; else: echo "'.$empty.'" ;endif; ?>';

        if(!empty($parseStr)) {
            return $parseStr;
        }
        return ;
    }

    /**
     * foreach标签解析 循环输出数据集
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string|void
     */
    public function _foreach($tag,$content) {
        $name       =   $tag['name'];
        $item       =   $tag['item'];
        $key        =   !empty($tag['key'])?$tag['key']:'key';
        $name       =   $this->autoBuildVar($name);
        $parseStr   =   '<?php if(is_array('.$name.')): foreach('.$name.' as $'.$key.'=>$'.$item.'): ?>';
        $parseStr  .=   $this->tpl->parse($content);
        $parseStr  .=   '<?php endforeach; endif; ?>';

        if(!empty($parseStr)) {
            return $parseStr;
        }
        return ;
    }

    /**
     * if标签解析
     * 格式：
     * <if condition=" $a eq 1" >
     * <elseif condition="$a eq 2" />
     * <else />
     * </if>
     * 表达式支持 eq neq gt egt lt elt == > >= < <= or and || &&
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string
     */
    public function _if($tag,$content) {
        $condition  =   $this->parseCondition($tag['condition']);
        $parseStr   =   '<?php if('.$condition.'): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }

    /**
     * else标签解析
     * 格式：见if标签
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string
     */
    public function _elseif($tag,$content) {
        $condition  =   $this->parseCondition($tag['condition']);
        $parseStr   =   '<?php elseif('.$condition.'): ?>';
        return $parseStr;
    }

    /**
     * else标签解析
     * @access public
     * @param array $tag 标签属性
     * @return string
     */
    public function _else($tag) {
        $parseStr = '<?php else: ?>';
        return $parseStr;
    }

    /**
     * switch标签解析
     * 格式：
     * <switch name="a.name" >
     * <case value="1" break="false">1</case>
     * <case value="2" >2</case>
     * <default />other
     * </switch>
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string
     */
    public function _switch($tag,$content) {
        $name       =   $tag['name'];
        $varArray   =   explode('|',$name);
        $name       =   array_shift($varArray);
        $name       =   $this->autoBuildVar($name);
        if(count($varArray)>0)
            $name   =   $this->tpl->parseVarFunction($name,$varArray);
        $parseStr   =   '<?php switch('.$name.'): ?>'.$content.'<?php endswitch;?>';
        return $parseStr;
    }

    /**
     * case标签解析 需要配合switch才有效
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string
     */
    public function _case($tag,$content) {
        $value  = $tag['value'];
        if('$' == substr($value,0,1)) {
            $varArray   =   explode('|',$value);
            $value	    =	array_shift($varArray);
            $value      =   $this->autoBuildVar(substr($value,1));
            if(count($varArray)>0)
                $value  =   $this->tpl->parseVarFunction($value,$varArray);
            $value      =   'case '.$value.': ';
        }elseif(strpos($value,'|')){
            $values     =   explode('|',$value);
            $value      =   '';
            foreach ($values as $val){
                $value   .=  'case "'.addslashes($val).'": ';
            }
        }else{
            $value	=	'case "'.$value.'": ';
        }
        $parseStr = '<?php '.$value.' ?>'.$content;
        $isBreak  = isset($tag['break']) ? $tag['break'] : '';
        if('' ==$isBreak || $isBreak) {
            $parseStr .= '<?php break;?>';
        }
        return $parseStr;
    }

    /**
     * default标签解析 需要配合switch才有效
     * 使用： <default />ddfdf
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string
     */
    public function _default($tag) {
        $parseStr = '<?php default: ?>';
        return $parseStr;
    }

    /**
     * compare标签解析
     * 用于值的比较 支持 eq neq gt lt egt elt heq nheq 默认是eq
     * 格式： <compare name="" type="eq" value="" >content</compare>
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string
     */
    public function _compare($tag,$content,$type='eq') {
        $name       =   $tag['name'];
        $value      =   $tag['value'];
        $type       =   isset($tag['type'])?$tag['type']:$type;
        $type       =   $this->parseCondition(' '.$type.' ');
        $varArray   =   explode('|',$name);
        $name       =   array_shift($varArray);
        $name       =   $this->autoBuildVar($name);
        if(count($varArray)>0)
            $name = $this->tpl->parseVarFunction($name,$varArray);
        if('$' == substr($value,0,1)) {
            $value  =  $this->autoBuildVar(substr($value,1));
        }else {
            $value  =   '"'.$value.'"';
        }
        $parseStr   =   '<?php if(('.$name.') '.$type.' '.$value.'): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }

    public function _eq($tag,$content) {
        return $this->_compare($tag,$content,'eq');
    }

    public function _equal($tag,$content) {
        return $this->_compare($tag,$content,'eq');
    }

    public function _neq($tag,$content) {
        return $this->_compare($tag,$content,'neq');
    }

    public function _notequal($tag,$content) {
        return $this->_compare($tag,$content,'neq');
    }

    public function _gt($tag,$content) {
        return $this->_compare($tag,$content,'gt');
    }

    public function _lt($tag,$content) {
        return $this->_compare($tag,$content,'lt');
    }

    public function _egt($tag,$content) {
        return $this->_compare($tag,$content,'egt');
    }

    public function _elt($tag,$content) {
        return $this->_compare($tag,$content,'elt');
    }

    public function _heq($tag,$content) {
        return $this->_compare($tag,$content,'heq');
    }

    public function _nheq($tag,$content) {
        return $this->_compare($tag,$content,'nheq');
    }

    /**
     * range标签解析
     * 如果某个变量存在于某个范围 则输出内容 type= in 表示在范围内 否则表示在范围外
     * 格式： <range name="var|function"  value="val" type='in|notin' >content</range>
     * example: <range name="a"  value="1,2,3" type='in' >content</range>
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @param string $type  比较类型
     * @return string
     */
    public function _range($tag,$content,$type='in') {
        $name       =   $tag['name'];
        $value      =   $tag['value'];
        $varArray   =   explode('|',$name);
        $name       =   array_shift($varArray);
        $name       =   $this->autoBuildVar($name);
        if(count($varArray)>0)
            $name   =   $this->tpl->parseVarFunction($name,$varArray);

        $type       =   isset($tag['type'])?$tag['type']:$type;

        if('$' == substr($value,0,1)) {
            $value  =   $this->autoBuildVar(substr($value,1));
            $str    =   'is_array('.$value.')?'.$value.':explode(\',\','.$value.')';
        }else{
            $value  =   '"'.$value.'"';
            $str    =   'explode(\',\','.$value.')';
        }
        if($type=='between') {
            $parseStr = '<?php $_RANGE_VAR_='.$str.';if('.$name.'>= $_RANGE_VAR_[0] && '.$name.'<= $_RANGE_VAR_[1]):?>'.$content.'<?php endif; ?>';
        }elseif($type=='notbetween'){
            $parseStr = '<?php $_RANGE_VAR_='.$str.';if('.$name.'<$_RANGE_VAR_[0] || '.$name.'>$_RANGE_VAR_[1]):?>'.$content.'<?php endif; ?>';
        }else{
            $fun        =  ($type == 'in')? 'in_array'    :   '!in_array';
            $parseStr   = '<?php if('.$fun.'(('.$name.'), '.$str.')): ?>'.$content.'<?php endif; ?>';
        }
        return $parseStr;
    }

    // range标签的别名 用于in判断
    public function _in($tag,$content) {
        return $this->_range($tag,$content,'in');
    }

    // range标签的别名 用于notin判断
    public function _notin($tag,$content) {
        return $this->_range($tag,$content,'notin');
    }

    public function _between($tag,$content){
        return $this->_range($tag,$content,'between');
    }

    public function _notbetween($tag,$content){
        return $this->_range($tag,$content,'notbetween');
    }

    /**
     * present标签解析
     * 如果某个变量已经设置 则输出内容
     * 格式： <present name="" >content</present>
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string
     */
    public function _present($tag,$content) {
        $name       =   $tag['name'];
        $name       =   $this->autoBuildVar($name);
        $parseStr   =   '<?php if(isset('.$name.')): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }

    /**
     * notpresent标签解析
     * 如果某个变量没有设置，则输出内容
     * 格式： <notpresent name="" >content</notpresent>
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string
     */
    public function _notpresent($tag,$content) {
        $name       =   $tag['name'];
        $name       =   $this->autoBuildVar($name);
        $parseStr   =   '<?php if(!isset('.$name.')): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }

    /**
     * empty标签解析
     * 如果某个变量为empty 则输出内容
     * 格式： <empty name="" >content</empty>
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string
     */
    public function _empty($tag,$content) {
        $name       =   $tag['name'];
        $name       =   $this->autoBuildVar($name);
        $parseStr   =   '<?php if(empty('.$name.')): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }

    public function _notempty($tag,$content) {
        $name       =   $tag['name'];
        $name       =   $this->autoBuildVar($name);
        $parseStr   =   '<?php if(!empty('.$name.')): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }

    /**
     * 判断是否已经定义了该常量
     * <defined name='TXT'>已定义</defined>
     * @param <type> $attr
     * @param <type> $content
     * @return string
     */
    public function _defined($tag,$content) {
        $name       =   $tag['name'];
        $parseStr   =   '<?php if(defined("'.$name.'")): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }

    public function _notdefined($tag,$content) {
        $name       =   $tag['name'];
        $parseStr   =   '<?php if(!defined("'.$name.'")): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }

    /**
     * import 标签解析 <import file="Js.Base" /> 
     * <import file="Css.Base" type="css" />
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @param boolean $isFile  是否文件方式
     * @param string $type  类型
     * @return string
     */
    public function _import($tag,$content,$isFile=false,$type='') {
        $file       =   isset($tag['file'])?$tag['file']:$tag['href'];
        $parseStr   =   '';
        $endStr     =   '';
        // 判断是否存在加载条件 允许使用函数判断(默认为isset)
        if (isset($tag['value'])) {
            $varArray  =    explode('|',$tag['value']);
            $name      =    array_shift($varArray);
            $name      =    $this->autoBuildVar($name);
            if (!empty($varArray))
                $name  =    $this->tpl->parseVarFunction($name,$varArray);
            else
                $name  =    'isset('.$name.')';
            $parseStr .=    '<?php if('.$name.'): ?>';
            $endStr    =    '<?php endif; ?>';
        }
        if($isFile) {
            // 根据文件名后缀自动识别
            $type  = $type?$type:(!empty($tag['type'])?strtolower($tag['type']):null);
            // 文件方式导入
            $array =  explode(',',$file);
            foreach ($array as $val){
                if (!$type || isset($reset)) {
                    $type = $reset = strtolower(substr(strrchr($val, '.'),1));
                }
                switch($type) {
                case 'js':
                    $parseStr .= '<script type="text/javascript" src="'.$val.'"></script>';
                    break;
                case 'css':
                    $parseStr .= '<link rel="stylesheet" type="text/css" href="'.$val.'" />';
                    break;
                case 'php':
                    $parseStr .= '<?php require_cache("'.$val.'"); ?>';
                    break;
                }
            }
        }else{
            // 命名空间导入模式 默认是js
            $type       =   $type?$type:(!empty($tag['type'])?strtolower($tag['type']):'js');
            $basepath   =   !empty($tag['basepath'])?$tag['basepath']:__ROOT__.'/Public';
            // 命名空间方式导入外部文件
            $array      =   explode(',',$file);
            foreach ($array as $val){
                if(strpos ($val, '?')) {
                    list($val,$version) =   explode('?',$val);
                } else {
                    $version = '';
                }
                switch($type) {
                case 'js':
                    $parseStr .= '<script type="text/javascript" src="'.$basepath.'/'.str_replace(array('.','#'), array('/','.'),$val).'.js'.($version?'?'.$version:'').'"></script>';
                    break;
                case 'css':
                    $parseStr .= '<link rel="stylesheet" type="text/css" href="'.$basepath.'/'.str_replace(array('.','#'), array('/','.'),$val).'.css'.($version?'?'.$version:'').'" />';
                    break;
                case 'php':
                    $parseStr .= '<?php import("'.$val.'"); ?>';
                    break;
                }
            }
        }
        return $parseStr.$endStr;
    }

    // import别名 采用文件方式加载(要使用命名空间必须用import) 例如 <load file="__PUBLIC__/Js/Base.js" />
    public function _load($tag,$content) {
        return $this->_import($tag,$content,true);
    }

    // import别名使用 导入css文件 <css file="__PUBLIC__/Css/Base.css" />
    public function _css($tag,$content) {
        return $this->_import($tag,$content,true,'css');
    }

    // import别名使用 导入js文件 <js file="__PUBLIC__/Js/Base.js" />
    public function _js($tag,$content) {
        return $this->_import($tag,$content,true,'js');
    }

    /**
     * assign标签解析
     * 在模板中给某个变量赋值 支持变量赋值
     * 格式： <assign name="" value="" />
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string
     */
    public function _assign($tag,$content) {
        $name       =   $this->autoBuildVar($tag['name']);
        if('$'==substr($tag['value'],0,1)) {
            $value  =   $this->autoBuildVar(substr($tag['value'],1));
        }else{
            $value  =   '\''.$tag['value']. '\'';
        }
        $parseStr   =   '<?php '.$name.' = '.$value.'; ?>';
        return $parseStr;
    }

    /**
     * define标签解析
     * 在模板中定义常量 支持变量赋值
     * 格式： <define name="" value="" />
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string
     */
    public function _define($tag,$content) {
        $name       =   '\''.$tag['name']. '\'';
        if('$'==substr($tag['value'],0,1)) {
            $value  =   $this->autoBuildVar(substr($tag['value'],1));
        }else{
            $value  =   '\''.$tag['value']. '\'';
        }
        $parseStr   =   '<?php define('.$name.', '.$value.'); ?>';
        return $parseStr;
    }
    
    /**
     * for标签解析
     * 格式： <for start="" end="" comparison="" step="" name="" />
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string
     */
    public function _for($tag, $content){
        //设置默认值
        $start 		= 0;
        $end   		= 0;
        $step 		= 1;
        $comparison = 'lt';
        $name		= 'i';
        $rand       = rand(); //添加随机数，防止嵌套变量冲突
        //获取属性
        foreach ($tag as $key => $value){
            $value = trim($value);
            if(':'==substr($value,0,1))
                $value = substr($value,1);
            elseif('$'==substr($value,0,1))
                $value = $this->autoBuildVar(substr($value,1));
            switch ($key){
                case 'start':   
                    $start      = $value; break;
                case 'end' :    
                    $end        = $value; break;
                case 'step':    
                    $step       = $value; break;
                case 'comparison':
                    $comparison = $value; break;
                case 'name':
                    $name       = $value; break;
            }
        }
        
        $parseStr   = '<?php $__FOR_START_'.$rand.'__='.$start.';$__FOR_END_'.$rand.'__='.$end.';';
        $parseStr  .= 'for($'.$name.'=$__FOR_START_'.$rand.'__;'.$this->parseCondition('$'.$name.' '.$comparison.' $__FOR_END_'.$rand.'__').';$'.$name.'+='.$step.'){ ?>';
        $parseStr  .= $content;
        $parseStr  .= '<?php } ?>';
        return $parseStr;
    }

}
