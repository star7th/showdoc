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
namespace Think;
/**
 * ThinkPHP内置模板引擎类
 * 支持XML标签和普通标签的模板解析
 * 编译型模板引擎 支持动态缓存
 */
class  Template {

    // 模板页面中引入的标签库列表
    protected   $tagLib          =   array();
    // 当前模板文件
    protected   $templateFile    =   '';
    // 模板变量
    public      $tVar            =   array();
    public      $config          =   array();
    private     $literal         =   array();
    private     $block           =   array();

    /**
     * 架构函数
     * @access public
     */
    public function __construct(){
        $this->config['cache_path']         =   C('CACHE_PATH');
        $this->config['template_suffix']    =   C('TMPL_TEMPLATE_SUFFIX');
        $this->config['cache_suffix']       =   C('TMPL_CACHFILE_SUFFIX');
        $this->config['tmpl_cache']         =   C('TMPL_CACHE_ON');
        $this->config['cache_time']         =   C('TMPL_CACHE_TIME');
        $this->config['taglib_begin']       =   $this->stripPreg(C('TAGLIB_BEGIN'));
        $this->config['taglib_end']         =   $this->stripPreg(C('TAGLIB_END'));
        $this->config['tmpl_begin']         =   $this->stripPreg(C('TMPL_L_DELIM'));
        $this->config['tmpl_end']           =   $this->stripPreg(C('TMPL_R_DELIM'));
        $this->config['default_tmpl']       =   C('TEMPLATE_NAME');
        $this->config['layout_item']        =   C('TMPL_LAYOUT_ITEM');
    }

    private function stripPreg($str) {
        return str_replace(
            array('{','}','(',')','|','[',']','-','+','*','.','^','?'),
            array('\{','\}','\(','\)','\|','\[','\]','\-','\+','\*','\.','\^','\?'),
            $str);        
    }

    // 模板变量获取和设置
    public function get($name) {
        if(isset($this->tVar[$name]))
            return $this->tVar[$name];
        else
            return false;
    }

    public function set($name,$value) {
        $this->tVar[$name]= $value;
    }

    /**
     * 加载模板
     * @access public
     * @param string $templateFile 模板文件
     * @param array  $templateVar 模板变量
     * @param string $prefix 模板标识前缀
     * @return void
     */
    public function fetch($templateFile,$templateVar,$prefix='') {
        $this->tVar         =   $templateVar;
        $templateCacheFile  =   $this->loadTemplate($templateFile,$prefix);
        Storage::load($templateCacheFile,$this->tVar,null,'tpl');
    }

    /**
     * 加载主模板并缓存
     * @access public
     * @param string $templateFile 模板文件
     * @param string $prefix 模板标识前缀
     * @return string
     * @throws ThinkExecption
     */
    public function loadTemplate ($templateFile,$prefix='') {
        if(is_file($templateFile)) {
            $this->templateFile    =  $templateFile;
            // 读取模板文件内容
            $tmplContent =  file_get_contents($templateFile);
        }else{
            $tmplContent =  $templateFile;
        }
         // 根据模版文件名定位缓存文件
        $tmplCacheFile = $this->config['cache_path'].$prefix.md5($templateFile).$this->config['cache_suffix'];

        // 判断是否启用布局
        if(C('LAYOUT_ON')) {
            if(false !== strpos($tmplContent,'{__NOLAYOUT__}')) { // 可以单独定义不使用布局
                $tmplContent = str_replace('{__NOLAYOUT__}','',$tmplContent);
            }else{ // 替换布局的主体内容
                $layoutFile  =  THEME_PATH.C('LAYOUT_NAME').$this->config['template_suffix'];
                // 检查布局文件
                if(!is_file($layoutFile)) {
                    E(L('_TEMPLATE_NOT_EXIST_').':'.$layoutFile);
                }
                $tmplContent = str_replace($this->config['layout_item'],$tmplContent,file_get_contents($layoutFile));
            }
        }
        // 编译模板内容
        $tmplContent =  $this->compiler($tmplContent);
        Storage::put($tmplCacheFile,trim($tmplContent),'tpl');
        return $tmplCacheFile;
    }

