<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\user\model;

use think\Db;
use think\Model;

class UserModel extends Model
{
    protected $type = [
        'more' => 'array',
    ];

    public function doMobile($user)
    {
        $result = $this->where(array('mobile'=>$user['mobile'],'is_delete'=>1))->find();

        if (!empty($result)) {
            if ($result['user_status']==0){
//            return 2;
                return array('code'=>0,'msg'=>'您的帐号已被禁用，请联系管理员');
            }elseif($result['user_status']==2){
                return array('code'=>0,'msg'=>'您的帐号还未通过审核，请联系管理员');
            }

            $comparePasswordResult = cmf_compare_password($user['user_pass'], $result['user_pass']);
            $hookParam             = [
                'user'                    => $user,
                'compare_password_result' => $comparePasswordResult
            ];
            hook_one("user_login_start", $hookParam);
            if ($comparePasswordResult) {
                session('user', $result->toArray());
                $data = [
                    'last_login_time' => time(),
                    'last_login_ip'   => get_client_ip(0, true),
                ];
                $this->where('id', $result["id"])->update($data);
                $token = cmf_generate_user_token($result["id"], 'web');
                if (!empty($token)) {
                    session('token', $token);
                }
//                return 0;
                return array('code'=>1,'msg'=>'登录成功');
            }
//            return 1;
            return array('code'=>0,'msg'=>'密码错误');
        }
        $hookParam = [
            'user'                    => $user,
            'compare_password_result' => false
        ];
        hook_one("user_login_start", $hookParam);
//        return 2;
        return array('code'=>0,'msg'=>'账户不存在');
    }

    public function doName($user)
    {
        $result = $this->where('user_login', $user['user_login'])->find();
        if (!empty($result)) {
            $comparePasswordResult = cmf_compare_password($user['user_pass'], $result['user_pass']);
            $hookParam             = [
                'user'                    => $user,
                'compare_password_result' => $comparePasswordResult
            ];
            hook_one("user_login_start", $hookParam);
            if ($comparePasswordResult) {
                //拉黑判断。
                if ($result['user_status'] == 0) {
                    return 3;
                }
                session('user', $result->toArray());
                $data = [
                    'last_login_time' => time(),
                    'last_login_ip'   => get_client_ip(0, true),
                ];
                $result->where('id', $result["id"])->update($data);
                $token = cmf_generate_user_token($result["id"], 'web');
                if (!empty($token)) {
                    session('token', $token);
                }
                return 0;
            }
            return 1;
        }
        $hookParam = [
            'user'                    => $user,
            'compare_password_result' => false
        ];
        hook_one("user_login_start", $hookParam);
        return 2;
    }

    public function doEmail($user)
    {

        $result = $this->where('user_email', $user['user_email'])->find();

        if (!empty($result)) {
            $comparePasswordResult = cmf_compare_password($user['user_pass'], $result['user_pass']);
            $hookParam             = [
                'user'                    => $user,
                'compare_password_result' => $comparePasswordResult
            ];
            hook_one("user_login_start", $hookParam);
            if ($comparePasswordResult) {

                //拉黑判断。
                if ($result['user_status'] == 0) {
                    return 3;
                }
                session('user', $result->toArray());
                $data = [
                    'last_login_time' => time(),
                    'last_login_ip'   => get_client_ip(0, true),
                ];
                $this->where('id', $result["id"])->update($data);
                $token = cmf_generate_user_token($result["id"], 'web');
                if (!empty($token)) {
                    session('token', $token);
                }
                return 0;
            }
            return 1;
        }
        $hookParam = [
            'user'                    => $user,
            'compare_password_result' => false
        ];
        hook_one("user_login_start", $hookParam);
        return 2;
    }

