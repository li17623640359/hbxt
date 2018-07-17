<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/28 0028
 * Time: 上午 10:17
 */
namespace app\magent\controller;
use app\agent\model\UserActivityModel;
use app\agent\model\UserModel;
use app\shop\model\UserShopModel;
use app\user\model\UserBillModel;
use app\user\model\UserCodeExtModel;
use app\user\model\UserRechargeModel;
use app\user\service\ActivityService;
use cmf\controller\UserBaseController;
use think\Db;

class IndexController extends UserBaseController
{
    /**
     *
     * @Author: Ldm
     * @FunctionName:代理商手机端首页
     * @Date:2018/6/28 0028
     */
   public function index(){
       $agent_id=cmf_get_current_user_id();
       $user_ids=[];
       $user= new UserModel();
       $agent['u.id']=$agent_id;
       $userinfo= $user->agentUser($agent);
       $where['a.referrer']=$agent_id;
       $where['a.is_delete']=1;
       $usershop=new UserShopModel();
       $shop_data= $usershop->refer($where);
       $activity_service = new ActivityService();
       $users = $activity_service->getSelectUser('agent',  $agent_id);
       foreach ($users as $v){
           array_push($user_ids,$v['id']);
       }
       $model =new UserActivityModel();
       $res = $model->getAgentPageList($where,$user_ids);
       $count_activity=count($res);
       $count_shop=count($shop_data);
       $this->assign('count_activity',$count_activity);
       $this->assign('count_shop',$count_shop);
       $this->assign('userinfo',$userinfo);
       return $this->fetch();
   }

    /**
     *
     * @Author: Ldm
     * @FunctionName:代理商充值
     * @Date:2018/6/28 0028
     */
   public function recharge(){
//       $agent_id=cmf_get_current_user_id();
//       $balance=$this->request->param('balance');
//       $this->assign('agent_id',$agent_id);
//       $this->assign('balance',$balance);
       $location_url = cmf_url('magent/index/index');
       $this->assign('url', $location_url);
       return $this->fetch();
   }