    /**
     * 编译模板文件内容
     * @access protected
     * @param mixed $tmplContent 模板内容
     * @return string
     */
    protected function compiler($tmplContent) {
        //模板解析
        $tmplContent =  $this->parse($tmplContent);
        // 还原被替换的Literal标签
        $tmplContent =  preg_replace_callback('/<!--###literal(\d+)###-->/is', array($this, 'restoreLiteral'), $tmplContent);
        // 添加安全代码
        $tmplContent =  '<?php if (!defined(\'THINK_PATH\')) exit();?>'.$tmplContent;
        // 优化生成的php代码
        $tmplContent = str_replace('?><?php','',$tmplContent);
        // 模版编译过滤标签
        Hook::listen('template_filter',$tmplContent);
        return strip_whitespace($tmplContent);
    }

    /**
     * 模板解析入口
     * 支持普通标签和TagLib解析 支持自定义标签库
     * @access public
     * @param string $content 要解析的模板内容
     * @return string
     */
    public function parse($content) {
        // 内容为空不解析
        if(empty($content)) return '';
        $begin      =   $this->config['taglib_begin'];
        $end        =   $this->config['taglib_end'];
        // 检查include语法
        $content    =   $this->parseInclude($content);
        // 检查PHP语法
        $content    =   $this->parsePhp($content);
        // 首先替换literal标签内容
        $content    =   preg_replace_callback('/'.$begin.'literal'.$end.'(.*?)'.$begin.'\/literal'.$end.'/is', array($this, 'parseLiteral'),$content);

        // 获取需要引入的标签库列表
        // 标签库只需要定义一次，允许引入多个一次
        // 一般放在文件的最前面
        // 格式：<taglib name="html,mytag..." />
        // 当TAGLIB_LOAD配置为true时才会进行检测
        if(C('TAGLIB_LOAD')) {
            $this->getIncludeTagLib($content);
            if(!empty($this->tagLib)) {
                // 对导入的TagLib进行解析
                foreach($this->tagLib as $tagLibName) {
                    $this->parseTagLib($tagLibName,$content);
                }
            }
        }
        // 预先加载的标签库 无需在每个模板中使用taglib标签加载 但必须使用标签库XML前缀
        if(C('TAGLIB_PRE_LOAD')) {
            $tagLibs =  explode(',',C('TAGLIB_PRE_LOAD'));
            foreach ($tagLibs as $tag){
                $this->parseTagLib($tag,$content);
            }
        }
        // 内置标签库 无需使用taglib标签导入就可以使用 并且不需使用标签库XML前缀
        $tagLibs =  explode(',',C('TAGLIB_BUILD_IN'));
        foreach ($tagLibs as $tag){
            $this->parseTagLib($tag,$content,true);
        }
        //解析普通模板标签 {$tagName}
        $content = preg_replace_callback('/('.$this->config['tmpl_begin'].')([^\d\w\s'.$this->config['tmpl_begin'].$this->config['tmpl_end'].'].+?)('.$this->config['tmpl_end'].')/is', array($this, 'parseTag'),$content);
        return $content;
    }

    // 检查PHP语法
    protected function parsePhp($content) {
        if(ini_get('short_open_tag')){
            // 开启短标签的情况要将<?标签用echo方式输出 否则无法正常输出xml标识
            $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>'."\n", $content );
        }
        // PHP语法检查
        if(C('TMPL_DENY_PHP') && false !== strpos($content,'<?php')) {
            E(L('_NOT_ALLOW_PHP_'));
        }
        return $content;
    }

    // 解析模板中的布局标签
    protected function parseLayout($content) {
        // 读取模板中的布局标签
        $find = preg_match('/'.$this->config['taglib_begin'].'layout\s(.+?)\s*?\/'.$this->config['taglib_end'].'/is',$content,$matches);
        if($find) {
            //替换Layout标签
            $content    =   str_replace($matches[0],'',$content);
            //解析Layout标签
            $array      =   $this->parseXmlAttrs($matches[1]);
            if(!C('LAYOUT_ON') || C('LAYOUT_NAME') !=$array['name'] ) {
                // 读取布局模板
                $layoutFile =   THEME_PATH.$array['name'].$this->config['template_suffix'];
                $replace    =   isset($array['replace'])?$array['replace']:$this->config['layout_item'];
                // 替换布局的主体内容
                $content    =   str_replace($replace,$content,file_get_contents($layoutFile));
            }
        }else{
            $content = str_replace('{__NOLAYOUT__}','',$content);
        }
        return $content;
    }

