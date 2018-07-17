<?php
/**
 * 二维码管理主控制器
 *
 * @date: 2018年6月7日上午11:24:45
 * @author: Hhb
 */

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use app\user\model\UserCodeModel;
use app\user\model\UserModel;
use Org\Util\StringExt;

/**
 * @adminMenuRoot(
 *     'name'   =>'二维码管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 300,
 *     'icon'   =>'windows',
 *     'remark' =>'二维码管理'
 * )
 *
 * @date: 2018年6月7日上午11:25:35
 * @author: Hhb
 */
class AdminCodeController extends AdminBaseController
{
    /**
     * 二维码列表
     * @adminMenu(
     *     'name'   => '二维码列表',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '二维码列表',
     *     'param'  => ''
     * )
     * @date: 2018年6月7日上午11:27:26
     * @author: Hhb
     * @return mixed|string
     */
    public function index(){
        $status = $this->request->param('status',0,'intval');
        $mobile = $this->request->param('mobile','');
        $start = $this->request->param('start','');
        $end = $this->request->param('end','');
        $param = array(
            'status'=>$status,
            'mobile'=>$mobile,
            'start'=>$start,
            'end'=>$end
        );
        $where=array();
        if(!empty($param['status'])){
            $where['status'] = $param['status'];
        }
        if(!empty($param['mobile'])){
            $user_model = new UserModel();
            $user = $user_model->getInfoByMobile($param['mobile']);
            $where['user_id'] = $user['id'];
        }
        if(!empty($param['start'])){
            $where['code_num'] = ['egt',$param['start']];
        }
        if(!empty($param['end'])){
            $where['code_num'] = [['egt',empty($param['start'])?0:$param['start']],['elt',$param['end']]];
        }
        $model = new UserCodeModel();
        $res=$model->getDetailsPage($where);
        $res->appends($param);
        $code_status = config('code_status');
        $this->assign('code_status',$code_status);
        $this->assign('param',$param);
        $this->assign('res',$res);
        $this->assign('page',$res->render());
        return $this->fetch();
    }

    /**
     * 添加二维码提交
     * @adminMenu(
     *     'name'   => '添加二维码提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加二维码提交',
     *     'param'  => ''
     * )
     * @date: 2018年6月7日上午11:28:28
     * @author: Hhb
     */
    public function addPost(){
        $param=$this->request->param();
        $id = $this->request->param('id');
        $total = $this->request->param('total');
        if($id+1 > $total){
            $this->error('已达最大数量');
        }
        $base = cmf_get_option('base_code');
        if(empty($base) || empty($base['number'])){
            $number = !empty(config('init_base_code')) ? config('init_base_code') : '10000000000';
            cmf_set_option('base_code', ['number'=>$number]);
        }else{
            $number = $base['number'];
        }
        $model = new UserCodeModel();
        $code = $number+1;
        $code_value = StringExt::keyGen();
        $content = $this->request->domain().'/User/activity/index/c/' . $code_value;
        $data = array(
            'code_num'=>$code,
            'code_value'=>$code_value,
            'code_img'=>cmf_qrcode_create($code, $content),
        );
        $re = $model->addData($data);
        if($re){
            cmf_set_option('base_code', ['number'=>$code]);
            $this->success('添加成功');
        }else{
            $this->error('添加失败');
        }
    }
    public function exportData(){
        $status = $this->request->param('status',0,'intval');
        $mobile = $this->request->param('mobile','');
        $start = $this->request->param('start','');
        $end = $this->request->param('end','');
        $param = array(
            'status'=>$status,
            'mobile'=>$mobile,
            'start'=>$start,
            'end'=>$end
        );
        $where=array();
        if(!empty($param['status'])){
            $where['status'] = $param['status'];
        }
        if(!empty($param['mobile'])){
            $user_model = new UserModel();
            $user = $user_model->getInfoByMobile($param['mobile']);
            $where['user_id'] = $user['id'];
        }
        if(!empty($param['start'])){
            $where['code_num'] = ['egt',$param['start']];
        }
        if(!empty($param['end'])){
            $where['code_num'] = [['egt',empty($param['start'])?0:$param['start']],['elt',$param['end']]];
        }
        $model = new UserCodeModel();
        $res = $model->getDataList($where);
        if(empty($res)){
            echo '<script>alert("暂时没有数据!");history.back();</script>';
            exit();
        }
        $root = CMF_ROOT.'/upload/';
        $fileList = array();
        foreach ($res as $v){
            $fileList[] = $root.$v['code_img'];
        }
        $zip_url=cmf_zip_files('code',$fileList,'',true);
    }

    /**
     * 删除二维码
     * @adminMenu(
     *     'name'   => '删除二维码',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '删除二维码',
     *     'param'  => ''
     * )
     * @date: 2018年6月7日上午11:30:04
     * @author: Hhb
     */
    public function delete(){
        $param = $this->request->param();
        $post_model = new UserCodeModel();
        if(isset($param['id'])){
            $result = $post_model->where(['id' => $param['id']])->delete();
            $this->success("删除成功！", '');
        }
        if(isset($param['ids'])){
            $ids = $this->request->param('ids/a');
            $result = $post_model->where(['id' => ['in', $ids]])->delete();
            $this->success("删除成功！", '');
        }
    }
}