<?php
/**
 *
 * @Author: Lcs
 * @Date: 2018/6/2 17:11
 */

namespace app\user\model;

use think\Db;
use think\Model;
use think\Validate;

class UserShopModel extends Model
{

    /**
     *添加商户
     * @param $data
     * @param $site 状态
     * @return array
     * @Author: Lcs
     * @Date: 2018/6/4 8:57
     */
    public function add($data, $site, $user_type,$user_status=2)
    {
        if (empty($data)) {
            return array('code' => 0, 'msg' => 'error');
        }

        $validate = new Validate([
            'shop_name|商铺名称' => 'require',
//            'shop_logo|商铺logo' => 'require',
            'principal|负责人' => 'require',
            'mobile|联系方式' => 'require',
            'user_pass|密码' => 'require',
            'certificate|营业执照' => 'require',
            'province|地址（省）' => 'require',
            'city|地址（市）' => 'require',
            'district|地址（区）' => 'require',
            'address|地址（详细地址）' => 'require'
        ]);

        if (!$validate->check($data)) {
            return array('code' => 0, 'msg' => $validate->getError());
        }

        $user = array(
            'mobile' => $data['mobile'],
            'user_pass' => cmf_password($data['user_pass']),
            'user_type' => $user_type,
            'create_time' => time(),
            'user_nickname'=>$data['principal'],
            'user_status'=>$user_status
        );
        $state = 1;
        Db::startTrans();
        try {
            $user_id = Db::name('user')->insertGetId($user);
            unset($data['mobile'], $data['user_pass']);
            $data['create_time'] = time();
            $data['site'] = $site;
            $data['user_id'] = $user_id;
            if (empty($data['start']) && empty($data['end'])){
            }elseif (!empty($data['start']) && !empty($data['end'])){
                $start=$data['start'];
                $end=$data['end'];
                unset($data['start'],$data['end']);
                $usercode=new UserCodeModel();
                $code=$usercode->getByCode($start,$end);
                $ids=array();
                foreach ($code as $k){
                    $ids[]=$k['id'];
                }
                $dataer=array(
                    'update_time'=>time(),
                    'fenpei_time'=>time(),
                    'status'=>2,
                    'user_id'=>$user_id,
                );
                $usercode->editCodeSite($ids,$dataer);
            }else{
                return array('code'=>0,'msg'=>'码值不能为空');
            }
            unset($data['start'],$data['end']);
            $data['normal_time']=time();
            $res = $this->insert($data);
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            $state = -1;
            Db::rollback();
        }
        if ($state == 1) {
            return array('code' => 1, 'msg' => 'ok');
        } else {
            return array('code' => 0, 'msg' => $this->getError());
        }

    }

    /**
     *修改商户
     * @param $data
     * @param $site 状态
     * @return array
     * @Author: Lcs
     * @Date: 2018/6/4 8:57
     */
    public function updater($data, $id)
    {
        if (empty($data)) {
            return array('code' => 0, 'msg' => 'error');
        }

        $validate = new Validate([
            'shop_name|商铺名称' => 'require',
            'principal|负责人' => 'require',
            'certificate|营业执照' => 'require',
            'province|地址（省）' => 'require',
            'city|地址（市）' => 'require',
            'district|地址（区）' => 'require',
            'address|地址（详细地址）' => 'require'
        ]);

        if (!$validate->check($data)) {
            return array('code' => 0, 'msg' => $validate->getError());
        }

        if (!empty($data['principal'])){
            $shop=$this->where(array('id'=>$id))->find();
            $userModel=new UserModel();
            $userModel->where(array('id'=>$shop['user_id']))->update(array('user_nickname'=>$data['principal']));
        }

        $res = $this->where(array('id' => $id))->update($data);
        if ($res) {
            return array('code' => 1, 'msg' => 'ok');
        } else {
            return array('code' => 0, 'msg' => $this->getError());
        }

    }

    /**
     *带条件的查询商户的详细信息
     * @Author: Lcs
     * @Date: 2018/6/2 17:12
     */
    public function refer($where = array(), $page = 20)
    {
        $result = $this->alias('a')
            ->where($where)
            ->join('user u', 'a.user_id=u.id')
            ->join('user s','s.id=a.referrer','left')
            ->join('user_code c','u.id=c.user_id and c.status=2','left')
            ->group('a.id')
            ->field('a.*,u.balance,u.mobile,count(c.code_num) number,s.mobile mobiler,s.user_nickname user_nicknamer')
            ->paginate($page);
        return $result;
    }

    /**
     *根据条件查询单条商户的详细信息
     * @Author: Lcs
     * @Date: 2018/6/2 17:17
     */
    public function findOut($where = array())
    {
        $result = $this->alias('a')
            ->where($where)
            ->join('user u', 'a.user_id=u.id')
            ->field('a.*,u.mobile')
            ->find();
        return $result;
    }


