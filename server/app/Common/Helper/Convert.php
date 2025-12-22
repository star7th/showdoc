<?php

namespace App\Common\Helper;

use App\Model\Options;
use PHPSQLParser\PHPSQLParser;

/**
 * 各类内容格式转换工具（SQL → Markdown、Runapi JSON ↔ Markdown 等）。
 *
 * 该实现基于旧版 `Api\Helper\Convert`，但不再依赖 ThinkPHP 的 D()/M()，
 * 所有配置通过新模型 `App\Model\Options` 获取。
 */
class Convert
{
    /**
     * 转换 SQL 为 Markdown 表格
     */
    public function convertSqlToMarkdownTable($sql)
    {
        $sqlArray = $this->convertSqlToArray($sql);

        if (!is_array($sqlArray) || empty($sqlArray['fields'])) {
            return "";
        }

        $headers = [
            ['字段', '类型', '允许空', '默认', '说明'],
            ['---', '---', '---', '---', '---'],
        ];

        $markdownRows = [];
        foreach ($sqlArray['fields'] as $field) {
            $markdownRows[] = [
                $field['name'] ?? '',
                $field['type'] ?? '',
                $field['nullable'] ?? '',
                $field['default'] ?? '',
                $field['comment'] ?? '',
            ];
        }

        array_unshift($markdownRows, ...$headers);

        $md = "\n- {$sqlArray['table']} {$sqlArray['comment']}\n\n";
        foreach ($markdownRows as $line) {
            $md .= '| ' . implode(' | ', $line) . " |\n";
        }

        return $md . "\n";
    }

    /**
     * 把 CREATE TABLE 语句解析成结构化数组
     */
    public function convertSqlToArray($sql)
    {
        $result = [
            'table'   => '',
            'comment' => '',
            'fields'  => [],
        ];

        try {
            $parser = new PHPSQLParser();
            $parsed = $parser->parse($sql);

            if (!isset($parsed['CREATE']) || ($parsed['CREATE']['expr_type'] ?? '') !== 'table') {
                return $result;
            }

            $tableNode = $parsed['TABLE'] ?? null;
            if (!$tableNode || !isset($tableNode['create-def']['sub_tree'])) {
                return $result;
            }

            $fields = $tableNode['create-def']['sub_tree'];
            $tableName = $tableNode['base_expr'] ?? '';

            foreach ($fields as $field) {
                if (!isset($field['sub_tree'][0])) {
                    continue;
                }

                // 跳过约束行（PRIMARY KEY/UNIQUE 等）
                if (($field['sub_tree'][0]['expr_type'] ?? '') === 'constraint') {
                    continue;
                }

                if (!isset($field['sub_tree'][1]['sub_tree'])) {
                    continue;
                }

                $type   = '';
                $length = '';
                foreach ($field['sub_tree'][1]['sub_tree'] as $item) {
                    if (($item['expr_type'] ?? '') === 'data-type') {
                        $type   = $item['base_expr'] ?? '';
                        $length = $item['length'] ?? '';
                    }
                }

                $name    = $field['sub_tree'][0]['base_expr'] ?? '';
                $comment = trim($field['sub_tree'][1]['comment'] ?? '', "'");
                $nullable = $field['sub_tree'][1]['nullable'] ?? false;
                $default  = $field['sub_tree'][1]['default'] ?? '';

                $typeStr = $length === '' ? $type : sprintf('%s (%s)', $type, $length);

                $result['fields'][] = [
                    'name'     => trim($name, '`'),
                    'type'     => $typeStr,
                    'nullable' => $nullable ? '是' : '否',
                    'default'  => trim($default, "'"),
                    'comment'  => $comment !== '' ? $comment : '-',
                ];
            }

            // 表注释
            $tableComment = '';
            $options = $tableNode['options'] ?? [];
            foreach ($options as $option) {
                $type = strtoupper($option['sub_tree'][0]['base_expr'] ?? '');
                if ($type === 'COMMENT') {
                    $tableComment = trim($option['sub_tree'][2]['base_expr'] ?? '', "'");
                    break;
                }
            }

            $result['table']   = trim($tableName, '`');
            $result['comment'] = $tableComment;
        } catch (\Throwable $e) {
            // 解析失败时返回空结构，由上层自行判断
            return $result;
        }

        return $result;
    }

