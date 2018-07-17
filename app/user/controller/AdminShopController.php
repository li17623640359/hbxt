<?php
/**
 *
 * @Author: Lcs
 * @Date: 2018/6/2 17:53
 */

namespace app\user\controller;


use app\user\model\UserCodeModel;
use app\user\model\UserModel;
use app\user\model\UserShopModel;
use cmf\controller\AdminBaseController;
use think\Db;

/**
 * Class AdminShopController
 * @adminMenuRoot(
 *     'name'   =>'商户管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 30,
 *     'icon'   =>'id-badge',
 *     'remark' =>'商户管理'
 * )
 * @package app\shop\controller
 * @Author: Lcs
 * @Date: 2018/6/4 9:15
 */
class AdminShopController extends AdminBaseController
{
    /**
     * 商户列表
     * @adminMenu(
     *     'name'   => '商户列表',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '商户列表',
     *     'param'  => ''
     * )
     * @return mixed
     * @Author: Lcs
     * @Date: 2018/6/2 17:54
     */
    public function index(){
        $param=$this->request->param();
        $where=array();
        empty($param['site'])?'':$where['a.site']=$param['site'];
        $user=new UserShopModel();
        $where['u.user_type']=3;
        $where['a.is_delete']=1;
        $res=$user->refer($where);
        $this->assign('res',$res);
        $this->assign('page',$res->render());
        return $this->fetch();
    }

    /**
     * 添加商户
     * @adminMenu(
     *     'name'   => '添加商户',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加商户',
     *     'param'  => ''
     * )
     * @return mixed
     * @Author: Lcs
     * @Date: 2018/6/2 17:54
     */
    public function add(){
        $province=Db::name('regions')->where(array('parent_id'=>'00000000-0000-0000-0000-000000000000'))->select();
        $this->assign('regions',$province);
        return $this->fetch();
    }

    /**
     * 添加商户提交
     * @adminMenu(
     *     'name'   => '添加商户提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加商户提交',
     *     'param'  => ''
     * )
     * @Author: Lcs
     * @Date: 2018/6/2 17:54
     */
    public function addPost(){
        $param=$this->request->param();
        $usershop=new UserShopModel();
        $param['referrer']=-1;
        $res=$usershop->add($param,$site='normal',$user_type=3,$user_status=1);
        if ($res['code']==0){
            $this->error($res['msg']);
        }else{
            $this->success('添加成功');
        }
    }

