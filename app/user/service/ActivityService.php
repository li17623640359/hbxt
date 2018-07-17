<?php
/**
 * 活动服务层模型
 *
 * @date: 2018年6月12日上午11:29:48
 * @author: Hhb
 */
namespace app\user\service;

use think\Db;
use app\user\model\UserModel;
use app\user\model\UserCodeModel;
use app\user\model\UserActivityModel;
use app\user\model\UserCodeExtModel;
use app\user\model\UserActivityRecordModel;
use app\user\model\UserBillModel;

class ActivityService{
    /**
     * 活动访问点击次数统计
     * @date: 2018年7月10日下午7:44:30
     * @author: Hhb
     * @param unknown $aid
     */
    public function setActivityClickNum($aid){
        $users_s = session('openid');
        if(!empty($users_s)){
            $users = json_decode($users_s,true);
        }else{
            $users = array();
        }
        $user_code = !empty($users) ? $users['openid'] : '';
        if(empty($user_code)){
            return array('code'=>'error','status'=>0,'msg'=>'请联系客服','value'=>'');
        }
        if(empty($aid)){
            return array('code'=>'error','status'=>0,'msg'=>'无效请求','value'=>'');
        }
        $act_model = new UserActivityModel();
        $act = $act_model->getInfoById($aid);
        if(empty($act)){
            return array('code'=>'error','status'=>0,'msg'=>'活动不存在','value'=>'');
        }
        $user_model = new UserModel();
        $one = $user_model->getInfoById($act['user_id']);
        if($one['balance']<=0){
            return array('code'=>'error','status'=>0,'msg'=>'商户余额不足，暂时无法查看活动','value'=>'');
        }
        
        $record_model = new UserActivityRecordModel();
        $re = $record_model->setClickNumLog($users, $aid);
        if($re['status'] != 1){
            return array('code'=>'success','status'=>1,'msg'=>'无效点击','value'=>$act);
        }
        
        $temp_max_num = config('temp_max_num');
        $temp_num = $act['temp_num']+1;
        if($temp_num < $temp_max_num){
            $data = array(
                'id'=>$aid,
                'temp_num'=>$temp_num
            );
            $act_model->editData($data);
        }else{
            $data = array(
                'id'=>$aid,
                'temp_num'=>0,
                'click_num'=>$act['click_num']+$temp_num
            );
            $act_model->editData($data);
            $click_money = config('click_money');
            $money = $temp_num * $click_money;
            $user_model->setUserBalance($act['user_id'], $money, 'dec');
            $user = $user_model->getInfoById($act['user_id']);
            $bill_model = new UserBillModel();
            $set = array(
                'user_id'=>$act['user_id'],
                'bill_type'=>2,
                'bill_kind'=>'server',
                'curr_amount'=>$money,
                'curr_balance'=>$user['balance'],
                'link_id'=>$aid,
            
            );
            $bill_model->addData($set);
        }
        return array('code'=>'success','status'=>1,'msg'=>'统计完成','value'=>$act);
    }
    /**
     * 领取红包
     * @date: 2018年7月10日下午3:54:22
     * @author: Hhb
     * @param unknown $data
     */
    public function receivePacket($data){
        if(empty($data)){
            return array('code'=>'error','status'=>0,'msg'=>'参数异常','value'=>'');
        }
        $users_s = session('openid');
        if(!empty($users_s)){
            $users = json_decode($users_s,true);
        }else{
            $users = array();
        }
        $user_code = !empty($users) ? $users['openid'] : '';
        if(empty($user_code)){
            return array('code'=>'error','status'=>0,'msg'=>'请咨询商家','value'=>'');
        }
        if($data['type'] == 1){
            $code_model = new UserCodeModel();
            $one = $code_model->getInfoById($data['cid']);
        }else{
            $ext_code_model = new UserCodeExtModel();
            $one = $ext_code_model->getInfoById($data['cid']);
        }
        if(empty($one)){
            return array('code'=>'error','status'=>0,'msg'=>'服务器繁忙A','value'=>'');
        }
        if(!empty($one['user_code']) && !empty($one['end_time'])){
            return array('code'=>'error','status'=>0,'msg'=>'红包已被领取过啦','value'=>'');
        }
        Db::startTrans();
        //1.处理红包表
        $time = time();
        if($data['type'] == 1){
            $set = array(
                'id'=>$data['cid'],
                'end_time'=>$time,
                'status'=>4,
            );
            $re = $code_model->editData($set);
        }else{
            $set = array(
                'eid'=>$data['cid'],
                'end_time'=>$time,
            );
            $re = $ext_code_model->editData($set);
        }
        if(empty($re)){
            Db::rollback();
            return array('code'=>'error','status'=>0,'msg'=>'服务器繁忙B','value'=>'');
        }
        //2.扣除活动举办者相关资金
        $user_id = $data['user_id'];
        $user_model = new UserModel();
        $user_one = $user_model->getInfoById($user_id);
        if($user_one['balance'] < $data['money']){
            return array('code'=>'error','status'=>0,'msg'=>'商户余额不足','value'=>'');
        }
        $re = $user_model->setUserBalance($user_id, $data['money'], 'dec');
        if(empty($re)){
            Db::rollback();
            return array('code'=>'error','status'=>0,'msg'=>'服务器繁忙C','value'=>'');
        }
        $re = $user_model->setUserFrozenTotal($user_id, $data['money'], 'dec');
        if(empty($re)){
            Db::rollback();
            return array('code'=>'error','status'=>0,'msg'=>'服务器繁忙D','value'=>'');
        }
        //3.记录资金流水
        $bill_model = new UserBillModel();
        $user = $user_model->getInfoById($user_id);
        $set = array(
            'user_id'=>$user_id,
            'bill_type'=>2,
            'bill_kind'=>'packet',
            'curr_amount'=>$data['money'],
            'curr_balance'=>$user['balance'],
            'link_id'=>$data['type'].'_'.$data['cid'],
            
        );
        $re = $bill_model->addData($set);
        if(empty($re)){
            Db::rollback();
            return array('code'=>'error','status'=>0,'msg'=>'服务器繁忙E','value'=>'');
        }
        //4.调用公众号发放红包TODO::
        
        Db::commit();
        return array('code'=>'success','status'=>1,'msg'=>"领取成功",'value'=>'');
    }
    /**
     * 根据二维码检测活动
     * @date: 2018年7月9日下午3:34:15
     * @author: Hhb
     * @param unknown $code_num
     */
    public function checkActivityByCode($code_num){
        if(empty($code_num)){
            return array('code'=>'error','status'=>0,'msg'=>'参数异常','value'=>'');
        }
        $users_s = session('openid');
        if(!empty($users_s)){
            $users = json_decode($users_s,true);
        }else{
            $users = array();
        }
        $user_code = !empty($users) ? $users['openid'] : '';
        if(empty($user_code)){
            return array('code'=>'error','status'=>0,'msg'=>'请咨询商家','value'=>'');
        }
        $code_model = new UserCodeModel();
        $ext_code_model = new UserCodeExtModel();
        
        $code = $code_model->getInfoByValue($code_num);
        if(empty($code)){
            return array('code'=>'error','status'=>0,'msg'=>'二维码红包不存在','value'=>'');
        }
        if($code['status'] == 1 || $code['status'] == 2){
            return array('code'=>'error','status'=>0,'msg'=>'二维码红包未激活','value'=>'');
        }
        if($code['status'] == 4){
            return array('code'=>'error','status'=>0,'msg'=>'二维码红包已被领取','value'=>'');
        }
        $activity_model = new UserActivityModel();
        $activity = $activity_model->getInfoById($code['activity_id']);
        if(empty($activity)){
            return array('code'=>'error','status'=>0,'msg'=>'活动不存在','value'=>'');
        }
        if(empty($activity['is_examine'])){
            return array('code'=>'error','status'=>0,'msg'=>'活动尚未开始','value'=>'');
        }
        $time = time();
        if(!empty($activity['start_time']) && $activity['start_time'] > $time){
            return array('code'=>'error','status'=>0,'msg'=>'活动时间未到，敬请等待','value'=>'');
        }
        if(!empty($activity['end_time']) && $activity['end_time'] < $time){
            return array('code'=>'error','status'=>0,'msg'=>'活动已经结束','value'=>'');
        }
        if(!empty($activity['is_limit'])){
            $ext_time = $time - $activity['is_limit']*3600;
            if($activity['type'] == 1){
                $record = $code_model->getLastReceiveByCode($user_code,$code['activity_id']);
                if(!empty($record) && $record['end_time']>$ext_time){
                    return array('code'=>'error','status'=>0,'msg'=>'短时间内，无法再次领取','value'=>'');
                }
            }else{
                $record = $ext_code_model->getLastReceiveByCode($user_code,$code['activity_id']);
                if(!empty($record) && $record['end_time']>$ext_time){
                    return array('code'=>'error','status'=>0,'msg'=>'短时间内，无法再次领取','value'=>'');
                }
            }
        }
        if($activity['type'] == 2){
            $has_scan = $ext_code_model->getScanPacketByUserCode($user_code, $code['activity_id']);
            if(empty($has_scan)){
                $first = $ext_code_model->getFirstNotReceiveCode($code['activity_id']);
                if(empty($first)){
                    return array('code'=>'error','status'=>0,'msg'=>'红包已被领完，下次早点啦','value'=>'');
                }
            }else{
                $first = $has_scan;
            }
            $data = array(
                'aid'=>$code['activity_id'],
                'cid'=>$first['eid'],
                'money'=>$first['money'],
                'type'=>2,
                'user_id'=>$code['user_id']
            );
        }else{
            if(!empty($code['end_time'])){
                return array('code'=>'error','status'=>0,'msg'=>'红包已被领取过','value'=>'');
            }
            if(!empty($code['user_code']) && $code['user_code'] != $user_code){
                return array('code'=>'error','status'=>0,'msg'=>'二维码红包已被扫码过','value'=>'');
            }
            $data = array(
                'aid'=>$code['activity_id'],
                'cid'=>$code['id'],
                'money'=>$code['money'],
                'type'=>1,
                'user_id'=>$code['user_id']
            );
        }
        
        $set = array(
            'user_code'=>$users['openid'],
            'user_name'=>$users['name'],
        );
        //1.记录用户扫码红包
        Db::startTrans();
        if($activity['type'] == 2){
            $set['eid'] = $first['eid'];
            $re = $ext_code_model->editData($set);
        }else{
            $set['id'] = $code['id'];
            $re = $code_model->editData($set);
        }
        if(empty($re)){
            Db::rollback();
            return array('code'=>'error','status'=>0,'msg'=>'服务器繁忙DB','value'=>'');
        }
        //2.记录用户扫码日志
        $act_record_model = new UserActivityRecordModel();
        $re = $act_record_model->setActivityRecordLog($users, $code['activity_id'], 2);
        if($re['status'] != 1){
            Db::rollback();
            return $re;
        }
        //3.设置活动扫码红包数量
        $activity_model->setNum($code['activity_id'],2);
        Db::commit();
        return array('code'=>'success','status'=>1,'msg'=>'','value'=>$data);
    }
    /**
     * 发布活动时获取下拉选择列表
     * @date: 2018年6月12日下午10:01:50
     * @author: Hhb
     * @param string $type
     * @param number $id//$type = agent,shop时，$id为必传参数
     */
    public function getSelectUser($type = 'admin',$id = 0){
        $user_model = new UserModel();
        if(in_array($type, ['agent','shop']) && empty($id)){
            return false;
        }
        switch ($type){
            case 'admin':
                $agent = $user_model->getAgentUserList();
                $shop = $user_model->getShopUserList();
                $users = array_merge($agent,$shop);
                break;
            case 'agent':
                $agent = $user_model->getAgentUserList($id);
                $shop = $user_model->getShopUserByAgentId($id);
                $users = array_merge($agent,$shop);
                break;
            case 'shop':
                $users = $user_model->getShopUserList($id);
                break;
            default:
                $agent = $user_model->getAgentUserList();
                $shop = $user_model->getShopUserList();
                $users = array_merge($agent,$shop);
                break;
        }
        return $users;
    }
    /**
     * 编辑活动
     * @date: 2018年6月13日上午10:44:31
     * @author: Hhb
     * @param unknown $data
     * @return string[]|number[]
     */
    public function editActivity($data){
        //1.参数初始校验
        if(empty($data['id'])){
            return array('code'=>'error','status'=>0,'msg'=>'参数异常','value'=>'');
        }
        if(empty($data['activity_name'])){
            return array('code'=>'error','status'=>0,'msg'=>'活动名称不能为空','value'=>'');
        }
        if(empty($data['start_time'])){
            return array('code'=>'error','status'=>0,'msg'=>'请选择活动开始时间','value'=>'');
        }else{
            $start_time = strtotime($data['start_time']);
            $data['start_time'] = $start_time;
        }
        if(empty($data['end_time'])){
            return array('code'=>'error','status'=>0,'msg'=>'请选择活动结束时间','value'=>'');
        }else{
            $end_time = strtotime($data['end_time']);
            $data['end_time'] = $end_time;
        }
        if($end_time <= $start_time){
            return array('code'=>'error','status'=>0,'msg'=>'活动结束时间早于开始时间','value'=>'');
        }
        if(empty($data['activity_content'])){
            return array('code'=>'error','status'=>0,'msg'=>'活动内容不能为空','value'=>'');
        }
        $model = new UserActivityModel();
        $re = $model->editData($data);
        if($re){
            return array('code'=>'success','status'=>1,'msg'=>'编辑成功','value'=>'');
        }else{
            return array('code'=>'error','status'=>0,'msg'=>'服务器繁忙','value'=>'');
        }
    }
    /**
     * 发布活动
     * @date: 2018年6月12日下午9:41:11
     * @author: Hhb
     * @param unknown $data
     */
    public function addActivity($data,$is_examine=0){
        //1.参数初始校验
        if(empty($data['activity_name'])){
            return array('code'=>'error','status'=>0,'msg'=>'活动名称不能为空','value'=>'');
        }
        if(empty($data['start_time'])){
            return array('code'=>'error','status'=>0,'msg'=>'请选择活动开始时间','value'=>'');
        }else{
            $start_time = strtotime($data['start_time']);
            $data['start_time'] = $start_time;
        }
        if(empty($data['end_time'])){
            return array('code'=>'error','status'=>0,'msg'=>'请选择活动结束时间','value'=>'');
        }else{
            $end_time = strtotime($data['end_time']);
            $data['end_time'] = $end_time;
        }
        if($end_time <= $start_time){
            return array('code'=>'error','status'=>0,'msg'=>'活动结束时间早于开始时间','value'=>'');
        }
        if(empty($data['activity_content'])){
            return array('code'=>'error','status'=>0,'msg'=>'活动内容不能为空','value'=>'');
        }
        if(empty($data['user_id'])){
            return array('code'=>'error','status'=>0,'msg'=>'活动举办者不能为空','value'=>'');
        }
        if(empty($data['type'])){
            return array('code'=>'error','status'=>0,'msg'=>'请选择活动类型','value'=>'');
        }
        if(empty($data['total_money'])){
            return array('code'=>'error','status'=>0,'msg'=>'活动总金额不能为空','value'=>'');
        }
        if(empty($data['packet_num'])){
            return array('code'=>'error','status'=>0,'msg'=>'红包数量不能为空','value'=>'');
        }
        if($data['total_money']<$data['packet_num']){
            return array('code'=>'error','status'=>0,'msg'=>'活动总金额配置不足，共需'.$data['packet_num'].'元','value'=>'');
        }
        if(empty($data['packet_type'])){
            return array('code'=>'error','status'=>0,'msg'=>'请选择红包分配方式','value'=>'');
        }
        $packet_ext = array();
        $fixed_ext = array();
        if(in_array($data['packet_type'], [1,3])){
            $big_count = 0;
            $big_num = 0;
            if(isset($data['packet_ext'])){
                if(empty($data['packet_ext'])){
                    return array('code'=>'error','status'=>0,'msg'=>'请配置大额红包','value'=>'');
                }
                $packet_ext = $data['packet_ext'];
                $min = 0;
                $max = 0;
                foreach ($packet_ext as $v){
                    if(empty($v['num'])){
                        return array('code'=>'error','status'=>0,'msg'=>'请设置大额红包数量','value'=>'');
                    }
                    if($v['num'] > $data['packet_num']){
                        return array('code'=>'error','status'=>0,'msg'=>'大额红包数量超出红包总数量','value'=>'');
                    }
                    if(empty($v['min'])){
                        return array('code'=>'error','status'=>0,'msg'=>'请设置大额红包最小金额','value'=>'');
                    }
                    if(empty($v['max'])){
                        return array('code'=>'error','status'=>0,'msg'=>'请设置大额红包最大金额','value'=>'');
                    }
                    if($v['min'] > $v['max']){
                        return array('code'=>'error','status'=>0,'msg'=>'请正确设置最大、小金额','value'=>'');
                    }
                    if($v['num'] * $v['min'] > $data['total_money'] || $v['num'] * $v['min'] > $data['total_money']){
                        return array('code'=>'error','status'=>0,'msg'=>'大额红包配额不能超过活动总额','value'=>'');
                    }
                    $min += $v['num']*$v['min'];
                    $max += $v['num']*$v['max'];
                    $big_count += $v['num']*$v['max'];
                    $big_num += $v['num'];
                }
                
                if($big_num > $data['packet_num']){
                    return array('code'=>'error','status'=>0,'msg'=>'大额红包数量超出红包总数量','value'=>'');
                }
                if($big_count > $data['total_money']){
                    return array('code'=>'error','status'=>0,'msg'=>'大额红包总额不能超过活动总额','value'=>'');
                }
            }
            $fixed_count = 0;
            $fixed_num = 0;
            if(isset($data['fixed_ext'])){
                if(empty($data['fixed_ext'])){
                    return array('code'=>'error','status'=>0,'msg'=>'请配置定额红包','value'=>'');
                }
                $fixed_ext = $data['fixed_ext'];
                
                foreach ($fixed_ext as $v){
                    if(empty($v['num'])){
                        return array('code'=>'error','status'=>0,'msg'=>'请正确配置定额红包数量','value'=>'');
                    }
                    if(empty($v['money'])){
                        return array('code'=>'error','status'=>0,'msg'=>'请正确配置定额红包金额','value'=>'');
                    }
                    $fixed_count += $v['num']*$v['money'];
                    $fixed_num += $v['num'];
                }
                if($fixed_num > $data['packet_num']){
                    return array('code'=>'error','status'=>0,'msg'=>'定额红包数量超出红包总数量','value'=>'');
                }
                if($fixed_num + $big_num > $data['packet_num']){
                    return array('code'=>'error','status'=>0,'msg'=>'红包数量超出红包总数量','value'=>'');
                }
                if($fixed_count > $data['total_money']){
                    return array('code'=>'error','status'=>0,'msg'=>'定额红包总额不能超过活动总额','value'=>'');
                }
                if($fixed_count+$big_count > $data['total_money']){
                    return array('code'=>'error','status'=>0,'msg'=>'红包总额不能超过活动总额','value'=>'');
                }
            }
            $total = $big_count + $fixed_count + ($data['packet_num']-$fixed_num-$big_num);
            if($data['total_money'] < $total){
                return array('code'=>'error','status'=>0,'msg'=>'活动总金额配置不足，共需'.$total.'元','value'=>'');
            }
        }
        //2.参数业务校验
        $id = $data['user_id'];
        $user_model = new UserModel();
        $user = $user_model->getInfoById($id);
        if(empty($user)){
            return array('code'=>'error','status'=>0,'msg'=>'用户不存在','value'=>'');
        }
        $bili = 100-config('bili');
        if($data['total_money'] > ($user['balance']-$user['frozen_total'])*$bili/100){
            //return array('code'=>'error','status'=>0,'msg'=>"活动总金额不能超过其账户余额的{$bili}%",'value'=>'');
            return array('code'=>'error','status'=>0,'msg'=>"账户余额不足",'value'=>'');
        }
        $code_model = new UserCodeModel();
        $user_code = $code_model->getUserCodeNums($id,2);
        if(empty($user_code) || empty($user_code[0]['status'])){
            $num = 0;
        }else{
            $num = $user_code[0]['total'];
        }
        if($data['type'] == 1){
            $code_num = $data['packet_num'];
        }else{
            $code_num = 1;
        }
        if($code_num > $num){
            return array('code'=>'error','status'=>0,'msg'=>"红包数量不能超过其二维码余量",'value'=>'');
        }
        $code_arr = $code_model->getUserCodeList($id, $code_num);
        if(empty($code_arr) || count($code_arr)!= $code_num){
            return array('code'=>'error','status'=>0,'msg'=>"二维码余量异常，请联系客服",'value'=>'');
        }
        //3.业务流程
        $set = array(
            'activity_name'=>$data['activity_name'],
            'activity_remark'=>$data['activity_remark'],
            'activity_content'=>$data['activity_content'],
            'activity_img'=>$data['activity_img'],
            'activity_video'=>$data['activity_video'],
            'user_id'=>$data['user_id'],
            'type'=>$data['type'],
            'is_limit'=>$data['is_limit'],
            'is_examine'=>$is_examine,
            'action_id'=>cmf_get_current_admin_id(),
            'total_money'=>$data['total_money'],
            'packet_num'=>$data['packet_num'],
            'packet_type'=>$data['packet_type'],
            'packet_ext'=>json_encode($packet_ext,JSON_UNESCAPED_UNICODE),
            'fixed_ext'=>json_encode($fixed_ext,JSON_UNESCAPED_UNICODE),
            'start_time'=>$data['start_time'],
            'end_time'=>$data['end_time']
        );
        switch ($data['packet_type']){
            case 1:
                $re = $this->fixedRandomPacket($data['total_money'], $data['packet_num'], $packet_ext, $fixed_ext);
                //$re = $this->bigRandomPacket($data['total_money'], $data['packet_num'], $data['packet_ext']);
                break;
            case 2:
                $re = $this->randomPacket($data['total_money'], $data['packet_num']);
                break;
            case 3:
                $re = $this->fixedAvgPacket($data['total_money'], $data['packet_num'], $packet_ext, $fixed_ext);
                //$re = $this->bigAvgPacket($data['total_money'], $data['packet_num'], $data['packet_ext']);
                break;
            case 4:
                $re = $this->avgPacket($data['total_money'], $data['packet_num']);
                break;
            default:
                return array('code'=>'error','status'=>0,'msg'=>'红包分配方式异常','value'=>'');
                break;
        }
        Db::startTrans();
        //3.1添加活动
        $activity_model = new UserActivityModel();
        $aid = $activity_model->addData($set);
        if(empty($aid)){
            Db::rollback();
            return array('code'=>'error','status'=>0,'msg'=>"服务器繁忙A",'value'=>'');
        }
        //3.2绑定二维码
        if($data['type'] == 1){
            $arr = array();
            foreach ($re['arr'] as $k=>$v){
                $temp = array(
                    'id'=>$code_arr[$k]['id'],
                    'use_time'=>time(),
                    'update_time'=>time(),
                    'status'=>3,
                    'activity_id'=>$aid,
                    'activity_type'=>1,
                    'money'=>$v
                );
                $arr[] = $temp;
            }
            $rs = $code_model->saveAll($arr);
            if(!$rs){
                Db::rollback();
                return array('code'=>'error','status'=>0,'msg'=>"服务器繁忙B",'value'=>'');
            }
        }else{
            $arr_temp = array(
                'id'=>$code_arr[0]['id'],
                'use_time'=>time(),
                'update_time'=>time(),
                'status'=>3,
                'activity_id'=>$aid,
                'activity_type'=>2,
                'money'=>0
            );
            $rs = $code_model->editData($arr_temp);
            if(!$rs){
                Db::rollback();
                return array('code'=>'error','status'=>0,'msg'=>"服务器繁忙C",'value'=>'');
            }
            $arr = array();
            $code_ext_model = new UserCodeExtModel();
            foreach ($re['arr'] as $k=>$v){
                $temp = array(
                    'code_num'=>$k+1,
                    'aid'=>$aid,
                    'cid'=>$code_arr[0]['id'],
                    'create_time'=>time(),
                    'update_time'=>time(),
                    'money'=>$v
                );
                $arr[] = $temp;
            }
            $rs = $code_ext_model->saveAll($arr);
            if(!$rs){
                Db::rollback();
                return array('code'=>'error','status'=>0,'msg'=>"服务器繁忙D",'value'=>'');
            }
        }
        //3.3冻结用户金额
        $rs = $user_model->setUserFrozenTotal($id, $data['total_money'], 'inc');
        if(!$rs){
            Db::rollback();
            return array('code'=>'error','status'=>0,'msg'=>"服务器繁忙E",'value'=>'');
        }
        Db::commit();
        return array('code'=>'success','status'=>1,'msg'=>"添加成功",'value'=>$aid);
    }
    /**
     * 定额红包，随机配置
     * @date: 2018年7月13日下午2:37:22
     * @author: Hhb
     * @param unknown $total
     * @param unknown $num
     * @param unknown $packet_ext
     * @param unknown $fixed_ext
     * @return \app\user\service\unknown[]|\app\user\service\number[]|unknown[]|number[]
     */
    public function fixedRandomPacket($total,$num,$packet_ext,$fixed_ext){
        if(empty($fixed_ext)){
            return $this->bigRandomPacket($total, $num, $packet_ext);
        }else{
            if(empty($packet_ext)){
                $fixed = $this->fixedSub($fixed_ext);
                $other = $this->randomPacket($total-$fixed['total'], $num-$fixed['num']);
                $arr = array_merge($fixed['arr'],$other['arr']);
                shuffle($arr);
                $data = array(
                    'arr'=>$arr,
                    'total'=>$total,
                    'sum'=>array_sum($arr),
                    'num'=>$num,
                    'packet_ext'=>$packet_ext,
                );
                return $data;
            }else{
                $fixed = $this->fixedSub($fixed_ext);
                $big = $this->bigRandomSub($packet_ext);
                $other = $this->randomPacket($total-$fixed['total']-$big['total'], $num-$fixed['num']-$big['num']);
                $arr = array_merge($fixed['arr'],$big['arr'],$other['arr']);
                shuffle($arr);
                $data = array(
                    'arr'=>$arr,
                    'total'=>$total,
                    'sum'=>array_sum($arr),
                    'num'=>$num,
                    'packet_ext'=>$packet_ext,
                );
                return $data;
            }
        }
    }
    /**
     * 定额红包，等额配置
     * @date: 2018年7月13日下午2:37:47
     * @author: Hhb
     * @param unknown $total
     * @param unknown $num
     * @param unknown $packet_ext
     * @param unknown $fixed_ext
     * @return \app\user\service\unknown[]|\app\user\service\number[]|unknown[]|number[]|unknown[]|number[]
     */
    public function fixedAvgPacket($total,$num,$packet_ext,$fixed_ext){
        if(empty($fixed_ext)){
            return $this->bigAvgPacket($total, $num, $packet_ext);
        }else{
            if(empty($packet_ext)){
                $fixed = $this->fixedSub($fixed_ext);
                $other = $this->avgPacket($total-$fixed['total'], $num-$fixed['num']);
                $arr = array_merge($fixed['arr'],$other['arr']);
                shuffle($arr);
                $data = array(
                    'arr'=>$arr,
                    'total'=>$total,
                    'sum'=>array_sum($arr),
                    'num'=>$num,
                    'packet_ext'=>$packet_ext,
                );
                return $data;
            }else{
                $fixed = $this->fixedSub($fixed_ext);
                $big = $this->bigRandomSub($packet_ext);
                $other = $this->avgPacket($total-$fixed['total']-$big['total'], $num-$fixed['num']-$big['num']);
                $arr = array_merge($fixed['arr'],$big['arr'],$other['arr']);
                shuffle($arr);
                $data = array(
                    'arr'=>$arr,
                    'total'=>$total,
                    'sum'=>array_sum($arr),
                    'num'=>$num,
                    'packet_ext'=>$packet_ext,
                );
                return $data;
            }
        }
    }
    /**
     * 大额红包随机，随机配置
     * @date: 2018年6月12日下午8:19:34
     * @author: Hhb
     * @param unknown $total
     * @param unknown $num
     * @param unknown $packet_ext
     * @return \app\user\service\unknown[]|\app\user\service\number[]|unknown[]|number[]
     */
    public function bigRandomPacket($total,$num,$packet_ext){
        if(empty($packet_ext)){
            return $this->randomPacket($total, $num);
        }
        $big = $this->bigRandomSub($packet_ext);
        $other = $this->randomPacket($total-$big['total'], $num-$big['num']);
        $arr = array_merge($big['arr'],$other['arr']);
        $data = array(
            'arr'=>$arr,
            'total'=>$total,
            'sum'=>array_sum($arr),
            'num'=>$num,
            'packet_ext'=>$packet_ext,
        );
        return $data;
    }
    /**
     * 大额红包随机，等额配置
     * @date: 2018年6月12日下午8:15:14
     * @author: Hhb
     * @param unknown $total
     * @param unknown $num
     * @param unknown $packet_ext
     */
    public function bigAvgPacket($total,$num,$packet_ext){
        if(empty($packet_ext)){
            return $this->avgPacket($total, $num);
        }
        $big = $this->bigRandomSub($packet_ext);
        $other = $this->avgPacket($total-$big['total'], $num-$big['num']);
        $arr = array_merge($big['arr'],$other['arr']);
        $data = array(
            'arr'=>$arr,
            'total'=>$total,
            'sum'=>array_sum($arr),
            'num'=>$num,
            'packet_ext'=>$packet_ext,
        );
        return $data;
    }
    
