<?php
/**
 * 后台主页主控制器
 *
 * @date: 2018年4月12日上午9:17:41
 * @author: Hhb
 */

namespace app\mob\controller;


use app\shop\model\UserShopModel;
use app\user\model\UserCodeModel;
use app\user\model\UserModel;
use think\Db;

class MobShopController extends AdminComController
{
    public function index()
    {
        $param = $this->request->param();
        $where = array();
        if ($this->request->isAjax()) {
            $user = new UserShopModel();
            $where['u.user_type'] = 3;
            $where['a.is_delete'] = 1;
            empty($param['shop_name']) ? '' : $where['a.shop_name'] = array('like','%'.$param['shop_name'].'%');
            $res = $user->refer($where);
            $res->appends($where);
            $this->success('ok','',array('list'=>$res->toArray()));
        }
        $this->assign('param',$param);
        return $this->fetch();
    }

    /**
     * 查看详情
     * @return mixed
     * @Author: Lcs
     * @Date: 2018/6/2 17:55
     */
    public function detals()
    {
        $id = $this->request->param('id');
        if (isset($id)) {
            $usershop = new UserShopModel();
            $res = $usershop->findOut($where = array('a.id' => $id));
            $this->assign('res', $res);

            $province = Db::name('regions')->where(['parent_id' => '00000000-0000-0000-0000-000000000000'])->select()->toArray();
            $city = Db::name('regions')->where(['parent_id' => $res['province']])->select();
            $town = Db::name('regions')->where(['parent_id' => $res['city']])->select();

            $this->assign('regions', $province);
            $this->assign('city', $city);
            $this->assign('town', $town);

            return $this->fetch();
        }
    }

    /**
     * 删除商户
     * @Author: Lcs
     * @Date: 2018/6/2 17:55
     */
    public function delete()
    {
        $param = $this->request->param();
        if (isset($param)) {
            $usershop = new UserShopModel();
            $res = $usershop->omit($param);
            if ($res['code'] == 1) {
                $this->success('删除成功');
            } else {
                $this->error($res['msg']);
            }
        }
    }

    /**
     * 封禁商户
     * @Author: Lcs
     * @Date: 2018/6/4 9:37
     */
    public function banned()
    {
        $param = $this->request->param();
        $usershop = new UserShopModel();
        $res = $usershop->forbidden($param);
        if ($res['code'] == 1) {
            $this->success('操作成功');
        } else {
            $this->error($res['msg']);
        }
    }

    /**
     *审核
     * @Author: Lcs
     * @Date: 2018/6/4 9:47
     */
    public function audit()
    {
        $param = $this->request->param();
        $usershop = new UserShopModel();
        $user = new UserModel();
        if (isset($param['ids'])) {
            $user_id = array();
            $ids = $this->request->param('ids/a');
            $user_list = $usershop->field('user_id')->where(['id' => ['in', $ids]])->select()->toArray();
            foreach ($user_list as $k => $v) {
                $user_id[$k] = $v['user_id'];
            }
            $user->isUpdate(true, ['id' => ['in', $user_id]])->save(['user_status' => 1]);
            $usershop->where(['id' => ['in', $ids]])->update(['site' => 'normal', 'normal_time' => time(), 'reject' => '']);

            $this->success("操作成功！", '');
        }
        if (isset($param['id'])) {
            $id = $this->request->param('id');
            $user_list = $usershop->field('user_id')->where(['id' => $id])->find();
            $user->isUpdate(true, ['id' => $user_list['user_id']])->save(['user_status' => 1]);
            $usershop->where(['id' => $id])->update(['site' => 'normal', 'normal_time' => time(), 'reject' => '']);
            $this->success("操作成功！", '');
        }

    }

    /**
     * 驳回
     * @Author: Lcs
     * @Date: 2018/6/4 9:47
     */
    public function auditFaile()
    {
        $param = $this->request->param();
        $usershop = new UserShopModel();

        $state = 1;
        Db::startTrans();
        try {
            $shop = $usershop->findOut(array('a.id' => $param['id']));
            $agent = Db::name('user')->where(array('id' => $shop['user_id']))->find();
            $mes = array('content' => $param['msg'], 'create_time' => time(), 'user_id' => $agent['id']);
            Db::name('user_shop')->where(array('id' => $param['id']))->update(array('site' => 'reject', 'reject' => $param['msg']));
            $res = Db::name('user_msg')->insert($mes);
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            $state = 0;
            Db::rollback();
        }
        if ($state == 1) {
            $this->success('操作成功');
        } else {
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
    public function is_mobile()
    {
        $mobile = $this->request->param('mobile');
        $res = Db::name('user')->where(array('mobile' => $mobile))->find();
        if ($res) {
            return array('code' => 0, 'msg' => '该帐号已存在');
        } else {
            return array('code' => 1, 'msg' => '该账号不存在');
        }
    }

    /**
     * 分配码段
     * @return array
     * @Author: Lcs
     * @Date: 2018/6/8 18:04
     */
    public function allot()
    {
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
            'update_time'=>time(),
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

