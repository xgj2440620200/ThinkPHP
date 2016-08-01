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
        //$lists    = D('Document')->lists(null);//一个select()
        //测试
		$list = M('Document')->field(true)->select();
		$data = array(
			'uid' => 1,
			'name' => 'pax',
			'title' => '添加文档',
			'category_id' => 2,
			'description' => '给ot进行注释条件',
			'root' => 0,
			'pid' => 0,
			'modle_id' => 2,
			'type' => 2,
			'position' => 0,
			'link_id' => 0,
			'cover_id' => 0,
			'display' => 1,
		);
		$list = M('Document')->add($data);
        $this->assign('category',$category);//栏目
        $this->assign('lists',$list);//列表
        $this->assign('page',D('Document')->page);//分页

                 
        $this->display();
    }

}