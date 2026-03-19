<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Common\Helper\Convert;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\ItemToken;
use App\Model\Item;
use App\Model\Page;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 通过注释生成 API 文档（新架构）。
 */
class FromCommentsController extends BaseController
{
    /**
     * 生成 API 文档（兼容旧接口 Api/FromComments/generate）。
     *
     * 功能：
     * - 从代码注释中解析 API 信息并生成文档
     * - 支持通过 api_key 和 api_token 鉴权
     * - 自动转换为 RunApi 格式或 Markdown 格式
     */
    public function generate(Request $request, Response $response): Response
    {
        $content = $this->getParam($request, 'content', '');
        $apiKey = $this->getParam($request, 'api_key', '');
        $apiToken = $this->getParam($request, 'api_token', '');

        // 验证 API Key 和 Token（旧版逻辑：只检查验证失败，不检查限流）
        $itemId = ItemToken::check($apiKey, $apiToken);
        if (!$itemId || $itemId <= 0) {
            // 没验证通过（旧版逻辑：只返回 false，不区分限流和验证失败）
            $response->getBody()->write("\napi_key或者api_token不匹配\n\n");
            return $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
        }

        // 处理内容（HTML 反转义，替换特殊字符）
        $content = htmlspecialchars_decode($content);
        $content = str_replace('_this_and_change_', '&', $content);

        // 解析注释中的 API 信息
        $p = "|/\*\*([\s\S]*)\*/|U";
        preg_match_all($p, $content, $matches);

        $ret = false;
        if ($matches && !empty($matches[0])) {
            foreach ($matches[0] as $value) {
                if (strpos($value, '@title') !== false && strpos($value, 'showdoc') !== false) {
                    $ret = $this->generateOne($itemId, $value);
                }
            }
        }

        if ($ret) {
            $response->getBody()->write("\n 成功 \n\n ");
        } else {
            $response->getBody()->write('失败');
        }

        return $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * 生成单个 API 文档
     *
     * @param int $itemId 项目 ID
     * @param string $content 注释内容
     * @return array|false 生成的页面信息或 false
     */
    private function generateOne(int $itemId, string $content)
    {
        $convert = new Convert();
        $item = Item::findById($itemId);
        if (!$item) {
            return false;
        }

        // 解析注释内容
        $array = $this->parseContent($content);

        // 转换为 RunApi 格式
        $pageContent = $this->toRunapiFormat($array);

        $pageTitle = $array['title'] ?? '';
        $catName = $array['cat_name'] ?? '';
        $sNumber = !empty($array['s_number']) ? (int) $array['s_number'] : 99;

        // 如果是 RunApi 项目，尝试保留已有页面中用户手动填写的值
        if ($item->item_type == 3) {
            $pageContent = $this->preserveExistingValues($itemId, $pageTitle, $catName, $pageContent);
        }

        // 如果不是 RunApi 项目，转换为 Markdown
        if ($item->item_type != 3) {
            $pageContent = $convert->runapiToMd($pageContent);
        }

        $pageContent = htmlspecialchars($pageContent);

        // 更新或创建页面
        $pageId = Page::updateByTitle($itemId, $pageTitle, $pageContent, $catName, $sNumber);

        if ($pageId) {
            // 查询页面信息（与旧版保持一致：D("Page")->where(" page_id = '%d' ", array($page_id))->find()）
            $page = Page::findById($pageId);
            return $page ? $page : false;
        }

        return false;
    }

    /**
     * 保留已有页面中用户手动填写的值
     *
     * 对于 RunApi 类型的项目，当通过注释更新文档时，保留用户已在 RunApi 中填写的：
     * - 请求参数的示例值（value）
     * - 返回参数的说明（remark）
     *
     * @param int $itemId 项目 ID
     * @param string $pageTitle 页面标题
     * @param string $catName 目录名称
     * @param string $newContent 新生成的 RunApi JSON 内容
     * @return string 合并后的 RunApi JSON 内容
     */
    private function preserveExistingValues(int $itemId, string $pageTitle, string $catName, string $newContent): string
    {
        // 获取目录 ID
        $catId = 0;
        if (!empty($catName)) {
            $catId = \App\Model\Catalog::saveCatPath($catName, $itemId);
            if ($catId === false) {
                return $newContent;
            }
        }

        // 查找已存在的页面（开源版：使用单表 page，不支持分表）
        $existing = DB::table('page')
            ->where('item_id', $itemId)
            ->where('is_del', 0)
            ->where('cat_id', $catId)
            ->where('page_title', $pageTitle)
            ->first();

        if (!$existing) {
            return $newContent;
        }

        // 解压已有页面内容（兼容旧压缩数据）
        $existingContent = $existing->page_content;
        $decompressed = \App\Common\Helper\ContentCodec::decompress($existingContent);
        if ($decompressed !== '' && $decompressed !== $existingContent) {
            $existingContent = $decompressed;
        }

        // HTML 解码
        $existingContent = htmlspecialchars_decode($existingContent);

        // 解析 JSON
        $existingData = json_decode($existingContent, true);
        $newData = json_decode($newContent, true);

        if (!$existingData || !$newData) {
            return $newContent;
        }

        // 合并请求参数中的 value
        $this->mergeRequestValues($existingData, $newData);

        // 合并返回参数说明中的 value 和 remark
        $this->mergeResponseValues($existingData, $newData);

        $result = json_encode($newData, JSON_UNESCAPED_UNICODE);
        if ($result === false) {
            return $newContent;
        }
        return $result;
    }

    /**
     * 合并请求参数中的 value 值
     *
     * @param array $existingData 已有页面数据
     * @param array $newData 新生成的页面数据
     */
    private function mergeRequestValues(array $existingData, array &$newData): void
    {
        if (!isset($existingData['request']) || !isset($newData['request'])) {
            return;
        }

        // 合并 headers
        $this->mergeParamListValues(
            $existingData['request']['headers'] ?? [],
            $newData['request']['headers'] ?? []
        );

        // 合并 query
        $this->mergeParamListValues(
            $existingData['request']['query'] ?? [],
            $newData['request']['query'] ?? []
        );

        // 合并 formdata
        $this->mergeParamListValues(
            $existingData['request']['params']['formdata'] ?? [],
            $newData['request']['params']['formdata'] ?? []
        );

        // 合并 jsonDesc
        $this->mergeParamListValues(
            $existingData['request']['params']['jsonDesc'] ?? [],
            $newData['request']['params']['jsonDesc'] ?? []
        );

        // 合并 urlencoded
        $this->mergeParamListValues(
            $existingData['request']['params']['urlencoded'] ?? [],
            $newData['request']['params']['urlencoded'] ?? []
        );
    }

    /**
     * 合并参数列表中的 value 值
     * 根据 name 字段匹配，保留已有页面中的 value
     *
     * @param array $existingList 已有参数列表
     * @param array $newList 新参数列表（引用传递，会被修改）
     */
    private function mergeParamListValues(array $existingList, array &$newList): void
    {
        if (empty($existingList) || empty($newList)) {
            return;
        }

        // 建立已有参数的 name -> value 映射
        $existingValues = [];
        foreach ($existingList as $param) {
            if (isset($param['name']) && $param['name'] !== '') {
                $existingValues[$param['name']] = $param['value'] ?? '';
            }
        }

        // 遍历新参数列表，如果 name 匹配且已有 value，则保留
        foreach ($newList as $key => $param) {
            $name = $param['name'] ?? '';
            if ($name !== '' && isset($existingValues[$name]) && $existingValues[$name] !== '') {
                $newList[$key]['value'] = $existingValues[$name];
            }
        }
    }

    /**
     * 合并返回参数说明中的 value 和 remark 值
     *
     * @param array $existingData 已有页面数据
     * @param array $newData 新生成的页面数据（引用传递，会被修改）
     */
    private function mergeResponseValues(array $existingData, array &$newData): void
    {
        if (
            !isset($existingData['response']['responseParamsDesc'])
            || !isset($newData['response']['responseParamsDesc'])
        ) {
            return;
        }

        $existingParams = $existingData['response']['responseParamsDesc'];
        $newParams = &$newData['response']['responseParamsDesc'];

        if (empty($existingParams) || empty($newParams)) {
            return;
        }

        // 建立已有参数的 name -> [value, remark] 映射
        $existingValues = [];
        foreach ($existingParams as $param) {
            if (isset($param['name']) && $param['name'] !== '') {
                $existingValues[$param['name']] = [
                    'value' => $param['value'] ?? '',
                    'remark' => $param['remark'] ?? '',
                ];
            }
        }

        // 遍历新参数列表，如果 name 匹配且已有值，则保留
        foreach ($newParams as $key => $param) {
            $name = $param['name'] ?? '';
            if ($name !== '' && isset($existingValues[$name])) {
                // 保留 value
                if ($existingValues[$name]['value'] !== '') {
                    $newParams[$key]['value'] = $existingValues[$name]['value'];
                }
                // 保留 remark（如果已有说明且新说明为空或相同）
                if ($existingValues[$name]['remark'] !== '') {
                    $newParams[$key]['remark'] = $existingValues[$name]['remark'];
                }
            }
        }
    }

    /**
     * 解析注释内容，返回数组
     *
     * @param string $content 注释内容
     * @return array 解析后的数组
     */
    private function parseContent(string $content): array
    {
        $array = [];

        // 解析标题
        $array['title'] = $this->parseOneLine('title', $content);
        $array['method'] = $this->parseOneLine('method', $content);
        $array['description'] = $this->parseOneLine('description', $content);
        $array['url'] = $this->parseOneLine('url', $content);
        $array['cat_name'] = $this->parseOneLine('catalog', $content);

        // 解析返回内容
        $return = $this->parseOneLine('return', $content);
        $return = htmlspecialchars_decode($return);
        $array['return'] = $return;

        // 解析请求参数
        $array['param'] = $this->parseMultiLine('param', $content);

        // 解析请求 header
        $array['header'] = $this->parseMultiLine('header', $content);

        // 解析返回参数
        $array['return_param'] = $this->parseMultiLine('return_param', $content);

        $array['remark'] = $this->parseOneLine('remark', $content);
        $array['s_number'] = $this->parseOneLine('number', $content);

        // 如果请求参数是 json，则生成请求示例
        $jsonParam = $this->parseOneLine('json_param', $content);
        $jsonParam = htmlspecialchars_decode($jsonParam);
        $array['json_param'] = $jsonParam;

        return $array;
    }

    /**
     * 解析单行标签，如 method、url
     *
     * @param string $tag 标签名
     * @param string $content 内容
     * @return string|false 解析结果或 false
     */
    private function parseOneLine(string $tag, string $content)
    {
        $p = '/@' . preg_quote($tag, '/') . '.+/';
        preg_match($p, $content, $matches);

        if ($matches && !empty($matches[0])) {
            return trim(str_replace('@' . $tag, '', $matches[0]));
        }

        return false;
    }

    /**
     * 解析多行标签，如 param
     *
     * @param string $tag 标签名
     * @param string $content 内容
     * @return array 解析结果数组
     */
    private function parseMultiLine(string $tag, string $content): array
    {
        $return = [];
        $array1 = explode('@', $content);

        foreach ($array1 as $value) {
            $array2 = preg_split("/[\s]+/", trim($value));
            if (!empty($array2[0]) && $array2[0] === $tag) {
                unset($array2[0]);
                $return[] = array_values($array2);
            }
        }

        return $return;
    }

    /**
     * 转成 RunApi 的接口格式
     *
     * @param array $array 解析后的数组
     * @return string JSON 格式的 RunApi 内容
     */
    private function toRunapiFormat(array $array): string
    {
        $contentArray = [
            'info' => [
                'from'        => 'runapi',
                'type'        => 'api',
                'title'       => $array['title'] ?? '',
                'description' => $array['description'] ?? '',
                'method'      => strtolower($array['method'] ?? 'get'),
                'url'         => $array['url'] ?? '',
                'remark'      => $array['remark'] ?? '',
            ],
            'request' => [
                'params' => [
                    'mode'       => 'formdata',
                    'json'       => '',
                    'urlencoded' => [],
                    'formdata'   => [],
                ],
                'headers' => [],
                'query'   => [],
                'cookies' => [],
                'auth'    => [],
            ],
            'response' => [
                'responseExample'    => $array['return'] ?? '',
                'responseParamsDesc' => [],
            ],
            'extend' => [],
        ];

        // JSON 美化
        $responseExample = $this->indentJson($contentArray['response']['responseExample']);
        $contentArray['response']['responseExample'] = $responseExample ?: $contentArray['response']['responseExample'];

        // 处理 header
        if (!empty($array['header'])) {
            foreach ($array['header'] as $value) {
                // |参数名|是否必选|类型|说明
                $contentArray['request']['headers'][] = [
                    'name'   => $value[0] ?? '',
                    'require' => ($value[1] ?? '') === '必选' ? '1' : '0',
                    'type'   => $value[2] ?? '',
                    'value'  => '',
                    'remark' => $value[3] ?? '',
                ];
            }
        }

        // 处理 JSON 参数
        if (!empty($array['json_param'])) {
            $contentArray['request']['params']['mode'] = 'json';
            $contentArray['request']['params']['json'] = $array['json_param'];

            // 请求方式是 json 的话，原有的参数说明数组就写入 json 描述中
            if (!empty($array['param'])) {
                foreach ($array['param'] as $value) {
                    // |参数名|是否必选|类型|说明
                    $contentArray['request']['params']['jsonDesc'][] = [
                        'name'   => $value[0] ?? '',
                        'require' => ($value[1] ?? '') === '必选' ? '1' : '0',
                        'type'   => $value[2] ?? '',
                        'value'  => '',
                        'remark' => $value[3] ?? '',
                    ];
                }
            }
        }

        // 处理普通参数
        if (!empty($array['param'])) {
            $method = strtolower($array['method'] ?? 'get');
            if ($method === 'get') {
                $queryStr = '';
                foreach ($array['param'] as $value) {
                    // |参数名|是否必选|类型|说明
                    $contentArray['request']['query'][] = [
                        'name'   => $value[0] ?? '',
                        'require' => ($value[1] ?? '') === '必选' ? '1' : '0',
                        'type'   => $value[2] ?? '',
                        'value'  => '',
                        'remark' => $value[3] ?? '',
                    ];
                    $queryStr .= ($value[0] ?? '') . '=&';
                }

                // 为了兼容，还要把 query 参数追加到 url 里去
                $url = $contentArray['info']['url'];
                if (strpos($url, '?') !== false) {
                    $contentArray['info']['url'] .= '&' . $queryStr;
                } else {
                    $contentArray['info']['url'] .= '?' . $queryStr;
                }
            } else {
                foreach ($array['param'] as $value) {
                    // |参数名|是否必选|类型|说明
                    $contentArray['request']['params']['formdata'][] = [
                        'name'   => $value[0] ?? '',
                        'require' => ($value[1] ?? '') === '必选' ? '1' : '0',
                        'type'   => $value[2] ?? '',
                        'value'  => '',
                        'remark' => $value[3] ?? '',
                    ];
                }
            }
        }

        // 处理返回参数
        if (!empty($array['return_param'])) {
            foreach ($array['return_param'] as $value) {
                // |参数名|类型|说明
                $contentArray['response']['responseParamsDesc'][] = [
                    'name'   => $value[0] ?? '',
                    'type'   => $value[1] ?? '',
                    'value'  => '',
                    'remark' => $value[2] ?? '',
                ];
            }
        }

        return json_encode($contentArray, JSON_UNESCAPED_UNICODE);
    }

    /**
     * JSON 美化
     *
     * @param string $json JSON 字符串
     * @return string|false 美化后的 JSON 或 false
     */
    private function indentJson(string $json)
    {
        if (empty($json)) {
            return false;
        }

        $decoded = json_decode($json, true);
        if ($decoded === null) {
            return false;
        }

        $jsonNew = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($jsonNew && $jsonNew !== 'null') {
            return $jsonNew;
        }

        return $json;
    }
}
