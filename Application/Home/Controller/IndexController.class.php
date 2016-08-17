<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use OT\DataDictionary;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends HomeController {

	//系统首页
    public function index(){
       $category = D('Category')->getTree();	//将select()出来的分类生成一个tree。
        $list    = D('Document')->lists(null);//一个select()
        //测试
        $where['id|name'] = array(1, 'pax', '_multi'=>true);
        //D('Document')->where($where)->field('id')->scope('test')->select();
        /*echo D('Document')->_sql()
         * SELECT * FROM `onethink_document` WHERE ( (`id` = 1) OR (`name` = 'pax') ) LIMIT 10 
         */
        $res = D('Document')->getField('title', 2);
        $data = array(
        	'uid' => 1,
        	'name' => 'test',
        	'title' => 'haha'
        );
        $model = D('Document');
        $model->create($data);
        $model->add();
        $this->assign('category',$category);//栏目
        $this->assign('lists',$list);//列表
        $this->assign('page',D('Document')->page);//分页

                 
        $this->display();
    }

}