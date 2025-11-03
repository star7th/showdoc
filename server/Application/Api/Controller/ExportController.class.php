<?php

namespace Api\Controller;

use Think\Controller;

class ExportController extends BaseController
{

    //导出整个项目为word
    public function word()
    {
        set_time_limit(100);
        ini_set('memory_limit', '800M');
        import("Vendor.Parsedown.Parsedown");
        $Parsedown = new \Parsedown();
        $convert = new \Api\Helper\Convert();
        $item_id =  I("item_id/d");
        $cat_id =  I("cat_id/d");
        $page_id =  I("page_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemEdit($login_user['uid'], $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        // 获取项目信息
        $item = D("Item")->where(array('item_id' => $item_id))->find();
        
        // 检查是否为runapi项目并获取全局header
        $global_headers = array();
        if ($item['item_type'] == '3') { // runapi项目类型为3
            $runapiModel = new \Api\Model\RunapiModel();
            $globalParam = $runapiModel->getGlobalParam($item_id);
            if (isset($globalParam['header']) && !empty($globalParam['header'])) {
                $global_headers = $globalParam['header'];
            }
        }

        // 成员目录权限：获取该用户在此项目下允许的目录集合（根下一层）。若非空，则导出仅限这些目录
        $allowedCatIds = D("Member")->getCatIds($item_id, $login_user['uid']);

        $menu = D("Item")->getContent($item_id, "*", "*", 1);
        if ($page_id > 0) {
            $page = D("Page")->where(array('page_id' => $page_id))->find();
            // 如果有限定目录，则校验页面所属目录是否在允许集合内
            if (!empty($allowedCatIds)) {
                $pageCatId = intval($page['cat_id']);
                $allowed = array_flip(array_map('intval', $allowedCatIds));
                if (!isset($allowed[$pageCatId])) {
                    $this->message(L('no_permissions'));
                    return;
                }
            }
            $pages[] = $page;
        } else if ($cat_id) {
            // 如果有限定目录，则cat_id必须在允许集合内
            if (!empty($allowedCatIds) && !in_array(intval($cat_id), array_map('intval', $allowedCatIds))) {
                $this->message(L('no_permissions'));
                return;
            }
            foreach ($menu['catalogs'] as $key => $value) {
                if ($cat_id == $value['cat_id']) {
                    $pages = $value['pages'];
                    $catalogs = $value['catalogs'];
                } else {
                    if ($value['catalogs']) {
                        foreach ($value['catalogs'] as $key2 => $value2) {
                            if ($cat_id == $value2['cat_id']) {
                                $pages = $value2['pages'];
                                $catalogs = $value2['catalogs'];
                            } else {
                                if ($value2['catalogs']) {
                                    foreach ($value2['catalogs'] as $key3 => $value3) {
                                        if ($cat_id == $value3['cat_id']) {
                                            $pages = $value3['pages'];
                                            $catalogs = $value3['catalogs'];
                                        } else {
                                            if ($value3['catalogs']) {
                                                foreach ($value3['catalogs'] as $key4 => $value4) {
                                                    if ($cat_id == $value4['cat_id']) {
                                                        $pages = $value4['pages'];
                                                        $catalogs = $value4['catalogs'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // 当存在目录限制时，仅导出被允许的二级目录合集
            if (!empty($allowedCatIds)) {
                $pages = array();
                $catalogs = array();
                $allowed = array_flip(array_map('intval', $allowedCatIds));
                if (!empty($menu['catalogs'])) {
                    foreach ($menu['catalogs'] as $one) {
                        if (isset($allowed[intval($one['cat_id'])])) {
                            $catalogs[] = $one;
                        }
                    }
                }
            } else {
                $pages = $menu['pages'];
                $catalogs = $menu['catalogs'];
            }
        }

        $data = '';
        $parent = 1;

        // 如果是runapi项目且有全局header，则先添加全局header信息
        if (!empty($global_headers)) {
            $data .= "<h1>全局Header参数</h1>";
            $data .= '<div style="margin-left:20px;">';
            $data .= "<table>";
            $data .= "<thead><tr><th>参数名</th><th>值</th><th>是否启用</th><th>备注</th></tr></thead>";
            $data .= "<tbody>";
            foreach ($global_headers as $header) {
                $enabled = isset($header['enabled']) && $header['enabled'] ? '是' : '否';
                $name = isset($header['name']) ? $header['name'] : '';
                $value = isset($header['value']) ? $header['value'] : '';
                $remark = isset($header['remark']) ? $header['remark'] : '';
                $data .= "<tr><td>{$name}</td><td>{$value}</td><td>{$enabled}</td><td>{$remark}</td></tr>";
            }
            $data .= "</tbody></table>";
            $data .= '</div>';
            $parent++;
        }

        if ($pages) {
            foreach ($pages as $key => $value) {
                if (count($pages) > 1) {
                    $data .= "<h1>{$parent}、{$value['page_title']}</h1>";
                } else {
                    $data .= "<h1>{$value['page_title']}</h1>";
                }
                $data .= '<div style="margin-left:20px;">';
                $tmp_content = $convert->runapiToMd($value['page_content']);
                $value['page_content'] = $tmp_content ? $tmp_content : $value['page_content'];
                $data .= htmlspecialchars_decode($Parsedown->text($value['page_content']));
                $data .= '</div>';
                $parent++;
            }
        }
        //var_export($catalogs);
        if ($catalogs) {
            foreach ($catalogs as $key => $value) {
                $data .= "<h1>{$parent}、{$value['cat_name']}</h1>";
                $data .= '<div style="margin-left:0px;">';
                $child = 1;
                if ($value['pages']) {
                    foreach ($value['pages'] as $page) {
                        $data .= "<h2>{$parent}.{$child}、{$page['page_title']}</h2>";
                        $data .= '<div style="margin-left:0px;">';
                        $tmp_content = $convert->runapiToMd($page['page_content']);
                        $page['page_content'] = $tmp_content ? $tmp_content : $page['page_content'];
                        $data .= htmlspecialchars_decode($Parsedown->text($page['page_content']));
                        $data .= '</div>';
                        $child++;
                    }
                }
                if ($value['catalogs']) {
                    $parent2 = 1;
                    foreach ($value['catalogs'] as $key3 => $value3) {
                        $data .= "<h2>{$parent}.{$parent2}、{$value3['cat_name']}</h2>";
                        $data .= '<div style="margin-left:20px;">';
                        $child2 = 1;
                        if ($value3['pages']) {
                            foreach ($value3['pages'] as $page3) {
                                $data .= "<h3>{$parent}.{$parent2}.{$child2}、{$page3['page_title']}</h3>";
                                $data .= '<div style="margin-left:0px;">';
                                $tmp_content = $convert->runapiToMd($page3['page_content']);
                                $page3['page_content'] = $tmp_content ? $tmp_content : $page3['page_content'];
                                $data .= htmlspecialchars_decode($Parsedown->text($page3['page_content']));
                                $data .= '</div>';
                                $child2++;
                            }
                        }

                        if ($value3['catalogs']) {
                            $parent3 = 1;
                            foreach ($value3['catalogs'] as $key4 => $value4) {
                                $data .= "<h2>{$parent}.{$parent2}.{$parent3}、{$value4['cat_name']}</h2>";
                                $data .= '<div style="margin-left:0px;">';
                                $child3 = 1;
                                if ($value4['pages']) {
                                    foreach ($value4['pages'] as $page4) {
                                        $data .= "<h3>{$parent}.{$parent2}.{$parent3}.{$child3}、{$page4['page_title']}</h3>";
                                        $data .= '<div style="margin-left:30px;">';
                                        $tmp_content = $convert->runapiToMd($page4['page_content']);
                                        $page4['page_content'] = $tmp_content ? $tmp_content : $page4['page_content'];
                                        $data .= htmlspecialchars_decode($Parsedown->text($page4['page_content']));
                                        $data .= '</div>';
                                        $child3++;
                                    }
                                }
                                if ($value4['catalogs']) {
                                    $parent4 = 1;
                                    foreach ($value4['catalogs'] as $key5 => $value5) {
                                        $data .= "<h2>{$parent}.{$parent2}.{$parent3}.{$parent4}、{$value5['cat_name']}</h2>";
                                        $data .= '<div style="margin-left:0px;">';
                                        $child4 = 1;
                                        if ($value4['pages']) {
                                            foreach ($value4['pages'] as $page5) {
                                                $data .= "<h3>{$parent}.{$parent2}.{$parent3}.{$parent4}.{$child4}、{$page5['page_title']}</h3>";
                                                $data .= '<div style="margin-left:30px;">';
                                                $tmp_content = $convert->runapiToMd($page5['page_content']);
                                                $page5['page_content'] = $tmp_content ? $tmp_content : $page5['page_content'];
                                                $data .= htmlspecialchars_decode($Parsedown->text($page5['page_content']));
                                                $data .= '</div>';
                                                $child3++;
                                            }
                                        }
                                        $data .= '</div>';
                                        $parent3++;
                                    }
                                }
                                $data .= '</div>';
                                $parent3++;
                            }
                        }
                        $data .= '</div>';
                        $parent2++;
                    }
                }
                $data .= '</div>';
                $parent++;
            }
        }
        // 记录项目变更日志：导出
        D("ItemChangeLog")->addLog($login_user['uid'], $item_id, 'export', 'item', $item_id, $item['item_name']);

        output_word($data,'showdoc_export_'.date('YmdHis'));
    }

    //导出整个项目为markdown压缩包
    public function markdown()
    {
        set_time_limit(100);
        ini_set('memory_limit', '800M');
        $item_id =  I("item_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemEdit($login_user['uid'], $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $item = D("Item")->where(array('item_id' => $item_id))->find();

        // 成员目录权限：获取该用户在此项目下允许的目录集合
        $allowedCatIds = D("Member")->getCatIds($item_id, $login_user['uid']);

        $exportJson = D("Item")->export($item_id, true);
        $exportData = json_decode($exportJson, 1);
        if (!empty($allowedCatIds) && isset($exportData['pages']) && is_array($exportData['pages'])) {
            $allowed = array_flip(array_map('intval', $allowedCatIds));
            // 目录受限：去掉根目录下的页面，仅保留被允许的二级目录
            $exportData['pages']['pages'] = array();
            $filteredCatalogs = array();
            if (!empty($exportData['pages']['catalogs']) && is_array($exportData['pages']['catalogs'])) {
                foreach ($exportData['pages']['catalogs'] as $one) {
                    if (isset($allowed[intval($one['cat_id'])])) {
                        $filteredCatalogs[] = $one;
                    }
                }
            }
            $exportData['pages']['catalogs'] = $filteredCatalogs;
        }
        $zipArc = new \ZipArchive();
        $temp_file = tempnam(sys_get_temp_dir(), 'Tux') . "_showdoc_.zip";
        $temp_dir = sys_get_temp_dir() . "/showdoc_" . time() . rand();
        mkdir($temp_dir);
        unset($exportData['members']);
        file_put_contents($temp_dir . '/' . 'info.json', json_encode($exportData));

        $this->_markdownTofile($exportData['pages'], $temp_dir);
        $ret = $this->_zip($temp_dir, $temp_file);

        clear_runtime($temp_dir);
        rmdir($temp_dir);
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename=showdoc.zip'); // 文件名
        header("Content-Type: application/zip"); // zip格式的
        header("Content-Transfer-Encoding: binary"); // 告诉浏览器，这是二进制文件
        header('Content-Length: ' . filesize($temp_file)); // 告诉浏览器，文件大小
        // 记录项目变更日志：导出
        D("ItemChangeLog")->addLog($login_user['uid'], $item_id, 'export', 'item', $item_id, $item['item_name']);
        @readfile($temp_file); //输出文件;
        unlink($temp_file);
    }

    public function checkMarkdownLimit()
    {
        $login_user = $this->checkLogin();
        $export_format =  I("export_format");
        $this->sendResult(array());
    }

    /**
     * 将目录数据转换为markdown文件，保持目录结构
     * @param array $catalogData 目录数据
     * @param string $temp_dir 临时目录
     * @param string $base_path 基础路径（用于递归创建子目录）
     * @return array
     */
    private function _markdownTofile($catalogData, $temp_dir, $base_path = '')
    {
        // 处理当前目录下的页面
        if (isset($catalogData['pages']) && !empty($catalogData['pages'])) {
            foreach ($catalogData['pages'] as $key => $value) {
                // 清理文件名中的非法字符
                $filename = $this->_sanitizeFilename($value['page_title']) . '.md';
                $file_path = $base_path ? $base_path . '/' . $filename : $filename;
                $full_path = $temp_dir . '/' . $file_path;
                
                // 如果文件已存在，添加序号避免冲突
                $counter = 1;
                while (file_exists($full_path)) {
                    $name_without_ext = $this->_sanitizeFilename($value['page_title']);
                    $filename = $name_without_ext . '_' . $counter . '.md';
                    $file_path = $base_path ? $base_path . '/' . $filename : $filename;
                    $full_path = $temp_dir . '/' . $file_path;
                    $counter++;
                }
                
                // 确保目录存在
                $dir = dirname($full_path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                // 保存文件内容
                file_put_contents($full_path, htmlspecialchars_decode($value['page_content']));
            }
        }

        // 递归处理子目录
        if (isset($catalogData['catalogs']) && !empty($catalogData['catalogs'])) {
            foreach ($catalogData['catalogs'] as $key => $value) {
                // 清理目录名中的非法字符
                $cat_name = isset($value['cat_name']) ? $value['cat_name'] : '目录';
                $dir_name = $this->_sanitizeFilename($cat_name);
                $new_base_path = $base_path ? $base_path . '/' . $dir_name : $dir_name;
                
                // 如果目录名已存在，添加序号避免冲突
                $dir_full_path = $temp_dir . '/' . $new_base_path;
                $counter = 1;
                while (is_dir($dir_full_path)) {
                    $dir_name = $this->_sanitizeFilename($cat_name) . '_' . $counter;
                    $new_base_path = $base_path ? $base_path . '/' . $dir_name : $dir_name;
                    $dir_full_path = $temp_dir . '/' . $new_base_path;
                    $counter++;
                }
                
                // 递归处理子目录
                $this->_markdownTofile($value, $temp_dir, $new_base_path);
            }
        }
        
        return $catalogData;
    }

    /**
     * 清理文件名/目录名中的非法字符
     * @param string $filename 原始文件名
     * @return string 清理后的文件名
     */
    private function _sanitizeFilename($filename)
    {
        // Windows/Linux 文件名非法字符: < > : " / \ | ? *
        // 同时去除前后空格和点号
        $filename = trim($filename);
        
        // 替换非法字符为下划线
        $filename = preg_replace('/[<>:"\/\\\|\?\*\x00-\x1F]/', '_', $filename);
        
        // 去除连续的下划线和点号
        $filename = preg_replace('/[_\.]+/', '_', $filename);
        
        // 去除前后下划线和点号
        $filename = trim($filename, '_.');
        
        // 如果文件名为空，使用默认名称
        if (empty($filename)) {
            $filename = '未命名';
        }
        
        // Windows 保留文件名
        $reserved_names = array('CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9');
        if (in_array(strtoupper($filename), $reserved_names)) {
            $filename = $filename . '_';
        }
        
        // 限制文件名长度（Windows 限制为 255 字符）
        if (mb_strlen($filename) > 200) {
            $filename = mb_substr($filename, 0, 200);
        }
        
        return $filename;
    }

    /**
     * 使用ZIP压缩文件或目录，保持目录结构
     * @param  [string] $temp_dir 被压缩的目录名
     * @param  [string] $temp_file   压缩后的文件名
     * @return [bool]             成功返回TRUE, 失败返回FALSE
     */
    private function _zip($temp_dir, $temp_file)
    {
        if (!file_exists($temp_dir) && !is_dir($temp_dir)) {
            return FALSE;
        }
        $zipArc = new \ZipArchive();
        if (!$zipArc->open($temp_file, \ZipArchive::CREATE)) {
            return FALSE;
        }
        
        if (is_dir($temp_dir)) {
            // 递归添加目录及其内容，保持目录结构
            $this->_addDirectoryToZip($temp_dir, $zipArc, '');
        } else {
            $zipArc->addFile($temp_dir, basename($temp_dir));
        }
        
        return $zipArc->close();
    }

    /**
     * 递归添加目录到ZIP文件，保持目录结构
     * @param string $dir 目录路径
     * @param \ZipArchive $zipArc ZIP对象
     * @param string $zipPath ZIP内的路径
     */
    private function _addDirectoryToZip($dir, $zipArc, $zipPath)
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            
            $filePath = $dir . '/' . $file;
            $zipFilePath = $zipPath ? $zipPath . '/' . $file : $file;
            
            if (is_dir($filePath)) {
                // 递归处理子目录
                $this->_addDirectoryToZip($filePath, $zipArc, $zipFilePath);
            } else {
                // 添加文件
                $zipArc->addFile($filePath, $zipFilePath);
            }
        }
    }
}