    /**
     *
     * @Author: Ldm
     * @FunctionName:代理商个人设置
     * @Date:2018/6/28 0028
     */
      public function set(){
        return $this->fetch();
        }
    /**
     *
     * @Author: Ldm
     * @FunctionName:代理商个人中心
     * @Date:2018/6/28 0028
     */
  public function info(){
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
     *
     * @Author: Ldm
     * @FunctionName:代理商密码修改
     * @Date:2018/6/7 0007
     */
   public function updatepwd(){
           if ($this->request->isAjax()) {
               $data = $this->request->param();
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
     * @return array
     * @Author: Ldm
     * @FunctionName:验证旧密码是否正确
     * @Date:2018/6/28 0028
     */
    public function isPwd(){
        $pwd=$this->request->param('pwd');
        $user=new UserModel();
        $res=$user->where(array('id'=>cmf_get_current_user_id(),'is_delete'=>1))->find();
        $pwder=cmf_compare_password($pwd,$res['user_pass']);
        if ($pwder){
            return array('code'=>1,'msg'=>'ok');
        }else{
            return array('code'=>0,'msg'=>'密码错误');
        }
    }

    /**
     *
     * @return mixed
     * @throws \think\exception\DbException
     * @Author: Ldm
     * @FunctionName:代理商与他得下级商户的所有活动
     * @Date:2018/6/28 0028
     */
    public function activity(){
        $activity_name=$this->request->param('activity_name','');
        if($this->request->isAjax()&&$this->request->isPost()){
            $agent_id=cmf_get_current_user_id();
            $activity_name=$this->request->param('activity_name','');
            $user_ids=[];
            $where['a.referrer']=$agent_id;
            $where['a.is_delete']=1;
            isset($activity_name)&&!empty($activity_name)?$where['activity_name']=array('like','%'.$activity_name.'%'):'';
            $activity_service = new ActivityService();
            $users = $activity_service->getSelectUser('agent',  $agent_id);
            foreach ($users as $v){
                array_push($user_ids,$v['id']);
            }
            $model =new UserActivityModel();
            $res = $model->getAgentPageList($where,$user_ids);
            $this->success('ok','',['list' => $res]);
        }
        $this->assign('activity_name',$activity_name);
        return $this->fetch();
    }

    /**
     *
     * @Author: Ldm
     * @FunctionName:活动详情
     * @Date:2018/6/28 0028
     */
    public function detals(){
        $id = $this->request->param('id', 0, 'intval');
        $activity_model = new \app\user\model\UserActivityModel();
        $one = $activity_model->getDetailsById($id);
        $code_model =   new \app\user\model\UserCodeModel();
        $this->assign('one', $one);
        if($one['type'] == 1){
            $code = array();
            $res = $code_model->getActivityCodePageListById($id,$one['type']);
        } else{
            $res = $code_model->getActivityCodePageListById($id,$one['type']);
            if(!empty($res)){
                $code = $res;
                $code_ext_model = new UserCodeExtModel();
                $res = $code_ext_model->getActivityCodePageListById($id, $code['id']);
            }else{
                $code = array();
            }
        }
        $this->assign('code',$code);
        $this->assign('res',$res);
        $this->assign('page',$res->render());
        $activity_type = config('activity_type');
        $packet_type = config('packet_type');
        $this->assign('activity_type',$activity_type);
        $this->assign('packet_type',$packet_type);
        return $this->fetch();
    }

    /**
     *
     * @Author: Ldm
     * @FunctionName:红包详情
     * @Date:2018/7/3 0003
     */
    public function redPacket(){
        $id = $this->request->param('id', 0, 'intval');
        $code_num=$this->request->param('code_num','');
        $activity_model =new \app\user\model\UserActivityModel();
        $one = $activity_model->getDetailsById($id);
        $code_model =   new \app\user\model\UserCodeModel();
        $this->assign('one', $one);
        if($one['type'] == 1){
            $code = array();
            $res = $code_model->getActivityCodePageListById($id,$one['type'],'',$code_num);
        } else{
            $res = $code_model->getActivityCodePageListById($id,$one['type']);
            if(!empty($res)){
                $code = $res;
                $code_ext_model = new UserCodeExtModel();
                $res = $code_ext_model->getActivityCodePageListById($id, $code['id']);
            }else{
                $code = array();
            }
        }
        if($this->request->isAjax()&&$this->request->isPost()){
            $this->success('ok','',['list' => $res]);
        }
        $this->assign('code_num',$code_num);
        $this->assign('code',$code);
        $activity_type = config('activity_type');
        $packet_type = config('packet_type');
        $this->assign('activity_type',$activity_type);
        $this->assign('packet_type',$packet_type);
        return $this->fetch();
    }
    /**
     * @Author: ldm
     * @FunctionName:代理商的商户列表
     * @Date: 2018/6/4 0004 13:57
     *
     */
    public function shop(){
        $shop_name=$this->request->param('shop_name','');
        isset($shop_name)&&!empty($shop_name)?$where['a.shop_name']=array('like','%'.$shop_name.'%'):'';
        if($this->request->isAjax()&&$this->request->isPost()){
            $agent_id=cmf_get_current_user_id();//代理商id
            $where['a.referrer']=$agent_id;
            $where['a.is_delete']=1;
            $usershop=new UserShopModel();
            $shop_data= $usershop->mrefer($where);
            $this->success('ok','',['list' => $shop_data]);
        }
        $this->assign('shop_name',$shop_name);
        return $this->fetch();
    }

    /**
     *
     * @Author: Ldm
     * @FunctionName:单个商户详情
     * @Date:2018/6/28 0028
     */
    public function shopDetals(){
        $id=$this->request->param('id');
        $usershop=new UserShopModel();
        $where['a.is_delete']=1;
        $where['a.id']=$id;
        $shop_site=config('shop_site');
        $shop_data= $usershop->findOut($where);
        $province=Db::name('regions')->where(['id'=>$shop_data['province']])->find();
        $city=Db::name('regions')->where(['id'=>$shop_data['city']])->find();
        $town=Db::name('regions')->where(['id'=>$shop_data['district']])->find();
        $this->assign('regions',$province);
        $this->assign('city',$city);
        $this->assign('town',$town);
        $this->assign('shop_site',$shop_site);
        $this->assign('shop_data',$shop_data);
        return $this->fetch();
    }

    /**
     *
     * @return mixed|void
     * @Author: Ldm
     * @FunctionName:个人信息修改
     * @Date:2018/7/3 0003
     */
    public function updateInfo(){
        $user=new UserModel();
        if($this->request->isAjax()&&$this->request->isPost()){
            $data=$this->request->param();
            if(isset($data['file'])){
                unset($data['file']);
            }
            $result = $this->validate($data, 'Agent');
            if ($result !== true) {
                $this->error($result);
            }
            $data['id']=cmf_get_current_user_id();
            $status=$user->agentEdit($data);
            if($status['code']==0){
                $this->error($status['msg']);
            }
            return $this->success('编辑成功!');
        }
        $id=cmf_get_current_user_id();//代理商ID

        $where['u.id']=$id;
        $data=$user->agentUser($where);
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     *
     * @Author: Ldm
     * @FunctionName:图片上传
     * @Date:2018/7/3 0003
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
     *
     * @Author: Ldm
     * @FunctionName:充值记录
     * @Date:2018/7/3 0003
     */
    public function rechargeRecord(){
        $user=new UserModel();
        $user_money=$user->where(['id'=>cmf_get_current_user_id()])->find()->toArray();
        if ($this->request->isAjax()&&$this->request->isPost()){
            $pay=new UserRechargeModel();

            $balance=$pay->refer(cmf_get_current_user_id());
            $this->success('ok','',['list' => $balance]);
        }
        $this->assign('user_money',$user_money);
        return $this->fetch();
   }

    /**
     *
     * @return mixed
     * @Author: Ldm
     * @FunctionName:流水明细
     * @Date:2018/7/16 0016
     */
   public function bill(){
       $where=[];
       $bill=new UserBillModel();
       $user_id=cmf_get_current_user_id();
       $bill_kind=config('bill_kind');
       if($this->request->isAjax()&&$this->request->isPost()){
           $where['user_id']=$user_id;
           $list=$bill->getPageList($where);
           $this->success('ok','',['list' => $list]);
       }
       $this->assign('bill_kind',$bill_kind);
       return $this->fetch();
   }

    /**
     *
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @Author: Ldm
     * @FunctionName:余额管理
     * @Date:2018/7/16 0016
     */
   public function balance(){
       $user=new UserModel();
       $user_money=$user->where(['id'=>cmf_get_current_user_id()])->find()->toArray();
       $this->assign('user_money',$user_money);
        return $this->fetch();
   }
}