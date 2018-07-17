<?php
/**
 * 二维码数据模型
 *
 * @date: 2018年6月7日下午3:05:30
 * @author: Hhb
 */
namespace app\user\model;

use think\Model;

class UserCodeModel extends Model{
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
        return $this->where(['id'=>$id])->find();
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
    public function getDataList($where = array()){
        $result = $this->where($where)->order('id DESC')->select()->toArray();
        return $result;
    }

    /**
     *根据起始码值和结束码值查询
     * @param $start
     * @param $end
     * @return false|\PDOStatement|string|\think\Collection
     * @Author: Lcs
     * @Date: 2018/6/8 18:54
     */
    public function getByCode($start,$end){
        $result=$this->where('code_num','between',[$start,$end])->where(array('status'=>1))->select();
        return $result;
    }

    /**
     *将符合条件的二维码改为‘未使用’
     * @param $ids
     * @param $data
     * @return int
     * @Author: Lcs
     * @Date: 2018/6/9 10:23
     */
    public function editCodeSite($ids,$data){
        $res=$this->where(array('id'=>array('in',$ids)))->update($data);
        if ($res){
            return 1;
        }else{
            return 0;
        }
    }
    /**
     * 获取用户的二维码数量
     * @date: 2018年6月12日上午10:57:44
     * @author: Hhb
     * @param unknown $id
     * @param number $status 0查询所有，2未使用，3已使用，4已完成
     */
    public function getUserCodeNums($id,$status = 0){
        $where = array(
            'user_id'=>$id
        );
        if(!empty($status)){
            $where['status'] = $status;
        }
        return $this->field("COUNT(code_num) AS total,status")->where($where)->group('status')->select()->toArray();
    }
    /**
     * 根据用户ID获取未使用的二维码
     * @date: 2018年6月12日下午8:28:11
     * @author: Hhb
     * @param unknown $id
     * @param unknown $limit
     */
    public function getUserCodeList($id,$limit){
        if(empty($id) || empty($limit)){
            return false;
        }
        $where = array(
            'user_id'=>$id,
            'status'=>2
        );
        return $this->where($where)->order('id ASC')->limit($limit)->select()->toArray();
    }
    /**
     * 根据活动ID获取其二维码
     * @date: 2018年6月13日上午11:34:18
     * @author: Hhb
     * @param unknown $id
     * @param number $type
     * @param number $limit
     */
    public function getActivityCodePageListById($id,$type = 1,$limit = 50 ,$code_num=''){
        $where = array(
            'activity_id'=>$id,
        );
        if(!empty($code_num)){
            $where['code_num']=$code_num;
        }
        if($type == 1){
            return $this->where($where)->order('id ASC')->paginate($limit);
        }else{
            return $this->where($where)->find();
        }
    }
    /**
     * 根据编号获取二维码数据
     * @date: 2018年7月9日下午2:16:44
     * @author: Hhb
     * @param unknown $code
     */
    public function getInfoByCode($code){
        return $this->where(['code_num'=>$code])->find();
    }
    /**
     * 根据二维码码值获取数据
     * @date: 2018年7月13日下午4:06:36
     * @author: Hhb
     * @param unknown $code_value
     */
    public function getInfoByValue($code_value){
        return $this->where(['code_value'=>$code_value])->find();
    }
    /**
     * 根据领取者code获取最后一条领取数据
     * @date: 2018年7月9日下午2:50:07
     * @author: Hhb
     * @param unknown $user_code
     */
    public function getLastReceiveByCode($user_code,$activity_id){
        return $this->where(['user_code'=>$user_code,'activity_id'=>$activity_id,'end_time'=>['gt',0]])->order('end_time DESC')->find();
    }

    /**
     *查询二维码的详情
     * @param array $where
     * @param string $order
     * @param int $page
     * @return \think\Paginator
     * @Author: Lcs
     * @Date: 2018/7/11 18:02
     */
    public function getDetailsPage($where=array(),$order='id DESC',$page=20){
        $result=$this->alias('a')
            ->join('user u','a.user_id=u.id','left')
            ->join('user_activity c','a.activity_id=c.id','left')
            ->where($where)
            ->order($order)
            ->field('a.*,u.user_nickname,u.mobile,c.activity_name')
            ->paginate($page);
        return $result;
    }

    /**
     *
     * @Author: Ldm
     * @FunctionName:根据条件查询所有未使用的二维码
     * @Date:2018/7/12 0012
     */
    public function getAgentBetween($where=''){
        $where['status']=2;
        $result=$this->where($where)->select()->toArray();
        return $result;
    }
    /**
     *
     * @Author: Ldm
     * @FunctionName:修改红包
     * @Date:2018/6/9 0009
     */
    public function setAllotCode($where='',$arrayData){
        $status=$this->isUpdate(true,$where)->save($arrayData);
        if($status){
            return $status;
        }
        return false;
    }
}