<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Common\Helper\Convert;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * 页面相关 Api（新架构）。
 *
 * 目前优先迁移与调试/运维相关的接口，例如：
 * - sqlToMarkdownTable：将建表 SQL 转为 Markdown 表格，便于文档化。
 */
class PageController extends BaseController
{
    /**
     * 将建表 SQL 语句转换为 Markdown 表格（兼容旧接口 Page/sqlToMarkdownTable）。
     *
     * 入参：
     * - sql: string，必填，建表 SQL 文本
     *
     * 返回：
     * - { error_code: 0, data: { markdown: "..." } }
     */
    public function sqlToMarkdownTable(Request $request, Response $response): Response
    {
        $sql = $this->getParam($request, 'sql', '');

        if ($sql === '') {
            // 旧版直接 return false；新实现统一走业务错误码，HTTP 仍为 200
            return $this->error($response, 10101, 'SQL 不能为空');
        }

        $converter = new Convert();
        $markdown  = $converter->convertSqlToMarkdownTable($sql);

        if ($markdown === '') {
            // 解析失败或非建表语句
            return $this->error($response, 10101, '无法解析该 SQL，请检查是否为合法的建表语句');
        }

        return $this->success($response, [
            'markdown' => $markdown,
        ]);
    }

    /**
     * 页面详情接口（兼容旧接口 Api/Page/info）。
     */
    public function info(Request $request, Response $response): Response
    {
        $pageId   = $this->getParam($request, 'page_id', 0);
        $withPath = $this->getParam($request, 'with_path', 0);

        if ($pageId <= 0) {
            sleep(1);
            return $this->error($response, 10101, '页面不存在');
        }

        // 获取页面
        $page = DB::table('page')
            ->where('page_id', $pageId)
            ->first();

        if (!$page || (int) ($page->is_del ?? 0) === 1) {
            sleep(1);
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) ($page->item_id ?? 0);

        // 获取登录用户（非严格模式，允许游客访问）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10101, '您没有访问权限');
        }

        // 转换为数组格式
        $page = (array) $page;

        // 检查草稿访问权限
        if (($page['is_draft'] ?? 0) == 1) {
            $authorUid = (int) ($page['author_uid'] ?? 0);
            if ($uid <= 0 || $uid !== $authorUid) {
                return $this->error($response, 10101, '该页面是草稿状态，暂不可访问');
            }
        }

        // 格式化时间
        $page['addtime'] = date('Y-m-d H:i:s', (int) ($page['addtime'] ?? time()));
        if (!empty($page['page_addtime']) && (int) $page['page_addtime'] > 0) {
            $page['page_addtime'] = date('Y-m-d H:i:s', (int) $page['page_addtime']);
        } else {
            $page['page_addtime'] = $page['addtime'];
        }

        // 附件数量
        $page['attachment_count'] = DB::table('file_page')
            ->where('page_id', $pageId)
            ->count();

        // 单页链接唯一标识
        $singlePage = DB::table('single_page')
            ->where('page_id', $pageId)
            ->first();
        if ($singlePage) {
            // 检查单页链接是否已过期
            $expireTime = (int) ($singlePage->expire_time ?? 0);
            if ($expireTime > 0 && $expireTime < time()) {
                // 链接已过期，从数据库中删除记录
                DB::table('single_page')
                    ->where('page_id', $pageId)
                    ->delete();
                $page['unique_key'] = '';
            } else {
                $page['unique_key'] = (string) ($singlePage->unique_key ?? '');
            }
        } else {
            $page['unique_key'] = '';
        }

        // 如果请求了完整路径信息，获取该页面的所有上级目录
        if ($withPath && !empty($page['cat_id'])) {
            $fullPath = $this->getFullPath((int) $page['cat_id'], $itemId);
            // 添加当前页面作为路径的最后一个元素
            $fullPath[] = [
                'page_id'    => $page['page_id'],
                'page_title' => $page['page_title'],
            ];
            $page['full_path'] = $fullPath;
        }

