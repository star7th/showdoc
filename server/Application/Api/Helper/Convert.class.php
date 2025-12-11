<?php
/*
  存放一些转换的逻辑代码，比如从xx格式转成markdown格式
*/

namespace Api\Helper;

use PHPSQLParser\PHPSQLParser;


class Convert
{


    /**
     * 转换 SQL 为 Markdown 表格
     */
    public function convertSqlToMarkdownTable($sql)
    {

        $sql_array = $this->convertSqlToArray($sql);

        $headers = [
            ['字段', '类型', '允许空', '默认', '说明'],
            ['---', '---', '---', '---', '---',],
        ];
        $markdowns = $sql_array['fields'];
        array_unshift($markdowns, ...$headers);

        $html = "\n- {$sql_array['table']} {$sql_array['comment']}\n\n";
        foreach ($markdowns as $line) {
            $html .= '| ' . implode(' | ', $line) . ' | ' . "\n";
        }

        return $html . "\n";
    }

    // 把sql转换成解析数组
    public function convertSqlToArray($sql)
    {
        $return = array(
            'table' => '', // 表名
            'comment' => '', // 注释
            'fields' => array()
        );

        try {
            $parser = new PHPSQLParser();
            $parsed = $parser->parse($sql);

            if (!isset($parsed['CREATE'])) {
                return null;
            }

            // var_dump($parsed);exit();

            if ($parsed['CREATE']['expr_type'] === 'table') {
                $fields = $parsed['TABLE']['create-def']['sub_tree'];
                $tableName = $parsed['TABLE']['base_expr']; // 表名

                foreach ($fields as $field) {
                    if ($field['sub_tree'][0]['expr_type'] == 'constraint') {
                        continue;
                    }

                    // 如果当前行不是列定义，则没有 sub_tree，比如 PRIMARY KEY(id)
                    if (!isset($field['sub_tree'][1]['sub_tree'])) {
                        continue;
                    }

                    $type = $length = '';
                    foreach ($field['sub_tree'][1]['sub_tree'] as $item) {
                        if ($item['expr_type'] == 'data-type') {
                            $type = $item['base_expr'] ?? '';
                            $length = $item['length'] ?? '';
                        }
                    }

                    $name = $field['sub_tree'][0]['base_expr'];
                    $comment = trim($field['sub_tree'][1]['comment'] ?? '', "'");
                    $nullable = $field['sub_tree'][1]['nullable'] ?? false;
                    $default = $field['sub_tree'][1]['default'] ?? '';

                    $type = empty($length) ? $type : "{$type} ($length)";
                    $markdowns[] = [trim($name, '`'), $type, $nullable ? 'Y' : 'N', $default, $comment];
                    $return['fields'][] = array(
                        'name' => trim($name, '`'),
                        'type' => $type,
                        'nullable' => $nullable ? '是' : '否',
                        'default' => trim($default, "'"),
                        'comment' => $comment ? $comment : '-',
                    );
                }

                $tableComment = '';
                $options = $parsed['TABLE']['options'] ?? [];
                if (!$options || empty($options)) {
                    $options = [];
                }

                foreach ($options as $option) {
                    $type = strtoupper($option['sub_tree'][0]['base_expr'] ?? '');
                    if ($type === 'COMMENT') {
                        // var_dump($option['sub_tree']);exit();
                        $tableComment = trim($option['sub_tree'][2]['base_expr'] ?? '', "'");
                        break;
                    }
                }
                $return['table'] = trim($tableName, '`'); // 表名
                $return['comment'] = $tableComment; // 表注释

            }
        } catch (\Exception $ex) {
            return "{$ex->getMessage()} @{$ex->getFile()}:{$ex->getLine()}";
        }



        return $return;
    }

    //把runapi的格式内容转换为markdown格式。如果不是runapi格式，则会返回false
    //参数content为json字符串或者数组
    public function runapiToMd($content)
    {
        if (!is_array($content)) {
            $content_json = htmlspecialchars_decode($content);
            $content = json_decode($content_json, true);
        }
        if (!$content || !$content['info'] || !$content['info']['url']) {
            return false;
        }

        // 判断类型（兼容旧数据）
        $type = isset($content['info']['type']) ? $content['info']['type'] : 'api';

        if ($type === 'websocket') {
            return $this->runapiWebSocketToMd($content);
        } elseif ($type === 'sse') {
            return $this->runapiSSEToMd($content);
        } else {
            return $this->runapiHttpApiToMd($content);
        }
    }

