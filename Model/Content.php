<?php

/**
 * PESCMS for PHP 5.4+
 *
 * Copyright (c) 2014 PESCMS (http://www.pescms.com)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Model;

/**
 * 内容模型
 */
class Content extends \Core\Model\Model {

    private static $table, $fieldPrefix, $model;

    /**
     * 查找指定内容（动态条件）
     * @param type $table 内容表名
     * @param type $value 内容值
     * @param type $field 查找的字段
     * @return type
     */
    public static function findContent($table, $value, $field, $showField = '*') {
        return self::db($table)->field($showField)->where("{$field} = :$field")->find(array($field => $value));
    }

    /**
     * 列出内容（动态条件）
     * @param type $table 内容表名
     * @param array $param 绑定参数
     * @param type $where 查找条件
     * @param type $order 排序
     * @param type $limit 限制输出
     * @return type
     */
    public static function listContent($param) {
        if(empty($param['table'])){
            self::error('Unkonw Table!');
        }
        $value = array_merge(['field' => '*', 'db' => '', 'prefix' => '', 'join' => '', 'condition' => '', 'order' => '', 'group' => '', 'limit' => '', 'param' => array()], $param);
        return self::db($value['table'], $value['db'], $value['prefix'])->field($value['field'])->join($value['join'])->where($value['condition'])->order($value['order'])->group($value['group'])->limit($value['limit'])->select($value['param']);
    }

    /**
     * 添加内容
     */
    public static function addContent() {
        $data = self::baseFrom();
        $addResult = self::db(self::$table)->insert($data);
        if (empty($addResult)) {
            self::error('添加内容失败');
        }
        self::setUrl($addResult);

        return $addResult;
    }

    /**
     * 更新内容
     */
    public static function updateContent() {

        $data = self::baseFrom();

        $condition = self::$fieldPrefix . 'id';
        $updateResult = self::db(self::$table)->where("{$condition} = :{$condition}")->update($data);
        if ($updateResult === false) {
            return self::error('更新内容失败');
        }

        self::setUrl($data['noset'][$condition]);

        return true;
    }

    /**
     * 基础表单
     */
    public static function baseFrom() {
        self::$table = strtolower(MODULE);
        self::$fieldPrefix = self::$table . "_";
        self::$model = \Model\ModelManage::findModel(self::$table, 'model_name');
        $field = \Model\Field::fieldList(self::$model['model_id'], array('field_status' => '1'));

        if (self::p('method') == 'PUT') {
            $data['noset'][self::$fieldPrefix . 'id'] = self::isP('id', '丢失模型ID');
            if (!self::findContent(self::$table, $data['noset'][self::$fieldPrefix . 'id'], self::$fieldPrefix . 'id')) {
                self::error('不存在的模型');
            }
        }

        foreach ($field as $value) {

            /**
             * 判断提交的字段是否为数组
             */
            if (is_array($_POST[$value['field_name']])) {
                $_POST[$value['field_name']] = (string)implode(',', $_POST[$value['field_name']]);
            }

            /**
             * 时间转换为时间戳
             */
            if ($value['field_type'] == 'date') {
                $_POST[$value['field_name']] = (string)strtotime($_POST[$value['field_name']]);
            }

            if ($value['field_required'] == '1') {
                if (!($data[self::$fieldPrefix . $value['field_name']] = self::p($value['field_name'])) && !is_numeric($data[self::$fieldPrefix . $value['field_name']])) {
                    self::error($value['field_display_name'] . '为必填选项');
                }
            } else {
                $field_name = self::p($value['field_name']);
                if(!empty($field_name)){
                    $data[self::$fieldPrefix . $value['field_name']] = $field_name;
                }elseif( empty($field_name) && !is_numeric($field_name) && !empty($value['field_default']) ){
                    $data[self::$fieldPrefix . $value['field_name']] = $value['field_default'];
                }else{
                    $data[self::$fieldPrefix . $value['field_name']] = $field_name;
                }
            }
        }

        return $data;
    }

    /**
     * 列出对应分类
     * @param type $table 表名
     * @param type $cid 分类ID
     */
    public static function listCategoryContent($table, $cid) {
        return self::db($table)->where("{$table}_catid = :cid")->select(array('cid' => $cid));
    }

    /**
     * 设置URL
     * @param type $id 需要更新的ID
     */
    private static function setUrl($id) {
        $existUrl = self::db()->fetch('SHOW columns FROM ' . self::$modelPrefix . self::$table . ' WHERE Field = :field;', array('field' => self::$fieldPrefix . 'url'));
        if (!empty($existUrl)) {
            $url = self::url(MODULE . '-view', array('id' => $id));
            return self::db(self::$table)->where(self::$fieldPrefix . 'id = :id')->update(array(self::$fieldPrefix . 'url' => $url, 'noset' => array('id' => $id)));
        }
    }

    /**
     * 快速构造内容分页
     * @param array $sql 结构内容如下：
     * count => 一个完整的SQL count查询。用户获取本当前内容的总数量 如：SELECT count(*) TABLE WHERE id = :id
     * normal => 结合上面的SQL。这部分是分类的。如： SELECT * TABLE WHERE id = :id
     * param => 预处理参数。如果SQL语句中有占位符，此处也应该调用。如: array('id' => $id)
     * page => '分页输出数量'
     * style => '分页的样式，具体参考\Expand\Page分页类'
     * LANG => '分页的语言设置，同上'
     * 上面说的可能不太好理解。有如下SQL：
     * $sql = SELECT %s FROM user WHERE user_id = :user_id ORDER BY user_id DESC
     * $param = array('user_id' => $uid);
     *
     * 最终可以这样调用本方法：
     * \Model\Content::listContent(array('count' => sprintf($sql, 'count(*)'), 'normal' => sprintf($sql, '*'), 'param' => $param))
     *
     * @return array 结果返回：处理好的 列表二维数组和 一个分类超链接 还有分页的对象
     */
    public static function quickListContent(array $sql = array('count' => '', 'normal' => '', 'param' => array())) {
        $sql = array_merge(['param' => array(), 'page' => '10', 'style' => [], 'LANG' => []], $sql);
        $page = new \Expand\Page();
        $page->style = $sql['style'];
        $page->LANG = $sql['LANG'];
        $page->listRows = $sql['page'];
        $count = self::db()->fetch($sql['count'], $sql['param']);
        $total = $count === false ? '0' : current($count);
        $page->total($total);
        $page->handle();
        $list = self::db()->getAll("{$sql['normal']} LIMIT {$page->firstRow}, {$page->listRows}", $sql['param']);
        return array('list' => $list, 'page' => $page->show(), 'pageObj' => $page);
    }

    /**
     * 快速插入方法，适用不能调用db方法的地方使用
     * @param $table 表名
     * @param $data 数据
     */
    public static function insert($table, $data){
        return self::db($table)->insert($data);
    }

}
