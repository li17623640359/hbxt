<?php
/**
 * Created by PhpStorm.
 * User: ldm
 * Date: 2018/6/4 0004
 * Time: 11:25
 */
namespace app\agent\model;
use think\Model;
class UserModel extends Model
{
    /**
     * @Author: ldm
     * @FunctionName:所有代理商的查询
     * @Date: 2018/6/2 0002 16:43
     *
     */
    public function agentSelete($where=''){
        $where['u.user_type']=2;
        $where['u.is_delete']=1;
        $data=$this->alias('u')->field('u.*,ua.address,count(uc.code_num) sum_code')
            ->join('user_agent ua','ua.user_id=u.id')
            ->join('user_code uc','uc.user_id=u.id and uc.status=2','left')
            ->group('u.id')
            ->where($where)
            ->paginate(30);
        if($data){
            return $data;
        }else{
            return false;
        }
    }

    /**
     * @Author: ldm
     * @FunctionName:代理商的添加
     * @Date: 2018/6/2 0002 17:29
     *
     */
    public function agentAdd($agen_data){
        $list=[];
        $agen_data['user_pass']= cmf_password($agen_data['user_pass']);
        $agen_data['create_time']= time();
        $agen_data['user_type']= 2;
        $this->db()->startTrans();
            $agen= $this->allowField(true)->save($agen_data);
            if(empty($agen)){
                $this->db()->rollback();
               return array('code'=>0,'msg'=>'用户表user添加失败');
            }
            $user_id=$this->id;
            $user_agent=new UserAgentModel();
            $list['address']=$agen_data['address'];
            $list['create_time']= time();
            $list['user_id']= $user_id;
            $status= $user_agent->allowField(true)->save($list);
            if(empty($status)){
                $this->db()->rollback();
                return array('code'=>0,'msg'=>'user_agent添加失败');
            }
            if(isset($agen_data['start_num'])&&!empty($agen_data['start_num'])&&isset($agen_data['end_num'])&&!empty($agen_data['end_num'])){
                $user_code=new UserCodeModel();
                $where['code_num']=array('between',''. $agen_data['start_num'] .','.$agen_data['end_num'] . '');
                $code_list= $user_code->getCodeMany($where);
                if(empty($code_list)){
                    $this->db()->rollback();
                    return array('code'=>0,'msg'=>'user_code请输入正确的码段');
                }
                $arrayData['status']=2;
                $arrayData['user_id']=$user_id;
                $arrayData['fenpei_time']=time();
                $status= $user_code->setAllotCode($where,$arrayData);
                if(empty($status)){
                    $this->db()->rollback();
                    return array('code'=>0,'msg'=>'user_code请输入正确的码段');
                }
            }
                   $this->db()->commit();
            return  array('code'=>1,'msg'=>'添加成功');
    }

    /**
     * @Author: ldm
     * @FunctionName:一个代理商的查询
     * @Date: 2018/6/4 0004 09:25
     *
     */
    public function agentUser($where=""){
        $where['u.user_type']=2;
        $where['u.is_delete']=1;
        $data=$this->alias('u')->field('u.*,ua.address')
              ->join('user_agent ua','ua.user_id=u.id')
              ->where($where)->find()->toArray();
        if($data){
            return $data;
        }else{
            return false;
        }
    }

    /**
     * @Author: ldm
     * @FunctionName:代理商的修改
     * @Date: 2018/6/4 0004 09:45
     *
     */
    public function agentEdit($agen_data){
        $list=[];
        $this->db()->startTrans();
            $user_agent=new UserAgentModel();
            $agent=$user_agent->where(['user_id'=>$agen_data['id']])->find()->toArray();
            if($agent['address']!=$agen_data['address']){
                $list['address']=$agen_data['address'];
                $list['user_id']= $agen_data['id'];
                $list['user_id']= $agen_data['id'];
                $status= $user_agent->isUpdate(true,['user_id'=>$agen_data['id']])->save($list);
                if(empty($status)){
                    $this->db()->rollback();
                    return array('code'=>0,'msg'=>'代理表user_agent编辑失败');
                }
            }
            unset($agen_data['address']);
            if(isset($agen_data['birthday'])){
                $agen_data['birthday']=strtotime($agen_data['birthday']);
            }
            $user= $this->isUpdate(true)->save($agen_data);
            if(empty($user)){
                   $this->db()->rollback();
            return array('code'=>0,'msg'=>'用户表user编辑失败');
        }
                   $this->db()->commit();
        return $result= array('code'=>1,'msg'=>'编辑成功');
    }

    /**
     *
     * @param $user
     * @return int
     * @Author: Ldm
     * @FunctionName:密码修改
     * @Date:2018/6/7 0007
     *
     */
    public function editPassword($user)
    {
        $userId = cmf_get_current_user_id();
        if ($user['password'] != $user['repassword']) {
            return 1;
        }
        $pass = $this->where('id', $userId)->find();
        if (!cmf_compare_password($user['old_password'], $pass['user_pass'])) {
            return 2;
        }
        $data['user_pass'] = cmf_password($user['password']);
        $this->isUpdate(true,['id'=>$userId])->save($data);
        return 0;
    }

}