<?php
/**
 * 活动管理主控制器
 *
 * @date: 2018年6月11日下午2:52:45
 * @author: Hhb
 */

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use app\user\model\UserCodeModel;
use app\user\model\UserModel;
use app\user\service\ActivityService;
use app\user\model\UserActivityModel;
use app\user\model\UserCodeExtModel;

/**
 * @adminMenuRoot(
 *     'name'   =>'活动管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 400,
 *     'icon'   =>'list',
 *     'remark' =>'活动管理'
 * )
 *
 * @date: 2018年6月7日上午11:25:35
 * @author: Hhb
 */
class AdminActivityController extends AdminBaseController
{
    /**
     * 活动列表
     * @adminMenu(
     *     'name'   => '活动列表',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '活动列表',
     *     'param'  => ''
     * )
     * @date: 2018年6月7日上午11:27:26
     * @author: Hhb
     * @return mixed|string
     */
    public function index()
    {
        $user_id = $this->request->param('user_id', 0, 'intval');
        $start = $this->request->param('start', '');
        $end = $this->request->param('end', '');
        $site=$this->request->param('site',-1);
        $is_examine=$this->request->param('is_examine',-1);
        $param = array(
            'user_id' => $user_id,
            'start' => $start,
//            'end' => $end,
            'site'=>$site,
            $is_examine=>$is_examine,
        );
        $where = array();
        if (!empty($param['user_id'])) {
            $where['user_id'] = $param['user_id'];
        }
        if (!empty($param['start'])) {
            $where['start_time'] = strtotime($param['start']);
        }
//        if (!empty($param['end'])) {
//            $where['create_time'] = [['egt', empty($param['start']) ? 0 : strtotime('-1 day', strtotime($param['start'] . ' 23:59:59'))], ['elt', strtotime('+1 day', strtotime($param['end'] . ' 00:00:00'))]];
//        }

        if (isset($param['site']) && $param['site'] != -1) {
            $where['site'] = $param['site'];
        }

        if (isset($param['is_examine']) && $param['is_examine'] != -1) {
            $where['is_examine'] = $param['is_examine'];
        }

        if(!empty($param['start'])){
            $timestamp = strtotime( $param['start'] );
            $start_time = date( 'Y-m-1 00:00:00', $timestamp );
            $mdays = date( 't', $timestamp );
            $end_time = date( 'Y-m-' . $mdays . ' 23:59:59', $timestamp );
            $where['start_time'] =array('between',''.strtotime( $start_time) .','.strtotime( $end_time ). '');
        }

        /* $start_time = !empty($param['start']) ? strtotime('-1 day', strtotime($param['start'] . '23:59:59')) : 0;
        $end_time = !empty($param['end']) ? strtotime('+1 day', strtotime($param['end'] . '00:00:00')) : time();
        $where['create_time'] = array('between', '' . $start . ',' . $end . ''); */

        $activity_type = config('activity_type');
        $packet_type = config('packet_type');
        $this->assign('activity_type', $activity_type);
        $this->assign('packet_type', $packet_type);
        $is_examine = config('is_examine');
        $activity_service = new ActivityService();
        $users = $activity_service->getSelectUser();
        $model = new UserActivityModel();
        $res = $model->getPageList($where);
        $res->appends($param);
        $page = $res->render();
        $lists = $res->toArray()['data'];
        if (!empty($lists)) {
            $temp_fixed = array();
            $temp_big = array();
            $temp_total = array();
            foreach ($lists as &$v) {
                if (!empty($v['fixed_ext'])) {
                    $temp_fixed = json_decode($v['fixed_ext'], true);
                    foreach ($temp_fixed as $vv) {
                        $temp_total[] = "数量：{$vv['num']} 金额：{$vv['money']}";
                    }
                }
                if (!empty($v['packet_ext'])) {
                    $temp_big = json_decode($v['packet_ext'], true);
                    foreach ($temp_big as $vv) {
                        $temp_total[] = "数量：{$vv['num']} 最小金额：{$vv['min']} 最大金额：{$vv['max']}";
                    }
                }
                if (!empty($temp_total)) {
                    $v['packet_set_list'] = implode(" <br/> ", $temp_total);
                } else {
                    $v['packet_set_list'] = '';
                }
                unset($v);
            }
        }
        $this->assign('param', $param);
        $this->assign('is_examine', $is_examine);
        $this->assign('res', $lists);
        $this->assign('page', $page);
        $this->assign('users', $users);
        $this->assign('site', config('site'));
        return $this->fetch();
    }

