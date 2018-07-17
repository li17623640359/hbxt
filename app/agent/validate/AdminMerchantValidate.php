<?php
/**
 * Created by PhpStorm.
 * User: ldm
 * Date: 2018/6/2 0002
 * Time: 17:20
 */

namespace app\agent\validate;


use think\Validate;

class AdminMerchantValidate extends Validate
{
    protected $rule = [
         'user_nickname'  => 'require',
        'avatar'  => 'require',
        'address' => 'require',
        'user_email' => 'email',
        'mobile' => 'require|min:8|max:11',
        'user_pass' => 'require|min:6',
            ];
            protected $message = [
        'user_nickname.require' => '代理商姓名不能为空',
        'avatar.require' => '头像不能为空',
        'user_pass.require' => '密码不能为空',
        'user_email' => '邮箱格式错误',
        'mobile.max'     => '电话不能超过11个字符',
        'mobile.min'     => '电话不能小于8个字符',
        'user_pass.min'     => '密码不能小于6个字符',
        'address.require'  => '地址不能为空',
    ];
}