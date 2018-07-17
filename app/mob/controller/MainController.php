<?php
/**
 * 后台主页主控制器
 *
 * @date: 2018年4月12日上午9:17:41
 * @author: Hhb
 */
namespace app\mob\controller;


class MainController extends AdminComController
{
    /**
     * 后台首页
     * @date: 2018年4月12日上午9:26:09
     * @author: Hhb
     */
    public function index()
    {
        return $this->fetch();
    }
}
