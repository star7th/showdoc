<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Model\ItemToken;
use App\Model\Page;
use App\Model\Catalog;
use App\Model\Item;
use App\Model\VersionUpdate;
use App\Model\Attachment;
use App\Model\UploadFile;
use App\Model\FilePage;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class OpenController extends BaseController
{
    /**
     * 根据内容更新页面（创建或更新）
     * 通过 api_key 和 api_token 鉴权
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function updatePage(Request $request, Response $response): Response
    {
        $apiKey = $this->getParam($request, 'api_key', '');
        $apiToken = $this->getParam($request, 'api_token', '');
        $catName = $this->getParam($request, 'cat_name', '');
        $catNameSub = $this->getParam($request, 'cat_name_sub', '');
        $pageTitle = $this->getParam($request, 'page_title', '');
        $pageContent = $this->getParam($request, 'page_content', '');
        $sNumber = $this->getParam($request, 's_number', 99);

        // 兼容之前的 cat_name_sub 参数
        if ($catNameSub) {
            $catName = $catName . '/' . $catNameSub;
        }

        // 鉴权
        $itemId = ItemToken::check($apiKey, $apiToken);
        if (!$itemId) {
            return $this->error($response, 10306, 'api_key或者api_token不匹配');
        }


        // 检查页面数量限制
        $pageCount = Page::getPageCount($itemId);
        if ($pageCount > 5000) {
            return $this->error($response, 10100, '你创建太多页面啦！如有需求请联系网站管理员');
        }

        // 更新或创建页面
        $pageId = Page::updateByTitle($itemId, $pageTitle, $pageContent, $catName, $sNumber, 0, 'API');
        if ($pageId) {
            $ret = Page::findPage($pageId, $itemId);
            if ($ret) {
                return $this->success($response, $ret);
            }
        }

        return $this->error($response, 10101, '操作失败');
    }

    /**
     * 根据内容更新项目（兼容旧接口，转向 updatePage）
     *
     * @deprecated 建议使用 updatePage 接口
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function updateItem(Request $request, Response $response): Response
    {
        // 兼容旧接口，直接调用新方法
        return $this->updatePage($request, $response);
    }

    /**
     * 根据 shell 上报的数据库结构信息生成数据字典
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function updateDbItem(Request $request, Response $response): Response
    {
        $apiKey = $this->getParam($request, 'api_key', '');
        $apiToken = $this->getParam($request, 'api_token', '');
        $tableInfo = $this->getParam($request, 'table_info', '');
        $tableDetail = $this->getParam($request, 'table_detail', '');
        $sNumber = $this->getParam($request, 's_number', 99);
        $catName = $this->getParam($request, 'cat_name', '');

        // 鉴权
        $itemId = ItemToken::check($apiKey, $apiToken);
        if (!$itemId) {
            $response->getBody()->write("\napi_key或者api_token不匹配\n");
            return $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
        }

        // 检查页面数量限制
        $pageCount = Page::getPageCount($itemId);
        if ($pageCount > 5000) {
            return $this->error($response, 10100, '你创建太多页面啦！如有需求请联系网站管理员');
        }

        $catName = str_replace(PHP_EOL, '', $catName);
        $tableInfo = str_replace("_this_and_change_", "&", $tableInfo);
        $tableDetail = str_replace("_this_and_change_", "&", $tableDetail);
        $tables = $this->analyzeDbStructureToArray($tableInfo, $tableDetail);

        $result = false;
        if (!empty($tables)) {
            foreach ($tables as $value) {
                $pageTitle = $value['table_name'];
                $pageContent = $value['markdown'];
                $pageId = Page::updateByTitle($itemId, $pageTitle, $pageContent, $catName, $sNumber, 0, 'API');
                if ($pageId) {
                    $result = true;
                }
            }
        }

        if ($result) {
            $response->getBody()->write("成功\n");
        } else {
            $response->getBody()->write("失败\n");
        }

        return $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * 解析数据库结构为数组
     */
    private function analyzeDbStructureToArray(string $tableInfo, string $tableDetail): array
    {
        $tables = [];

        // 解析 table_info
        $array = explode("\n", $tableInfo);
        if (!empty($array)) {
            foreach ($array as $key => $value) {
                if ($key == 0) {
                    continue; // 跳过表头
                }
                $array2 = explode("\t", $value);
                if (empty($array2[0])) {
                    continue;
                }
                $tableName = str_replace(PHP_EOL, '', $array2[0]);
                $tables[$array2[0]] = [
                    'table_name'    => $tableName,
                    'table_comment' => $array2[1] ?? '',
                ];
            }
        }

        // 解析 table_detail
        $array = explode("\n", $tableDetail);
        if (!empty($array)) {
            foreach ($array as $key => $value) {
                if ($key == 0) {
                    continue; // 跳过表头
                }
                $array2 = explode("\t", $value);
                if (empty($array2[0]) || empty($array2[1])) {
                    continue;
                }
                if (!isset($tables[$array2[0]])) {
                    $tables[$array2[0]] = [
                        'table_name'    => $array2[0],
                        'table_comment' => '',
                    ];
                }
                $tables[$array2[0]]['columns'][$array2[1]] = [
                    'column_name'    => $array2[1],
                    'default'         => $array2[2] ?? '',
                    'is_nullable'     => $array2[3] ?? '',
                    'column_type'     => $array2[4] ?? '',
                    'column_comment'  => $array2[5] ?? '无',
                ];
            }
        }

        // 生成 markdown 内容
        if (!empty($tables)) {
            foreach ($tables as $key => $value) {
                $markdown = '';
                $markdown .= "- {$value['table_comment']} \n \n";
                $markdown .= "|字段|类型|允许空|默认|注释| \n ";
                $markdown .= "|:----    |:-------    |:--- |----|------      | \n ";
                if (!empty($value['columns'])) {
                    foreach ($value['columns'] as $value2) {
                        $markdown .= "|{$value2['column_name']} |{$value2['column_type']} |{$value2['is_nullable']} | {$value2['default']} | {$value2['column_comment']}  | \n ";
                    }
                }
                $tables[$key]['markdown'] = $markdown;
            }
        }

        return $tables;
    }

    /**
     * 通过注释生成 API 文档
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function fromComments(Request $request, Response $response): Response
    {
        // 调用 FromCommentsController 的 generate 方法
        $fromCommentsController = new \App\Api\Controller\FromCommentsController();
        return $fromCommentsController->generate($request, $response);
    }

    /**
     * 获取页面详情
     * 通过 api_key 和 api_token 鉴权
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getPage(Request $request, Response $response): Response
    {
        $apiKey = $this->getParam($request, 'api_key', '');
        $apiToken = $this->getParam($request, 'api_token', '');
        $pageId = $this->getParam($request, 'page_id', 0);
        $pageTitle = $this->getParam($request, 'page_title', '');

        // 鉴权
        $itemId = ItemToken::check($apiKey, $apiToken);
        if (!$itemId) {
            return $this->error($response, 10306, 'api_key或者api_token不匹配');
        }

        // 如果提供了 page_title，则通过标题查找页面
        if ($pageTitle && !$pageId) {
            $page = \Illuminate\Database\Capsule\Manager::table('page')
                ->where('item_id', $itemId)
                ->where('page_title', $pageTitle)
                ->where('is_del', 0)
                ->first();
            if ($page) {
                $pageId = (int) $page->page_id;
            }
        }

        if (!$pageId) {
            return $this->error($response, 10101, 'page_id或page_title参数必填');
        }

        // 获取页面详情
        $page = Page::findPage($pageId, $itemId);
        if (!$page || (int) ($page['is_del'] ?? 0) === 1 || (int) ($page['item_id'] ?? 0) !== $itemId) {
            return $this->error($response, 10101, '页面不存在或已删除');
        }

        // 格式化 addtime 为日期字符串（与旧版保持一致）
        if (isset($page['addtime']) && is_numeric($page['addtime'])) {
            $page['addtime'] = date('Y-m-d H:i:s', (int) $page['addtime']);
        }

        return $this->success($response, $page);
    }

    /**
     * 删除页面
     * 通过 api_key 和 api_token 鉴权
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function deletePage(Request $request, Response $response): Response
    {
        $apiKey = $this->getParam($request, 'api_key', '');
        $apiToken = $this->getParam($request, 'api_token', '');
        $pageId = $this->getParam($request, 'page_id', 0);

        // 鉴权
        $itemId = ItemToken::check($apiKey, $apiToken);
        if (!$itemId) {
            return $this->error($response, 10306, 'api_key或者api_token不匹配');
        }

        if (!$pageId) {
            return $this->error($response, 10101, 'page_id参数必填');
        }

        // 检查页面是否属于该项目
        $page = Page::findById($pageId);
        if (!$page || (int) $page['item_id'] !== $itemId) {
            return $this->error($response, 10101, '页面不存在或不属于该项目');
        }

        // 获取项目创建者信息
        $item = Item::findById($itemId);
        $uid = $item ? (int) ($item->uid ?? 0) : 0;
        $username = 'API';
        if ($uid > 0) {
            $user = \App\Model\User::findById($uid);
            $username = $user ? ($user->username ?? 'API') : 'API';
        }

        // 软删除页面
        $ret = Page::softDeletePage($pageId, $itemId, $uid, $username);
        if ($ret) {
            return $this->success($response, ['page_id' => $pageId]);
        } else {
            return $this->error($response, 10101, '删除页面失败');
        }
    }

    /**
     * 获取项目的目录树结构（包含页面标题）
     * 通过 api_key 和 api_token 鉴权
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getCatalogTree(Request $request, Response $response): Response
    {
        $apiKey = $this->getParam($request, 'api_key', '');
        $apiToken = $this->getParam($request, 'api_token', '');

        // 鉴权
        $itemId = ItemToken::check($apiKey, $apiToken);
        if (!$itemId) {
            return $this->error($response, 10306, 'api_key或者api_token不匹配');
        }

        // 复用 ItemModel 的 getContent 方法获取完整的目录树结构
        // page_field: 只获取必要的字段，不获取 page_content 节省资源
        $menu = Item::getContent($itemId, false);

        return $this->success($response, $menu);
    }

    /**
     * 创建目录
     * 通过 api_key 和 api_token 鉴权
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function createCatalog(Request $request, Response $response): Response
    {
        $apiKey = $this->getParam($request, 'api_key', '');
        $apiToken = $this->getParam($request, 'api_token', '');
        $catName = $this->getParam($request, 'cat_name', '');
        $parentCatId = $this->getParam($request, 'parent_cat_id', 0);
        $sNumber = $this->getParam($request, 's_number', 99);

        // 鉴权
        $itemId = ItemToken::check($apiKey, $apiToken);
        if (!$itemId) {
            return $this->error($response, 10306, 'api_key或者api_token不匹配');
        }

        if (empty($catName)) {
            return $this->error($response, 10101, 'cat_name参数必填');
        }


        // 创建目录
        $catalog = Catalog::save(0, $itemId, htmlspecialchars($catName), $parentCatId, $sNumber);
        if ($catalog) {
            Item::deleteCache($itemId);
            return $this->success($response, $catalog);
        } else {
            return $this->error($response, 10101, '创建目录失败');
        }
    }

    /**
     * 修改目录
     * 通过 api_key 和 api_token 鉴权
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function updateCatalog(Request $request, Response $response): Response
    {
        $apiKey = $this->getParam($request, 'api_key', '');
        $apiToken = $this->getParam($request, 'api_token', '');
        $catId = $this->getParam($request, 'cat_id', 0);
        $catName = $this->getParam($request, 'cat_name', '');
        $sNumber = $this->getParam($request, 's_number', 0);

        // 鉴权
        $itemId = ItemToken::check($apiKey, $apiToken);
        if (!$itemId) {
            return $this->error($response, 10306, 'api_key或者api_token不匹配');
        }

        if (!$catId) {
            return $this->error($response, 10101, 'cat_id参数必填');
        }

        // 检查目录是否属于该项目
        $catalog = Catalog::findByIdAndItemId($catId, $itemId);
        if (!$catalog) {
            return $this->error($response, 10101, '目录不存在或不属于该项目');
        }

        // 准备更新数据
        $updateData = [];
        if ($catName) {
            $updateData['cat_name'] = htmlspecialchars($catName);
        }
        if ($sNumber !== null && $sNumber !== '') {
            $updateData['s_number'] = $sNumber;
        }

        if (empty($updateData)) {
            return $this->error($response, 10101, '请至少提供cat_name或s_number参数');
        }

        // 更新目录
        $affected = \Illuminate\Database\Capsule\Manager::table('catalog')
            ->where('cat_id', $catId)
            ->where('item_id', $itemId)
            ->update($updateData);
        
        if ($affected !== false) {
            // 重新查询目录信息
            $catalog = Catalog::findByIdAndItemId($catId, $itemId);
            if ($catalog) {
                return $this->success($response, (array) $catalog);
            }
        }
        
        return $this->error($response, 10101, '更新目录失败');
    }

    /**
     * 删除目录
     * 通过 api_key 和 api_token 鉴权
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function deleteCatalog(Request $request, Response $response): Response
    {
        $apiKey = $this->getParam($request, 'api_key', '');
        $apiToken = $this->getParam($request, 'api_token', '');
        $catId = $this->getParam($request, 'cat_id', 0);

        // 鉴权
        $itemId = ItemToken::check($apiKey, $apiToken);
        if (!$itemId) {
            return $this->error($response, 10306, 'api_key或者api_token不匹配');
        }

        if (!$catId) {
            return $this->error($response, 10101, 'cat_id参数必填');
        }

        // 检查目录是否属于该项目
        $catalog = Catalog::findByIdAndItemId($catId, $itemId);
        if (!$catalog) {
            return $this->error($response, 10101, '目录不存在或不属于该项目');
        }

        // 删除目录
        $ret = Catalog::deleteCat($catId);
        if ($ret) {
            return $this->success($response, ['cat_id' => $catId]);
        } else {
            return $this->error($response, 10101, '删除目录失败');
        }
    }

    /**
     * 上传附件
     * 通过 api_key 和 api_token 鉴权
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function uploadAttachment(Request $request, Response $response): Response
    {
        $apiKey = $this->getParam($request, 'api_key', '');
        $apiToken = $this->getParam($request, 'api_token', '');
        $pageId = $this->getParam($request, 'page_id', 0);

        // 鉴权
        $itemId = ItemToken::check($apiKey, $apiToken);
        if (!$itemId) {
            return $this->error($response, 10306, 'api_key或者api_token不匹配');
        }

        // 获取项目所有者
        $item = Item::findById($itemId);
        if (!$item) {
            return $this->error($response, 10101, '项目不存在');
        }
        $uid = (int) ($item->uid ?? 0);

        // 获取上传的文件
        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles['file'])) {
            return $this->error($response, 10101, '请上传文件');
        }

        $file = $uploadedFiles['file'];

        // 检查文件扩展名
        $filename = $file->getClientFilename();
        if (!Attachment::isAllowedFilename($filename)) {
            return $this->error($response, 10101, '不支持上传该文件类型');
        }

        // 转换为 $_FILES 格式
        $tmpFile = sys_get_temp_dir() . '/' . \App\Common\Helper\FileHelper::getRandStr();
        $file->moveTo($tmpFile);

        $_files = [
            'file' => [
                'name'     => $filename,
                'type'     => $file->getClientMediaType(),
                'tmp_name' => $tmpFile,
                'error'    => UPLOAD_ERR_OK,
                'size'     => $file->getSize(),
            ],
        ];

        // 上传文件
        $url = Attachment::upload($_files, 'file', $uid, $itemId, $pageId, true);
        @unlink($tmpFile);

        if ($url) {
            // 从 URL 中提取 sign 参数
            $parsedUrl = parse_url($url);
            $sign = '';
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $params);
                if (isset($params['sign'])) {
                    $sign = $params['sign'];
                }
            }

            // 通过 sign 查找 file_id
            $fileId = 0;
            if ($sign) {
                $file = UploadFile::findBySign($sign);
                if ($file) {
                    // UploadFile::findBySign 返回的是对象，不是数组
                    $fileId = (int) ($file->file_id ?? 0);
                }
            }

            $result = ['url' => $url];
            if ($fileId) {
                $result['file_id'] = $fileId;
            }
            if ($sign) {
                $result['sign'] = $sign;
            }
            return $this->success($response, $result);
        } else {
            return $this->error($response, 10101, '上传失败');
        }
    }

    /**
     * 删除附件
     * 通过 api_key 和 api_token 鉴权
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function deleteAttachment(Request $request, Response $response): Response
    {
        $apiKey = $this->getParam($request, 'api_key', '');
        $apiToken = $this->getParam($request, 'api_token', '');
        $fileId = $this->getParam($request, 'file_id', 0);
        $fileUrl = $this->getParam($request, 'file_url', '');
        $sign = $this->getParam($request, 'sign', '');

        // 鉴权
        $itemId = ItemToken::check($apiKey, $apiToken);
        if (!$itemId) {
            return $this->error($response, 10306, 'api_key或者api_token不匹配');
        }

        // 通过 file_id, file_url 或 sign 查找文件
        $file = null;
        if ($fileId > 0) {
            $file = UploadFile::findById($fileId);
        } elseif ($sign) {
            $file = UploadFile::findBySign($sign);
            if ($file) {
                // UploadFile::findBySign 返回的是对象，不是数组
                $fileId = (int) ($file->file_id ?? 0);
            }
        } elseif ($fileUrl) {
            // 从 URL 中提取 sign 参数
            $parsedUrl = parse_url($fileUrl);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $params);
                if (isset($params['sign'])) {
                    $sign = $params['sign'];
                    $file = UploadFile::findBySign($sign);
                    if ($file) {
                        // UploadFile::findBySign 返回的是对象，不是数组
                        $fileId = (int) ($file->file_id ?? 0);
                    }
                }
            }
        }

        if (!$fileId || !$file) {
            return $this->error($response, 10101, '请提供 file_id、file_url 或 sign 参数，且文件必须存在');
        }

        // 检查文件是否关联到该项目
        $filePage = FilePage::findByFileIdAndItemId($fileId, $itemId);
        // UploadFile::findById/findBySign 返回的是对象，不是数组
        if (!$filePage && (int) ($file->item_id ?? 0) !== $itemId) {
            return $this->error($response, 10101, '文件不属于该项目');
        }

        // 删除文件
        $ret = Attachment::deleteFile($fileId);
        if ($ret) {
            return $this->success($response, ['file_id' => $fileId]);
        } else {
            return $this->error($response, 10101, '删除失败');
        }
    }

}