    public function registerEmail($user)
    {
        $userQuery = Db::name("user");
        $result    = $userQuery->where('user_email', $user['user_email'])->find();

        $userStatus = 1;

        if (cmf_is_open_registration()) {
            $userStatus = 2;
        }

        if (empty($result)) {
            $data   = [
                'user_login'      => '',
                'user_email'      => $user['user_email'],
                'mobile'          => '',
                'user_nickname'   => '',
                'user_pass'       => cmf_password($user['user_pass']),
                'last_login_ip'   => get_client_ip(0, true),
                'create_time'     => time(),
                'last_login_time' => time(),
                'user_status'     => $userStatus,
                "user_type"       => 2,
            ];
            $userId = $userQuery->insertGetId($data);
            $data   = $userQuery->where('id', $userId)->find();
            cmf_update_current_user($data);
            $token = cmf_generate_user_token($userId, 'web');
            if (!empty($token)) {
                session('token', $token);
            }
            return 0;
        }
        return 1;
    }

    public function registerMobile($user)
    {
        $result = Db::name("user")->where('mobile', $user['mobile'])->find();

        $userStatus = 1;

        if (cmf_is_open_registration()) {
            $userStatus = 2;
        }

        if (empty($result)) {
            $data   = [
                'user_login'      => '',
                'user_email'      => '',
                'mobile'          => $user['mobile'],
                'user_nickname'   => '',
                'user_pass'       => cmf_password($user['user_pass']),
                'last_login_ip'   => get_client_ip(0, true),
                'create_time'     => time(),
                'last_login_time' => time(),
                'user_status'     => $userStatus,
                "user_type"       => 2,//会员
            ];
            $userId = Db::name("user")->insertGetId($data);
            $data   = Db::name("user")->where('id', $userId)->find();
            cmf_update_current_user($data);
            cmf_update_current_user($data);
            $token = cmf_generate_user_token($userId, 'web');
            if (!empty($token)) {
                session('token', $token);
            }
            return 0;
        }
        return 1;
    }

    /**
     * 通过邮箱重置密码
     * @param $email
     * @param $password
     * @return int
     */
    public function emailPasswordReset($email, $password)
    {
        $result = $this->where('user_email', $email)->find();
        if (!empty($result)) {
            $data = [
                'user_pass' => cmf_password($password),
            ];
            $this->where('user_email', $email)->update($data);
            return 0;
        }
        return 1;
    }

    /**
     * 通过手机重置密码
     * @param $mobile
     * @param $password
     * @return int
     */
    public function mobilePasswordReset($mobile, $password)
    {
        $userQuery = Db::name("user");
        $result    = $userQuery->where('mobile', $mobile)->find();
        if (!empty($result)) {
            $data = [
                'user_pass' => cmf_password($password),
            ];
            $userQuery->where('mobile', $mobile)->update($data);
            return 0;
        }
        return 1;
    }

    public function editData($user)
    {
        $userId = cmf_get_current_user_id();

        if (isset($user['birthday'])) {
            $user['birthday'] = strtotime($user['birthday']);
        }

        if ($this->allowField('user_nickname,sex,birthday,user_url,signature,more')->save($user, ['id' => $userId])) {
            $userInfo = $this->where('id', $userId)->find();
            cmf_update_current_user($userInfo->toArray());
            return 1;
        }
        return 0;
    }

    /**
     * 用户密码修改
     * @param $user
     * @return int
     */
    public function editPassword($user)
    {
        $userId    = cmf_get_current_user_id();
        $userQuery = Db::name("user");
        if ($user['password'] != $user['repassword']) {
            return 1;
        }
        $pass = $userQuery->where('id', $userId)->find();
        if (!cmf_compare_password($user['old_password'], $pass['user_pass'])) {
            return 2;
        }
        $data['user_pass'] = cmf_password($user['password']);
        $userQuery->where('id', $userId)->update($data);
        return 0;
    }

    public function comments()
    {
        $userId               = cmf_get_current_user_id();
        $userQuery            = Db::name("Comment");
        $where['user_id']     = $userId;
        $where['delete_time'] = 0;
        $favorites            = $userQuery->where($where)->order('id desc')->paginate(10);
        $data['page']         = $favorites->render();
        $data['lists']        = $favorites->items();
        return $data;
    }

    public function deleteComment($id)
    {
        $userId              = cmf_get_current_user_id();
        $userQuery           = Db::name("Comment");
        $where['id']         = $id;
        $where['user_id']    = $userId;
        $data['delete_time'] = time();
        $userQuery->where($where)->update($data);
        return $data;
    }