    // 解析模板中的include标签
    protected function parseInclude($content, $extend = true) {
        // 解析继承
        if($extend)
            $content    =   $this->parseExtend($content);
        // 解析布局
        $content    =   $this->parseLayout($content);
        // 读取模板中的include标签
        $find       =   preg_match_all('/'.$this->config['taglib_begin'].'include\s(.+?)\s*?\/'.$this->config['taglib_end'].'/is',$content,$matches);
        if($find) {
            for($i=0;$i<$find;$i++) {
                $include    =   $matches[1][$i];
                $array      =   $this->parseXmlAttrs($include);
                $file       =   $array['file'];
                unset($array['file']);
                $content    =   str_replace($matches[0][$i],$this->parseIncludeItem($file,$array,$extend),$content);
            }
        }
        return $content;
    }

    // 解析模板中的extend标签
    protected function parseExtend($content) {
        $begin      =   $this->config['taglib_begin'];
        $end        =   $this->config['taglib_end'];        
        // 读取模板中的继承标签
        $find       =   preg_match('/'.$begin.'extend\s(.+?)\s*?\/'.$end.'/is',$content,$matches);
        if($find) {
            //替换extend标签
            $content    =   str_replace($matches[0],'',$content);
            // 记录页面中的block标签
            preg_replace_callback('/'.$begin.'block\sname=[\'"](.+?)[\'"]\s*?'.$end.'(.*?)'.$begin.'\/block'.$end.'/is', array($this, 'parseBlock'),$content);
            // 读取继承模板
            $array      =   $this->parseXmlAttrs($matches[1]);
            $content    =   $this->parseTemplateName($array['name']);
            $content    =   $this->parseInclude($content, false); //对继承模板中的include进行分析
            // 替换block标签
            $content = $this->replaceBlock($content);
        }else{
            $content    =   preg_replace_callback('/'.$begin.'block\sname=[\'"](.+?)[\'"]\s*?'.$end.'(.*?)'.$begin.'\/block'.$end.'/is', function($match){return stripslashes($match[2]);}, $content);
        }
        return $content;
    }

    /**
     * 分析XML属性
     * @access private
     * @param string $attrs  XML属性字符串
     * @return array
     */
    private function parseXmlAttrs($attrs) {
        $xml        =   '<tpl><tag '.$attrs.' /></tpl>';
        $xml        =   simplexml_load_string($xml);
        if(!$xml)
            E(L('_XML_TAG_ERROR_'));
        $xml        =   (array)($xml->tag->attributes());
        $array      =   array_change_key_case($xml['@attributes']);
        return $array;
    }

    /**
     * 替换页面中的literal标签
     * @access private
     * @param string $content  模板内容
     * @return string|false
     */
    private function parseLiteral($content) {
        if(is_array($content)) $content = $content[1];
        if(trim($content)=='')  return '';
        //$content            =   stripslashes($content);
        $i                  =   count($this->literal);
        $parseStr           =   "<!--###literal{$i}###-->";
        $this->literal[$i]  =   $content;
        return $parseStr;
    }

    /**
     * 还原被替换的literal标签
     * @access private
     * @param string $tag  literal标签序号
     * @return string|false
     */
    private function restoreLiteral($tag) {
        if(is_array($tag)) $tag = $tag[1];
        // 还原literal标签
        $parseStr   =  $this->literal[$tag];
        // 销毁literal记录
        unset($this->literal[$tag]);
        return $parseStr;
    }

    /**
     * 记录当前页面中的block标签
     * @access private
     * @param string $name block名称
     * @param string $content  模板内容
     * @return string
     */
    private function parseBlock($name,$content = '') {
        if(is_array($name)){
            $content = $name[2];
            $name    = $name[1];
        }
        $this->block[$name]  =   $content;
        return '';
    }

    /**
     * 替换继承模板中的block标签
     * @access private
     * @param string $content  模板内容
     * @return string
     */
    private function replaceBlock($content){
        static $parse = 0;
        $begin = $this->config['taglib_begin'];
        $end   = $this->config['taglib_end'];
        $reg   = '/('.$begin.'block\sname=[\'"](.+?)[\'"]\s*?'.$end.')(.*?)'.$begin.'\/block'.$end.'/is';
        if(is_string($content)){
            do{
                $content = preg_replace_callback($reg, array($this, 'replaceBlock'), $content);
            } while ($parse && $parse--);
            return $content;
        } elseif(is_array($content)){
            if(preg_match('/'.$begin.'block\sname=[\'"](.+?)[\'"]\s*?'.$end.'/is', $content[3])){ //存在嵌套，进一步解析
                $parse = 1;
                $content[3] = preg_replace_callback($reg, array($this, 'replaceBlock'), "{$content[3]}{$begin}/block{$end}");
                return $content[1] . $content[3];
            } else {
                $name    = $content[2];
                $content = $content[3];
                $content = isset($this->block[$name]) ? $this->block[$name] : $content;
                return $content;
            }
        }
    }