    /**
     *删除
     * @param $id
     * @Author: Lcs
     * @Date: 2018/6/4 9:02
     */
    public function omit($param)
    {
        $arr = array('is_delete' => -1, 'delete_time' => time());
        if (isset($param['id'])) {
            $state = 1;
            Db::startTrans();
            try {
                $user = $this->where(array('id' => $param['id']))->find();
                $res = $this->where(array('id' => $param['id']))->update($arr);
                Db::name('user')->where(array('id' => $user['user_id']))->update(array('is_delete' => -1));
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                $state = -1;
                Db::rollback();
            }
            if ($state == 1) {
                return array('code' => 1, 'msg' => '删除成功');
            } else {
                return array('code' => 0, 'msg' => $this->getError());
            }
        }
        if (isset($param['ids'])) {
            $user = $this->where(array('id' => array('in', $param['ids'])))->select();
            $user_id = array();
            foreach ($user as $v) {
                $user_id[] = $v['user_id'];
            }
            $state = 1;
            Db::startTrans();
            try {
                $res = $this->where(array('id' => array('in', $param['ids'])))->update($arr);
                Db::name('user')->where(array('id' => array('in', $user_id)))->update(array('is_delete' => -1));
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                $state = -1;
                Db::rollback();
            }
            if ($state == 1) {
                return array('code' => 1, 'msg' => '删除成功');
            } else {
                return array('code' => 0, 'msg' => $this->getError());
            }
        }
    }

    /**
     *判断该商户是否审核
     * @param $id
     * @Author: Lcs
     * @Date: 2018/6/4 16:36
     */
    public function is_audit($id)
    {
        $res = $this->where(array('id' => $id))->find();
        if ($res['site'] == 'audit') {
            //未审核
            return array('code' => 0, 'msg' => '未审核');
        } else {
            return array('code' => 1, 'msg' => '已审核');
        }
    }

    /**
     *禁用
     * @param $param
     * @return array
     * @Author: Lcs
     * @Date: 2018/6/5 8:33
     */
    public function forbidden($param)
    {
        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $param['ids'];
            foreach ($ids as $k) {
                $res = $this->is_audit($k);
                if ($res['code'] == 0) {
                    return array('code' => 0, 'msg' => '帐号尚未审核,不能禁用');
                }
            }
            $this->where(['id' => ['in', $ids]])->update(['site' => 'banned', 'failed_msg' => $param['msg']]);
            foreach ($ids as $k) {
                $res = $this->siteType($k, 0);
                if ($res['code'] == 0) {
                    return array('code' => 0, 'msg' => '修改user表状态失败,失败id' . $k);
                }
            }
            return array('code' => 1, 'msg' => '禁用成功！');
        }
        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $param['ids'];
            $this->where(['id' => ['in', $ids]])->update(['site' => 'normal', 'failed_msg' => '']);
            foreach ($ids as $k) {
                $res = $this->siteType($k, 1);
                if ($res['code'] == 0) {
                    return array('code' => 0, 'msg' => '修改user表状态失败,失败id' . $k);
                }
            }
            return array('code' => 1, 'msg' => '取消禁用成功！');
        }
        if (isset($param['id']) && isset($param["yes"])) {
            $id = $param['id'];
            $res = $this->is_audit($id);
            if ($res['code'] == 0) {
                return array('code' => 0, 'msg' => '帐号尚未审核,不能禁用');
            }
            $this->where(['id' => $id])->update(['site' => 'banned', 'failed_msg' => $param['msg']]);
            $res = $this->siteType($id, 0);
            if ($res['code'] == 0) {
                return array('code' => 0, 'msg' => '修改user表状态失败,失败id' . $id);
            }
            return array('code' => 1, 'msg' => '禁用成功！');
        }
        if (isset($param['id']) && isset($param["no"])) {
            $id = $param['id'];
            $this->where(['id' => $id])->update(['site' => 'normal', 'failed_msg' => '']);
            $res = $this->siteType($id, 1);
            if ($res['code'] == 0) {
                return array('code' => 0, 'msg' => '修改user表状态失败,失败id' . $id);
            }
            return array('code' => 1, 'msg' => '取消禁用成功！');
        }
    }

    /**
     *根据商户id修改user表状态
     * @param $id
     * @param $type
     * @return array
     * @Author: Lcs
     * @Date: 2018/6/5 8:32
     */
    public function siteType($id, $type)
    {
        $user = $this->where(array('id' => $id))->find();
        $res = Db::name('user')->where(array('id' => $user['user_id']))->update(array('user_status' => $type));
        if ($res) {
            return array('code' => 1, 'msg' => 'ok');
        } else {
            return array('code' => 0, 'msg' => 'error');
        }
    }

}