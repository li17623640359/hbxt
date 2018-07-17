<?php
/**
 * Created by PhpStorm.
 * User: ldm
 * Date: 2018/6/2 0002
 * Time: 16:33
 */

namespace app\agent\controller;



use app\agent\model\UserCodeModel;
use app\agent\model\UserModel;
use cmf\controller\AdminBaseController;
use think\Db;

/**
 * Class AdminMerchantController
 * @package app\agent\controller
 * @adminMenuRoot(
 *     'name'   =>'代理商管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 31,
 *     'icon'   =>'user-secret',
 *     'remark' =>'代理商管理'
 * )
 */
class AdminMerchantController extends AdminBaseController
{
    /**
     *@adminMenu(
     *     'name'   => '代理商列表',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '代理商列表',
     *     'param'  => ''
     * )
     * @Author: ldm
     * @FunctionName:代理商列表
     * @Date: 2018/6/2 0002 16:37
     *
     */
    public function index(){
        $list=$this->request->param('','');
        $where=[];
        if(isset($list['user_nickname'])&&!empty($list['user_nickname'])){
            $where['u.user_nickname']=array('like','%'.$list['user_nickname'].'%') ;
            $this->assign('user_nickname',$list['user_nickname']);
        }
        $user=new UserModel();
        $data= $user->agentSelete($where);
        $data->appends($list);
        $this->assign('page',$data->render());
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * @adminMenu(
     *     'name'   => '代理商添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '代理商添加',
     *     'param'  => ''
     * )
     * @Author: ldm
     * @FunctionName:代理商添加
     * @Date: 2018/6/2 0002 16:58
     *
     */
    public function add(){
        return $this->fetch();
    }

    /**
     *@adminMenu(
     *     'name'   => '代理商添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '代理商添加',
     *     'param'  => ''
     * )
     * @Author: ldm
     * @FunctionName:代理商添加提交
     * @Date: 2018/6/2 0002 17:13
     *
     */
    public function addPost(){
    $data=$this->request->param();
        $result = $this->validate($data, 'AdminMerchant');
        if ($result !== true) {
            $this->error($result);
        }
        if(!preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $data['mobile'])){
            $this->error("请输入正确的手机");
        }
        if(!empty($data['start_num'])||!empty($data['end_num'])){
            if(empty($data['start_num'])){
                $this->error("起始码段不能为空");
            }
            if(empty($data['end_num'])){
                $this->error("截止码段不能为空");
            }
        }
        $user=new UserModel();
        $status=$user->agentAdd($data);
         if($status['code']==0){
             $this->error($status['msg']);
         }
        return $this->success('添加成功!', url('AdminMerchant/index'));
    }

    /**
     *  @adminMenu(
     *     'name'   => '代理商编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '代理商编辑',
     *     'param'  => ''
     * )
     * @Author: ldm
     * @FunctionName:代理商编辑
     * @Date: 2018/6/4 0004 09:22
     *
     */
    public function edit(){
        $id=$this->request->param('id');
        $user=new UserModel();
        $where['u.id']=$id;
        $data=$user->agentUser($where);
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     *  @adminMenu(
     *     'name'   => '代理商编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '代理商编辑提交',
     *     'param'  => ''
     * )
     * @Author: ldm
     * @FunctionName:代理商编辑提交
     * @Date: 2018/6/4 0004 09:37
     *
     */
    public function editPost(){
    $data=$this->request->param();
        $result = $this->validate($data, 'AdminMerchant');
        if ($result !== true) {
            $this->error($result);
        }
//        if(!preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $data['mobile'])){
//            $this->error("请输入正确的手机");
//        }
        $user=new UserModel();
        $status=$user->agentEdit($data);
        if($status['code']==0){
            $this->error($status['msg']);
        }
        return $this->success('编辑成功!', url('AdminMerchant/index'));
    }
    /**
     * @adminMenu(
     *     'name'   => '代理商启用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '代理商启用',
     *     'param'  => ''
     * )
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
     * @adminMenu(
     *     'name'   => '代理商禁用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '代理商禁用',
     *     'param'  => ''
     * )
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
     * @adminMenu(
     *     'name'   => '代理商删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '代理商删除',
     *     'param'  => ''
     * )
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
            if($this->request->isGet()){
                $id=$this->request->param('id');
                $where['id']=$id;
            }
            if($this->request->isPost()){
                //多删除操作
                $ids=$this->request->param();
                $where['id']=array('in',$ids['ids']);
            }
            $status=$user->where($where)->update(['is_delete'=>-1]);
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
     *@adminMenu(
     *     'name'   => '代理商二维码分配',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '代理商二维码分配',
     *     'param'  => ''
     * )
     * @Author: Ldm
     * @FunctionName:代理商二维码分配
     * @Date:2018/6/8 0008
     */
    public function agentCode(){
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