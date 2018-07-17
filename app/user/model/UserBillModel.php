<?php
/**
 * 用户资金流水数据模型
 *
 * @date: 2018年6月28日下午5:13:27
 * @author: Hhb
 */
namespace app\user\model;

use think\Model;

class UserBillModel extends Model{
    protected $autoWriteTimestamp = true;
    
    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    protected function getActivityContentAttr($value){
        return cmf_replace_content_file_url(htmlspecialchars_decode($value));
    }
    
    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    protected function setActivityContentAttr($value){
        return htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode($value), true));
    }
    /**
     * 添加数据
     * @date: 2018年6月8日下午12:34:54
     * @author: Hhb
     * @param unknown $data
     */
    public function addData($data){
        $re = $this->allowField(true)->isUpdate(false)->data($data, true)->save();
        if($re){
            return $this->data[$this->pk];
        }else{
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
    public function editData($data){
        return $this->allowField(true)->isUpdate(true)->data($data, true)->save();
    }
    /**
     * 根据ID获取一条数据
     * @date: 2018年6月8日下午12:35:07
     * @author: Hhb
     * @param unknown $id
     */
    public function getInfoById($id){
        return $this->where(['id'=>$id])->find();
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
        $result = $this->where($where)->order('id DESC')->paginate($limit);
        return $result;
    }
    /**
     * 获取所有数据
     * @date: 2018年6月8日下午12:35:22
     * @author: Hhb
     * @param array $where
     * @return unknown
     */
    public function getDataList($where = array()){
        $result = $this->where($where)->order('id DESC')->column('*','id');
        return $result;
    }

    /**
     *查询用户的资金流水
     * @param $user_id
     * @Author: Lcs
     * @Date: 2018/7/11 17:07
     */
    public function getDataByUserid($user_id,$where,$page=20){
        $result=$this->where(array('user_id'=>$user_id))->where($where)->paginate($page);
        return $result;
    }

}