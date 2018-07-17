<?php
/**
 *
 * @Author: Lcs
 * @Date: 2018/6/5 16:43
 */

namespace app\user\controller;

use app\user\model\PayLogModel;
use app\user\model\UserRechargeModel;
use cmf\controller\AdminBaseController;

/**
 * Class AdminOrderController
 * @adminMenuRoot(
 *     'name'   =>'订单管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 30,
 *     'icon'   =>'th',
 *     'remark' =>'订单管理'
 * )
 * @package app\portal\controller
 * @Author: Lcs
 * @Date: 2018/6/5 16:44
 */
class AdminOrderController extends AdminBaseController
{
    /**
     * 订单列表
     * @adminMenu(
     *     'name'   => '订单列表',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '订单列表',
     *     'param'  => ''
     * )
     * @Author: Lcs
     * @Date: 2018/6/5 16:44
     */
    public function index(){
        $param=$this->request->param();
        $where=array();
        $likes=array();
        empty($param['user_type'])?'':$where['u.user_type']=$param['user_type'];
        empty($param['paymode'])?'':$where['p.in_type']=$param['paymode'];
        empty($param['mobile'])?'':$likes=array('u.mobile','%'.$param['mobile'].'%');
        !empty($param['start']) ? $start = strtotime('-1 day', strtotime($param['start'] . '23:59:59')) : $start = 0;
        !empty($param['end']) ? $end = strtotime('+1 day', strtotime($param['end'] . '0:0:0')) : $end = time();
        if (!empty($param['start']) || !empty($param['end'])) {
            $where['create_time'] = array('between', '' . $start . ',' . $end . '');
        }
        $paylog=new UserRechargeModel();
        $result=$paylog->getPageWhere($where,$likes);
        $result->appends($where);
        $result->appends($likes);
        $this->assign('res',$result);
        $this->assign('pay_type',config('pay_type'));
        return $this->fetch();
    }

    /**
     *订单详情
     * @return mixed
     * @Author: Lcs
     * @Date: 2018/6/29 10:05
     */
    public function details(){
        $id=$this->request->param('id');
        $paylog=new UserRechargeModel();
        $res=$paylog->findOut($id);
        $this->assign('res',$res);
        return $this->fetch();
    }
}