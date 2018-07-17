<?php
/**
 * Created by PhpStorm.
 * User: ldm
 * Date: 2018/6/2 0002
 * Time: 16:33
 */

namespace app\mob\controller;



use app\agent\model\UserCodeModel;
use app\agent\model\UserModel;

use think\Db;

/**
 * Class MobAgentController
 *
 * @package app\mob\controller
 * @Author: ldm
 * @Date: abc
 */
class MobAgentController extends AdminComController
{
    /**
     * @Author: ldm
     * @FunctionName:代理商列表
     * @Date: 2018/6/2 0002 16:37
     *
     */
    public function index(){
        $user_nickname=$this->request->param('user_nickname','');
        $where=[];

        if($this->request->isAjax()&&$this->request->isPost()){
            if(isset($user_nickname)&&!empty($user_nickname)){
                $where['u.user_nickname']=array('like','%'.$user_nickname.'%') ;
            }
            $user=new UserModel();
            $data= $user->agentSelete($where);
            $this->success('ok','',['list' => $data]);
        }
        $this->assign('user_nickname',$user_nickname);
        return $this->fetch();
    }

    /**
     * @Author: ldm
     * @FunctionName:代理商启用
     * @Date:2018/6/4 0004 09:37
     *
     */
    public function examine(){
        $list=$this->request->param();
        if(isset($list['id'])){
            $where['id']=$list['id'];
        }else{
            $where['id']=array('in',$list['ids']);
        }
        $user=new UserModel();
        $status=$user->where($where)->update(['user_status'=>1]);
        if(!$status){
            $this->error('启用失败!');
        }
        return $this->success('启用成功!', url('AdminMerchant/index'));
    }

    /**
     * @Author: ldm
     * @FunctionName:代理商禁用
     * @Date: 2018/6/4 0004 10:10
     *
     */
 public function quExamine(){
     $list=$this->request->param();
     if(isset($list['id'])){
         $where['id']=$list['id'];
     }else{
         $where['id']=array('in',$list['ids']);
     }
     $user=new UserModel();
     $status=$user->where($where)->update(['user_status'=>0]);
     if(!$status){
         $this->error('禁用失败!');
     }
     return $this->success('禁用成功!', url('AdminMerchant/index'));
 }
    /**
     * @Author: ldm
     * @FunctionName:代理商删除
     * @Date: 2018/4/24 0024 15:18
     *
     */
    public function delete(){
        $user=new UserModel();
        $where=[];
        Db::startTrans();
        try{
            if($this->request->isPost()){
                $id=$this->request->param('id');
                $where['id']=$id;
            }
            $status=$user->where($where)->update(['is_delete'=>-1,'user_status'=>0]);
            if(!$status){
                $this->error('删除失败!');
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->error('删除失败!');
        }
        return $this->success('删除成功!', url('AdminMerchant/index'));
    }

    /**
     *
     * @Author: Ldm
     * @FunctionName:代理商信息查看与修改
     * @Date:2018/7/6 0006
     */
    public function agentInfo(){
        $user=new UserModel();
//        if($this->request->isAjax()&&$this->request->isPost()){
//            $data=$this->request->param();
//            if(isset($data['file'])){
//                unset($data['file']);
//            }
//            $result = $this->validate($data, 'Agent');
//            if ($result !== true) {
//                $this->error($result);
//            }
//            $data['id']=cmf_get_current_user_id();
//            $status=$user->agentEdit($data);
//            if($status['code']==0){
//                $this->error($status['msg']);
//            }
//            return $this->success('编辑成功!');
//        }
        $id=$this->request->param('id');//代理商ID

        $where['u.id']=$id;
        $data=$user->agentUser($where);
        $this->assign('data',$data);
        return $this->fetch();
    }
    /*
     * @Author: Ldm
     * @FunctionName:代理商二维码分配
     * @Date:2018/6/8 0008
     */
    public function agentCode(){
        $where=[];
        $where_code=[];
        $data=$this->request->param();
        if(empty($data['start_num'])){
            $this->error('起始码段不能为空');
        }
        if(empty($data['end_num'])){
            $this->error(' 截止码段不能为空');
        }
        if(empty($data['agent_id'])){
            $this->error('代理商不能为空');
        }
        $user_code=new UserCodeModel();
        $where['code_num']=array('between',''. $data['start_num'] .','.$data['end_num'] . '');
        $code_list= $user_code->getCodeMany($where);
        if($code_list==false){
            $this->error('请输入正确的码段');
        }
        $arrayData['status']=2;
        $arrayData['user_id']=$data['agent_id'];
        $arrayData['fenpei_time']=time();
        $ids=[];
        foreach ($code_list as $k){
            $ids[]=$k['id'];
        }
        $where_code['id']=array('in',$ids);
        $status= $user_code->setAllotCode($where_code,$arrayData);
        if($status==false){
            $this->error('请输入正确的码段');
        }
        return $this->success("分配成功");
    }
}