<?php
/**
 * 支付服务类
 *
 * @date: 2018年7月2日上午11:22:00
 * @author: Hhb
 */
namespace app\user\service;

use think\Db;
use app\user\model\UserRechargeModel;
use app\user\model\UserModel;
use app\user\model\UserBillModel;

class PayService{
    /**
     * 充值支付回调处理
     * @date: 2018年7月2日上午11:22:16
     * @author: Hhb
     * @param unknown $type
     * @param unknown $order_code
     * @param array $parameter
     * @param array $ext
     */
    public static function dealNotifyFn($type,$order_code,$parameter = array(),$ext = array()){
        if(empty($order_code)){
            return false;
        }
        $time = time();
        $amount = 0;
        if($type == 'wx' || $type == 1){
            $amount = !empty($parameter['total_fee']) ? $parameter['total_fee']/100 : 0;
        }else {
            $amount = !empty($parameter['total_fee']) ? $parameter['total_fee'] : 0;
        }
        $order_model = new UserRechargeModel();
        $order = $order_model->getInfoByCode($order_code);
        if($order['status'] == 1){
            return false;
        }
        Db::startTrans();
        //1.处理订单
        $data = array(
            'more'=>!empty($parameter) ? json_encode($parameter,JSON_UNESCAPED_UNICODE) : '',
            'pay_log'=>!empty($ext) ? json_encode($ext,JSON_UNESCAPED_UNICODE) : '',
            'status'=>1
        );
        $re = $order_model->filishRecharge($order_code,$data);
        if($re['status'] != 1){
            Db::rollback();
            return false;
        }
        //2.处理用户资金
        $user_id = $order['user_id'];
        $user_model = new UserModel();
        $re = $user_model->setUserBalance($user_id, $amount, 'inc');
        if(empty($re)){
            Db::rollback();
            return false;
        }
        //3.记录资金明细
        $bill_model = new UserBillModel();
        $user = $user_model->getInfoById($user_id);
        $data = array(
            'user_id'=>$user_id,
            'bill_type'=>1,
            'bill_kind'=>'pay',
            'curr_amount'=>$amount,
            'curr_balance'=>$user['balance'],
            'link_id'=>$order_code,
            
        );
        $re = $bill_model->addData($data);
        if(empty($re)){
            Db::rollback();
            return false;
        }
        Db::commit();
        return true;
    }
}