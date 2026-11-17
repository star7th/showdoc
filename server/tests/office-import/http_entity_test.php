<?php
/**
 * HTTP 端到端验证：双重/多重 HTML 实体编码载荷经真实 Office 导入链路
 * （auto 接口 → anydoc 转换 → MarkdownSanitizer → updateByTitle 落库）
 * 不得在库中残留可执行 HTML。
 *
 * 前置：ADMIN_TOKEN（http_test.php 同口径），容器内 anydoc 可用，
 *       fixture 由宿主机 python3 生成：make_entity_fixtures.py → fixtures/
 * 用法：docker exec -e ADMIN_TOKEN=... showdoc-dev \
 *         php /app/showdoc.cc/server/tests/office-import/http_entity_test.php
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$ADMIN_TOKEN = getenv('ADMIN_TOKEN') ?: '';
$BASE        = getenv('BASE_URL') ?: 'http://localhost/showdoc.cc/server/Api/Import/auto';

$failures = 0;
$pass = function (string $name, $cond, $got = null) use (&$failures) {
    if ($cond) {
        echo "  ✓ {$name}\n";
    } else {
        $failures++;
        $gotStr = is_scalar($got) ? var_export($got, true) : json_encode($got, JSON_UNESCAPED_UNICODE);
        echo "  ✗ {$name}\n    got: {$gotStr}\n";
    }
};
$fx = static fn(string $f): string => (getenv('FX_DIR') ?: __DIR__ . '/fixtures') . '/' . $f;

function importFile(string $url, string $token, string $path, ?string $clientName = null): array
{
    $ch = curl_init($url);
    $cf = new CURLFile($path, 'application/octet-stream', $clientName ?: basename($path));
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => ['file' => $cf],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["X-API-Token: {$token}"],
        CURLOPT_TIMEOUT        => 180,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $json = json_decode((string) $body, true);
    if (!is_array($json)) {
        return ['error_code' => -1, 'error_message' => "HTTP {$code}: " . substr((string) $body, 0, 200), 'data' => []];
    }
    return $json;
}

// 开源版差异：默认 sqlite 单表 page、page_content 明文存储（写入时整体
// htmlspecialchars）；DB_TYPE=mysql 时与主版同口径分表+压缩
function dbIsMysql(): bool
{
    $v = $_ENV['DB_TYPE'] ?? $_SERVER['DB_TYPE'] ?? getenv('DB_TYPE');
    return strtolower((string) ($v ?: 'sqlite')) === 'mysql';
}
function db(): PDO
{
    if (dbIsMysql()) {
        $env = static fn(string $k, $d) => $_ENV[$k] ?? $_SERVER[$k] ?? getenv($k) ?: $d;
        return new PDO(
            'mysql:host=' . $env('DB_HOST', '192.168.2.9') . ';dbname=' . $env('DB_NAME', 'showdoc') . ';charset=utf8mb4',
            (string) $env('DB_USER', 'showdoc'),
            (string) $env('DB_PWD', '')
        );
    }
    return new PDO('sqlite:' . dirname(__DIR__, 3) . '/Sqlite/showdoc.db.php');
}
function pageRows(int $itemId): array
{
    $table = dbIsMysql() ? ('page_' . (($itemId % 100) + 1)) : 'page';
    $st = db()->prepare("SELECT page_title, page_content FROM {$table} WHERE item_id = ?");
    $st->execute([$itemId]);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $raw = $row['page_content'];
        $dec = @gzuncompress(base64_decode($raw));
        $txt = ($dec !== false && $dec !== '') ? $dec : $raw;
        // 开源版明文存储口径：读取消费者（页面渲染前）会做实体解码，判定同口径
        $txt = html_entity_decode($txt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $rows[] = ['title' => $row['page_title'], 'text' => $txt];
    }
    return $rows;
}
/** 递归实体解码至不动点：确认无论解码多少层都不会出现可执行标签 */
function fullDecode(string $s): string
{
    for ($i = 0; $i < 30; $i++) {
        $d = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($d === $s) {
            return $d;
        }
        $s = $d;
    }
    return $s;
}
/** 可利用性判定（与 http_security_test 同口径） */
function findExecutable(array $rows): array
{
    $bad = [];
    foreach ($rows as $row) {
        // 递归解码到不动点后再判：任何层数的实体伪装都被还原
        $text = fullDecode($row['text']);
        if (preg_match('/(?<!\\\\)<\s*[a-zA-Z\/!]/', $text)) {
            $bad[] = $row['title'] . ' <= 裸标签: ' . mb_substr($text, 0, 120);
        }
        if (preg_match_all('/(?<!\\\\)(!?)\[[^\]]*\]\(([^)]*)\)/', $text, $mm)) {
            foreach ($mm[2] as $url) {
                $u = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $u = preg_replace('/[\x00-\x20\x7f]+/u', '', $u);
                if (preg_match('~^(javascript|vbscript|data|file|blob|livescript|mocha|jscript|about):~i', $u)) {
                    $bad[] = $row['title'] . ' <= 危险链接 ' . $url;
                }
            }
        }
    }
    return $bad;
}

if (!$ADMIN_TOKEN || $ADMIN_TOKEN === 'CHANGE_ME') {
    die("需要 ADMIN_TOKEN\n");
}

/**
 * fixtures 前置检查：缺失时自动尝试调宿主机 python3 运行生成脚本。
 * python3 或 python-docx/python-pptx 任一缺失时打印提示并整体跳过，不报错中断。
 *
 * @param string[] $scripts 生成脚本路径（依次尝试执行）
 * @param string[] $files   必需的 fixture 文件名列表
 * @return void（不可用时已打印 SKIP 提示并 exit）
 */
