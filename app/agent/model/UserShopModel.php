<?php
/**
 * Created by PhpStorm.
 * User: ldm
 * Date: 2018/6/4 0004
 * Time: 15:36
 */

namespace app\agent\model;


use think\Model;
use think\Validate;

class UserShopModel extends Model
{
    /**
     * @Author: ldm
     * @FunctionName:根据条件查询单个商户的所有信息
     * @Date: 2018/6/4 0004 15:40
     *
     */
    public function findOut($where = array())
    {
        $result = $this->alias('a')
            ->where($where)
            ->join('user u', 'a.user_id=u.id')
            ->join('regions p', 'p.id=a.province')
            ->join('regions c', 'c.id=a.city')
            ->join('regions d', 'd.id=a.district')
            ->field('a.*,u.mobile,p.name p_name,c.name c_name,d.name d_name')
            ->find();
        return $result;
    }

    /**
     * @Author: ldm
     * @FunctionName:代理商商户的修改
     * @Date: 2018/6/4 0004 16:19
     *
     */
    public function updater($data,$id)
    {
        if (empty($data)) {
            return array('code' => 0, 'msg' => 'error');
        }

        $validate = new Validate([
            'shop_name|商铺名称' => 'require',
            'principal|负责人' => 'require',
            'certificate|营业执照'=>'require',
            'province|地址（省）' => 'require',
            'city|地址（市）' => 'require',
            'district|地址（区）' => 'require',
            'address|地址（详细地址）' => 'require'
        ]);

        if (!$validate->check($data)) {
            return array('code' => 0, 'msg' => $validate->getError());
        }


        $res = $this->where(array('id'=>$id))->update($data);
        if ($res) {
            return array('code' => 1, 'msg' => 'ok');
        } else {
            return array('code' => 0, 'msg' => $this->getError());
        }

    }

}