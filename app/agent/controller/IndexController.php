<?php
/**
 * Created by PhpStorm.
 * User: ldm
 * Date: 2018/6/4 0004
 * Time: 10:54
 */
namespace app\agent\controller;
use app\agent\model\RegionsModel;
use app\agent\model\UserModel;
use app\shop\model\UserShopModel;
use app\user\model\UserBillModel;
use app\user\model\UserCodeModel;
use app\user\model\UserRechargeModel;
use cmf\controller\UserBaseController;
use think\Db;
use Three\Mall;

class IndexController extends UserBaseController
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        if (cmf_is_mobile()){
            echo "请在手机端访问本页面";
            $this->redirect(url('mshop/Login/login'));
        }
        $this->assign('user',session('user'));
    }

    /**
     * @Author: ldm
     * @FunctionName:代理商首页
     * @Date: 2018/6/4 0004 10:55
     *
     */
  public function index(){
      $user=new UserModel();
      if($this->request->isPost()){
          $data=$this->request->param();
          if(isset($data['file'])){
              unset($data['file']);
          }
          $result = $this->validate($data, 'Agent');
          if ($result !== true) {
              $this->error($result);
          }
          $status=$user->agentEdit($data);
          if($status['code']==0){
              $this->error($status['msg']);
          }
          return $this->success('编辑成功!', url('Index/index'));
      }
    $id=cmf_get_current_user_id();//代理商ID

    $where['u.id']=$id;
    $data=$user->agentUser($where);
    $this->assign('data',$data);
    return $this->fetch();
  }

    /**
     * @Author: ldm
     * @FunctionName:代理商的商户添加
     * @Date: 2018/6/4 0004 12:15
     *
     */
  public function add(){
      $province=Db::name('regions')->where(array('parent_id'=>'00000000-0000-0000-0000-000000000000'))->select()->toArray();
      $this->assign('regions',$province);
   return $this->fetch();
  }

    /**
     *根据id获取地区的下级
     * @Author: Lcs
     * @Date: 2018/4/23 19:01
     */
    public function address()
    {
        $id = $this->request->param('id');
        $regionsModel = new RegionsModel();
        $result = $regionsModel->where(array('parent_id' => $id))->select()->toArray();
        echo json_encode($result);
    }

    /**
     * @Author: ldm
     * @FunctionName:代理商添加商户
     * @Date: 2018/6/4 0004 12:58
     *
     */
  public function addPost(){
      $param=$this->request->param();
      if(isset($param['file'])){
          unset($param['file']);
      }
      $usershop=new UserShopModel();
      $param['referrer']=cmf_get_current_user_id();//代理商ID
      $res=$usershop->add($param,$site='audit',$user_type=3,$user_status=2);
      if ($res['code']==0){
          $this->error($res['msg']);
      }else{
          $this->success('添加成功');
      }
  }

    /**
     * @Author: ldm
     * @FunctionName:代理商的商户列表
     * @Date: 2018/6/4 0004 13:57
     *
     */
    public function merchant(){
        $agent_id=cmf_get_current_user_id();//代理商id
        $user_data=$this->request->param('','');
        isset($user_data['shop_name'])&&!empty($user_data['shop_name'])?$where['a.shop_name']=array('like','%'.$user_data['shop_name'].'%'):'';
        isset($user_data['principal'])&&!empty($user_data['principal'])?$where['a.principal']=array('like','%'.$user_data['principal'].'%'):'';
        isset($user_data['site'])&&!empty($user_data['site'])?$where['a.site']=$user_data['site']:'';
        $where['a.referrer']=$agent_id;
        $where['a.is_delete']=1;
        $usershop=new UserShopModel();
       $shop_data= $usershop->refer($where);
       $this->assign('user_shop',$shop_data);
        $this->assign('page',$shop_data->render());
       return $this->fetch();
   }

    /**
     * @Author: ldm
     * @FunctionName:商户的详细信息
     * @Date: 2018/6/4 0004 14:32
     *
     */
   public function shopIndex(){
    $id=$this->request->param('id');
       $usershop=new UserShopModel();
       $where['a.is_delete']=1;
       $where['a.id']=$id;
       $shop_data= $usershop->findOut($where);
       $province=Db::name('regions')->where(['id'=>$shop_data['province']])->find();
       $city=Db::name('regions')->where(['id'=>$shop_data['city']])->find();
       $town=Db::name('regions')->where(['id'=>$shop_data['district']])->find();
       $this->assign('regions',$province);
       $this->assign('city',$city);
       $this->assign('town',$town);
       $this->assign('shop_data',$shop_data);
       return $this->fetch();
   }

    /**
     * @Author: ldm
     * @FunctionName:代理商对商户的修改
     * @Date: 2018/6/4 0004 15:17
     *
     */
   public function shopEdit(){
       $id=$this->request->param('id');
       if (isset($id)){
           $usershop=new \app\agent\model\UserShopModel();
           $where['a.id']=$id;
           $res=$usershop->findOut($where);
           $this->assign('res',$res);
           $province=Db::name('regions')->where(['parent_id'=>'00000000-0000-0000-0000-000000000000'])->select()->toArray();
           $city=Db::name('regions')->where(['parent_id'=>$res['province']])->select();
           $town=Db::name('regions')->where(['parent_id'=>$res['city']])->select();
           $this->assign('regions',$province);
           $this->assign('city',$city);
           $this->assign('town',$town);

           return $this->fetch();
       }
   }

    /**
     * @Author: ldm
     * @FunctionName:代理商商户修改的提交
     * @Date: 2018/6/4 0004 16:09
     *
     */
   public function shopEditPost(){
       $param=$this->request->param();
           if(isset($param['file'])){
               unset($param['file']);
           }
       $id=$param['id'];
       unset($param['id']);
       $usershop= new \app\agent\model\UserShopModel();
       $param['site']='audit';
       $res=$usershop->updater($param,$id);
       if ($res['code']==1){
           $this->success('修改成功',url('Index/merchant'));
       }else{
           $this->error($usershop->getError());
       }
   }

    /**
 * 图片上传
 * @date: 2018年5月23日上午11:32:04
 * @author: Hhb
 */
    public function imgUpload()
    {
        $file = $this->request->file('file');
        $path = $this->request->param('path');
        if (empty($file)) {
            $this->error("服务器繁忙！", '', [
                'code' => 0,
                "msg" => '服务器繁忙！',
                "data" => "",
                "url" => ''
            ]);
        }
        $result = $file->validate([
            'ext' => 'jpg,jpeg,png,gif',
            'size' => 1024 * 1024 * 20
        ])->move(ROOT_PATH . DS . 'upload' . DS . $path . DS, $path.'_'.date('His') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT));

        if ($result) {
            $img_save_name = str_replace('//', '/', str_replace('\\', '/', $result->getSaveName()));
            $img = $path . '/' . $img_save_name;
            $this->success("上传成功", '', [
                'code' => 1,
                "msg" => "上传成功",
                "data" => ['file' => $img],
                "url" => ''
            ]);
        } else {
            $this->error("上传失败！", '', [
                'code' => 0,
                "msg" => $file->getError(),
                "data" => "",
                "url" => ''
            ]);
        }
    }

    /**
     * @Author: ldm
     * @FunctionName:代理商余额管理
     * @Date: 2018/6/5 0005 10:32
     *
     */
    public function balance(){
        $pay=new UserRechargeModel();
       $user=new UserModel();
       $where=[];
       $data=$this->request->param('','');
        !empty($data['start'])?$start=strtotime('-1 day',strtotime($data['start'].'23:59:59')):$start=0;
        !empty($data['end'])?$end=strtotime('+1 day',strtotime($data['end'].'0:0:0')):$end=time();
        if(!empty($data['start'])||!empty($data['end'])){
            $where['create_time']=array('between',''.$start.','.$end.'');
        }
       $where_user['u.id']=cmf_get_current_user_id();
       $user_info=$user->agentUser($where_user);
       $balance=$pay->refer(cmf_get_current_user_id(),$where);
       $balance->appends($data);
       $pay_type=config('pay_type');
       $this->assign('type','pay');
       $this->assign('list',$balance);
       $this->assign('pay_type',$pay_type);
       $this->assign('user_info',$user_info);
       $this->assign('page',$balance->render());
       return $this->fetch();
    }

    /**
     *
     * @Author: Ldm
     * @FunctionName:流水明细
     * @Date:2018/7/12 0012
     */
    public function bill(){
        $where=[];
        $user=new UserModel();
        $user_id=cmf_get_current_user_id();
        $where_user['u.id']=$user_id;
        $user_info=$user->agentUser($where_user);
        $where['user_id']=$user_id;
        $bill=new UserBillModel();
        $list=$bill->getPageList($where);
        $this->assign('user_info',$user_info);
        $bill_kind=config('bill_kind');
        $this->assign('page',$list->render());
        $this->assign('bill',$list);
        $this->assign('type','all');
        $this->assign('bill_kind',$bill_kind);

        return  $this->fetch('balance');
    }

    /**
     * @Author: ldm
     * @FunctionName:代理商充值
     * @Date: 2018/6/5 0005 10:44
     *
     */
    public function Recharge(){
     return $this->fetch();
    }
    /**
     * 用户充值
     * @date: 2018年5月13日下午2:51:03
     * @author: Hhb
     */
    public function setOrder()
    {
        $param = $this->request->param();
        $data=[];
        $data['user_id']=cmf_get_current_user_id();
        if($param['pay_type']=='weixin'){
           $data['in_type']=1;
        }else{
            $data['in_type']=2;
        }
        $data['in_code']=cmf_create_order_code();
        $data['in_money']=$param['money'];
        $usergoodsorder=new UserRechargeModel();
        $re=$usergoodsorder->addRecharge($data);
        if ($re['status'] != 1) {
            $this->error($re['msg']);
        } else {
            $this->success($re['msg'], '', $re['value']);
        }
    }

    public function pay()
    {
        $type = $this->request->param('type');
        $order_id = $this->request->param('orderid', 0, 'intval');
        if (empty($type) || empty($order_id) || !in_array($type, ['weixin', 'alipay'])) {
            $this->redirect(url('agent/Index/balance'));
        }
//        $usergoodsorder =new PayLogModel();
//        $order_info = $usergoodsorder->getUserOrderById($order_id, cmf_get_current_user_id());
        $usergoodsorder=new UserRechargeModel();
        $order_info=$usergoodsorder->getUserOrderById($order_id,cmf_get_current_user_id());
        if (empty($order_info)) {
            $this->redirect(url('agent/Index/balance'));
        }
        if ($type == 'alipay') {
            $this->alipay($order_info);
        } elseif ($type == 'weixin') {
            return $this->weixinpay($order_info);
        }
    }
    /**
     * 支付宝支付
     * @date: 2018年5月12日下午5:13:25
     * @author: Hhb
     * @param unknown $order_id
     */
    protected function alipay($order_info)
    {
        vendor('Alipay.Corefunction');
        vendor('Alipay.Md5function');
        vendor('Alipay.Notify');
        vendor('Alipay.Submit');
        $alipay_config = config('alipay_config');
        $payment_type = "1"; //支付类型 //必填，不能修改
        $notify_url = config('alipay.notify_url'); //服务器异步通知页面路径
        $return_url = config('alipay.return_url'); //页面跳转同步通知页面路径
        $seller_email = config('alipay.seller_email');//卖家支付宝帐户必填

        $out_trade_no = $order_info['in_code'];//商户订单号 通过支付页面的表单进行传递，注意要唯一！
        $subject = '测试商品';//$order_info['order_name']  //订单名称 //必填 通过支付页面的表单进行传递
        $total_fee = 0.01;//$order_info['sum_money']   //付款金额  //必填 通过支付页面的表单进行传递
        $body = '';  //订单描述 通过支付页面的表单进行传递
        $show_url = '';  //商品展示地址 通过支付页面的表单进行传递
        $anti_phishing_key = "";//防钓鱼时间戳 //若要使用请调用类文件submit中的query_timestamp函数
        $exter_invoke_ip = get_client_ip(); //客户端的IP地址

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => "create_direct_pay_by_user",
            "partner" => trim($alipay_config['partner']),
            "payment_type" => $payment_type,
            "notify_url" => $notify_url,
            "return_url" => $return_url,
            "seller_id" => $seller_email,
            "out_trade_no" => $out_trade_no,
            "subject" => $subject,
            "total_fee" => $total_fee,
            "body" => $body,
            "show_url" => $show_url,
            "anti_phishing_key" => $anti_phishing_key,
            "exter_invoke_ip" => $exter_invoke_ip,
            "_input_charset" => trim(strtolower($alipay_config['input_charset']))
        );
        //建立请求
        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter, "post", "正在跳转支付....");
        header("Content-Type:text/html;charset=utf-8");
        echo $html_text;
    }
    /**
     * 微信支付
     * @date: 2018年5月12日下午5:13:49
     * @author: Hhb
     */
    protected function weixinpay($order_info)
    {
//         dump($order_info);exit;
        Vendor('WxPay.WxPay#JsApiPay');
        $url = Mall::createNativePay($order_info);
        if (empty($url)) {
            $this->redirect(url('gent/Index/balance'));
        }
        $user=cmf_get_current_user();
        if ($user['user_type'] == 2) {
            $location_url = cmf_url('agent/index/index');
        } else {
            $location_url = cmf_url('shop/funed/index');
        }
        $this->assign('location_url', $location_url);
        $this->assign('order_info', $order_info);
        $this->assign('url', $url);
        $this->assign('orderid', $order_info['id']);
        return $this->fetch('weixin');
    }

    /**
     *
     * @Author: Ldm
     * @FunctionName:代理商密码修改
     * @Date:2018/6/7 0007
     */
    public function pwd(){
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if(empty($data['old_password'])){
                $this->error('旧密码不能为空');
            }
            if(empty($data['password'])){
                $this->error('新密码不能为空');
            }
            if(empty($data['repassword'])){
                $this->error('确认新密码不能为空');
            }
            $usermodel = new UserModel();
            $result = $usermodel->editPassword($data);
            switch ($result) {
                case 1:
                    $this->error('您两次输入的新密码不相同，请重新输入!');
                    break;
                case 2:
                    $this->error('您的旧密码输入错误,请重新输入!');
                    break;
                case 0:
                    $this->success('修改成功!', url('index'));
                    break;
            }
        }
    return $this->fetch();
    }
    /**
     *
     * @Author: Ldm
     * @FunctionName:检查电话是否存在
     * @Date:2018/6/9 0009
     */
    public function is_mobile(){
        $mobile=$this->request->param();
        $user_model=new UserModel();
        $user=$user_model->where(['mobile'=>$mobile['mobile']])->find();
        if(!empty($user)){
            $this->error('此账号已经存在');
        }
        return $this->success('账号不存在');
    }

    /**
     *
     * @Author: Ldm
     * @FunctionName:代理给加盟商分配码段
     * @Date:2018/7/12 0012
     */
    public function shopCode(){
        $data=$this->request->param();
        if(empty($data['start_num'])){
            $this->error('起始码段不能为空');
        }
        if(empty($data['end_num'])){
            $this->error(' 截止码段不能为空');
        }
        if(empty($data['user_id'])){
            $this->error('商户不能为空');
        }
        $user_code=new UserCodeModel();
        $where['code_num']=array('between',''. $data['start_num'] .','.$data['end_num'] . '');
        $agent_id=cmf_get_current_user_id();
        $where['user_id']=$agent_id;
        $code_list= $user_code->getAgentBetween($where);
        if($code_list==false){
            $this->error('请输入正确的码段');
        }
        $ids=[];
        foreach ($code_list as $k){
            $ids[]=$k['id'];
        }
        $where_code['id']=array('in',$ids);
        $arrayData['status']=2;
        $arrayData['user_id']=$data['user_id'];
        $arrayData['parent_id']=$agent_id;
        $arrayData['fenpei_time']=time();
        $status= $user_code->setAllotCode($where_code,$arrayData);
        if($status==false){
            $this->error('请输入正确的码段');
        }
        return $this->success("分配成功");
    }
}