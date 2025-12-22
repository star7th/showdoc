<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Model\Item;
use App\Common\Helper\Convert;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ImportPostmanController extends BaseController
{
    /**
     * 导入 Postman 集合
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function import(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);

        if ($itemId > 0) {
            if (!$this->checkItemEdit($uid, $itemId)) {
                return $this->error($response, 10302, '没有权限');
            }
        }

        // 获取上传的文件
        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles['file'])) {
            return $this->error($response, 10101, '请上传文件');
        }

        $file = $uploadedFiles['file'];
        $filename = $file->getClientFilename();

        // 检查文件扩展名（只允许 .json 文件）
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext !== 'json') {
            return $this->error($response, 10101, '只支持上传 JSON 格式的 Postman 集合文件');
        }

        $tmpFile = sys_get_temp_dir() . '/' . \App\Common\Helper\FileHelper::getRandStr() . '.json';
        $file->moveTo($tmpFile);

        $json = file_get_contents($tmpFile);
        @unlink($tmpFile);

        $jsonArray = json_decode($json, true);
        unset($json);

        if (empty($jsonArray)) {
            return $this->error($response, 10101, '导入失败，请确保是符合格式要求的文件。如有疑问请联系网站管理人员');
        }

        $jsonArray['item_id'] = $itemId;

        // 判断 Postman 版本
        if (isset($jsonArray['id'])) {
            // Postman V1 格式
            return $this->fromPostmanV1($jsonArray, $itemId, $uid, $request, $response);
        } elseif (isset($jsonArray['info'])) {
            // Postman V2 格式
            return $this->fromPostmanV2($jsonArray, $itemId, $uid, $request, $response);
        }

        return $this->error($response, 10101, '导入失败，请确保是符合格式要求的文件。如有疑问请联系网站管理人员');
    }

    /**
     * 从 Postman 导入（V1 版本）
     */
    private function fromPostmanV1(array $jsonArray, int $itemId, int $uid, Request $request, Response $response): Response
    {
        $from = $this->getParam($request, 'from', '');

        $itemArray = [
            'item_id'         => $jsonArray['item_id'],
            'item_name'       => $jsonArray['name'] ?? 'from postman',
            'item_type'       => ($from == 'runapi') ? '3' : '1',
            'item_description' => $jsonArray['description'] ?? '',
            'password'        => time() . rand(),
            'members'         => [],
            'pages'           => [
                'pages'    => [],
                'catalogs' => [],
            ],
        ];

        $level = 2;

        // 处理没有目录的页面
        if (isset($jsonArray['requests'])) {
            foreach ($jsonArray['requests'] as $value) {
                if (empty($value['folder'])) {
                    $itemArray['pages']['pages'][] = $this->requestToDoc($value, $request);
                }
            }
        }

        // 处理目录
        if (isset($jsonArray['folders'])) {
            foreach ($jsonArray['folders'] as $value) {
                if (empty($value['folder'])) {
                    $catArray = [
                        'id'       => $value['id'],
                        'cat_name' => $value['name'],
                        'level'    => $level,
                        's_number' => 99,
                    ];
                    $catArray['pages'] = $this->getPageByFolders($value['id'], $jsonArray, $request);
                    $catArray['catalogs'] = $this->getSubByFolders($value['id'], $value['name'], $level + 1, $jsonArray, $request);

                    $itemArray['pages']['catalogs'][] = $catArray;
                }
            }
        }

        $itemId = Item::import(json_encode($itemArray), $uid, $itemId);

        return $this->success($response, ['item_id' => $itemId]);
    }

    /**
     * 根据 Postman 的 folders 获取子页面和子目录
     */
    private function getSubByFolders(string $id, string $name, int $level, array $jsonArray, Request $request): array
    {
        $return = [];
        if (!isset($jsonArray['folders'])) {
            return $return;
        }

        foreach ($jsonArray['folders'] as $value) {
            if (($value['folder'] ?? '') == $id) {
                $catArray = [
                    'id'       => $value['id'],
                    'cat_name' => $value['name'],
                    'level'    => $level,
                    's_number' => 99,
                ];
                $catArray['pages'] = $this->getPageByFolders($value['id'], $jsonArray, $request);
                $catArray['catalogs'] = $this->getSubByFolders($value['id'], $value['name'], $level + 1, $jsonArray, $request);
                $return[] = $catArray;
            }
        }

        return $return;
    }

    /**
     * 根据 Postman 的 folders 获取页面
     */
    private function getPageByFolders(string $id, array $jsonArray, Request $request): array
    {
        $return = [];
        if (!isset($jsonArray['requests'])) {
            return $return;
        }

        foreach ($jsonArray['requests'] as $value) {
            if (($value['folder'] ?? '') == $id) {
                $return[] = $this->requestToDoc($value, $request);
            }
        }

        return $return;
    }

    /**
     * 请求转文档
     */
    private function requestToDoc(array $request, Request $httpRequest): array
    {
        $from = $this->getParam($httpRequest, 'from', '');
        $res = $this->requestToApi($request);
        if ($from == 'runapi') {
            return $res;
        } else {
            $convert = new Convert();
            $res['page_content'] = $convert->runapiToMd($res['page_content']);
            return $res;
        }
    }

    /**
     * 转成 RunApi 所需要的 API 格式（V1）
     */
    private function requestToApi(array $request): array
    {
        $return = [];
        $return['page_title'] = $request['name'] ?? '';
        $return['id'] = $request['id'] ?? '';
        $return['s_number'] = 99;
        $return['page_comments'] = '';

        // 若 page_title 为很长的 URL，则做一些特殊处理
        $tmpTitleArray = explode('/', $return['page_title']);
        if (!empty($tmpTitleArray)) {
            $tmpTitleArray = array_slice($tmpTitleArray, -2); // 倒数2个
            if (!empty($tmpTitleArray[1])) {
                $return['page_title'] = $tmpTitleArray[0] . '/' . $tmpTitleArray[1];
            }
        }

        $contentArray = [
            'info'     => [
                'from'        => 'runapi',
                'type'       => 'api',
                'title'      => $request['name'] ?? '',
                'description' => $request['description'] ?? '',
                'method'     => strtolower($request['method'] ?? 'get'),
                'url'        => $request['url'] ?? '',
                'remark'     => '',
            ],
            'request'  => [
                'params'  => [
                    'mode'      => 'urlencoded',
                    'json'      => '',
                    'urlencoded' => [],
                    'formdata'  => [],
                ],
                'headers' => [],
                'cookies' => [],
                'auth'    => [],
            ],
            'response' => [],
            'extend'   => [],
        ];

        // 处理请求头
        if (isset($request['headerData'])) {
            foreach ($request['headerData'] as $value) {
                $contentArray['request']['headers'][] = [
                    'name'    => $value['key'] ?? '',
                    'type'    => 'string',
                    'value'   => $value['value'] ?? '',
                    'require' => '1',
                    'remark'  => $value['description'] ?? '',
                ];
            }
        }

        // 处理请求参数
        if (isset($request['data'])) {
            foreach ($request['data'] as $value) {
                $contentArray['request']['params']['urlencoded'][] = [
                    'name'    => $value['key'] ?? '',
                    'type'    => 'string',
                    'value'   => $value['value'] ?? '',
                    'require' => '1',
                    'remark'  => $value['description'] ?? '',
                ];
            }
        }

        $return['page_content'] = json_encode($contentArray);
        return $return;
    }

    /**
     * 从 Postman 导入（V2 版本）
     */
    private function fromPostmanV2(array $jsonArray, int $itemId, int $uid, Request $request, Response $response): Response
    {
        $from = $this->getParam($request, 'from', '');

        $itemArray = [
            'item_id'         => $jsonArray['item_id'],
            'item_name'       => $jsonArray['info']['name'] ?? 'from postman',
            'item_type'       => ($from == 'runapi') ? '3' : '1',
            'item_description' => $jsonArray['info']['description'] ?? '',
            'password'        => time() . rand(),
            'members'         => [],
            'pages'           => [
                'pages'    => [],
                'catalogs' => [],
            ],
        ];

        $level = 2;
        $itemArray['pages']['pages'] = $this->getPageByItem($jsonArray['item'] ?? [], $request);
        $itemArray['pages']['catalogs'] = $this->getItemByItem($jsonArray['item'] ?? [], $level, $request);

        $itemId = Item::import(json_encode($itemArray), $uid, $itemId);

        return $this->success($response, ['item_id' => $itemId]);
    }

    /**
     * 获取某个目录下的所有页面
     */
    private function getPageByItem(array $itemArray, Request $request): array
    {
        $return = [];
        foreach ($itemArray as $value) {
            // 含有 request，则这是一个子页面
            if (isset($value['request'])) {
                $return[] = $this->requestToDocV2($value['name'] ?? '', $value, $request);
            }
        }
        return $return;
    }

    /**
     * 获取某个目录下的所有子目录
     */
    private function getItemByItem(array $itemArray, int $level, Request $request): array
    {
        $return = [];
        foreach ($itemArray as $value) {
            // 含有 item，则这是一个子目录
            if (isset($value['item'])) {
                $oneAry = [
                    'cat_name' => $value['name'] ?? '',
                    'level'    => $level,
                    's_number' => 99,
                    'pages'    => $this->getPageByItem($value['item'], $request), // 递归
                    'catalogs' => $this->getItemByItem($value['item'], $level + 1, $request), // 递归
                ];
                $return[] = $oneAry;
            }
        }
        return $return;
    }

    /**
     * 请求转文档（V2）
     */
    private function requestToDocV2(string $name, array $page, Request $request): array
    {
        $from = $this->getParam($request, 'from', '');
        $res = $this->requestToApiV2($name, $page);
        if ($from == 'runapi') {
            return $res;
        } else {
            $convert = new Convert();
            $res['page_content'] = $convert->runapiToMd($res['page_content']);
            return $res;
        }
    }

    /**
     * 转成 RunApi 所需要的 API 格式（V2）
     */
    private function requestToApiV2(string $name, array $page): array
    {
        $request = $page['request'] ?? [];
        $return = [];
        $return['page_title'] = $name;
        $return['s_number'] = 99;
        $return['page_comments'] = '';

        // 若 page_title 为很长的 URL，则做一些特殊处理
        $tmpTitleArray = explode('/', $return['page_title']);
        if (!empty($tmpTitleArray)) {
            $tmpTitleArray = array_slice($tmpTitleArray, -2); // 倒数2个
            if (!empty($tmpTitleArray[1])) {
                $return['page_title'] = $tmpTitleArray[0] . '/' . $tmpTitleArray[1];
            }
        }

        $url = is_array($request['url'] ?? null) ? ($request['url']['raw'] ?? '') : ($request['url'] ?? '');
        $rawModeData = ($request['body']['mode'] ?? '') == 'raw' ? ($request['body']['raw'] ?? '') : ($request['rawModeData'] ?? '');

        $contentArray = [
            'info'     => [
                'from'        => 'runapi',
                'type'       => 'api',
                'title'      => $name,
                'description' => $request['description'] ?? '',
                'method'     => strtolower($request['method'] ?? 'get'),
                'url'        => $url,
                'remark'     => '',
            ],
            'request'  => [
                'params'  => [
                    'mode'      => $request['body']['mode'] ?? 'urlencoded',
                    'json'      => '',
                    'urlencoded' => [],
                    'formdata'  => [],
                ],
                'headers' => [],
                'cookies' => [],
                'auth'    => [],
            ],
            'response' => [],
            'extend'   => [],
        ];

        // 处理请求头
        if (isset($request['header'])) {
            foreach ($request['header'] as $value) {
                $contentArray['request']['headers'][] = [
                    'name'    => $value['key'] ?? '',
                    'type'    => 'string',
                    'value'   => $value['value'] ?? '',
                    'require' => '1',
                    'remark'  => $value['description'] ?? '',
                ];
            }
        }

        // 处理请求体
        $bodyMode = $request['body']['mode'] ?? 'urlencoded';
        if (in_array($bodyMode, ['formdata', 'urlencoded']) && isset($request['body'][$bodyMode])) {
            foreach ($request['body'][$bodyMode] as $value) {
                $contentArray['request']['params'][$bodyMode][] = [
                    'name'    => $value['key'] ?? '',
                    'type'    => 'string',
                    'value'   => $value['value'] ?? '',
                    'require' => '1',
                    'remark'  => $value['description'] ?? '',
                ];
            }
        } elseif ($rawModeData && json_decode($rawModeData)) {
            $contentArray['request']['params']['mode'] = 'json';
            $contentArray['request']['params']['json'] = $rawModeData;
        }

        // 兼容 GET 请求参数的场景
        if (isset($request['url']['query'])) {
            foreach ($request['url']['query'] as $value) {
                $contentArray['request']['params'][$bodyMode][] = [
                    'name'    => $value['key'] ?? '',
                    'type'    => 'string',
                    'value'   => $value['value'] ?? '',
                    'require' => '1',
                    'remark'  => $value['description'] ?? '',
                ];
            }
        }

        // 处理响应示例
        if (isset($page['response'][0]['body'])) {
            $contentArray['response']['responseExample'] = $page['response'][0]['body'];
        }

        $return['page_content'] = json_encode($contentArray);

        return $return;
    }
}