    // HTTP API 转换函数（原 runapiToMd 逻辑）
    private function runapiHttpApiToMd($content)
    {
        // 兼容query
        if ($content['info']['method'] == 'get') {
            if (!$content['request']['query']) {
                $content['request']['query'] = $content['request']['params'][$content['request']['params']['mode']];
            }
            $content['request']['params'][$content['request']['params']['mode']] = array();
        }

        $new_content = "\n##### 简要描述\n\n- " . ($content['info']['description'] ? $content['info']['description'] : '无');

        if ($content['info']['apiStatus']) {
            $statusText = '';
            switch ($content['info']['apiStatus']) {
                case '1':
                    $statusText = '开发中';
                    break;
                case '2':
                    $statusText = '测试中';
                    break;
                case '3':
                    $statusText = '已完成';
                    break;
                case '4':
                    $statusText = '需修改';
                    break;
                case '5':
                    $statusText = '已废弃';
                    break;
                default:
                    break;
            }

            $new_content .= "\n\n##### 接口状态\n\n - " . $statusText;
        }

        // 如果有query参数组，则把url中的参数去掉
        $query = $content['request']['query'];
        if ($query && is_array($query) && $query[0] && $query[0]['name']) {
            $words = explode('?', $content['info']['url']);
            $content['info']['url']  = $words[0];
        }

        $new_content .= "\n\n##### 请求URL\n\n - `{$content['info']['url']}` \n\n##### 请求方式\n\n- {$content['info']['method']}\n";
        $pathVariable = $content['request']['pathVariable'];
        if ($pathVariable && is_array($pathVariable) && $pathVariable[0] && $pathVariable[0]['name']) {
            $new_content .= " \n##### 路径变量\n\n|变量名|必选|类型|说明|\n|:-----  |:-----|-----|\n";
            foreach ($pathVariable as $key => $value) {
                // 如果名字为空，或者存在禁用的key且禁用状态生效中，则跳过本条参数
                if (!$value['name'] || (isset($value['disable']) && $value['disable'] >= 1)) {
                    continue;
                }
                $value['require'] = $value['require'] > 0 ? "是" : "否";
                $value['remark'] = $value['remark'] ? $value['remark'] : '无';
                $new_content .= "|{$value['name']}|  {$value['require']} |  {$value['type']} |  {$value['remark']} | \n";
            }
        }

        if ($content['request']['headers'] && $content['request']['headers'][0] && $content['request']['headers'][0]['name']) {
            $new_content .= " \n##### Header \n\n|字段名|示例值|必选|类型|说明|\n|:-----  |:-----|-----|\n";
            foreach ($content['request']['headers'] as $key => $value) {
                // 如果名字为空，或者存在禁用的key且禁用状态生效中，则跳过本条参数
                if (!$value['name'] || (isset($value['disable']) && $value['disable'] >= 1)) {
                    continue;
                }
                $value['require'] = $value['require'] > 0 ? "是" : "否";
                $value['remark'] = $value['remark'] ? $value['remark'] : '无';
                $value['value'] = $value['value'] ? $value['value'] : '';
                $new_content .= "|{$value['name']}|  {$value['value']} |  {$value['require']} |  {$value['type']} |  {$value['remark']} | \n";
            }
        }

        $query = $content['request']['query'];
        if ($query && is_array($query) && $query[0] && $query[0]['name']) {
            $new_content .= " \n##### 请求Query参数\n\n|参数名|示例值|必选|类型|说明|\n|:-----  |:-----|-----|\n";
            foreach ($query as $key => $value) {
                // 如果名字为空，或者存在禁用的key且禁用状态生效中，则跳过本条参数
                if (!$value['name'] || (isset($value['disable']) && $value['disable'] >= 1)) {
                    continue;
                }
                $value['require'] = $value['require'] > 0 ? "是" : "否";
                $value['remark'] = $value['remark'] ? $value['remark'] : '无';
                $value['value'] = $value['value'] ? $value['value'] : '';
                $new_content .= "|{$value['name']}|  {$value['value']}|  {$value['require']} |  {$value['type']} |  {$value['remark']} | \n";
            }
        }

        $params = $content['request']['params'][$content['request']['params']['mode']];
        if ($params && is_array($params) && $params[0] && $params[0]['name']) {
            $new_content .= " \n##### 请求Body参数\n\n|参数名|示例值|必选|类型|说明|\n|:-----  |:-----|-----|\n";
            foreach ($params as $key => $value) {
                // 如果名字为空，或者存在禁用的key且禁用状态生效中，则跳过本条参数
                if (!$value['name'] || (isset($value['disable']) && $value['disable'] >= 1)) {
                    continue;
                }
                $value['require'] = $value['require'] > 0 ? "是" : "否";
                $value['remark'] = $value['remark'] ? $value['remark'] : '无';
                $value['value'] = $value['value'] ? $value['value'] : '';
                $new_content .= "|{$value['name']}|  {$value['value']} |  {$value['require']} |  {$value['type']} |  {$value['remark']} | \n";
            }
        }
        //如果参数类型为json
        if ($content['request']['params']['mode'] == 'json' && $params) {
            $params = $this->_indent_json($params);
            $new_content .= " \n##### 请求参数示例  \n```\n{$params}\n\n``` \n";
        }
        // json字段说明
        $jsonDesc = $content['request']['params']['jsonDesc'];
        if ($content['request']['params']['mode'] == 'json' && $jsonDesc && $jsonDesc[0] && $jsonDesc[0]['name']) {
            $new_content .= " \n##### json字段说明\n\n|字段名|必选|类型|说明|\n|:-----  |:-----|-----|\n";
            foreach ($jsonDesc as $key => $value) {
                // 如果名字为空，或者存在禁用的key且禁用状态生效中，则跳过本条参数
                if (!$value['name'] || (isset($value['disable']) && $value['disable'] >= 1)) {
                    continue;
                }
                $value['require'] = $value['require'] > 0 ? "是" : "否";
                $value['remark'] = $value['remark'] ? $value['remark'] : '无';
                $new_content .= "|{$value['name']}|  {$value['require']} |  {$value['type']} |  {$value['remark']} | \n";
            }
        }

        //成功返回示例
        if ($content['response']['responseExample']) {
            $responseExample = $this->_indent_json($content['response']['responseExample']);
            $responseExample = $responseExample ? $responseExample : $content['response']['responseExample'];
            $new_content .= " \n##### 成功返回示例  \n```\n{$responseExample}\n\n``` \n";
        }

        //返回示例说明
        if ($content['response']['responseParamsDesc'] && $content['response']['responseParamsDesc'][0] && $content['response']['responseParamsDesc'][0]['name']) {
            $new_content .= " \n##### 成功返回示例的参数说明 \n\n|参数名|类型|说明|\n|:-----  |:-----|-----|\n";
            foreach ($content['response']['responseParamsDesc'] as $key => $value) {
                $value['remark'] = $value['remark'] ? $value['remark'] : '无';
                $new_content .= "|{$value['name']}| {$value['type']} |  {$value['remark']} | \n";
            }
        }

        //失败返回示例
        if ($content['response']['responseFailExample']) {
            $responseFailExample = $this->_indent_json($content['response']['responseFailExample']);
            $responseFailExample = $responseFailExample ? $responseFailExample : $content['response']['responseFailExample'];
            $new_content .= " \n##### 失败返回示例  \n```\n{$responseFailExample}\n\n``` \n";
        }

        //返回示例说明
        if ($content['response']['responseFailParamsDesc'] && $content['response']['responseFailParamsDesc'][0] && $content['response']['responseFailParamsDesc'][0]['name']) {
            $new_content .= " \n##### 失败返回示例的参数说明 \n|参数名|类型|说明|\n|:-----  |:-----|-----|\n";
            foreach ($content['response']['responseFailParamsDesc'] as $key => $value) {
                $value['remark'] = $value['remark'] ? $value['remark'] : '无';
                $new_content .= "|{$value['name']}| {$value['type']} |  {$value['remark']} | \n";
            }
        }

        if ($content['info']['remark']) {
            $new_content .= " \n##### 备注 \n {$content['info']['remark']}\n";
        }


        return $new_content;
    }

