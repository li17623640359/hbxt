<?php
/**
 * 活动数据模型
 *
 * @date: 2018年6月7日下午3:05:30
 * @author: Hhb
 */

namespace app\user\model;

use think\Model;

class UserActivityModel extends Model
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
     * 根据ID获取一条活动详情
     * @date: 2018年6月13日上午10:58:56
     * @author: Hhb
     * @param unknown $id
     * @return unknown
     */
    public function getDetailsById($id)
    {
        $options = array(
            'a.id' => $id
        );
        $field = "a.*,u.user_nickname,u.user_type,u.mobile";
        $result = $this->alias('a')
            ->field($field)
            ->where($options)
            ->join('user u', 'a.user_id=u.id')
            ->find();
        if (!empty($result)) {
            $temp_fixed = array();
            $temp_big = array();
            $temp_total = array();
            if (!empty($result['fixed_ext'])) {
                $temp_fixed = json_decode($result['fixed_ext'], true);
                foreach ($temp_fixed as $vv) {
                    $temp_total[] = "数量：{$vv['num']} 金额：{$vv['money']}";
                }
            }
            if (!empty($result['packet_ext'])) {
                $temp_big = json_decode($result['packet_ext'], true);
                foreach ($temp_big as $vv) {
                    $temp_total[] = "数量：{$vv['num']} 最小金额：{$vv['min']} 最大金额：{$vv['max']}";
                }
            }
            $result['packet_set_list'] = $temp_total;
        } else {
            $result['packet_set_list'] = array();
        }
        return $result;
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
        if(!empty($user_id)){
            $options['a.user_id'] =array('in',$user_id);
        }
        if(!empty($where['start_time'])){
            $options['a.start_time'] = $where['start_time'];
        }
        if(!empty($where['end_time'])){
            $options['a.end_time'] = $where['end_time'];
        }
        if(isset($where['is_examine'])){
            $options['a.is_examine'] = $where['is_examine'];
        }
        if(isset($where['site'])){
            $options['a.site'] = $where['site'];
        }
        if(!empty($where['activity_name'])){
            $options['a.activity_name'] = $where['activity_name'];
        }
        $field = "a.*,u.user_nickname,u.user_type,u.mobile";
        $result =$this->alias('a')
            ->field($field)
            ->where($options)
            ->join('user u','a.user_id=u.id')
            ->order('a.id DESC')
            ->paginate($limit);
        return $result;
    }

    /**
     *获取一个用户的所有活动
     * @param array $where
     * @return false|\PDOStatement|string|\think\Collection
     * @Author: Lcs
     * @Date: 2018/6/28 11:01
     */
    public function getDateList($where = array())
    {
        $options = array();
        if (!empty($where['user_id'])) {
            $options['a.user_id'] = $where['user_id'];
        }
        if (!empty($where['create_time'])) {
            $options['a.create_time'] = $where['create_time'];
        }

        if (!empty($where['activity_name'])) {
            $options['a.activity_name'] = array('like', '%' . $where['activity_name'] . '%');
        }

        $field = "a.*,u.user_nickname,u.user_type,u.mobile";
        $result = $this->alias('a')
            ->field($field)
            ->where($options)
            ->join('user u', 'a.user_id=u.id')
            ->order('a.id DESC')
            ->select();
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
     * 设置活动点击次数和扫码红包次数自增
     * @date: 2018年7月10日下午4:58:11
     * @author: Hhb
     * @param unknown $id
     * @param unknown $type 1点击，2扫码
     * @return boolean
     */
    public function setNum($id, $type = 1)
    {
        if (empty($id) || empty($type)) {
            return false;
        }
        if ($type == 1) {
            $field = 'click_num';
        } else {
            $field = 'record_num';
        }
        $where = array('id' => $id);
        return $this->where($where)->setInc($field);
    }

    /**
     *根据活动id修改状态
     * @param $id
     * @param $site
     * @return $this
     * @Author: Lcs
     * @Date: 2018/7/14 11:26
     */
    public function editSiteById($id, $site)
    {
        $result = $this->where(array('id' => $id))->update(array('site' => $site));
        return $result;
    }

}