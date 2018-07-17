<?php
/**
 * 二维码扩展数据模型
 *
 * @date: 2018年6月7日下午3:05:30
 * @author: Hhb
 */
namespace app\user\model;

use think\Model;

class UserCodeExtModel extends Model{
    protected $autoWriteTimestamp = true;
    /**
     * 添加数据
     * @date: 2018年6月8日下午12:34:54
     * @author: Hhb
     * @param unknown $data
     */
    public function addData($data){
        return $this->allowField(true)->isUpdate(false)->data($data, true)->save();
    }
    /**
     * 修改数据
     * @date: 2018年6月8日下午12:35:02
     * @author: Hhb
     * @param unknown $data
     * @return number|\think\false
     */
    public function editData($data){
        return $this->allowField(true)->isUpdate(true)->data($data, true)->save();
    }
    /**
     * 根据ID获取一条数据
     * @date: 2018年6月8日下午12:35:07
     * @author: Hhb
     * @param unknown $id
     */
    public function getInfoById($id){
        return $this->where(['eid'=>$id])->find();
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
        $result = $this->where($where)->order('eid DESC')->paginate($limit);
        return $result;
    }
    /**
     * 获取所有数据
     * @date: 2018年6月8日下午12:35:22
     * @author: Hhb
     * @param array $where
     * @return unknown
     */
    public function getDataList($where = array()){
        $result = $this->where($where)->order('eid DESC')->select()->toArray();
        return $result;
    }
    /**
     *  根据活动ID获取其虚拟红包
     * @date: 2018年6月13日上午11:31:30
     * @author: Hhb
     * @param unknown $activity_id
     * @param unknown $code_id
     */
    public function getActivityCodePageListById($activity_id,$code_id,$limit = 50){
        $where = array(
            'aid'=>$activity_id,
            'cid'=>$code_id
        );
        return $this->where($where)->order('eid ASC')->paginate($limit);
    }

    /**
     *获取某一活动的红包领取情况
     * @param $active_id
     * @param int $page
     * @return \think\Paginator
     * @Author: Lcs
     * @Date: 2018/7/3 11:24
     */
    public function getRedpackByActive($id,$page=20){
        return $this->where(array('aid'=>$id))->order('eid ASC')->paginate($page);
    }
    /**
     * 根据领取者code获取最后一条领取数据
     * @date: 2018年7月9日下午2:50:07
     * @author: Hhb
     * @param unknown $user_code
     */
    public function getLastReceiveByCode($user_code,$activity_id){
        return $this->where(['user_code'=>$user_code,'aid'=>$activity_id,'end_time'=>['gt',0]])->order('end_time DESC')->find();
    }
    /**
     * 获取用户扫码过但未领取的一个红包
     * @date: 2018年7月10日下午5:59:08
     * @author: Hhb
     * @param unknown $user_code
     * @param unknown $activity_id
     */
    public function getScanPacketByUserCode($user_code,$activity_id){
        return $this->where(['user_code'=>$user_code,'aid'=>$activity_id,'end_time'=>0])->order('eid DESC')->find();
    }
    /**
     * 获取活动未领红包中的第一个
     * @date: 2018年7月9日下午3:40:32
     * @author: Hhb
     * @param unknown $activity_id
     */
    public function getFirstNotReceiveCode($activity_id){
        return $this->where(['end_time'=>0,'user_code'=>'','aid'=>$activity_id])->order('eid ASC')->find();
    }
}