<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\shop\model;

use think\Model;

class UserModel extends Model
{
    /**
     * @Author: ldm
     * @FunctionName:所有代理商的查询
     * @Date: 2018/6/2 0002 16:43
     *
     */
  public function agentSelete($where=''){
      $where['u.user_type']=2;
      $where['u.is_delete']=1;
      $data=$this->alias('u')->field('u.*,ua.address')
          ->join('user_agent ua','ua.user_id=u.id','left')
          ->where($where)->select()->toArray();
      if($data){
       return $data;
      }else{
       return false;
    }
  }

    /**
     * @Author: ldm
     * @FunctionName:代理商的添加
     * @Date: 2018/6/2 0002 17:29
     *
     */
  public function agentAdd($agen_data){
      $list=[];
      $result=[];
      $agen_data['user_pass']= cmf_password($agen_data['user_pass']);
      $agen_data['create_time']= time();
      $agen_data['user_type']= 2;
      self::startTrans();
      try {
          $agen= $this->allowField(true)->save($agen_data);
          if($agen==false){
            $result=array('code'=>0,'msg'=>'用户表user添加失败');
          }
          $user_id=$this->id;
          $user_agent=new UserAgentModel();
          $list['address']=$agen_data['address'];
          $list['create_time']= time();
          $list['user_id']= $user_id;
          $status= $user_agent->allowField(true)->save($list);
          if($status==false){
             $result= array('code'=>0,'msg'=>'user_agent添加失败');
          }
          self::commit();
      }catch (\Exception $e){
          self::rollback();
          return $result;
      }
      return $result= array('code'=>1,'msg'=>'添加成功');
  }

    /**
     * @Author: ldm
     * @FunctionName:一个代理商的查询
     * @Date: 2018/6/4 0004 09:25
     *
     */
    public function agentUser($where=""){
        $where['u.user_type']=2;
        $where['u.is_delete']=1;
        $data=$this->alias('u')->field('u.*,ua.address')
            ->join('user_agent ua','ua.user_id=u.id')
            ->where($where)->find()->toArray();
        if($data){
            return $data;
        }else{
            return false;
        }
    }

    /**
     * @Author: ldm
     * @FunctionName:代理商的修改
     * @Date: 2018/6/4 0004 09:45
     *
     */
    public function agentEdit($agen_data){
        $list=[];
        $result=[];
        self::startTrans();
        try {
        $user_agent=new UserAgentModel();
        $agent=$user_agent->where(['user_id'=>$agen_data['id']])->find()->toArray();
        if($agent['address']!=$agen_data['address']){
            $list['address']=$agen_data['address'];
            $list['user_id']= $agen_data['id'];
            $status= $user_agent->isUpdate(true,['user_id'=>$agen_data['id']])->save($list);
            if($status==false){
                $result= array('code'=>0,'msg'=>'user_agent编辑失败');
            }
        }
          unset($agen_data['address']);
            $user= $this->isUpdate(true)->save($agen_data);
            if($user==false){
                $result=array('code'=>0,'msg'=>'用户表user编辑失败');
            }
            self::commit();
        }catch (\Exception $e){
            self::rollback();
            return $result;
        }
        return $result= array('code'=>1,'msg'=>'编辑成功');
    }

}