    // WebSocket 转换函数
    private function runapiWebSocketToMd($content)
    {
        $new_content = "\n##### 简要描述\n\n- " . ($content['info']['description'] ? $content['info']['description'] : '无');

        // 接口状态
        if (isset($content['info']['apiStatus']) && $content['info']['apiStatus']) {
            $statusText = '';
            switch ($content['info']['apiStatus']) {
                case '1':
                    $statusText = '开发中';
                    break;
                case '2':
                    $statusText = '测试中';
                    break;
                case '3':
                    $statusText = '已完成';
                    break;
                case '4':
                    $statusText = '需修改';
                    break;
                case '5':
                    $statusText = '已废弃';
                    break;
                default:
                    break;
            }
            if ($statusText) {
                $new_content .= "\n\n##### 接口状态\n\n - " . $statusText;
            }
        }

        $new_content .= "\n\n##### 协议类型\n\n- WebSocket\n";
        $new_content .= "\n\n##### 连接URL\n\n - `{$content['info']['url']}`\n";

        // 子协议
        if (
            isset($content['protocolConfig']['websocket']['subProtocols']) &&
            is_array($content['protocolConfig']['websocket']['subProtocols']) &&
            !empty($content['protocolConfig']['websocket']['subProtocols'])
        ) {
            $protocols = implode(', ', $content['protocolConfig']['websocket']['subProtocols']);
            $new_content .= "\n\n##### 子协议\n\n- {$protocols}\n";
        }

        // Headers（握手阶段）- 使用 request.headers（复用）
        if (
            isset($content['request']['headers']) &&
            is_array($content['request']['headers']) &&
            !empty($content['request']['headers']) &&
            isset($content['request']['headers'][0]['name'])
        ) {
            $new_content .= " \n##### 连接Headers \n\n|字段名|示例值|必选|类型|说明|\n|:-----  |:-----|:-----|:-----|:-----|\n";
            foreach ($content['request']['headers'] as $key => $value) {
                if (!isset($value['name']) || !$value['name'] || (isset($value['disable']) && $value['disable'] >= 1)) {
                    continue;
                }
                $require = isset($value['require']) && $value['require'] > 0 ? "是" : "否";
                $remark = isset($value['remark']) && $value['remark'] ? $value['remark'] : '无';
                $val = isset($value['value']) && $value['value'] ? $value['value'] : '';
                $type = isset($value['type']) && $value['type'] ? $value['type'] : 'string';
                $new_content .= "|{$value['name']}|  {$val} |  {$require} |  {$type} |  {$remark} | \n";
            }
        }

        // Query 参数 - 使用 request.query（复用）
        if (
            isset($content['request']['query']) &&
            is_array($content['request']['query']) &&
            !empty($content['request']['query']) &&
            isset($content['request']['query'][0]['name'])
        ) {
            $new_content .= " \n##### 连接Query参数\n\n|参数名|示例值|必选|类型|说明|\n|:-----  |:-----|:-----|:-----|:-----|\n";
            foreach ($content['request']['query'] as $key => $value) {
                if (!isset($value['name']) || !$value['name'] || (isset($value['disable']) && $value['disable'] >= 1)) {
                    continue;
                }
                $require = isset($value['require']) && $value['require'] > 0 ? "是" : "否";
                $remark = isset($value['remark']) && $value['remark'] ? $value['remark'] : '无';
                $val = isset($value['value']) && $value['value'] ? $value['value'] : '';
                $type = isset($value['type']) && $value['type'] ? $value['type'] : 'string';
                $new_content .= "|{$value['name']}|  {$val}|  {$require} |  {$type} |  {$remark} | \n";
            }
        }

        // 认证配置
        if (
            isset($content['request']['auth']) &&
            is_array($content['request']['auth']) &&
            isset($content['request']['auth']['type']) &&
            $content['request']['auth']['type'] &&
            (!isset($content['request']['auth']['disabled']) || $content['request']['auth']['disabled'] !== '1')
        ) {
            $authType = $content['request']['auth']['type'];
            $authTypeText = '';
            switch ($authType) {
                case 'bearer':
                    $authTypeText = 'Bearer Token';
                    break;
                case 'basic':
                    $authTypeText = 'Basic Auth';
                    break;
                default:
                    $authTypeText = $authType;
                    break;
            }
            $new_content .= " \n##### 认证方式\n\n- {$authTypeText}\n";
        }

        // 重连配置
        if (
            isset($content['protocolConfig']['websocket']['autoReconnect']) &&
            $content['protocolConfig']['websocket']['autoReconnect']
        ) {
            $interval = isset($content['protocolConfig']['websocket']['reconnectInterval']) ?
                $content['protocolConfig']['websocket']['reconnectInterval'] : 3000;
            $maxTimes = isset($content['protocolConfig']['websocket']['reconnectMaxTimes']) ?
                $content['protocolConfig']['websocket']['reconnectMaxTimes'] : 5;
            $maxTimesText = $maxTimes > 0 ? "{$maxTimes}次" : "无限";
            $new_content .= " \n##### 重连配置\n\n- 自动重连：是\n- 重连间隔：{$interval}毫秒\n- 最大重连次数：{$maxTimesText}\n";
        }

        // 心跳配置
        if (
            isset($content['protocolConfig']['websocket']['heartbeat']['enabled']) &&
            $content['protocolConfig']['websocket']['heartbeat']['enabled']
        ) {
            $interval = isset($content['protocolConfig']['websocket']['heartbeat']['interval']) ?
                $content['protocolConfig']['websocket']['heartbeat']['interval'] : 30000;
            $pingMsg = isset($content['protocolConfig']['websocket']['heartbeat']['pingMessage']) ?
                $content['protocolConfig']['websocket']['heartbeat']['pingMessage'] : 'ping';
            $new_content .= " \n##### 心跳配置\n\n- 心跳间隔：{$interval}毫秒\n- Ping 消息：`{$pingMsg}`\n";
        }

        // 消息模板
        if (
            isset($content['messaging']['templates']) &&
            is_array($content['messaging']['templates']) &&
            !empty($content['messaging']['templates'])
        ) {
            $new_content .= " \n##### 消息模板\n\n";
            foreach ($content['messaging']['templates'] as $msg) {
                if (isset($msg['enabled']) && !$msg['enabled']) {
                    continue;
                }
                $msgType = isset($msg['type']) ? $msg['type'] : 'text';
                $msgName = isset($msg['name']) ? $msg['name'] : '未命名';
                $msgContent = isset($msg['payload']) ? $msg['payload'] : '';

                // 格式化 JSON
                if ($msgType === 'json') {
                    $msgContent = $this->_indent_json($msgContent);
                }

                $new_content .= "**{$msgName}** ({$msgType})\n```\n{$msgContent}\n```\n\n";
            }
        }

        // 消息示例（支持多示例）
        if (
            isset($content['response']['examples']) &&
            is_array($content['response']['examples']) &&
            !empty($content['response']['examples'])
        ) {
            $new_content .= " \n##### 消息示例\n\n";

            foreach ($content['response']['examples'] as $example) {
                if (!isset($example['data'])) {
                    continue;
                }

                $exampleName = isset($example['name']) ? $example['name'] : '示例';
                $exampleData = $this->_indent_json($example['data']);
                $exampleData = $exampleData ? $exampleData : $example['data'];

                $new_content .= "**{$exampleName}**\n```\n{$exampleData}\n```\n\n";

                // 字段说明
                if (
                    isset($example['param']) &&
                    is_array($example['param']) &&
                    !empty($example['param']) &&
                    isset($example['param'][0]['name'])
                ) {
                    $new_content .= "|字段名|类型|说明|\n|:-----  |:-----|:-----|\n";
                    foreach ($example['param'] as $param) {
                        if (!isset($param['name']) || !$param['name']) {
                            continue;
                        }
                        $paramType = isset($param['type']) ? $param['type'] : 'string';
                        $paramRemark = isset($param['remark']) && $param['remark'] ? $param['remark'] : '无';
                        $new_content .= "|{$param['name']}| {$paramType} |  {$paramRemark} | \n";
                    }
                    $new_content .= "\n";
                }
            }
        }

        // 备注
        if (isset($content['response']['remark']) && $content['response']['remark']) {
            $new_content .= " \n##### 备注 \n {$content['response']['remark']}\n";
        } elseif (isset($content['info']['remark']) && $content['info']['remark']) {
            $new_content .= " \n##### 备注 \n {$content['info']['remark']}\n";
        }

        return $new_content;
    }

