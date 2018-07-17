<?php
/**
 * 首页主控制器
 *
 * @date: 2018年4月12日上午9:17:41
 * @author: Hhb
 */
namespace app\mob\controller;

use app\user\model\UserModel;
use think\Db;

class IndexController extends AdminComController
{

    public function _initialize()
    {
        $adminSettings = cmf_get_option('admin_settings');
        if (empty($adminSettings['admin_password']) || $this->request->path() == $adminSettings['admin_password']) {
            $adminId = cmf_get_current_admin_id();
            if (empty($adminId)) {
                session("__LOGIN_BY_CMF_ADMIN_PW__", 1);//设置后台登录加密码
            }
        }

        parent::_initialize();
    }

    /**
     * 首页
     * @date: 2018年4月12日上午9:26:09
     * @author: Hhb
     */
    public function index()
    {
        $session_admin_id = session('ADMIN_ID');
        if (!empty($session_admin_id)) {
            $this->redirect(url("mob/main/index"));
        } else {
            $this->redirect(url("mob/public/login"));
        }
    }
    /**
     * 密码修改
     * @date: 2018年4月12日上午9:28:16
     * @author: Hhb
     */
    public function password()
    {
        return $this->fetch();
    }
    /**
     * 密码修改提交
     * @date: 2018年4月12日上午9:28:27
     * @author: Hhb
     */
    public function passwordPost()
    {
        if ($this->request->isPost()) {
    
            $data = $this->request->param();
            if (empty($data['old_password'])) {
                $this->error("原始密码不能为空！");
            }
            if (empty($data['password'])) {
                $this->error("新密码不能为空！");
            }
    
            $userId = cmf_get_current_admin_id();
    
            $admin = Db::name('user')->where(["id" => $userId])->find();
    
            $oldPassword = $data['old_password'];
            $password    = $data['password'];
            $rePassword  = $data['re_password'];
    
            if (cmf_compare_password($oldPassword, $admin['user_pass'])) {
                if ($password == $rePassword) {
    
                    if (cmf_compare_password($password, $admin['user_pass'])) {
                        $this->error("新密码不能和原始密码相同！");
                    } else {
                        Db::name('user')->where('id', $userId)->update(['user_pass' => cmf_password($password)]);
                        $this->success("密码修改成功！");
                    }
                } else {
                    $this->error("密码输入不一致！");
                }
    
            } else {
                $this->error("原始密码不正确！");
            }
        }
    }
    /**
     * 图片上传
     * @date: 2018年4月19日下午3:13:57
     * @author: Hhb
     */
    public function upload(){
        $file   = $this->request->file('file');
        $path = $this->request->param('path');
        $type = $this->request->param('type',0,'intval');
        $result = $file->validate([
            'ext'  => 'jpg,jpeg,png,gif',
            'size' => 1024 * 1024 * 20
        ])->move('.' . DS . 'upload' . DS . $path . DS);
    
        if ($result) {
            if($type){
                $data = $file->getInfo();
                $file_id = cmf_file_save($data);
            }else{
                $file_id = 0;
            }
            $avatarSaveName = str_replace('//', '/', str_replace('\\', '/', $result->getSaveName()));
            $avatar         = $path.'/' . $avatarSaveName;
            $this->success("上传成功！",'',[
                'code' => 1,
                "msg"  => "上传成功",
                "data" => ['file' => $avatar,'file_id'=>$file_id],
                "url"  => ''
            ]);
        } else {
            $this->error("上传失败！",'',[
                'code' => 0,
                "msg"  => $file->getError(),
                "data" => "",
                "url"  => ''
            ]);
        }
    }
    /**
     * 初始化权限
     * @date: 2018年4月12日下午7:15:28
     * @author: Hhb
     */
    public function init(){
        $roles = config('roles');
        foreach ($roles as $v){
            $app = $v['app'];
            $controllerName = $v['controller'];
            $action = $v['action'];
            $param = '';
            $menuName = $v['name'];
            $authRuleName      = "{$app}/{$controllerName}/{$action}";
            $findAuthRuleCount = Db::name('auth_rule')->where([
                'app'  => $app,
                'name' => $authRuleName,
                'type' => 'admin_url'
            ])->count();
        
            if ($findAuthRuleCount == 0) {
                Db::name('auth_rule')->insert([
                    'app'   => $app,
                    'name'  => $authRuleName,
                    'type'  => 'admin_url',
                    'param' => $param,
                    'title' => $menuName
                ]);
            } else {
                Db::name('auth_rule')->where([
                    'app'  => $app,
                    'name' => $authRuleName,
                    'type' => 'admin_url',
                ])->update([
                    'param' => $param,
                    'title' => $menuName
                ]);
            }
        }
        echo 'ok';
    }
    public function sysPay(){
        $id=$this->request->param('id');
        $url=$this->request->param('url','');//回跳地址
        $user=new UserModel();
        $user_list=$user->where(['id'=>$id])->find()->toArray();
        $this->assign('user_list',$user_list);
        $this->assign('id',$id);
        $this->assign('url',$url);
        return $this->fetch();
    }
}
