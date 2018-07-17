<?php
/**
 *
 * @Author: Lcs
 * @Date: 2018/6/5 10:46
 */

namespace app\user\model;


use think\Db;
use think\Model;

class PayLogModel extends Model
{
    /**
     *创建订单
     * @param $param
     * @Author: Lcs
     * @Date: 2018/6/5 10:47
     */
    public function createOrder($param){
        if (empty($param) || empty($param['pay_type']) || empty($param['money'])) {
            return array('status' => 0, 'code' => 'paramErr', 'msg' => '参数异常', 'value' => '');
        }

        $order=array(
            'type'=>'pay',
            'user_id'=>cmf_get_current_user_id(),
            'order_code'=>ordernum(),
            'money'=>$param['money'],
            'create_time'=>time(),
            'paymode'=>$param['pay_type']
        );

        $id=$this->insertGetId($order);
        if ($id){
            return array('status' => 1, 'code' => 'success', 'msg' => '下单成功', 'value' => array('id' => $id));
        }
    }

    /**
     *根据订单id和用户id查询订单
     * @param $id
     * @param $uid
     * @return array|false|\PDOStatement|string|Model
     * @Author: Lcs
     * @Date: 2018/6/5 12:22
     */
    public function getUserOrderById($id,$uid){
        $result = $this->where(array('id' => $id, 'user_id' => $uid))->find();
        return $result;
    }

    /**
     *根据订单号查询订单
     * @param $order_code
     * @return array|false|\PDOStatement|string|Model
     * @Author: Lcs
     * @Date: 2018/6/5 12:22
     */
    public function getInfoByCode($order_code){
        $result = $this->where(array('order_code' => $order_code))->find();
        return $result;
    }

    /**
     *修改订单状态
     * @Author: Lcs
     * @Date: 2018/6/5 12:34
     */
    public function updateSite($order_code,$money){
        $res=$this->where(array('order_code'=>$order_code))->find();
        $sum_money=$money+$res['money'];
        $res=$this->where(array('order_code'=>$order_code))->update(array('site'=>'yes','balance'=>$sum_money));
        if ($res){
            return array('code'=>0,'msg'=>'ok');
        }else{
            return array('code'=>1,'msg'=>'error');
        }
    }

    /**
     *根据用户id查询订单
     * @param array $where
     * @param int $page
     * @Author: Lcs
     * @Date: 2018/6/5 15:05
     */
    public function refer($where=array(),$page=20){
        $id=cmf_get_current_user_id();
        $result=$this->where($where)
//            ->where(array('user_id'=>$id,'site'=>'yes'))
            ->where(array('user_id'=>$id))
            ->paginate($page);
        return $result;
    }

    /**
     *查询全部满足条件的订单
     * @Author: Lcs
     * @Date: 2018/6/5 16:47
     */
    public function referAll($where=array(),$whereLike=array(),$page=20){
        if (count($whereLike)==0){
            $res=$this->alias('p')
                ->where($where)
                ->where(array('u.is_delete'=>1))
                ->join('user u','p.user_id=u.id','left')
                ->field('p.*,u.mobile,u.user_type')
                ->paginate($page);
        }else{
            $res=$this->alias('p')
                ->where($where)
                ->where(array('u.is_delete'=>1))
                ->whereLike($whereLike[0],$whereLike[1])
                ->join('user u','p.user_id=u.id','left')
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
    public function findOut($id){
        $res=$this->where(array('id'=>$id))->find()->toArray();
        $user=Db::name('user')->where(array('id'=>$res['user_id'],'is_delete'=>1))->find();
        if ($user['user_type']==2){//代理
            $usery=Db::name('user_agent')->where(array('user_id'=>$user['id']))->find();

        }elseif ($user['user_type']==3){//商户
            $usery=Db::name('user_shop')->where(array('user_id'=>$user['id']))->find();
            if ($usery['referrer']!=-1){
                $agent=Db::name('user_agent')->where(array('id'=>$usery['referrer']))->find();
                $us=Db::name('user')->where(array('id'=>$agent['user_id']))->find();
                $usery['agent_name']=$us['user_nickname'];
            }
        }
        return array('order'=>$res,'user'=>$user,'type'=>$usery);
    }
}