        return $this->success($response, $page);
    }

    /**
     * 获取目录的完整路径
     *
     * @param int $catId 当前目录 ID
     * @param int $itemId 项目 ID
     * @return array 完整路径数组（从上到下）
     */
    private function getFullPath(int $catId, int $itemId): array
    {
        if ($catId <= 0 || $itemId <= 0) {
            return [];
        }

        $path = [];
        $this->findCatPath($catId, $itemId, $path);

        // 返回路径（从上到下排序）
        return array_reverse($path);
    }

    /**
     * 递归查找目录路径
     *
     * @param int $catId 当前目录 ID
     * @param int $itemId 项目 ID
     * @param array &$path 路径数组（引用传递）
     * @return bool 是否找到路径
     */
    private function findCatPath(int $catId, int $itemId, array &$path): bool
    {
        // 查找当前目录信息
        $catalog = \App\Model\Catalog::findByIdAndItemId($catId, $itemId);
        if (!$catalog) {
            return false;
        }

        // 添加当前目录到路径
        $path[] = [
            'cat_id'   => $catalog->cat_id,
            'cat_name' => $catalog->cat_name,
        ];

        // 如果有父目录，继续递归查找
        $parentCatId = (int) ($catalog->parent_cat_id ?? 0);
        if ($parentCatId > 0) {
            return $this->findCatPath($parentCatId, $itemId, $path);
        }

        return true;
    }

    /**
     * 保存页面接口（兼容旧接口 Api/Page/save）。
     *
     * 功能：
     * - 支持新建和更新页面
     * - 权限检查
     * - 页面历史版本保存
     * - 菜单缓存更新
     * - 订阅通知
     * - AI 索引更新（异步）
     */
    public function save(Request $request, Response $response): Response
    {
        ini_set('memory_limit', '128M');

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 获取参数
        $pageId       = $this->getParam($request, 'page_id', 0);
        $isUrlencode  = $this->getParam($request, 'is_urlencode', 0);
        $pageTitle    = $this->getParam($request, 'page_title', '默认标题');
        $pageComments = $this->getParam($request, 'page_comments', '');
        $pageContent  = $this->getParam($request, 'page_content', '');
        $catId        = $this->getParam($request, 'cat_id', 0);
        $itemId       = $this->getParam($request, 'item_id', 0);
        $sNumber      = $this->getParam($request, 's_number', 0);
        $isNotify     = $this->getParam($request, 'is_notify', 0);
        $notifyContent = $this->getParam($request, 'notify_content', '');
        $extInfo      = $this->getParam($request, 'ext_info', '');
        $isDraft      = $this->getParam($request, 'is_draft', -1); // -1 表示不改变状态

        // 验证内容不能为空
        if (empty($pageContent)) {
            return $this->error($response, 10101, '不允许保存空内容，请随便写点什么');
        }

        // URL 解码
        if ($isUrlencode) {
            $pageContent = urldecode($pageContent);
        }

        // HTML 转义
        $pageContent = htmlspecialchars($pageContent, ENT_QUOTES, 'UTF-8');

        // 检查编辑权限
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10101, '没有编辑权限');
        }

        $item = \App\Model\Item::findById($itemId);


        // 准备数据
        $data = [
            'page_title'      => $pageTitle,
            'page_content'    => $pageContent,
            'page_comments'   => $pageComments,
            'item_id'         => $itemId,
            'cat_id'          => $catId,
            'addtime'         => time(),
            'author_uid'      => $uid,
            'author_username' => $loginUser['username'] ?? '',
            'ext_info'        => $extInfo,
        ];

        if ($sNumber > 0) {
            $data['s_number'] = $sNumber;
        }

        // 处理草稿状态
        if ($isDraft >= 0) {
            $data['is_draft'] = (int) $isDraft;
        }

        // 对于 runapi 项目类型，填充 ext_info 字段
        if (empty($data['ext_info']) && $item && (int) $item->item_type === 3) {
            $contentJson = htmlspecialchars_decode($pageContent);
            $content     = json_decode($contentJson, true);
            if ($content && isset($content['info']['url'])) {
                $type = $content['info']['type'] ?? 'api';

                if ($type === 'websocket') {
                    $extInfoArray = [
                        'page_type' => 'websocket',
                        'api_info'  => ['type' => 'websocket'],
                    ];
                } elseif ($type === 'sse') {
                    $extInfoArray = [
                        'page_type' => 'sse',
                        'api_info'  => ['type' => 'sse'],
                    ];
                } else {
                    $extInfoArray = [
                        'page_type' => 'api',
                        'api_info'  => ['method' => $content['info']['method'] ?? 'GET'],
                    ];
                }

                $data['ext_info'] = json_encode($extInfoArray, JSON_UNESCAPED_UNICODE);
            }
        }

        if ($pageId > 0) {
            // 更新页面
            $page = \App\Model\Page::findById($pageId);
            if (!$page) {
                return $this->error($response, 10101, '页面不存在');
            }

            if ((int) $page['item_id'] !== $itemId) {
                return $this->error($response, 10101, '页面不属于该项目');
            }

            if (!$this->checkItemEdit($uid, (int) $page['item_id'])) {
                return $this->error($response, 10101, '您没有编辑权限');
            }

            // 保存历史版本
            $historyData = [
                'page_id'         => $page['page_id'],
                'item_id'         => $page['item_id'],
                'cat_id'          => $page['cat_id'],
                'page_title'      => $page['page_title'],
                'page_comments'   => $page['page_comments'] ?? '',
                'page_content'    => $page['page_content'],
                's_number'        => $page['s_number'] ?? 0,
                'addtime'         => $page['addtime'] ?? time(),
                'author_uid'      => $page['author_uid'] ?? 0,
                'author_username' => $page['author_username'] ?? '',
                'ext_info'        => $page['ext_info'] ?? '',
            ];
            \App\Model\PageHistory::add($pageId, $historyData);

            // 更新页面
            $ret = \App\Model\Page::savePage($pageId, $itemId, $data);

            if (!$ret) {
                return $this->error($response, 10101, '保存失败');
            }

            // 记录变更日志
            \App\Model\ItemChangeLog::addLog($uid, $itemId, 'update', 'page', $pageId, $pageTitle);

            // 统计历史版本数量并清理旧版本
            $count = \App\Model\PageHistory::getCount($pageId);
            // 开源版：保留最近 100 个历史版本
            $keepCount = 100;
            if ($count > $keepCount) {
                \App\Model\PageHistory::deleteOldVersions($pageId, $keepCount);
            }

            // 更新项目时间
            if ($item && (int) $item->item_type === 2) {
                // 单页项目，将页面标题设置为项目名
                \Illuminate\Database\Capsule\Manager::table('item')
                    ->where('item_id', $itemId)
                    ->update([
                        'last_update_time' => time(),
                        'item_name'        => $pageTitle,
                    ]);
            } else {
                \Illuminate\Database\Capsule\Manager::table('item')
                    ->where('item_id', $itemId)
                    ->update(['last_update_time' => time()]);
            }

            // 检测目录 id 和页面顺序号有没有发生变化
            if ($data['cat_id'] == $page['cat_id'] && ($data['s_number'] ?? 0) == ($page['s_number'] ?? 0)) {
                // 如果没有变化，则仅更新缓存 menu 中的这个页面
                \App\Model\Item::updateMenuCachePage($itemId, $pageId, $pageTitle);
            } else {
                // 其他情况，直接删除菜单缓存
                \App\Model\Item::deleteCache($itemId);
            }

            // 订阅通知
            if ($isNotify) {
                $subscriptions = \App\Model\Subscription::getListByObjectId($pageId, 'page', 'update');
                foreach ($subscriptions as $sub) {
                    \App\Model\Message::addMsg(
                        $uid,
                        $loginUser['username'] ?? '',
                        (int) $sub['uid'],
                        'remind',
                        $notifyContent,
                        'update',
                        'page',
                        $pageId
                    );
                }
            }

            $return = \App\Model\Page::findById($pageId);
        } else {
            // 新建页面（开源版无页面数量限制）

            // 添加页面
            $pageId = \App\Model\Page::addPage($itemId, $data);
            if ($pageId <= 0) {
                return $this->error($response, 10101, '创建页面失败');
            }

            // 记录变更日志
            \App\Model\ItemChangeLog::addLog($uid, $itemId, 'create', 'page', $pageId, $pageTitle);

            // 更新项目时间
            \Illuminate\Database\Capsule\Manager::table('item')
                ->where('item_id', $itemId)
                ->update(['last_update_time' => time()]);

            // 删除菜单缓存
            \App\Model\Item::deleteCache($itemId);

            // 暂停 800 毫秒，以便应对主从数据库同步延迟
            usleep(800000);

            // 添加页面的时候把最初的创建者加入消息订阅
            \App\Model\Subscription::addSub($uid, $pageId, 'page', 'update');

            $return = ['page_id' => $pageId];
        }

        // 删除页面缓存
        \App\Model\Page::deleteCache($pageId);

        // 获取一次菜单以便生成缓存
        \App\Model\Item::getMenuByCache($itemId);


        // 先返回响应，确保用户能收到结果
        $result = $this->success($response, $return);

        // 使用 register_shutdown_function 在响应发送后异步触发 AI 索引更新
        // 这样既能正常返回响应，又不会阻塞用户请求
        register_shutdown_function(function () use ($itemId, $pageId) {
            // 在响应发送后执行，不会阻塞用户
            try {
                // 检查 AI 知识库功能是否启用
                $itemAiConfig = \App\Model\ItemAiConfig::getConfig($itemId);
                if (empty($itemAiConfig['enabled'])) {
                    // AI 功能未启用，不触发索引更新
                    return;
                }

                // 从全局配置获取 AI 服务地址和 Token
                $aiServiceUrl = \App\Model\Options::get('ai_service_url', '');
                $aiServiceToken = \App\Model\Options::get('ai_service_token', '');

                if (empty($aiServiceUrl) || empty($aiServiceToken)) {
                    // AI 服务未配置，不触发索引更新
                    return;
                }

                // 触发整个项目的索引重建（异步）
                \App\Common\Helper\AiHelper::rebuild($itemId, $aiServiceUrl, $aiServiceToken);
            } catch (\Throwable $e) {
                // 索引更新失败不影响页面保存，只记录错误日志
                error_log("触发 AI 索引更新失败: item_id={$itemId}, page_id={$pageId}, error=" . $e->getMessage());
            }
        });

        return $result;
    }

    /**
     * 触发 AI 索引更新（异步，不阻塞主流程）
     *
     * @param int $itemId 项目ID
     * @param int $pageId 页面ID
     * @param string $action 操作类型：'create' 或 'update'
     * @return void
     */
    private function triggerAiIndex(int $itemId, int $pageId, string $action = 'update'): void
    {
        try {
            // 检查 AI 知识库功能是否启用
            $itemAiConfig = \App\Model\ItemAiConfig::getConfig($itemId);
            if (empty($itemAiConfig['enabled'])) {
                // AI 功能未启用，不触发索引更新
                return;
            }

            // 从全局配置获取 AI 服务地址和 Token
            $aiServiceUrl = \App\Model\Options::get('ai_service_url', '');
            $aiServiceToken = \App\Model\Options::get('ai_service_token', '');

            if (empty($aiServiceUrl) || empty($aiServiceToken)) {
                // AI 服务未配置，不触发索引更新
                return;
            }

            // 如果是单个页面更新，可以只更新该页面；如果是创建或需要全量重建，则重建整个项目索引
            // 这里简化处理：页面保存后触发整个项目的索引重建（异步）
            // 注意：rebuild 方法会处理整个项目，所以这里直接调用 rebuild
            \App\Common\Helper\AiHelper::rebuild($itemId, $aiServiceUrl, $aiServiceToken);
        } catch (\Throwable $e) {
            // 索引更新失败不影响页面保存，只记录错误日志
            error_log("触发 AI 索引更新失败: item_id={$itemId}, page_id={$pageId}, action={$action}, error=" . $e->getMessage());
        }
    }

    /**
     * 页面历史版本列表（兼容旧接口 Api/Page/history）。
     *
     * 功能：
     * - 获取页面的历史版本列表
     * - 权限检查（checkItemVisit）
     * - 内容解压和格式化
     */
    public function history(Request $request, Response $response): Response
    {
        $pageId = $this->getParam($request, 'page_id', 0);

        if ($pageId <= 0) {
            return $this->error($response, 10101, '页面不存在');
        }

        // 获取页面信息
        $page = \App\Model\Page::findById($pageId);
        if (!$page) {
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) $page['item_id'];

        // 获取登录用户（非严格模式，允许游客访问）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10101, '您没有访问权限');
        }

        // 获取历史版本列表
        $pageHistory = \App\Model\PageHistory::getList($pageId, 100);

        return $this->success($response, $pageHistory);
    }

    /**
     * 页面版本对比（兼容旧接口 Api/Page/diff）。
     *
     * 功能：
     * - 返回当前页面和历史某个版本的页面以供比较
     * - 权限检查（checkItemVisit）
     */
    public function diff(Request $request, Response $response): Response
    {
        $pageId        = $this->getParam($request, 'page_id', 0);
        $pageHistoryId = $this->getParam($request, 'page_history_id', 0);

        if ($pageId <= 0) {
            return $this->error($response, 10101, '页面不存在');
        }

        if ($pageHistoryId <= 0) {
            return $this->error($response, 10101, '历史版本不存在');
        }

        // 获取当前页面
        $page = \App\Model\Page::findById($pageId);
        if (!$page) {
            sleep(1);
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) $page['item_id'];

        // 获取登录用户（非严格模式，允许游客访问）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10101, '您没有访问权限');
        }

        // 获取历史版本
        $historyPage = \App\Model\PageHistory::findById($pageId, $pageHistoryId);
        if (!$historyPage) {
            return $this->error($response, 10101, '历史版本不存在');
        }

        return $this->success($response, [
            'page'         => $page,
            'history_page' => $historyPage,
        ]);
    }

    /**
     * 更新历史版本备注（兼容旧接口 Api/Page/updateHistoryComments）。
     *
     * 功能：
     * - 更新历史版本的备注信息
     * - 权限检查（checkItemEdit）
     */
    public function updateHistoryComments(Request $request, Response $response): Response
    {
        $pageId        = $this->getParam($request, 'page_id', 0);
        $pageHistoryId = $this->getParam($request, 'page_history_id', 0);
        $pageComments  = $this->getParam($request, 'page_comments', '');

        if ($pageId <= 0 || $pageHistoryId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        // 获取页面信息
        $page = \App\Model\Page::findById($pageId);
        if (!$page) {
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) $page['item_id'];

        // 获取登录用户（非严格模式，允许游客访问，但需要编辑权限）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查编辑权限
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10101, '您没有编辑权限');
        }

        // 更新历史版本备注
        $ret = \App\Model\PageHistory::updateComments($pageId, $pageHistoryId, $pageComments);

        return $this->success($response, ['success' => $ret]);
    }

    /**
     * 判断页面是否加了编辑锁（兼容旧接口 Api/Page/isLock）。
     */
    public function isLock(Request $request, Response $response): Response
    {
        $pageId = (int) $this->getParam($request, 'page_id', 0);
        $itemId = (int) $this->getParam($request, 'item_id', 0);
        $lock   = 0;
        $exceed = 0;
        $now    = time();

        // 登录用户（非强制）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查锁记录
        $res = \Illuminate\Database\Capsule\Manager::table('page_lock')
            ->where('page_id', $pageId)
            ->where('page_id', '>', 0)
            ->where('lock_to', '>', $now)
            ->first();

        if ($res) {
            $lock = 1;
        }

        // 开源版无页面数量限制

        $lockUid      = $res->lock_uid ?? '';
        $lockUsername = $res->lock_username ?? '';
        $isCurUser    = ($uid > 0 && $lockUid && $uid === (int) $lockUid) ? 1 : 0;

        return $this->success($response, [
            'lock'          => $lock,
            'exceed'        => $exceed,
            'lock_uid'      => $lockUid ?: '',
            'lock_username' => $lockUsername ?: '',
            'is_cur_user'   => $isCurUser,
        ]);
    }

    /**
     * 删除页面（兼容旧接口 Api/Page/delete）。
     */
    public function delete(Request $request, Response $response): Response
    {
        $pageId = (int) $this->getParam($request, 'page_id', 0);
        if ($pageId <= 0) {
            return $this->error($response, 10101, '页面不存在');
        }

        $page = \App\Model\Page::findById($pageId);
        if (!$page) {
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) ($page['item_id'] ?? 0);

        // 登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 需要项目管理权限或页面作者本人
        if (
            !$this->checkItemManage($uid, $itemId) &&
            $uid !== (int) ($page['author_uid'] ?? 0)
        ) {
            return $this->error($response, 10303, '您没有管理权限');
        }

        $ret = \App\Model\Page::softDeletePage($pageId, $itemId, $uid, (string) ($loginUser['username'] ?? ''));

        if ($ret) {
            // 更新项目时间
            DB::table('item')
                ->where('item_id', $itemId)
                ->update(['last_update_time' => time()]);

            // 删除页面相关评论与反馈
            DB::table('page_comment')->where('page_id', $pageId)->delete();
            DB::table('page_feedback')->where('page_id', $pageId)->delete();

            // 记录变更日志
            \App\Model\ItemChangeLog::addLog(
                $uid,
                $itemId,
                'delete',
                'page',
                $pageId,
                (string) ($page['page_title'] ?? '')
            );

            // 删除菜单与页面缓存
            \App\Model\Item::deleteCache($itemId);
            \App\Model\Page::deleteCache($pageId);

            // 先返回成功，再异步触发 AI 索引删除
            $result = $this->success($response, []);

            // 使用 register_shutdown_function 在响应发送后异步触发 AI 索引删除
            register_shutdown_function(function () use ($itemId, $pageId) {
                // 在响应发送后执行，不会阻塞用户
                try {
                    // 检查 AI 知识库功能是否启用
                    $itemAiConfig = \App\Model\ItemAiConfig::getConfig($itemId);
                    if (empty($itemAiConfig['enabled'])) {
                        // AI 功能未启用，不触发索引更新
                        return;
                    }

                    // 从全局配置获取 AI 服务地址和 Token
                    $aiServiceUrl = \App\Model\Options::get('ai_service_url', '');
                    $aiServiceToken = \App\Model\Options::get('ai_service_token', '');

                    if (empty($aiServiceUrl) || empty($aiServiceToken)) {
                        // AI 服务未配置，不触发索引更新
                        return;
                    }

                    // 删除操作：触发整个项目的索引重建（异步）
                    \App\Common\Helper\AiHelper::rebuild($itemId, $aiServiceUrl, $aiServiceToken);
                } catch (\Throwable $e) {
                    // 索引更新失败不影响页面删除，只记录错误日志
                    error_log("触发 AI 索引更新失败: item_id={$itemId}, page_id={$pageId}, action=delete, error=" . $e->getMessage());
                }
            });

            return $result;
        }

        return $this->error($response, 10101, '删除失败');
    }

    /**
     * 上传图片（兼容旧接口 Api/Page/uploadImg，转发到 AttachmentController::uploadImg）。
     */
    public function uploadImg(Request $request, Response $response): Response
    {
        $controller = new \App\Api\Controller\AttachmentController(
            $this->container ?? null,
            $this->logger ?? null
        );

        return $controller->uploadImg($request, $response);
    }

    /**
     * 上传附件（兼容旧接口 Api/Page/upload，转发到 AttachmentController::attachmentUpload）。
     */
    public function upload(Request $request, Response $response): Response
    {
        $controller = new \App\Api\Controller\AttachmentController(
            $this->container ?? null,
            $this->logger ?? null
        );

        return $controller->attachmentUpload($request, $response);
    }

    /**
     * 获取页面上传文件列表（兼容旧接口 Api/Page/uploadList）。
     */
    public function uploadList(Request $request, Response $response): Response
    {
        $controller = new \App\Api\Controller\AttachmentController(
            $this->container ?? null,
            $this->logger ?? null
        );

        return $controller->pageAttachmentUploadList($request, $response);
    }

    /**
     * 删除页面已上传文件（兼容旧接口 Api/Page/deleteUploadFile）。
     */
    public function deleteUploadFile(Request $request, Response $response): Response
    {
        $controller = new \App\Api\Controller\AttachmentController(
            $this->container ?? null,
            $this->logger ?? null
        );

        return $controller->deletePageUploadFile($request, $response);
    }

    /**
     * 创建/删除单页链接（兼容旧接口 Api/Page/createSinglePage）。
     */
    public function createSinglePage(Request $request, Response $response): Response
    {
        $pageId           = (int) $this->getParam($request, 'page_id', 0);
        $isCreateSingle   = $this->getParam($request, 'isCreateSiglePage', '');
        $expireDays       = (int) $this->getParam($request, 'expire_days', 0);

        if ($pageId <= 0) {
            return $this->error($response, 10101, '页面不存在');
        }

        $page = \App\Model\Page::findById($pageId);
        if (!$page || (int) ($page['is_del'] ?? 0) === 1) {
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) ($page['item_id'] ?? 0);

        // 登录用户（非强制）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10101, '您没有编辑权限');
        }

        // 先删除旧记录
        DB::table('single_page')->where('page_id', $pageId)->delete();

        // 计算过期时间（0 表示永久）
        $expireTime = 0;
        if ($expireDays > 0) {
            $expireTime = time() + ($expireDays * 24 * 60 * 60);
        }

        // 按旧实现约定，仅当 isCreateSiglePage 为 'true' 时创建
        if ((string) $isCreateSingle === 'true') {
            $uniqueKey = md5(microtime(true) . rand() . 'showdoc_single_page_salt');
            $data      = [
                'unique_key'  => $uniqueKey,
                'page_id'     => $pageId,
                'expire_time' => $expireTime,
            ];

            DB::table('single_page')->insert($data);

            return $this->success($response, $data);
        }

        return $this->success($response, []);
    }

    /**
     * 通过唯一 key 获取页面详情（兼容旧接口 Api/Page/infoByKey）。
     */
    public function infoByKey(Request $request, Response $response): Response
    {
        $uniqueKey = (string) $this->getParam($request, 'unique_key', '');
        if ($uniqueKey === '') {
            return $this->error($response, 10101, '参数错误');
        }

        $singlePage = DB::table('single_page')
            ->where('unique_key', $uniqueKey)
            ->first();

        if (!$singlePage) {
            return $this->error($response, 10101, '该分享链接已过期或不存在');
        }

        $pageId = (int) ($singlePage->page_id ?? 0);

        // 检查链接是否已过期
        $expireTime = (int) ($singlePage->expire_time ?? 0);
        if ($expireTime > 0 && $expireTime < time()) {
            // 链接已过期，从数据库中删除记录
            DB::table('single_page')
                ->where('unique_key', $uniqueKey)
                ->delete();
            return $this->error($response, 10101, '该分享链接已过期');
        }

        $page = DB::table('page')
            ->where('page_id', $pageId)
            ->first();

        if (!$page || (int) ($page->is_del ?? 0) === 1) {
            sleep(1);
            return $this->error($response, 10101, '页面不存在');
        }

        // 登录用户（非强制）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);

        // 转换为数组格式
        $page = (array) $page;

        // 去掉 item_id 和 cat_id
        unset($page['item_id'], $page['cat_id']);

        // 格式化时间
        $page['addtime'] = date('Y-m-d H:i:s', (int) ($page['addtime'] ?? time()));

        // 附件数量
        $page['attachment_count'] = DB::table('file_page')
            ->where('page_id', $pageId)
            ->count();

        // 添加单页链接过期时间字段
        $page['expire_time'] = $expireTime;

        return $this->success($response, $page);
    }

    /**
     * 同一目录下页面排序（兼容旧接口 Api/Page/sort）。
     */
    public function sort(Request $request, Response $response): Response
    {
        $pagesJson = (string) $this->getParam($request, 'pages', '');
        $itemId    = (int) $this->getParam($request, 'item_id', 0);

        if ($itemId <= 0 || $pagesJson === '') {
            return $this->error($response, 10101, '参数错误');
        }

        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10101, '您没有编辑权限');
        }

        $dataArray = json_decode(htmlspecialchars_decode($pagesJson, ENT_QUOTES), true);
        if (!is_array($dataArray) || empty($dataArray)) {
            return $this->success($response, []);
        }

        foreach ($dataArray as $pageId => $sNumber) {
            $pageId  = (int) $pageId;
            $sNumber = (int) $sNumber;
            if ($pageId <= 0) {
                continue;
            }

            DB::table('page')
                ->where('page_id', $pageId)
                ->where('item_id', $itemId)
                ->update(['s_number' => $sNumber]);

            \App\Model\Page::deleteCache($pageId);
        }

        \App\Model\Item::deleteCache($itemId);

        return $this->success($response, []);
    }

    /**
     * 设置页面编辑锁（兼容旧接口 Api/Page/setLock）。
     */
    public function setLock(Request $request, Response $response): Response
    {
        $pageId = (int) $this->getParam($request, 'page_id', 0);
        $lockTo = (int) $this->getParam(
            $request,
            'lock_to',
            time() + 30 * 60 * 60
        );
        $itemId = (int) $this->getParam($request, 'item_id', 0);

        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($pageId <= 0 || $itemId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10101, '您没有编辑权限');
        }

        $now = time();

        // 清理过期锁
        DB::table('page_lock')
            ->where('lock_to', '<', $now)
            ->delete();

        // 如果存在其他用户的锁，拒绝
        $row = DB::table('page_lock')
            ->where('page_id', $pageId)
            ->first();

        if ($row && (int) ($row->lock_uid ?? 0) !== $uid) {
            return $this->error($response, 10101, '该页面已被其他用户锁定');
        }

        // 删除当前页面的旧锁
        DB::table('page_lock')
            ->where('page_id', $pageId)
            ->delete();

        // 创建新锁
        $id = DB::table('page_lock')->insertGetId([
            'page_id'       => $pageId,
            'lock_uid'      => $uid,
            'lock_username' => (string) ($loginUser['username'] ?? ''),
            'lock_to'       => $lockTo,
            'addtime'       => time(),
        ]);

        return $this->success($response, ['id' => $id]);
    }
}
