<?php
/**
 * 活动访问记录数据模型
 *
 * @date: 2018年6月7日下午3:05:30
 * @author: Hhb
 */

namespace app\user\model;

use think\Model;

class UserActivityRecordModel extends Model
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
        $options = array();
        if (!empty($where['user_id'])) {
            $options['a.user_id'] = $where['user_id'];
        }
        if (!empty($where['create_time'])) {
            $options['a.create_time'] = $where['create_time'];
        }
        $option = array();
        if (!empty($where['activity_name'])) {
            $options['a.activity_name'] = array('like', '%' . $where['activity_name'] . '%');

        }
        $field = "a.*,u.user_nickname,u.user_type,u.mobile";
        $result = $this->alias('a')
            ->field($field)
            ->where($options)
            ->where($option)
            ->join('user u', 'a.user_id=u.id')
            ->order('a.id DESC')
            ->paginate($limit);
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
        $result = $this->where($where)->order('id DESC')->select()->toArray();
        return $result;
    }
    /**
     * 记录活动操作日志（点击访问，扫码领红包）
     * @date: 2018年7月10日下午4:46:37
     * @author: Hhb
     * @param unknown $user
     * @param unknown $aid
     * @param number $type
     */
    public function setActivityRecordLog($user,$aid,$type = 1){
        if(empty($user)){
            return array('code'=>'error','status'=>0,'msg'=>'用户不能为空','value'=>'');
        }
        if(empty($aid)){
            return array('code'=>'error','status'=>0,'msg'=>'活动ID不能为空','value'=>'');
        }
        $time = time();
        $y = date('Y',$time);
        $m = date('m',$time);
        $d = date('d',$time);
        $where = array(
            'aid'=>$aid,
            'user_code'=>$user['openid'],
            'type'=>$type,
            'time_y'=>$y,
            'time_m'=>$m,
            'time_d'=>$d,
        );
        $one = $this->where($where)->find();
        if(!empty($one)){
            $data = array(
                'id'=>$one['id'],
                'num'=>$one['num']+1,
            );
            $re = $this->editData($data);
        }else{
            $data = array(
                'aid'=>$aid,
                'user_code'=>$user['openid'],
                'user_name'=>$user['name'],
                'type'=>$type,
                'time_y'=>$y,
                'time_m'=>$m,
                'time_d'=>$d,
                'num'=>1,
            );
            $re = $this->addData($data);
        }
        if(!empty($re)){
            return array('code'=>'success','status'=>1,'msg'=>'','value'=>$re);
        }else{
            return array('code'=>'error','status'=>0,'msg'=>'服务器繁忙Re','value'=>'');
        }
    }
    /**
     * 统计活动点击量
     * @date: 2018年7月10日下午7:27:53
     * @author: Hhb
     * @param unknown $user
     * @param unknown $aid
     * @param number $max_num
     */
    public function setClickNumLog($user,$aid,$max_num = 0){
        $type = 1;
        if(empty($user)){
            return array('code'=>'error','status'=>0,'msg'=>'用户不能为空','value'=>'');
        }
        if(empty($aid)){
            return array('code'=>'error','status'=>0,'msg'=>'活动ID不能为空','value'=>'');
        }
        $time = time();
        $y = date('Y',$time);
        $m = date('m',$time);
        $d = date('d',$time);
        $where = array(
            'aid'=>$aid,
            'user_code'=>$user['openid'],
            'type'=>$type,
            'time_y'=>$y,
            'time_m'=>$m,
            'time_d'=>$d,
        );
        $one = $this->where($where)->find();
        if(!empty($one)){
            $num = $one['num']+1;
            $max_num = !empty($max_num) ? $max_num : (!empty(config('click_max_num')) ? config('click_max_num') : 0);
            if(!empty($max_num) && $num > $max_num){
                return array('code'=>'error','status'=>0,'msg'=>'超过个人点击量统计最大阈值','value'=>'');
            }
            $data = array(
                'id'=>$one['id'],
                'num'=>$num,
            );
            $re = $this->editData($data);
        }else{
            $data = array(
                'aid'=>$aid,
                'user_code'=>$user['openid'],
                'user_name'=>$user['name'],
                'type'=>$type,
                'time_y'=>$y,
                'time_m'=>$m,
                'time_d'=>$d,
                'num'=>1,
            );
            $re = $this->addData($data);
        }
        if(!empty($re)){
            return array('code'=>'success','status'=>1,'msg'=>'','value'=>$re);
        }else{
            return array('code'=>'error','status'=>0,'msg'=>'服务器繁忙Re','value'=>'');
        }
    }

}