<?php
/*
  存放一些转换的逻辑代码，比如从xx格式转成markdown格式
*/

namespace Api\Helper;
use PHPSQLParser\PHPSQLParser;


class Convert {


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
        $markdowns = $sql_array['fields'] ;
        array_unshift($markdowns, ...$headers);

        $html = "\n- {$sql_array['table']} {$sql_array['comment']}\n\n" ;
        foreach ($markdowns as $line) {
            $html .= '| ' . implode(' | ', $line) . ' | ' . "\n";
        }

        return $html."\n";

  }

  // 把sql转换成解析数组
  public function convertSqlToArray($sql){
    $return = array(
        'table'=>'',// 表名
        'comment'=>'', // 注释
        'fields'=>array()
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
                $markdowns[] = [trim($name, '`'), $type, $nullable ? 'Y' : 'N',$default , $comment];
                $return['fields'][] = array(
                    'name'=>trim($name, '`') ,
                    'type'=>$type ,
                    'nullable'=>$nullable ? '是' : '否' ,
                    'default'=>trim($default, "'") ,
                    'comment'=>$comment?$comment:'-' ,
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
            $return['table'] =trim($tableName, '`') ;// 表名
            $return['comment'] = $tableComment ;// 表注释

        }
    } catch (Exception $ex) {
        return "{$ex->getMessage()} @{$ex->getFile()}:{$ex->getLine()}";
    }



    return $return ;


  }

}