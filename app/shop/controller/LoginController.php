<?php
/**
 *
 * @Author: Lcs
 * @Date: 2018/6/2 15:53
 */

namespace app\shop\controller;


use app\shop\model\UserShopModel;
use app\user\model\UserModel;
use cmf\controller\HomeBaseController;
use think\Validate;

class LoginController extends HomeBaseController
{
    /**
     *商户登陆
     * @Author: Lcs
     * @Date: 2018/6/2 15:54
     */
    public function login()
    {
        if (cmf_is_user_login()){
            return redirect($this->request->root() . '/');
        }else{
            if (cmf_is_mobile()){
                $this->redirect(url('mshop/Login/login'));
            }else{
                return $this->fetch();
            }
        }
    }

    /**
     *用户登陆提交
     * @Author: Lcs
     * @Date: 2018/6/2 15:57
     */
    public function loginPost()
    {
        $param = $this->request->param();
        $validate=new Validate([
            'username|帐号'=>'require',
            'pwd|密码'=>'require',
//            'code|验证码'=>'require',
        ]);
        if (!$validate->check($param)) {
            $this->error($validate->getError());
        }
       $user=new UserModel();
       $where=array(
           'mobile'=>$param['username'],
          'user_pass' => $param['pwd']
       );
        $log=$user->doMobile($where);
        if ($log['code']==1){
            cmf_user_action('login');
            $user=cmf_get_current_user();
            if ($user['user_type']==3){
                //商户
                $this->success('登录成功',url('shop/Index/index'));
            }elseif ($user['user_type']==2){
                //代理
                $this->success('登录成功',url('agent/Index/index'));
            }
        }elseif ($log['code']==0){
            $this->error($log['msg']);
        }

//        switch ($log) {
//            case 0:
//
//                break;
//            case 1:
//                $this->error(lang('PASSWORD_NOT_RIGHT'));
//                break;
//            case 2:
//                $this->error('账户不存在');
//                break;
//            case 3:
//                $this->error('账号被禁止访问系统');
//                break;
//            default :
//                $this->error('未受理的请求');
//        }
    }

    /**
     *忘记密码
     * @return mixed
     * @Author: Lcs
     * @Date: 2018/6/2 16:48
     */
    public function forget()
    {
        return $this->fetch();
    }

    /**
     *忘记密码提交
     * @Author: Lcs
     * @Date: 2018/6/2 16:49
     */
    public function forgetPost()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'username' => 'require',
                'password' => 'require|min:6|max:32',
                'repassword' => 'require|min:6|max:32',
//                'verification_code' => 'require',

            ]);
            $validate->message([
                'username.require' => '请输入手机号码',
                'password.require' => '密码不能为空',
                'password.max' => '密码不能超过32个字符',
                'password.min' => '密码不能小于6个字符',
                'repassword.require' => '重复密码不能为空',
                'repassword.max' => '重复密码不能超过32个字符',
                'repassword.min' => '重复密码不能小于6个字符',
                'verification_code.require' => '手机验证码不能为空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            if ($data['repassword'] != $data['password']) {
                $this->error('两次密码不一致');
            }


            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }


            $userModel=new UserModel();
            if ($validate::is($data['username'], 'email')) {

                $log = $userModel->emailPasswordReset($data['username'], $data['password']);

            } else if (preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $data['username'])) {
                $user['mobile'] = $data['username'];
                $log = $userModel->mobilePasswordReset($data['username'], $data['password']);
            } else {
                $log = 2;
            }
            switch ($log) {
                case 0:
                    $this->success('密码重置成功', $this->request->root());
                    break;
                case 1:
                    $this->error("您的账户尚未注册");
                    break;
                case 2:
                    $this->error("您输入的账号格式错误");
                    break;
                default :
                    $this->error('未受理的请求');
            }

        } else {
            $this->error("请求错误");
        }
    }

    /**
     *退出登录
     * @Author: Lcs
     * @Date: 2018/6/4 17:58
     */
    public function logoOut(){
        session("user", null);
        $this->redirect(url('shop/Login/login'));
    }
}