    /**
     * 搜索模板页面中包含的TagLib库
     * 并返回列表
     * @access public
     * @param string $content  模板内容
     * @return string|false
     */
    public function getIncludeTagLib(& $content) {
        //搜索是否有TagLib标签
        $find = preg_match('/'.$this->config['taglib_begin'].'taglib\s(.+?)(\s*?)\/'.$this->config['taglib_end'].'\W/is',$content,$matches);
        if($find) {
            //替换TagLib标签
            $content        =   str_replace($matches[0],'',$content);
            //解析TagLib标签
            $array          =   $this->parseXmlAttrs($matches[1]);
            $this->tagLib   =   explode(',',$array['name']);
        }
        return;
    }

    /**
     * TagLib库解析
     * @access public
     * @param string $tagLib 要解析的标签库
     * @param string $content 要解析的模板内容
     * @param boolean $hide 是否隐藏标签库前缀
     * @return string
     */
    public function parseTagLib($tagLib,&$content,$hide=false) {
        $begin      =   $this->config['taglib_begin'];
        $end        =   $this->config['taglib_end'];
        if(strpos($tagLib,'\\')){
            // 支持指定标签库的命名空间
            $className  =   $tagLib;
            $tagLib     =   substr($tagLib,strrpos($tagLib,'\\')+1);
        }else{
            $className  =   'Think\\Template\TagLib\\'.ucwords($tagLib);            
        }
        $tLib       =   \Think\Think::instance($className);
        $that       =   $this;
        foreach ($tLib->getTags() as $name=>$val){
            $tags = array($name);
            if(isset($val['alias'])) {// 别名设置
                $tags       = explode(',',$val['alias']);
                $tags[]     =  $name;
            }
            $level      =   isset($val['level'])?$val['level']:1;
            $closeTag   =   isset($val['close'])?$val['close']:true;
            foreach ($tags as $tag){
                $parseTag = !$hide? $tagLib.':'.$tag: $tag;// 实际要解析的标签名称
                if(!method_exists($tLib,'_'.$tag)) {
                    // 别名可以无需定义解析方法
                    $tag  =  $name;
                }
                $n1 = empty($val['attr'])?'(\s*?)':'\s([^'.$end.']*)';
                $this->tempVar = array($tagLib, $tag);

                if (!$closeTag){
                    $patterns       = '/'.$begin.$parseTag.$n1.'\/(\s*?)'.$end.'/is';
                    $content        = preg_replace_callback($patterns, function($matches) use($tLib,$tag,$that){
                        return $that->parseXmlTag($tLib,$tag,$matches[1],$matches[2]);
                    },$content);
                }else{
                    $patterns       = '/'.$begin.$parseTag.$n1.$end.'(.*?)'.$begin.'\/'.$parseTag.'(\s*?)'.$end.'/is';
                    for($i=0;$i<$level;$i++) {
                        $content=preg_replace_callback($patterns,function($matches) use($tLib,$tag,$that){
                            return $that->parseXmlTag($tLib,$tag,$matches[1],$matches[2]);
                        },$content);
                    }
                }
            }
        }
    }

    /**
     * 解析标签库的标签
     * 需要调用对应的标签库文件解析类
     * @access public
     * @param object $tagLib  标签库对象实例
     * @param string $tag  标签名
     * @param string $attr  标签属性
     * @param string $content  标签内容
     * @return string|false
     */
    public function parseXmlTag($tagLib,$tag,$attr,$content) {
        if(ini_get('magic_quotes_sybase'))
            $attr   =   str_replace('\"','\'',$attr);
        $parse      =   '_'.$tag;
        $content    =   trim($content);
        $tags       =   $tagLib->parseXmlAttr($attr,$tag);
        return $tagLib->$parse($tags,$content);
    }