    /**
     *修改活动状态
     * @Author: Lcs
     * @Date: 2018/7/16 10:21
     */
    public function editSite(){
        $param=$this->request->param();
        if (!isset($param['site']) || !isset($param['id'])){
            $this->error('参数异常');
        }
        $activityModel=new UserActivityModel();
        $result=$activityModel->editSiteById($param['id'],$param['site']);
        if($result){
            $this->success('修改成功');
        }else{
            $this->error('修改失败');
        }
    }

    /**
     * 添加活动
     * @adminMenu(
     *     'name'   => '添加活动',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加活动',
     *     'param'  => ''
     * )
     * @date: 2018年6月11日下午2:55:28
     * @author: Hhb
     */
    public function add()
    {
        $activity_service = new ActivityService();
        $users = $activity_service->getSelectUser();
        $activity_type = config('activity_type');
        $packet_type = config('packet_type');
        $bili = 100 - config('bili');
        $this->assign('bili', $bili);
        $this->assign('activity_type', $activity_type);
        $this->assign('packet_type', $packet_type);
        $this->assign('users', $users);
        return $this->fetch();
    }

    /**
     * 添加活动提交
     * @adminMenu(
     *     'name'   => '添加活动提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加活动提交',
     *     'param'  => ''
     * )
     * @date: 2018年6月7日上午11:28:28
     * @author: Hhb
     */
    public function addPost()
    {
        $param = $this->request->param();
        $post = $param['post'];
        $activity_service = new ActivityService();
        $re = $activity_service->addActivity($post, 1);
        if ($re['status'] == 1) {
            $this->success('添加成功');
        } else {
            $this->error($re['msg']);
        }
    }

