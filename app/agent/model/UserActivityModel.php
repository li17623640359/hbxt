<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/14 0014
 * Time: 上午 11:38
 */

namespace app\agent\model;



use think\Db;
use think\Model;

class UserActivityModel extends Model
{
    /**
     *
     * @param array $where
     * @param int $limit
     * @param string $user_id
     * @return \think\Paginator
     * @throws \think\exception\DbException
     * @Author: Ldm
     * @FunctionName:代理和代理商下的所有商户的活动列表
     * @Date:2018/6/28 0028
     */
    public function getAgentPageList($where = array() ,$user_id='', $limit = 20)
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
        $result =Db::name('user_activity')->alias('a')
            ->field($field)
            ->where($options)
            ->join('user u','a.user_id=u.id')
            ->order('a.id DESC')
            ->paginate($limit);
        return $result;
    }
}