    /**
     * 模板标签解析
     * 格式： {TagName:args [|content] }
     * @access public
     * @param string $tagStr 标签内容
     * @return string
     */
    public function parseTag($tagStr){
        if(is_array($tagStr)) $tagStr = $tagStr[2];
        //if (MAGIC_QUOTES_GPC) {
            $tagStr = stripslashes($tagStr);
        //}
        $flag   =  substr($tagStr,0,1);
        $flag2  =  substr($tagStr,1,1);
        $name   = substr($tagStr,1);
        if('$' == $flag && '.' != $flag2 && '(' != $flag2){ //解析模板变量 格式 {$varName}
            return $this->parseVar($name);
        }elseif('-' == $flag || '+'== $flag){ // 输出计算
            return  '<?php echo '.$flag.$name.';?>';
        }elseif(':' == $flag){ // 输出某个函数的结果
            return  '<?php echo '.$name.';?>';
        }elseif('~' == $flag){ // 执行某个函数
            return  '<?php '.$name.';?>';
        }elseif(substr($tagStr,0,2)=='//' || (substr($tagStr,0,2)=='/*' && substr(rtrim($tagStr),-2)=='*/')){
            //注释标签
            return '';
        }
        // 未识别的标签直接返回
        return C('TMPL_L_DELIM') . $tagStr .C('TMPL_R_DELIM');
    }

    /**
     * 模板变量解析,支持使用函数
     * 格式： {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param string $varStr 变量数据
     * @return string
     */
    public function parseVar($varStr){
        $varStr     =   trim($varStr);
        static $_varParseList = array();
        //如果已经解析过该变量字串，则直接返回变量值
        if(isset($_varParseList[$varStr])) return $_varParseList[$varStr];
        $parseStr   =   '';
        $varExists  =   true;
        if(!empty($varStr)){
            $varArray = explode('|',$varStr);
            //取得变量名称
            $var = array_shift($varArray);
            if('Think.' == substr($var,0,6)){
                // 所有以Think.打头的以特殊变量对待 无需模板赋值就可以输出
                $name = $this->parseThinkVar($var);
            }elseif( false !== strpos($var,'.')) {
                //支持 {$var.property}
                $vars = explode('.',$var);
                $var  =  array_shift($vars);
                switch(strtolower(C('TMPL_VAR_IDENTIFY'))) {
                    case 'array': // 识别为数组
                        $name = '$'.$var;
                        foreach ($vars as $key=>$val)
                            $name .= '["'.$val.'"]';
                        break;
                    case 'obj':  // 识别为对象
                        $name = '$'.$var;
                        foreach ($vars as $key=>$val)
                            $name .= '->'.$val;
                        break;
                    default:  // 自动判断数组或对象 只支持二维
                        $name = 'is_array($'.$var.')?$'.$var.'["'.$vars[0].'"]:$'.$var.'->'.$vars[0];
                }
            }elseif(false !== strpos($var,'[')) {
                //支持 {$var['key']} 方式输出数组
                $name = "$".$var;
                preg_match('/(.+?)\[(.+?)\]/is',$var,$match);
                $var = $match[1];
            }elseif(false !==strpos($var,':') && false ===strpos($var,'(') && false ===strpos($var,'::') && false ===strpos($var,'?')){
                //支持 {$var:property} 方式输出对象的属性
                $vars = explode(':',$var);
                $var  =  str_replace(':','->',$var);
                $name = "$".$var;
                $var  = $vars[0];
            }else {
                $name = "$$var";
            }
            //对变量使用函数
            if(count($varArray)>0)
                $name = $this->parseVarFunction($name,$varArray);
            $parseStr = '<?php echo ('.$name.'); ?>';
        }
        $_varParseList[$varStr] = $parseStr;
        return $parseStr;
    }

    /**
     * 对模板变量使用函数
     * 格式 {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param string $name 变量名
     * @param array $varArray  函数列表
     * @return string
     */
    public function parseVarFunction($name,$varArray){
        //对变量使用函数
        $length = count($varArray);
        //取得模板禁止使用函数列表
        $template_deny_funs = explode(',',C('TMPL_DENY_FUNC_LIST'));
        for($i=0;$i<$length ;$i++ ){
            $args = explode('=',$varArray[$i],2);
            //模板函数过滤
            $fun = trim($args[0]);
            switch($fun) {
            case 'default':  // 特殊模板函数
                $name = '(isset('.$name.') && ('.$name.' !== ""))?('.$name.'):'.$args[1];
                break;
            default:  // 通用模板函数
                if(!in_array($fun,$template_deny_funs)){
                    if(isset($args[1])){
                        if(strstr($args[1],'###')){
                            $args[1] = str_replace('###',$name,$args[1]);
                            $name = "$fun($args[1])";
                        }else{
                            $name = "$fun($name,$args[1])";
                        }
                    }else if(!empty($args[0])){
                        $name = "$fun($name)";
                    }
                }
            }
        }
        return $name;
    }