    /**
     * 编辑商户
     * @adminMenu(
     *     'name'   => '编辑商户',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑商户',
     *     'param'  => ''
     * )
     * @return mixed
     * @Author: Lcs
     * @Date: 2018/6/2 17:55
     */
    public function edit(){
        $id=$this->request->param('id');
        if (isset($id)){
            $usershop=new UserShopModel();
            $res=$usershop->findOut($where=array('a.id'=>$id));
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
     * 编辑商户提交
     * @adminMenu(
     *     'name'   => '编辑商户提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑商户提交',
     *     'param'  => ''
     * )
     * @Author: Lcs
     * @Date: 2018/6/2 17:55
     */
    public function editPost(){
        $param=$this->request->param();
        $id=$param['id'];
        unset($param['id']);
        $usershop=new UserShopModel();
        $res=$usershop->updater($param,$id);
        if ($res['code']==1){
            $this->success('修改成功',url('AdminShop/index'));
        }else{
            $this->error($usershop->getError());
        }
    }

    /**
     * 删除商户
     * @adminMenu(
     *     'name'   => '删除商户',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '删除商户',
     *     'param'  => ''
     * )
     * @Author: Lcs
     * @Date: 2018/6/2 17:55
     */
    public function delete(){
        $param=$this->request->param();
        if (isset($param)){
            $usershop=new UserShopModel();
            $res=$usershop->omit($param);
            if ($res['code']==1){
                $this->success('删除成功');
            }else{
                $this->error($res['msg']);
            }
        }
    }

    /**
     * 封禁商户
     * @adminMenu(
     *     'name'   => '封禁商户',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '封禁商户',
     *     'param'  => ''
     * )
     * @Author: Lcs
     * @Date: 2018/6/4 9:37
     */
    public function banned(){
        $param=$this->request->param();
        $usershop=new UserShopModel();
        $res=$usershop->forbidden($param);
        if ($res['code']==1){
            $this->success('操作成功');
        }else{
            $this->error($res['msg']);
        }
    }

    /**
     *审核
     * @adminMenu(
     *     'name'   => '审核商户',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '审核商户',
     *     'param'  => ''
     * )
     * @Author: Lcs
     * @Date: 2018/6/4 9:47
     */
    public function audit(){
        $param=$this->request->param();
        $usershop=new UserShopModel();
        $user=new UserModel();
        if (isset($param['ids'])) {
            $user_id=array();
            $ids = $this->request->param('ids/a');
            $user_list= $usershop->field('user_id')->where(['id' => ['in', $ids]])->select()->toArray();
            foreach ($user_list as $k=>$v){
                $user_id[$k]=$v['user_id'];
            }
            $user->isUpdate(true,['id'=>['in',$user_id]])->save(['user_status'=>1]);
            $usershop->where(['id' => ['in', $ids]])->update(['site' => 'normal','normal_time'=>time(),'reject'=>'']);

            $this->success("操作成功！", '');
        }
        if (isset($param['id'])) {
            $id = $this->request->param('id');
            $user_list= $usershop->field('user_id')->where(['id' =>$id])->find();
            $user->isUpdate(true,['id'=>$user_list['user_id']])->save(['user_status'=>1]);
            $usershop->where(['id' => $id])->update(['site' => 'normal','normal_time'=>time(),'reject'=>'']);
            $this->success("操作成功！", '');
        }

    }

    /**
     * @adminMenu(
     *     'name'   => '驳回',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '驳回',
     *     'param'  => ''
     * )
     * @Author: Lcs
     * @Date: 2018/6/4 9:47
     */
    public function auditFaile(){
        $param=$this->request->param();
        $usershop=new UserShopModel();

        $state=1;
        Db::startTrans();
        try{
            $shop=$usershop->findOut(array('a.id'=>$param['id']));
            $agent=Db::name('user')->where(array('id'=>$shop['user_id']))->find();
            $mes=array('content'=>$param['msg'],'create_time'=>time(),'user_id'=>$agent['id']);
            Db::name('user_shop')->where(array('id'=>$param['id']))->update(array('site'=>'reject','reject'=>$param['msg']));
            $res=Db::name('user_msg')->insert($mes);
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            $state=0;
            Db::rollback();
        }
        if ($state==1){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }

    /**
     *查询住址
     * @Author: Lcs
     * @Date: 2018/6/4 16:55
     */
    public function address()
    {
        $id = $this->request->param('id');
        $result = Db::name('regions')->where(array('parent_id' => $id))->select();
        echo json_encode($result);
    }

    /**
     *验证手机号是否存在
     * @Author: Lcs
     * @Date: 2018/6/4 16:56
     */
    public function is_mobile(){
        $mobile=$this->request->param('mobile');
        $res=Db::name('user')->where(array('mobile'=>$mobile))->find();
        if ($res){
            return array('code'=>0,'msg'=>'该帐号已存在');
        }else{
            return array('code'=>1,'msg'=>'该账号不存在');
        }
    }

    /**
     * @adminMenu(
     *     'name'   => '分配码段',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '分配码段',
     *     'param'  => ''
     * )
     * @return array
     * @Author: Lcs
     * @Date: 2018/6/8 18:04
     */
    public function allot(){
        $param=$this->request->param();
        if (empty($param['code_start'])){
            $this->error('起始码值不能为空');
        }
        if (empty($param['code_end'])){
            $this->error('结束码值不能为空');
        }
        if (empty($param['user_id'])){
            $this->error('商户id错误');
        }

        $data=array(
            'fenpei_time'=>time(),
            'status'=>2,
            'user_id'=>$param['user_id'],
        );
        $usercode=new UserCodeModel();
        $code=$usercode->getByCode($param['code_start'],$param['code_end']);
        $id=array();
        foreach ($code as $k){
            $id[]=$k['id'];
        }
        $res=$usercode->editCodeSite($id,$data);
        if ($res==1){
            $this->success('分配成功');
        }else{
            $this->error('码值中含有已被分配过码值');
        }
    }

}