    /**
     * 绑定用户手机号
     */
    public function bindingMobile($user)
    {
        $userId          = cmf_get_current_user_id();
        $data ['mobile'] = $user['username'];
        Db::name("user")->where('id', $userId)->update($data);
        $userInfo = Db::name("user")->where('id', $userId)->find();
        cmf_update_current_user($userInfo);
        return 0;
    }

    /**
     * 绑定用户邮箱
     */
    public function bindingEmail($user)
    {
        $userId              = cmf_get_current_user_id();
        $data ['user_email'] = $user['username'];
        Db::name("user")->where('id', $userId)->update($data);
        $userInfo = Db::name("user")->where('id', $userId)->find();
        cmf_update_current_user($userInfo);
        return 0;
    }

    /**
     *查询用户的详细信息
     * @Author: Lcs
     * @Date: 2018/6/5 9:11
     */
    public function details(){
        $id=cmf_get_current_user_id();
        $res= $this->alias('u')
            ->where(array('u.id'=>$id,'u.is_delete'=>1,'user_status'=>1,'s.is_delete'=>1,'s.site'=>'normal'))
            ->join('user_shop s','s.user_id=u.id')
            ->field('u.balance')
            ->find();
       if ($res){
           return array('code'=>1,'msg'=>$res);
       }else{
           return array('code'=>0,'msg'=>'error');
       }
    }
    /**
     * 根据手机号获取用户信息
     * @date: 2018年6月12日下午9:39:15
     * @author: Hhb
     * @param unknown $mobile
     */
    public function getInfoByMobile($mobile){
        return $this->where(['mobile'=>$mobile])->find();
    }
    /**
     * 根据ID获取用户信息
     * @date: 2018年6月12日下午9:39:36
     * @author: Hhb
     * @param unknown $id
     */
    public function getInfoById($id){
        return $this->where(['id'=>$id,'user_status'=>1])->find();
    }
    /**
     * 获取代理商
     * @date: 2018年6月12日下午9:39:46
     * @author: Hhb
     * @param number $id
     */
    public function getAgentUserList($id = 0){
        $where = array(
            'user_type'=>2,
            'user_status'=>1,
            'is_delete'=>1
        );
        if(!empty($id)){
            $where['id'] = $id;
        }
        return $this->where($where)->select()->toArray();
    }
    /**
     * 根据代理商ID获取商户列表
     * @date: 2018年6月12日下午9:57:35
     * @author: Hhb
     * @param unknown $id
     */
    public function getShopUserByAgentId($id){
        $where = array(
            'user_type'=>3,
            'user_status'=>1,
            'parent_id'=>$id
        );
        return $this->where($where)->select()->toArray();
    }
    /**
     * 获取商户
     * @date: 2018年6月12日下午9:40:00
     * @author: Hhb
     * @param number $id
     */
    public function getShopUserList($id = 0){
        $where = array(
            'user_type'=>3,
            'user_status'=>1,
            'is_delete'=>1
        );
        if(!empty($id)){
            $where['id'] = $id;
        }
        return $this->where($where)->select()->toArray();
    }
    /**
     * 设置用户冻结金额
     * @date: 2018年6月12日下午9:39:03
     * @author: Hhb
     * @param unknown $id
     * @param unknown $money
     * @param unknown $type
     * @return boolean|unknown
     */
    public function setUserFrozenTotal($id,$money,$type){
        if(empty($type)){
            return false;
        }
        $where = array('id'=>$id);
        if($type == 'inc'){
            $re = $this->where($where)->setInc('frozen_total',$money);
        }elseif ($type == 'dec'){
            $re = $this->where($where)->setDec('frozen_total',$money);
        }
        return $re;
    }
    /**
     * 设置用户可用余额
     * @date: 2018年7月2日上午10:50:22
     * @author: Hhb
     * @param unknown $id
     * @param unknown $money
     * @param unknown $type
     * @return boolean|unknown
     */
    public function setUserBalance($id,$money,$type){
        if(empty($type)){
            return false;
        }
        $where = array('id'=>$id);
        if($type == 'inc'){
            $re = $this->where($where)->setInc('balance',$money);
        }elseif ($type == 'dec'){
            $re = $this->where($where)->setDec('balance',$money);
        }
        return $re;
    }
}
