<?php
/**
 * 用户充值数据模型
 *
 * @date: 2018年6月28日下午5:13:27
 * @author: Hhb
 */

namespace app\user\model;

use think\Db;
use think\Model;

class UserRechargeModel extends Model
{
    protected $autoWriteTimestamp = true;

    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    protected function getActivityContentAttr($value)
    {
        return cmf_replace_content_file_url(htmlspecialchars_decode($value));
    }

    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    protected function setActivityContentAttr($value)
    {
        return htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode($value), true));
    }

    /**
     * 添加数据
     * @date: 2018年6月8日下午12:34:54
     * @author: Hhb
     * @param unknown $data
     */
    public function addData($data)
    {
        $re = $this->allowField(true)->isUpdate(false)->data($data, true)->save();
        if ($re) {
            return $this->data[$this->pk];
        } else {
            return 0;
        }
    }

    /**
     * 修改数据
     * @date: 2018年6月8日下午12:35:02
     * @author: Hhb
     * @param unknown $data
     * @return number|\think\false
     */
    public function editData($data)
    {
        return $this->allowField(true)->isUpdate(true)->data($data, true)->save();
    }

    /**
     * 根据ID获取一条数据
     * @date: 2018年6月8日下午12:35:07
     * @author: Hhb
     * @param unknown $id
     */
    public function getInfoById($id)
    {
        return $this->where(['id' => $id])->find();
    }

    /**
     * 获取分页数据
     * @date: 2018年6月8日下午12:35:16
     * @author: Hhb
     * @param array $where
     * @param number $limit
     * @return unknown
     */
    public function getPageList($where = array(), $limit = 20)
    {
        $result = $this->where($where)->order('id DESC')->paginate($limit);
        return $result;
    }

    /**
     * 获取所有数据
     * @date: 2018年6月8日下午12:35:22
     * @author: Hhb
     * @param array $where
     * @return unknown
     */
    public function getDataList($where = array())
    {
        $result = $this->where($where)->order('id DESC')->column('*', 'id');
        return $result;
    }

    /**
     * 添加充值订单
     * @date: 2018年6月19日下午2:56:56
     * @author: Hhb
     * @param unknown $data
     * @return string[]|number[]|string[]|number[]|array[]|object[]
     */
    public function addRecharge($data)
    {
        if (empty($data['user_id'])) {
            return array('code' => 'error', 'status' => 0, 'msg' => '用户数据异常', 'value' => '');
        }
        if (empty($data['in_code'])) {
            return array('code' => 'error', 'status' => 0, 'msg' => '充值单号不能为空', 'value' => '');
        }
        if (empty($data['in_money'])) {
            return array('code' => 'error', 'status' => 0, 'msg' => '充值金额不能为空', 'value' => '');
        }
        if (empty($data['in_type'])) {
            return array('code' => 'error', 'status' => 0, 'msg' => '充值方式不能为空', 'value' => '');
        }
        $check = $this->getInfoByCode($data['in_code']);
        if (!empty($check)) {
            return array('code' => 'error', 'status' => 0, 'msg' => '充值单号已存在', 'value' => '');
        }
        $re = $this->addData($data);
        if ($re) {
            return array('code' => 'success', 'status' => 1, 'msg' => '添加成功', 'value' => $re);
        } else {
            return array('code' => 'dbError', 'status' => 0, 'msg' => '服务器繁忙，请稍后再试', 'value' => '');
        }
    }

    /**
     * 编辑站点
     * @date: 2018年6月19日下午4:02:28
     * @author: Hhb
     * @param unknown $data
     * @return string[]|number[]|string[]|number[]|array[]|object[]
     */
    public function editRecharge($data)
    {
        if (empty($data['id'])) {
            return array('code' => 'error', 'status' => 0, 'msg' => '参数异常', 'value' => '');
        }
        $re = $this->editData($data);
        if ($re) {
            return array('code' => 'success', 'status' => 1, 'msg' => '编辑成功', 'value' => $re);
        } else {
            return array('code' => 'dbError', 'status' => 0, 'msg' => '服务器繁忙，请稍后再试', 'value' => '');
        }
    }

    /**
     * 根据编码获取数据
     * @date: 2018年6月19日下午3:44:00
     * @author: Hhb
     * @param unknown $code
     */
    public function getInfoByCode($code)
    {
        return $this->where(['in_code' => $code])->find();
    }

    /**
     *根据订单id和用户id查询订单
     * @param $id
     * @param $uid
     * @return array|false|\PDOStatement|string|Model
     * @Author: Lcs
     * @Date: 2018/6/5 12:22
     */
    public function getUserOrderById($id, $uid)
    {
        $result = $this->where(array('id' => $id, 'user_id' => $uid))->find();
        return $result;
    }

    /**
     *根据用户id查询订单
     * @param array $where
     * @param int $page
     * @Author: Lcs
     * @Date: 2018/6/5 15:05
     */
    public function refer($id, $where = array(), $page = 20)
    {
        $where['user_id'] = $id;
        $result = $this->where($where)
            ->order('id DESC')
//            ->where(array('user_id'=>$id,'status'=>1))
            ->paginate($page);
        return $result;
    }

    /**
     *
     * @param array $where
     * @param array $whereLike $whereLike=array('表字段'，表达式)
     * @param int $page
     * @return \think\Paginator
     * @Author: Lcs
     * @Date: 2018/6/29 10:06
     */
    public function getPageWhere($where = array(), $whereLike = array(), $page = 20,$order='create_time DESC')
    {
        if (count($whereLike) == 0) {
            $res = $this->alias('p')
                ->where($where)
                ->where(array('u.is_delete' => 1))
                ->join('user u', 'p.user_id=u.id', 'left')
                ->order($order)
                ->field('p.*,u.mobile,u.user_type')
                ->paginate($page);
        } else {
            $res = $this->alias('p')
                ->where($where)
                ->where(array('u.is_delete' => 1))
                ->whereLike($whereLike[0], $whereLike[1])
                ->join('user u', 'p.user_id=u.id', 'left')
                ->order($order)
                ->field('p.*,u.mobile,u.user_type')
                ->paginate($page);
        }

        return $res;
    }

    /**
     *根据订单id查询订单的详细信息
     * @param $id
     * @Author: Lcs
     * @Date: 2018/6/5 18:13
     */
    public function findOut($id)
    {
        $res = $this->where(array('id' => $id))->find()->toArray();
        $user = Db::name('user')->where(array('id' => $res['user_id'], 'is_delete' => 1))->find();
        if ($user['user_type'] == 2) {//代理
            $usery = Db::name('user_agent')->where(array('user_id' => $user['id']))->find();

        } elseif ($user['user_type'] == 3) {//商户
            $usery = Db::name('user_shop')->where(array('user_id' => $user['id']))->find();
            if ($usery['referrer'] != -1) {
                $agent = Db::name('user_agent')->where(array('id' => $usery['referrer']))->find();
                $us = Db::name('user')->where(array('id' => $agent['user_id']))->find();
                $usery['agent_name'] = $us['user_nickname'];
            }
        }
        return array('order' => $res, 'user' => $user, 'type' => $usery);
    }
    /**
     * 完成支付
     * @date: 2018年7月2日上午10:45:54
     * @author: Hhb
     * @param unknown $code
     * @param array $data
     */
    public function filishRecharge($code,$data =array()){
        if(!isset($data['status']) || empty($data['status'])){
            $data ['status'] = 1;
        }
        $data['update_time'] = time();
        $re = $this->where(['in_code'=>$code])->update($data);
        if($re){
            return array('code'=>'success','status'=>1,'msg'=>'支付成功','value'=>$re);
        }else{
            return array('code'=>'dbError','status'=>0,'msg'=>'支付失败，请稍后再试','value'=>'');
        }
    }
}