    // SSE 转换函数
    private function runapiSSEToMd($content)
    {
        $new_content = "\n##### 简要描述\n\n- " . ($content['info']['description'] ? $content['info']['description'] : '无');

        // 接口状态
        if (isset($content['info']['apiStatus']) && $content['info']['apiStatus']) {
            $statusText = '';
            switch ($content['info']['apiStatus']) {
                case '1':
                    $statusText = '开发中';
                    break;
                case '2':
                    $statusText = '测试中';
                    break;
                case '3':
                    $statusText = '已完成';
                    break;
                case '4':
                    $statusText = '需修改';
                    break;
                case '5':
                    $statusText = '已废弃';
                    break;
                default:
                    break;
            }
            if ($statusText) {
                $new_content .= "\n\n##### 接口状态\n\n - " . $statusText;
            }
        }

        $new_content .= "\n\n##### 协议类型\n\n- SSE (Server-Sent Events)\n";

        // HTTP 方法
        $method = isset($content['info']['method']) ? strtoupper($content['info']['method']) : 'POST';
        $new_content .= "\n\n##### 请求方法\n\n- {$method}\n";

        $new_content .= "\n\n##### 请求URL\n\n - `{$content['info']['url']}`\n";

        // Headers
        if (
            isset($content['request']['headers']) &&
            is_array($content['request']['headers']) &&
            !empty($content['request']['headers']) &&
            isset($content['request']['headers'][0]['name'])
        ) {
            $new_content .= " \n##### 请求Headers \n\n|字段名|示例值|必选|类型|说明|\n|:-----  |:-----|:-----|:-----|:-----|\n";
            foreach ($content['request']['headers'] as $key => $value) {
                if (!isset($value['name']) || !$value['name'] || (isset($value['disable']) && $value['disable'] >= 1)) {
                    continue;
                }
                $require = isset($value['require']) && $value['require'] > 0 ? "是" : "否";
                $remark = isset($value['remark']) && $value['remark'] ? $value['remark'] : '无';
                $val = isset($value['value']) && $value['value'] ? $value['value'] : '';
                $type = isset($value['type']) && $value['type'] ? $value['type'] : 'string';
                $new_content .= "|{$value['name']}|  {$val} |  {$require} |  {$type} |  {$remark} | \n";
            }
        }

        // Query 参数
        if (
            isset($content['request']['query']) &&
            is_array($content['request']['query']) &&
            !empty($content['request']['query']) &&
            isset($content['request']['query'][0]['name'])
        ) {
            $new_content .= " \n##### 请求Query参数\n\n|参数名|示例值|必选|类型|说明|\n|:-----  |:-----|:-----|:-----|:-----|\n";
            foreach ($content['request']['query'] as $key => $value) {
                if (!isset($value['name']) || !$value['name'] || (isset($value['disable']) && $value['disable'] >= 1)) {
                    continue;
                }
                $require = isset($value['require']) && $value['require'] > 0 ? "是" : "否";
                $remark = isset($value['remark']) && $value['remark'] ? $value['remark'] : '无';
                $val = isset($value['value']) && $value['value'] ? $value['value'] : '';
                $type = isset($value['type']) && $value['type'] ? $value['type'] : 'string';
                $new_content .= "|{$value['name']}|  {$val}|  {$require} |  {$type} |  {$remark} | \n";
            }
        }

        // Body 参数（POST 等方法）
        if (
            isset($content['request']['params']) &&
            isset($content['request']['params']['mode']) &&
            $content['request']['params']['mode'] !== 'none'
        ) {
            $mode = $content['request']['params']['mode'];
            $modeText = '';
            switch ($mode) {
                case 'json':
                    $modeText = 'JSON';
                    break;
                case 'urlencoded':
                    $modeText = 'URL Encoded';
                    break;
                case 'formdata':
                    $modeText = 'Form Data';
                    break;
                default:
                    $modeText = $mode;
                    break;
            }

            if ($mode === 'json' && isset($content['request']['params']['json']) && $content['request']['params']['json']) {
                $jsonBody = $this->_indent_json($content['request']['params']['json']);
                $jsonBody = $jsonBody ? $jsonBody : $content['request']['params']['json'];
                $new_content .= " \n##### 请求Body ({$modeText})\n\n```\n{$jsonBody}\n```\n";
            } elseif ($mode === 'urlencoded' && isset($content['request']['params']['urlencoded']) && is_array($content['request']['params']['urlencoded']) && !empty($content['request']['params']['urlencoded'])) {
                $new_content .= " \n##### 请求Body ({$modeText})\n\n|参数名|示例值|必选|类型|说明|\n|:-----  |:-----|:-----|:-----|:-----|\n";
                foreach ($content['request']['params']['urlencoded'] as $key => $value) {
                    if (!isset($value['name']) || !$value['name'] || (isset($value['disable']) && $value['disable'] >= 1)) {
                        continue;
                    }
                    $require = isset($value['require']) && $value['require'] > 0 ? "是" : "否";
                    $remark = isset($value['remark']) && $value['remark'] ? $value['remark'] : '无';
                    $val = isset($value['value']) && $value['value'] ? $value['value'] : '';
                    $type = isset($value['type']) && $value['type'] ? $value['type'] : 'string';
                    $new_content .= "|{$value['name']}|  {$val}|  {$require} |  {$type} |  {$remark} | \n";
                }
            } elseif ($mode === 'formdata' && isset($content['request']['params']['formdata']) && is_array($content['request']['params']['formdata']) && !empty($content['request']['params']['formdata'])) {
                $new_content .= " \n##### 请求Body ({$modeText})\n\n|参数名|示例值|必选|类型|说明|\n|:-----  |:-----|:-----|:-----|:-----|\n";
                foreach ($content['request']['params']['formdata'] as $key => $value) {
                    if (!isset($value['name']) || !$value['name'] || (isset($value['disable']) && $value['disable'] >= 1)) {
                        continue;
                    }
                    $require = isset($value['require']) && $value['require'] > 0 ? "是" : "否";
                    $remark = isset($value['remark']) && $value['remark'] ? $value['remark'] : '无';
                    $val = isset($value['value']) && $value['value'] ? $value['value'] : '';
                    $type = isset($value['type']) && $value['type'] ? $value['type'] : 'string';
                    $new_content .= "|{$value['name']}|  {$val}|  {$require} |  {$type} |  {$remark} | \n";
                }
            }
        }

        // 认证配置
        if (
            isset($content['request']['auth']) &&
            is_array($content['request']['auth']) &&
            isset($content['request']['auth']['type']) &&
            $content['request']['auth']['type'] &&
            (!isset($content['request']['auth']['disabled']) || $content['request']['auth']['disabled'] !== '1')
        ) {
            $authType = $content['request']['auth']['type'];
            $authTypeText = '';
            switch ($authType) {
                case 'bearer':
                    $authTypeText = 'Bearer Token';
                    break;
                case 'basic':
                    $authTypeText = 'Basic Auth';
                    break;
                default:
                    $authTypeText = $authType;
                    break;
            }
            $new_content .= " \n##### 认证方式\n\n- {$authTypeText}\n";
        }

        // 消息示例（支持多示例）
        if (
            isset($content['response']['examples']) &&
            is_array($content['response']['examples']) &&
            !empty($content['response']['examples'])
        ) {
            $new_content .= " \n##### 返回示例\n\n";

            foreach ($content['response']['examples'] as $example) {
                if (!isset($example['data'])) {
                    continue;
                }

                $exampleName = isset($example['name']) ? $example['name'] : '示例';
                $exampleData = $this->_indent_json($example['data']);
                $exampleData = $exampleData ? $exampleData : $example['data'];

                $new_content .= "**{$exampleName}**\n```\n{$exampleData}\n```\n\n";

                // 字段说明
                if (
                    isset($example['param']) &&
                    is_array($example['param']) &&
                    !empty($example['param']) &&
                    isset($example['param'][0]['name'])
                ) {
                    $new_content .= "|字段名|类型|说明|\n|:-----  |:-----|:-----|\n";
                    foreach ($example['param'] as $param) {
                        if (!isset($param['name']) || !$param['name']) {
                            continue;
                        }
                        $paramType = isset($param['type']) ? $param['type'] : 'string';
                        $paramRemark = isset($param['remark']) && $param['remark'] ? $param['remark'] : '无';
                        $new_content .= "|{$param['name']}| {$paramType} |  {$paramRemark} | \n";
                    }
                    $new_content .= "\n";
                }
            }
        }

        // 备注
        if (isset($content['response']['remark']) && $content['response']['remark']) {
            $new_content .= " \n##### 备注 \n {$content['response']['remark']}\n";
        } elseif (isset($content['info']['remark']) && $content['info']['remark']) {
            $new_content .= " \n##### 备注 \n {$content['info']['remark']}\n";
        }

        return $new_content;
    }

    // json美化
    private function _indent_json($json)
    {
        $json_new = json_encode(json_decode($json), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json_new && $json_new != 'null') {
            return $json_new;
        }
        return $json;
    }
}