    /**
     * 特殊模板变量解析
     * 格式 以 $Think. 打头的变量属于特殊模板变量
     * @access public
     * @param string $varStr  变量字符串
     * @return string
     */
    public function parseThinkVar($varStr){
        $vars = explode('.',$varStr);
        $vars[1] = strtoupper(trim($vars[1]));
        $parseStr = '';
        if(count($vars)>=3){
            $vars[2] = trim($vars[2]);
            switch($vars[1]){
                case 'SERVER':
                    $parseStr = '$_SERVER[\''.strtoupper($vars[2]).'\']';break;
                case 'GET':
                    $parseStr = '$_GET[\''.$vars[2].'\']';break;
                case 'POST':
                    $parseStr = '$_POST[\''.$vars[2].'\']';break;
                case 'COOKIE':
                    if(isset($vars[3])) {
                        $parseStr = '$_COOKIE[\''.$vars[2].'\'][\''.$vars[3].'\']';
                    }else{
                        $parseStr = 'cookie(\''.$vars[2].'\')';
                    }
                    break;
                case 'SESSION':
                    if(isset($vars[3])) {
                        $parseStr = '$_SESSION[\''.$vars[2].'\'][\''.$vars[3].'\']';
                    }else{
                        $parseStr = 'session(\''.$vars[2].'\')';
                    }
                    break;
                case 'ENV':
                    $parseStr = '$_ENV[\''.strtoupper($vars[2]).'\']';break;
                case 'REQUEST':
                    $parseStr = '$_REQUEST[\''.$vars[2].'\']';break;
                case 'CONST':
                    $parseStr = strtoupper($vars[2]);break;
                case 'LANG':
                    $parseStr = 'L("'.$vars[2].'")';break;
                case 'CONFIG':
                    if(isset($vars[3])) {
                        $vars[2] .= '.'.$vars[3];
                    }
                    $parseStr = 'C("'.$vars[2].'")';break;
                default:break;
            }
        }else if(count($vars)==2){
            switch($vars[1]){
                case 'NOW':
                    $parseStr = "date('Y-m-d g:i a',time())";
                    break;
                case 'VERSION':
                    $parseStr = 'THINK_VERSION';
                    break;
                case 'TEMPLATE':
                    $parseStr = "'".$this->templateFile."'";//'C("TEMPLATE_NAME")';
                    break;
                case 'LDELIM':
                    $parseStr = 'C("TMPL_L_DELIM")';
                    break;
                case 'RDELIM':
                    $parseStr = 'C("TMPL_R_DELIM")';
                    break;
                default:
                    if(defined($vars[1]))
                        $parseStr = $vars[1];
            }
        }
        return $parseStr;
    }

    /**
     * 加载公共模板并缓存 和当前模板在同一路径，否则使用相对路径
     * @access private
     * @param string $tmplPublicName  公共模板文件名
     * @param array $vars  要传递的变量列表
     * @return string
     */
    private function parseIncludeItem($tmplPublicName,$vars=array(),$extend){
        // 分析模板文件名并读取内容
        $parseStr = $this->parseTemplateName($tmplPublicName);
        // 替换变量
        foreach ($vars as $key=>$val) {
            $parseStr = str_replace('['.$key.']',$val,$parseStr);
        }
        // 再次对包含文件进行模板分析
        return $this->parseInclude($parseStr,$extend);
    }

    /**
     * 分析加载的模板文件并读取内容 支持多个模板文件读取
     * @access private
     * @param string $tmplPublicName  模板文件名
     * @return string
     */    
    private function parseTemplateName($templateName){
        if(substr($templateName,0,1)=='$')
            //支持加载变量文件名
            $templateName = $this->get(substr($templateName,1));
        $array  =   explode(',',$templateName);
        $parseStr   =   ''; 
        foreach ($array as $templateName){
            if(empty($templateName)) continue;
            if(false === strpos($templateName,$this->config['template_suffix'])) {
                // 解析规则为 模块@主题/控制器/操作
                $templateName   =   T($templateName);
            }
            // 获取模板文件内容
            $parseStr .= file_get_contents($templateName);
        }
        return $parseStr;
    }    
}
