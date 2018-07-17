<?php
/**
 * 功能说明：微信支付
 *
 * @date: 2018年5月12日下午5:20:13
 * @author: Hhb
 */

namespace Three;


class Mall{
    /**
     * 扫码支付
     * @date: 2018年7月2日上午11:26:49
     * @author: Hhb
     * @param unknown $order_info array('id'=>'','user_id'=>'','order_name'=>'','order_code'=>'','money'=>'');
     */
    public static function createNativePay($order_info){
        // 统一下单
        $input = new \WxPayUnifiedOrder();
        $body = isset($order_info['order_name']) ? $order_info['order_name'] : '微信扫码支付';
        $product_id = cmf_encode($order_info['id'].'-'.$order_info['user_id']);
        $attach = cmf_encode($order_info['in_code'].'-'.$order_info['id'].'-'.$order_info['user_id']);
        $trade_no = isset($order_info['in_code']) ? $order_info['in_code'] : cmf_create_order_code();
        $money = isset($order_info['in_money']) ? $order_info['in_money'] : 0;
        
        $input->SetBody($body);
        $input->SetOut_trade_no($trade_no);
        $input->SetTotal_fee($money * 100);
        $input->SetProduct_id($product_id);
        $input->SetAttach($attach);
        $input->SetGoods_tag('pay');
        $input->SetTime_start(date("YmdHis"));
        $input->SetNotify_url('http://' . $_SERVER['HTTP_HOST'] . '/user/Pay/wxPayBack');
        $input->SetTrade_type("NATIVE");
        $result = \WxPayApi::unifiedOrder($input);
        if(isset($result["code_url"])){
            return $result["code_url"];
        }else{
            return array('status'=>0,'msg'=>$result['err_code_des'],'value'=>'');
        }
        
    }
    /**
     * JSAPI支付
     * @date: 2018年7月2日上午9:53:07
     * @author: Hhb
     * @param unknown $order_info array('id'=>'','user_id'=>'','order_name'=>'','order_code'=>'','money'=>'');
     */
    public static function createJsApiPay($order_info){
        Vendor('WxPay.WxPay#JsApiPay');
        // 获取用户openid
        $tools = new \JsApiPay();
        $openIds = session('self_openid');//$tools->GetOpenid();
        $openId = !empty($openIds) ? json_decode($openIds,true)['openid'] : '';
        // 统一下单
        $body = isset($order_info['order_name']) ? $order_info['order_name'] : '微信JS支付';
        $attach = cmf_encode($order_info['in_code'].'-'.$order_info['id'].'-'.$order_info['user_id']);
        $trade_no = isset($order_info['in_code']) ? $order_info['in_code'] : cmf_create_order_code();
        $money = isset($order_info['in_money']) ? $order_info['in_money'] : 0;
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($body);
        $input->SetAttach($attach);
        $input->SetOut_trade_no($trade_no);
        $input->SetTotal_fee($money * 100);
        $input->SetTime_start(date("YmdHis"));
        //$input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag('pay');
        $input->SetNotify_url('http://' . $_SERVER['HTTP_HOST'] . '/user/Pay/wxPayBack');
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);
        if(!isset($order['prepay_id'])){
            return array('status'=>0,'msg'=>$order['err_code_des'],'value'=>'');
        }
        $jsApiParameters = $tools->GetJsApiParameters($order);
        $obj = json_decode($jsApiParameters, true);
        return array('status'=>1,'msg'=>'ok','value'=>$obj);
    }
}