    /**
     * 编辑活动
     * @adminMenu(
     *     'name'   => '编辑活动',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑活动',
     *     'param'  => ''
     * )
     * @date: 2018年6月11日下午2:57:07
     * @author: Hhb
     */
    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        $model = new UserActivityModel();
        $post = $model->getInfoById($id);
        $this->assign('post', $post);
        return $this->fetch();
    }

    /**
     * 编辑活动提交
     * @adminMenu(
     *     'name'   => '编辑活动提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑活动提交',
     *     'param'  => ''
     * )
     * @date: 2018年6月11日下午2:58:03
     * @author: Hhb
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $post = $data['post'];
            $service = new ActivityService();
            $re = $service->editActivity($post);
            if ($re['status'] == 1) {
                $this->success('编辑成功', url('AdminActivity/index'));
            } else {
                $this->error($re['msg']);
            }
        } else {
            $this->error('无效请求');
        }
    }

    /**
     * 删除活动
     * @adminMenu(
     *     'name'   => '删除活动',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '删除活动',
     *     'param'  => ''
     * )
     * @date: 2018年6月7日上午11:30:04
     * @author: Hhb
     */
    public function delete()
    {
        $param = $this->request->param();
        $post_model = new UserActivityModel();
        if (isset($param['id'])) {
            $result = $post_model->where(['id' => $param['id']])->delete();
            $this->success("删除成功！", '');
        }
        if (isset($param['ids'])) {
            $ids = $this->request->param('ids/a');
            $result = $post_model->where(['id' => ['in', $ids]])->delete();
            $this->success("删除成功！", '');
        }
    }

    /**
     * 查看红包详情
     * @adminMenu(
     *     'name'   => '查看红包详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '查看红包详情',
     *     'param'  => ''
     * )
     * @date: 2018年6月13日下午12:08:16
     * @author: Hhb
     */
    public function details()
    {
        $id = $this->request->param('id', 0, 'intval');
        $activity_model = new UserActivityModel();
        $one = $activity_model->getDetailsById($id);
        $code_model = new UserCodeModel();
        $this->assign('one', $one);
        if ($one['type'] == 1) {
            $code = array();
            $res = $code_model->getActivityCodePageListById($id, $one['type']);
        } else {
            $res = $code_model->getActivityCodePageListById($id, $one['type']);
            if (!empty($res)) {
                $code = $res;
                $code_ext_model = new UserCodeExtModel();
                $res = $code_ext_model->getActivityCodePageListById($id, $code['id']);
            } else {
                $code = array();
            }
        }

        $this->assign('code', $code);
        $this->assign('res', $res);
        $this->assign('page', $res->render());

        $activity_type = config('activity_type');
        $packet_type = config('packet_type');
        $this->assign('activity_type', $activity_type);
        $this->assign('packet_type', $packet_type);
        return $this->fetch();
    }

    /**
     * 获取用户余额以及二维码余量
     * @date: 2018年6月12日上午11:22:39
     * @author: Hhb
     */
    public function getUserData()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数异常');
        }
        $model = new UserModel();
        $user = $model->getInfoById($id);
        if (empty($user)) {
            $this->error('数据异常');
        }
        $code_model = new UserCodeModel();
        $user_code = $code_model->getUserCodeNums($id, 2);
        if (empty($user_code) || empty($user_code[0]['status'])) {
            $num = 0;
        } else {
            $num = $user_code[0]['total'];
        }

        $data = array(
            'code_num' => $num,
            'user_money' => $user['balance'] - $user['frozen_total']
        );
        $this->success('ok', '', $data);
    }

    public function exportData()
    {
        $status = $this->request->param('status', 0, 'intval');
        $mobile = $this->request->param('mobile', '');
        $start = $this->request->param('start', '');
        $end = $this->request->param('end', '');
        $param = array(
            'status' => $status,
            'mobile' => $mobile,
            'start' => $start,
            'end' => $end
        );
        $where = array();
        if (!empty($param['status'])) {
            $where['status'] = $param['status'];
        }
        if (!empty($param['mobile'])) {
            $user_model = new UserModel();
            $user = $user_model->getInfoByMobile($param['mobile']);
            $where['user_id'] = $user['id'];
        }
        if (!empty($param['start'])) {
            $where['code_num'] = ['egt', $param['start']];
        }
        if (!empty($param['end'])) {
            $where['code_num'] = [['egt', empty($param['start']) ? 0 : $param['start']], ['elt', $param['end']]];
        }
        $model = new UserCodeModel();
        $res = $model->getDataList($where);
        if (empty($res)) {
            echo '<script>alert("暂时没有数据!");history.back();</script>';
            exit();
        }
        $root = CMF_ROOT . '/upload/';
        $fileList = array();
        foreach ($res as $v) {
            $fileList[] = $root . $v['code_img'];
        }
        $zip_url = cmf_zip_files('code', $fileList, '', true);
    }

    /**
     *删除活动
     * @adminMenu(
     *     'name'   => '活动审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '活动审核',
     *     'param'  => ''
     * )
     * @Author: Ldm
     * @FunctionName:活动审核
     * @Date:2018/6/14 0014
     */
    public function examine()
    {
        $param = $this->request->param();
        $activity_model = new UserActivityModel();
        $status = $activity_model->isUpdate(true, ['id' => $param['id']])->save(['is_examine' => 1, 'update_time' => time()]);
        if (empty($status)) {
            $this->error("审核失败！", '');
        }
        return $this->success("审核成功！");
    }
}