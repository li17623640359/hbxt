<?php
/**
 * Created by PhpStorm.
 * User: ldm
 * Date: 2018/6/5 0005
 * Time: 11:38
 */

namespace app\agent\model;


use think\Db;
use think\Model;

class PayLogModel extends Model
{
    /**
     * 创建订单
     * @date: 2018年5月13日下午2:50:30
     * @author: Hhb
     * @param unknown $param
     * @return number[]|string[]|number[]|string[]
     */
    public function createOrder($param)
    {
        if (empty($param)) {
            return array('status' => 0, 'code' => 'paramErr', 'msg' => '参数异常', 'value' => '');
        }
        $user = cmf_get_current_user();
        //订单总表
        $order_code = ordernum();
        $time = time();
        $arr = array(
            'order_code' => $order_code,
            'user_id' => $user['id'],
            'type' => $param['type'],
            'money' => $param['money'],
            'create_time' => $time,
            'update_time' => $time,
            'paymode'=>$param['pay_type'],
        );
        $this->db()->startTrans();
        $id = $this->db()->insertGetId($arr);
        if (empty($id)) {
            $this->db()->rollback();
            return array('status' => 0, 'code' => 'dbErr', 'msg' => '服务器繁忙A', 'value' => '');
        }
        $this->db()->commit();
        return array('status' => 1, 'code' => 'success', 'msg' => '下单成功', 'value' => array('id' => $id));
    }
    /**
     * 根据用户ID订单ID获取数据
     * @date: 2018年5月13日下午3:42:14
     * @author: Hhb
     * @param unknown $uid
     * @param unknown $id
     */
    public function getUserOrderById($id, $uid)
    {
        $result = $this->where(array('id' => $id, 'user_id' => $uid))->find();
        return $result;
    }

    /**
     * @Author: ldm
     * @FunctionName:订单的查询
     * @Date: 2018/6/5 0005 15:26
     *
     */
    public function payIndes($where='',$paginate=30){
    $pay_list=$this->alias('pl')->field('pl.* ,u.user_nickname')
        ->join('user u','u.id=pl.user_id')
        ->order('pl.create_time','desc')
        ->where($where)->paginate($paginate);
    if(empty($pay_list)){
        return false;
      }
      return $pay_list;
    }

    /**
     *
     * @param $id
     * @param $a
     * @return string
     * @Author: Ldm
     * @FunctionName:
     * @Date:2018/6/6 0006
     */
    public function index($id,$a){
        return 'a';

    }
}