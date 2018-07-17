<?php
/**
 * 支付回调控制器
 * 功能说明：支付中间页面控制器制器。
 *
 * @date: 2018年5月12日下午4:40:37
 * @author: Hhb
 */

namespace app\user\controller;

use app\user\service\PayService;
use app\user\model\UserRechargeModel;
use Three\Mall;
use cmf\controller\HomeBaseController;

class PayController extends HomeBaseController{
    public function _initialize(){
        vendor('Alipay.Corefunction');
        vendor('Alipay.Md5function');
        vendor('Alipay.Notify');
        vendor('Alipay.Submit');
    }
    public function qrcode(){
        $url = urldecode($_GET["data"]);
        vendor('phpqrcode.phpqrcode');
        \QRcode::png($url);
    }
    /**
     * 后台系统充值
     * @date: 2018年7月9日上午10:49:38
     * @author: Hhb
     */
    public function sysPay(){
        $user_id = $this->request->param('id',0,'intval');
        $money = $this->request->param('money',0);
        if(empty($user_id) || empty($money)){
            $this->error('参数异常');
        }
        $order_code = cmf_create_order_code();
        $data = array(
            'user_id'=>$user_id,
            'in_code'=>$order_code,
            'in_money'=>$money,
            'in_type'=>3
        );
        $recharge_model = new UserRechargeModel();
        $re = $recharge_model->addRecharge($data);
        if($re['status'] != 1){
            $this->error($re['msg']);
        }
        $param = array(
            'total_fee'=>$money,
            'action_user'=>cmf_get_current_admin_id()
        );
        $re = PayService::dealNotifyFn('pt',$order_code,$param);
        if($re){
            $this->success('充值成功');
        }else{
            $this->error('充值失败');
        }
    }
    /**
     * 充值页面
     * @date: 2018年7月3日上午1:26:18
     * @author: Hhb
     * @return mixed|string
     */
    public function recharge(){
        $user=cmf_get_current_user();
        if(empty($user)){
            $this->redirect(url('mshop/Login/login'));
        }
        if($user['user_type'] == 2){
            //代理
            $url = cmf_url('magent/Index/index');
        }elseif($user['user_type']==3){
            //商户
            $url = cmf_url('mshop/Index/index');
        }
        $this->assign('url', $url);
        return $this->fetch();
    }
    /**
     * JSAPI异步支付
     * @date: 2018年7月3日上午1:26:05
     * @author: Hhb
     */
    public function wxJsPay(){
        $user_id = cmf_get_current_user_id();
        if(empty($user_id)){
            $this->error('请先登录');
        }
        if(!cmf_is_wechat()){
            $this->error('请在微信内访问');
        }
        //1.创建本地订单
        $money = $this->request->param('money',0);
        $order_code = cmf_create_order_code();
        $data = array(
            'user_id'=>$user_id,
            'in_code'=>$order_code,
            'in_money'=>$money,
            'in_type'=>1
        );
        $recharge_model = new UserRechargeModel();
        $re = $recharge_model->addRecharge($data);
        if($re['status'] != 1){
            $this->error($re['msg']);
        }
        //2.JSSDK调用支付(JSAPI接口参数)
        $order_info = array(
            'id'=>$re['value'],
            'user_id'=>$user_id,
            'order_name'=>'账户充值',
            'in_code'=>$order_code,
            'in_money'=>$money,
        );
        $re = Mall::createJsApiPay($order_info);
        if($re['status'] != 1){
            $this->error('微信支付繁忙');
        }
        $this->success('ok','',$re['value']);
    }
    /**
     * 支付宝支付异步通知页面
     * @date: 2018年5月12日下午4:42:10
     * @author: Hhb
     */
    public function notifyurl(){
        //这里还是通过C函数来读取配置项，赋值给$alipay_config
        $alipay_config = config('alipay_config');
        //计算得出通知验证结果
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if ($verify_result) {
            //验证成功
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            $out_trade_no = $_POST['out_trade_no'];      //商户订单号
            $trade_no = $_POST['trade_no'];          //支付宝交易号
            $trade_status = $_POST['trade_status'];      //交易状态
            $total_fee = $_POST['total_fee'];         //交易金额
            $notify_id = $_POST['notify_id'];         //通知校验ID。
            $notify_time = $_POST['notify_time'];       //通知的发送时间。格式为yyyy-MM-dd HH:mm:ss。
            $buyer_email = $_POST['buyer_email'];       //买家支付宝帐号；
            $parameter = array(
                "out_trade_no" => $out_trade_no, //商户订单编号；
                "trade_no" => $trade_no,     //支付宝交易号；
                "total_fee" => $total_fee,    //交易金额；
                "trade_status" => $trade_status, //交易状态
                "notify_id" => $notify_id,    //通知校验ID。
                "notify_time" => $notify_time,  //通知的发送时间。
                "buyer_email" => $buyer_email,  //买家支付宝帐号；
            );
            if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                PayService::dealNotifyFn('alipay',$out_trade_no,$parameter,$_POST);
//                $paylog=new PayLogModel();
//                $paylog->updateSite($out_trade_no,$total_fee);

            }
            echo "success";        //请不要修改或删除
        } else {
            //验证失败
            echo "fail";
        }
    }
    
    /**
     * 支付宝支付同步跳转页面
     * @date: 2018年5月12日下午5:07:06
     * @author: Hhb
     */
    public function returnurl(){
        $user=cmf_get_current_user();
        if($user['user_type'] == 2){
            //代理
            $url = cmf_url('agent/Index/balance');
        }elseif($user['user_type']==3){
            //商户
            $url = cmf_url('shop/Fund/index');
        }
        //头部的处理跟上面两个方法一样，这里不罗嗦了！
        $alipay_config = config('alipay_config');
        $alipayNotify = new \AlipayNotify($alipay_config);//计算得出通知验证结果
        $verify_result = $alipayNotify->verifyReturn();
        if (!$verify_result) {
            $this->redirect($url);
        }
    
        //验证成功
        //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
        $out_trade_no = $_GET['out_trade_no'];      //商户订单号
        $trade_no = $_GET['trade_no'];          //支付宝交易号
        $trade_status = $_GET['trade_status'];      //交易状态
        $total_fee = $_GET['total_fee'];         //交易金额
        $notify_id = $_GET['notify_id'];         //通知校验ID。
        $notify_time = $_GET['notify_time'];       //通知的发送时间。
        $buyer_email = $_GET['buyer_email'];       //买家支付宝帐号；
    
        $parameter = array(
            "out_trade_no" => $out_trade_no,      //商户订单编号；
            "trade_no" => $trade_no,          //支付宝交易号；
            "total_fee" => $total_fee,         //交易金额；
            "trade_status" => $trade_status,      //交易状态
            "notify_id" => $notify_id,         //通知校验ID。
            "notify_time" => $notify_time,       //通知的发送时间。
            "buyer_email" => $buyer_email,       //买家支付宝帐号
        );
        if (($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS')) {
            if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
                PayService::dealNotifyFn('alipay',$out_trade_no,$parameter,$_GET);
            }
            $this->redirect($url);
            //header('Location: http://' . $_SERVER['HTTP_HOST'] . $url);//跳转到配置项中配置的支付成功页面；
        } else {
            $this->error("支付失败！ trade_status=" . $_GET['trade_status']);
            //跳转到配置项中配置的支付失败页面；
        }
    }
    /**
     * 微信支付回调
     * @date: 2018年5月12日下午6:34:53
     * @author: Hhb
     */
    public function wxPayBack(){
        $notify = new PayNotifyCallBack();
        $notify->Handle(false);
    }
}
Vendor('WxPay.WxPay#Api');
Vendor('WxPay.WxPay#Notify');

class PayNotifyCallBack extends \WxPayNotify{
    //查询订单
    public function Queryorder($transaction_id){
        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = \WxPayApi::orderQuery($input);
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg) {
        //Log::DEBUG("call back:" . json_encode($data));
        $notfiyOutput = array();

        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            return false;
        }

        //查询订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"])){
            $msg = "订单查询失败";
            return false;
        }

        // 自定义处理事件
        PayService::dealNotifyFn('wx',$data['out_trade_no'],$data);
        return true;
    }

}