    /**
     * 把 runapi 的 JSON/字符串内容尝试转换为 Markdown。
     * 非 runapi 格式时返回 false（保持与旧实现兼容）。
     */
    public function runapiToMd($content)
    {
        if (!is_array($content)) {
            $contentJson = html_entity_decode((string) $content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $content = json_decode($contentJson, true);
        }

        if (!$content || !isset($content['info']) || empty($content['info']['url'])) {
            return false;
        }

        $type = $content['info']['type'] ?? 'api';

        if ($type === 'websocket') {
            return $this->runapiWebSocketToMd($content);
        }
        if ($type === 'sse') {
            return $this->runapiSSEToMd($content);
        }

        return $this->runapiHttpApiToMd($content);
    }

    /**
     * HTTP API → Markdown（摘自旧实现，略做整理）。
     */
    private function runapiHttpApiToMd(array $content)
    {
        // 兼容 query：GET 且 query 为空时，从 params.mode 对应的数据填充
        if (($content['info']['method'] ?? '') === 'get') {
            if (empty($content['request']['query'])) {
                $mode = $content['request']['params']['mode'] ?? '';
                if ($mode && !empty($content['request']['params'][$mode])) {
                    $content['request']['query'] = $content['request']['params'][$mode];
                    $content['request']['params'][$mode] = [];
                }
            }
        }

        $desc = $content['info']['description'] ?? '无';
        $new  = "\n##### 简要描述\n\n- {$desc}";

        // 接口状态
        if (!empty($content['info']['apiStatus'])) {
            $statusText = '';
            switch ((string) $content['info']['apiStatus']) {
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
            }
            if ($statusText !== '') {
                $new .= "\n\n##### 接口状态\n\n - {$statusText}";
            }
        }

        // 如果有 query 参数则去掉 URL 上的查询串
        $query = $content['request']['query'] ?? [];
        if (!empty($query) && isset($query[0]['name']) && $query[0]['name']) {
            $parts = explode('?', $content['info']['url'] ?? '');
            $content['info']['url'] = $parts[0];
        }

        $url    = $content['info']['url'] ?? '';
        $method = $content['info']['method'] ?? '';
        $new   .= "\n\n##### 请求URL\n\n - `{$url}` \n\n##### 请求方式\n\n- {$method}\n";

        // 路径变量
        $pathVariable = $content['request']['pathVariable'] ?? [];
        if (!empty($pathVariable) && isset($pathVariable[0]['name']) && $pathVariable[0]['name']) {
            $new .= " \n##### 路径变量\n\n|变量名|必选|类型|说明|\n|:-----  |:-----|-----|-----|\n";
            foreach ($pathVariable as $v) {
                if (empty($v['name']) || (!empty($v['disable']) && $v['disable'] >= 1)) {
                    continue;
                }
                $require = !empty($v['require']) ? '是' : '否';
                $remark  = $v['remark'] ?? '无';
                $type    = $v['type'] ?? '';
                $new    .= "|{$v['name']}|  {$require} |  {$type} |  {$remark} | \n";
            }
        }

        // Header
        $headers = $content['request']['headers'] ?? [];
        if (!empty($headers) && isset($headers[0]['name']) && $headers[0]['name']) {
            $new .= " \n##### Header \n\n|字段名|示例值|必选|类型|说明|\n|:-----  |:-----|-----|-----|-----|\n";
            foreach ($headers as $h) {
                if (empty($h['name']) || (!empty($h['disable']) && $h['disable'] >= 1)) {
                    continue;
                }
                $require = !empty($h['require']) ? '是' : '否';
                $remark  = $h['remark'] ?? '无';
                $value   = $h['value'] ?? '';
                $type    = $h['type'] ?? '';
                $new    .= "|{$h['name']}|  {$value} |  {$require} |  {$type} |  {$remark} | \n";
            }
        }

        // Query 参数
        if (!empty($query) && isset($query[0]['name']) && $query[0]['name']) {
            $new .= " \n##### 请求Query参数\n\n|参数名|示例值|必选|类型|说明|\n|:-----  |:-----|-----|-----|-----|\n";
            foreach ($query as $q) {
                if (empty($q['name']) || (!empty($q['disable']) && $q['disable'] >= 1)) {
                    continue;
                }
                $require = !empty($q['require']) ? '是' : '否';
                $remark  = $q['remark'] ?? '无';
                $value   = $q['value'] ?? '';
                $type    = $q['type'] ?? '';
                $new    .= "|{$q['name']}|  {$value}|  {$require} |  {$type} |  {$remark} | \n";
            }
        }

        // Body 参数
        $mode   = $content['request']['params']['mode'] ?? '';
        $params = $mode ? ($content['request']['params'][$mode] ?? []) : [];
        if (!empty($params) && isset($params[0]['name']) && $params[0]['name']) {
            $new .= " \n##### 请求Body参数\n\n|参数名|示例值|必选|类型|说明|\n|:-----  |:-----|-----|-----|-----|\n";
            foreach ($params as $p) {
                if (empty($p['name']) || (!empty($p['disable']) && $p['disable'] >= 1)) {
                    continue;
                }
                $require = !empty($p['require']) ? '是' : '否';
                $remark  = $p['remark'] ?? '无';
                $value   = $p['value'] ?? '';
                $type    = $p['type'] ?? '';
                $new    .= "|{$p['name']}|  {$value} |  {$require} |  {$type} |  {$remark} | \n";
            }
        }

        // JSON 示例
        if ($mode === 'json' && !empty($params)) {
            $json = $this->indentJson($params);
            $new .= " \n##### 请求参数示例  \n```\n{$json}\n\n``` \n";
        }

        // json 字段说明
        $jsonDesc = $content['request']['params']['jsonDesc'] ?? [];
        if ($mode === 'json' && !empty($jsonDesc) && isset($jsonDesc[0]['name']) && $jsonDesc[0]['name']) {
            $new .= " \n##### json字段说明\n\n|字段名|必选|类型|说明|\n|:-----  |:-----|-----|-----|\n";
            foreach ($jsonDesc as $j) {
                if (empty($j['name']) || (!empty($j['disable']) && $j['disable'] >= 1)) {
                    continue;
                }
                $require = !empty($j['require']) ? '是' : '否';
                $remark  = $j['remark'] ?? '无';
                $type    = $j['type'] ?? '';
                $new    .= "|{$j['name']}|  {$require} |  {$type} |  {$remark} | \n";
            }
        }

        // 成功返回示例
        if (!empty($content['response']['responseExample'])) {
            $example = $this->indentJson($content['response']['responseExample']);
            $new    .= " \n##### 成功返回示例  \n```\n{$example}\n\n``` \n";
        }

        // 成功返回参数说明
        $respParams = $content['response']['responseParamsDesc'] ?? [];
        if (!empty($respParams) && isset($respParams[0]['name']) && $respParams[0]['name']) {
            $new .= " \n##### 成功返回示例的参数说明 \n\n|参数名|类型|说明|\n|:-----  |:-----|-----|\n";
            foreach ($respParams as $rp) {
                $remark = $rp['remark'] ?? '无';
                $new   .= "|{$rp['name']}| {$rp['type']} |  {$remark} | \n";
            }
        }

        // 失败返回示例
        if (!empty($content['response']['responseFailExample'])) {
            $example = $this->indentJson($content['response']['responseFailExample']);
            $new    .= " \n##### 失败返回示例  \n```\n{$example}\n\n``` \n";
        }

        // 失败返回参数说明
        $failParams = $content['response']['responseFailParamsDesc'] ?? [];
        if (!empty($failParams) && isset($failParams[0]['name']) && $failParams[0]['name']) {
            $new .= " \n##### 失败返回示例的参数说明 \n|参数名|类型|说明|\n|:-----  |:-----|-----|\n";
            foreach ($failParams as $fp) {
                $remark = $fp['remark'] ?? '无';
                $new   .= "|{$fp['name']}| {$fp['type']} |  {$remark} | \n";
            }
        }

        if (!empty($content['info']['remark'])) {
            $new .= " \n##### 备注 \n {$content['info']['remark']}\n";
        }

        return $new;
    }

    // WebSocket / SSE 转 Markdown 这里略，同样可以在需要时完整迁移
    private function runapiWebSocketToMd(array $content)
    {
        // 为保持迁移粒度可控，先复用 HTTP 版本的摘要信息
        return $this->runapiHttpApiToMd($content);
    }

    private function runapiSSEToMd(array $content)
    {
        return $this->runapiHttpApiToMd($content);
    }

    /**
     * JSON 字符串美化
     */
    private function indentJson($json)
    {
        $jsonNew = json_encode(json_decode((string) $json, true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($jsonNew && $jsonNew !== 'null') {
            return $jsonNew;
        }
        return (string) $json;
    }

    /**
     * （可选）Markdown → runapi JSON：保留接口，内部依旧走 OpenAI，但使用新 Options 模型
     * 以便后续平滑接入，不再依赖 ThinkPHP 的 D()。
     */
    public function mdToRunapi(string $markdownContent): ?string
    {
        $aiModelName = Options::get('ai_model_name', 'gpt-4o-mini');
        $openApiKey  = Options::get('open_api_key', '');
        if ($openApiKey === '') {
            return null;
        }

        $postData = json_encode([
            'model'    => $aiModelName,
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => '你是一个经验丰富的API接口文档转换专家，用户会输入一段markdown格式的API接口文档，请将这段文档转换为runapi格式的API接口。只返回转换后的json，不要包裹在代码块里，也不要额外解释。',
                ],
                [
                    'role'    => 'user',
                    'content' => $markdownContent,
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $openApiHost = Options::get('open_api_host', 'https://api.openai.com');
        if (strpos($openApiHost, 'http') !== 0) {
            $openApiHost = 'https://' . $openApiHost;
        }
        $openApiHost = rtrim($openApiHost, '/');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_URL, $openApiHost . '/v1/chat/completions');
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 480);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openApiKey,
            'Content-Length: ' . strlen($postData),
        ]);

        $result = curl_exec($ch);
        if ($result === false) {
            error_log('Convert mdToRunapi curl error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        return $result;
    }
}