function ensureFixturesOrSkip(array $scripts, array $files): void
{
    $dir = getenv('FX_DIR') ?: __DIR__ . '/fixtures';
    $missing = array_values(array_filter($files, static fn($f) => !is_file($dir . '/' . $f)));
    if (!$missing) {
        return;
    }
    echo '缺少 fixtures：' . implode(', ', $missing) . "\n";
    $hint = "请在宿主机安装 python3 与 python-docx/python-pptx 后运行：\n";
    foreach ($scripts as $s) {
        $hint .= "  python3 {$s}\n";
    }
    $py = trim((string) shell_exec('command -v python3 2>/dev/null'));
    if ($py === '') {
        echo "SKIP：当前环境无 python3。\n{$hint}";
        exit(0);
    }
    exec(escapeshellarg($py) . ' -c ' . escapeshellarg('import docx, pptx') . ' 2>/dev/null', $out, $rc);
    if ($rc !== 0) {
        echo "SKIP：python3 缺少 python-docx / python-pptx 依赖（pip3 install python-docx python-pptx）。\n{$hint}";
        exit(0);
    }
    $envPrefix = getenv('FX_DIR') ? 'FX_OUT=' . escapeshellarg($dir) . ' ' : '';
    foreach ($scripts as $s) {
        echo "尝试生成：python3 {$s}\n";
        system($envPrefix . escapeshellarg($py) . ' ' . escapeshellarg($s) . ' 2>&1', $rc);
        if ($rc !== 0) {
            echo "SKIP：生成脚本执行失败（exit={$rc}）。\n{$hint}";
            exit(0);
        }
    }
    $still = array_filter($files, static fn($f) => !is_file($dir . '/' . $f));
    if ($still) {
        echo 'SKIP：生成后仍缺少 ' . implode(', ', $still) . "。\n{$hint}";
        exit(0);
    }
}

ensureFixturesOrSkip(
    [__DIR__ . '/make_entity_fixtures.py'],
    ['entity_attack.docx', 'entity_normal.docx']
);

$trackedItems = []; // item_id => item_name，finally 里清理

try {
    echo "== 1. 多重实体编码恶意 docx（entity_attack.docx）==\n";
    $r = importFile($BASE, $ADMIN_TOKEN, $fx('entity_attack.docx'));
    $pass('导入成功', ($r['error_code'] ?? -1) === 0, $r['error_message'] ?? $r);
    $itemId = (int) ($r['data']['item_id'] ?? 0);
    $pass('返回 item_id', $itemId > 0, $r['data'] ?? null);
    $trackedItems[$itemId] = 'UT_http_entity_' . $itemId;
    if ($itemId > 0) {
        $rows = pageRows($itemId);
        $pass('落库页面数 ≥ 1', count($rows) >= 1, array_column($rows, 'title'));
        // 原文断言：双重/三重载荷关键词已被净化（不得以任何实体形态残留为可执行）
        $bad = findExecutable($rows);
        $pass('库中无可利用 XSS 载荷（递归解码至不动点后判定）', empty($bad), $bad);
        foreach ($rows as $row) {
            $needle = 'avascript:'; // javascript 去掉首字符的稳定子串（覆盖实体拆分形态）
            $pass("「{$row['title']}」不含 javascript 伪协议（任意实体层）", stripos(fullDecode($row['text']), $needle) === false, mb_substr($row['text'], 0, 200));
        }
        $ok = false;
        foreach ($rows as $row) {
            if (strpos($row['text'], 'https://showdoc.com.cn') !== false) {
                $ok = true;
            }
        }
        $pass('正常链接保留（功能无损）', $ok, array_map(static fn($x) => mb_substr($x['text'], 0, 100), $rows));
    }

    echo "\n== 2. 正常 docx 功能验证（entity_normal.docx）==\n";
    $r = importFile($BASE, $ADMIN_TOKEN, $fx('entity_normal.docx'), '正常功能验证.docx');
    $pass('导入成功', ($r['error_code'] ?? -1) === 0, $r['error_message'] ?? $r);
    $itemId2 = (int) ($r['data']['item_id'] ?? 0);
    if ($itemId2 > 0) {
        $trackedItems[$itemId2] = 'UT_http_entity_' . $itemId2;
        $rows2 = pageRows($itemId2);
        $titles = array_column($rows2, 'title');
        $pass('按标题拆出 3 页（H1 + 2×H2）', count($rows2) === 3, $titles);
        $ok = false;
        foreach ($rows2 as $row) {
            if (strpos($row['text'], 'https://example.com') !== false) {
                $ok = true;
            }
        }
        $pass('正文与链接无损落库', $ok, array_map(static fn($x) => mb_substr($x['text'], 0, 100), $rows2));
    }
} finally {
    // 清理：删除本次创建的项目与页面
    foreach (array_keys($trackedItems) as $id) {
        if ($id <= 0) {
            continue;
        }
        try {
            $table = dbIsMysql() ? ('page_' . (($id % 100) + 1)) : 'page';
            db()->exec("DELETE FROM {$table} WHERE item_id = {$id}");
            db()->exec("DELETE FROM catalog WHERE item_id = {$id}");
            db()->exec("DELETE FROM item WHERE item_id = {$id}");
            echo "已清理 item_id={$id}\n";
        } catch (Throwable $e) {
            echo "清理 item_id={$id} 失败: {$e->getMessage()}\n";
        }
    }
}

echo "\n";
if ($failures === 0) {
    echo "ALL PASS\n";
    exit(0);
}
echo "{$failures} FAILURES\n";
exit(1);