    /**
     * 等额配置
     * @date: 2018年6月12日下午6:05:51
     * @author: Hhb
     * @param unknown $total
     * @param unknown $num
     * @return unknown[]|number[]
     */
    public function avgPacket($total,$num){
        $rs = $this->avgSub($total, $num);
        $arr = array();
        if(!empty($rs['avg_num'])){
            for ($i=0;$i<$rs['avg_num'];$i++){
                $arr[] = $rs['avg'];
            }
        }
        if(!empty($rs['reset_avg_num'])){
            for ($i=0;$i<$rs['reset_avg_num'];$i++){
                $arr[] = $rs['reset_avg'];
            }
        }
        shuffle($arr);
        $data = array(
            'arr'=>$arr,
            'total'=>$total,
            'sum'=>array_sum($arr),
            'num'=>$num,
            'temp'=>$rs
        );
        return $data;
    }
    /**
     * 随机配置
     * @date: 2018年6月12日下午6:06:00
     * @author: Hhb
     * @param unknown $total
     * @param unknown $num
     */
    public function randomPacket($total,$num){
        $re = $this->randomSub($total, $num);
        $remain = $total - $re['cur'];
        $arr = $re['arr'];
        
        /* $re1 = $this->randomSub($remain, $num);
        $remain = $remain - $re1['cur'];
        $arr = array();
        foreach($re['arr'] as $key => $value){
            $arr[$key] = $value + $re1['arr'][$key];
        } */
        
        $rs = $this->avgSub($remain, floor($num/2));
        $a = array();
        if(!empty($rs['avg_num'])){
            for ($i=0;$i<$rs['avg_num'];$i++){
                $k = $this->randomKey($num,$a);
                $arr[$k] = $arr[$k] + $rs['avg'];
            }
        }
        if(!empty($rs['reset_avg_num'])){
            for ($i=0;$i<$rs['reset_avg_num'];$i++){
                $k = $this->randomKey($num,$a);
                $arr[$k] = $arr[$k] + $rs['reset_avg'];
            }
        }
        //shuffle($arr);
        $data = array(
            'arr'=>$arr,
            'total'=>$total,
            'sum'=>array_sum($arr),
            'num'=>$num
        );
        return $data;
    }
    /**
     * 定额红包子方法
     * @date: 2018年7月13日上午10:55:16
     * @author: Hhb
     * @param unknown $packet
     */
    protected function fixedSub($packet){
        $c_num = 0;
        $arr = array();
        foreach ($packet as $v){
            for ($i=0;$i<$v['num'];$i++){
                $arr[] = $v['money'];
            }
            $c_num += $v['num'];
        }
        $data = array(
            'arr'=>$arr,
            'total'=>array_sum($arr),
            'num'=>$c_num,
            'packet'=>$packet
        );
        return $data;
    }
    /**
     * 大额红包随机子方法
     * @date: 2018年6月12日下午8:09:08
     * @author: Hhb
     * @param unknown $num
     * @param unknown $min
     * @param unknown $max
     */
    protected function bigRandomSub($packet){
        $c_num = 0;
        $arr = array();
        foreach ($packet as $v){
            for ($i=0;$i<$v['num'];$i++){
                $arr[] = mt_rand($v['min']*100,$v['max']*100)/100;
            }
            $c_num += $v['num'];
        }
        $data = array(
            'arr'=>$arr,
            'total'=>array_sum($arr),
            'num'=>$c_num,
            'packet'=>$packet
        );
        return $data;
    }
    /**
     * 等额子方法
     * @date: 2018年6月12日下午6:11:49
     * @author: Hhb
     * @param unknown $total
     * @param unknown $num
     */
    protected function avgSub($total,$num){
        $avg = floor($total/$num*100)/100;
        $current = $avg * $num;
        $remain = $total - $current;
        if($remain <= 0){
            $avg_num = $num;
            $reset_avg = 0;
            $reset_avg_num = 0;
        }else{
            $reset_avg_num = round($remain/0.01);
            $reset_avg = $avg + 0.01;
            $avg_num = $num - $reset_avg_num;
        }
        $count = $avg*$avg_num + $reset_avg*$reset_avg_num;
        $arr = array(
            'avg'=>$avg,
            'avg_num'=>$avg_num,
            'reset_avg'=>$reset_avg,
            'reset_avg_num'=>$reset_avg_num,
            'total'=>$total,
            'sum'=>$count,
            'num'=>$num
        );
        return $arr;
    }
    /**
     * 随机下标
     * @date: 2018年6月12日下午5:53:04
     * @author: Hhb
     * @param unknown $num
     * @param array &$a
     * @param number $deep
     */
    protected function randomKey($num, &$a = array(), $deep = 0){
        $k = mt_rand(1,$num-1);
        if(isset($a[$k])){
            if($a[$k] < 3){//最多重复3次
                $a[$k] = $a[$k]+1;
                return $k;
            }else{
                if($deep >= 10){//最多递归10次，强制返回重复
                    return $k;
                }else{
                    return $this->randomKey($num,$a,++$deep);
                }
            }
        }else{
            $a[$k] = 1;
            return $k;
        }
    }
    /**
     * 随机子方法
     * @date: 2018年6月12日下午5:52:53
     * @author: Hhb
     * @param unknown $total
     * @param unknown $num
     */
    protected function randomSub($total,$num){
        $avg = floor($total/$num*100);
        $arr = array();
        for($i=0;$i<$num;$i++){
            $arr[] = mt_rand(99,$avg-1)/100+0.01;
        }
        $cur = array_sum($arr);
        return array('arr'=>$arr,'cur'=>$cur);
    }
}