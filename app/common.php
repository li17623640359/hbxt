<?php

/**
 * 手机号格式化
 * @date: 2018年3月13日上午8:59:03
 * @author: Hhb
 * @param unknown $mobile
 */
function cmf_mobile_fomart($mobile){
    if(empty($mobile)){
        return '';
    }
    //$mobile = substr($mobile, 0, 3).'****'.substr($mobile, 7);
    //$mobile = substr_replace($mobile, '****', 3, 4);
    $mobile = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $mobile);
    return $mobile;
}

/**
 *
 * @Author: Lcs
 * @Date: 2018/6/4 11:00
 */
function cmf_get_repetition($array) {
    // 获取去掉重复数据的数组
    $unique_arr = array_unique ( $array );
    // 获取重复数据的数组
    $repeat_arr = array_diff_assoc ( $array, $unique_arr );
    return $repeat_arr;
}
/**
 * 生成订单编号
 * @date: 2018年7月2日上午9:40:57
 * @author: Hhb
 */
function cmf_create_order_code(){
    $str=date('YmdHis',time()).str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return $str;
}
/**
 * 数字格式化，小数点后补2个零
 * @date: 2018年6月14日上午11:29:40
 * @author: Hhb
 * @param unknown $num
 */
function cmf_num_format($num){
    if(is_array($num)){
        foreach ($num as $k=>$v){
            if(is_numeric($v)){
                $num[$k] = sprintf("%01.2f",$v);
            }
        }
    }else{
        if(is_numeric($num)){
            $num = sprintf("%01.2f",$num);
        }
    }
    return $num;
}
/**
 * 生成二维码图片
 * @date: 2018年6月8日上午11:38:34
 * @author: Hhb
 * @param unknown $name 文件名称
 * @param unknown $content 二维码内容或者链接地址
 * @param string $text 写入图片内容
 * @return string
 */
function cmf_qrcode_create($name,$content,$text = ''){
    $logo = CMF_ROOT. "/static/qrcode/center_logo.png";
    $qr = CMF_ROOT . "/upload/qrcode/base_" . $name . ".png";
    $last = CMF_ROOT . "/upload/qrcode/last_" . $name . ".png";
    $bg = CMF_ROOT. "/static/qrcode/newbg.png";
    $ad = CMF_ROOT . "/upload/qrcode/ad_" . $name . ".png";
    $file_name = "qrcode/" . $name . ".png";
    $last_path =  CMF_ROOT . "/upload/" . $file_name;
    cmf_dir_create(CMF_ROOT . "/upload/qrcode");
    if(!file_exists($ad)){
        Vendor('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QRcode();
        $str = $content;//网址或者是文本内容
        $errorCorrectionLevel = 3;//容错级别
        $matrixPointSize = 13;//生成图片大小
        $object->png($str, $qr, $errorCorrectionLevel, $matrixPointSize, 2);
    
        if ($logo !== false && file_exists($logo)) {
            $qr_re = imagecreatefromstring(file_get_contents($qr));
            $logo = imagecreatefromstring(file_get_contents($logo));
            $qr_width = imagesx($qr_re);
            $qr_height = imagesy($qr_re);
            $logo_width = imagesx($logo);
            $logo_height = imagesy($logo);
            $logo_qr_width = $qr_width / 5;
            $scale = $logo_width / $logo_qr_width;
            $logo_qr_height = $logo_height / $scale;
            $from_width = ($qr_width - $logo_qr_width) / 2;
            imagecopyresampled($qr_re, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
        }
        imagepng($qr_re, $last); // 生成最终的文件
        // 创建图片对象
        $image_1 = imagecreatefrompng($bg);
        $image_2 = imagecreatefrompng($last);
        // 合成图片
        imagecopymerge($image_1, $image_2, 140, 60, 0, 0, imagesx($image_2), imagesy($image_2), 100);
        // 输出合成图片
        imagepng($image_1, $ad);
    }
    $text = empty($text) ? $name : $text;
    $font = CMF_ROOT. '/static/qrcode/fonts/simhei.ttf';
    $fontSize = 60;
    
    $dst = imagecreatefromstring(file_get_contents($ad));
    $color = imagecolorallocate($dst, 240, 133, 25);//字体颜色
    
    $imgsize = getimagesize($ad);
    $imgWidth = $imgsize[0];
    
    $textSize = imagettfbbox($fontSize, 0, $font, $text);
    $textWidth = $textSize[2] - $textSize[1];
    
    $porintLeft = floor(($imgWidth - $textWidth) / 2);
    imagefttext($dst, $fontSize, 0, $porintLeft, 810, $color, $font, $text);
    imagepng($dst,$last_path);
    //ob_end_clean();
    @unlink($qr);
    @unlink($last);
    @unlink($ad);
    return $file_name;
}
/**
 * 创建目录
 *
 * @param   string $path 路径
 * @param   string $mode 属性
 * @return  string  如果已经存在则返回true，否则为flase
 * @date: 2018年6月8日上午10:37:39
 * @author: Hhb
 */
function cmf_dir_create($path, $mode = 0777){
    if (is_dir($path)){
        return TRUE;
    }
    $ftp_enable = 0;
    $path = cmf_dir_path($path);
    $temp = explode('/', $path);
    $cur_dir = '';
    $max = count($temp) - 1;
    for ($i = 0; $i < $max; $i++) {
        $cur_dir .= $temp[$i] . '/';
        if (@is_dir($cur_dir)){
            continue;
        }
        @mkdir($cur_dir, 0777, true);
        @chmod($cur_dir, 0777);
    }
    return is_dir($path);
}
/**
 * 转化 \ 为 /
 *
 * @param   string $path 路径
 * @return  string  路径
 * @date: 2018年6月8日上午10:38:27
 * @author: Hhb
 */
function cmf_dir_path($path){
    $path = str_replace('\\', '/', $path);
    if (substr($path, -1) != '/'){
        $path = $path . '/';
    } 
    return $path;
}
/**
 * 压缩多个文件ZIP
 * @date: 2018年5月28日下午6:01:10
 * @author: Hhb
 * @param unknown $name
 * @param unknown $data
 * @param bool $is_show
 */
function cmf_zip_files($name,$data,$time = '',$is_show = false){
    $upload_path = '/upload/';
    $save_path = 'zip';
    $file_name = $name.'_'.date('YmdHis',!empty($time) ? $time : time()) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT) .".zip";//文件名
    $file_path = $upload_path.$save_path.'/'.date('Ymd');//文件保存目录
    $diy_root = CMF_ROOT.$file_path;//文件物理目录路径
    if(!is_dir($diy_root)){
        mkdir($diy_root, 0777, true);
    }
    $doc_name = $diy_root.'/'.$file_name;//文件地址
    $doc_url = $save_path.'/'.date('Ymd').'/'.$file_name;//文件相对地址
    $zip = new \ZipArchive();
    $zip->open($doc_name,\ZipArchive::CREATE);   //打开压缩包
    foreach($data as $file){
        $zip->addFile($file,basename($file));   //向压缩包中添加文件
    }
    $zip->close();  //关闭压缩包
    if($is_show){
        header ( "Cache-Control: max-age=0" );
        header ( "Content-Description: File Transfer" );
        header ( 'Content-disposition: attachment; filename=' . basename($doc_name)); // 文件名
        header ( "Content-Type: application/zip" ); // zip格式的
        header ( "Content-Transfer-Encoding: binary" ); // 告诉浏览器，这是二进制文件
        header ( 'Content-Length: ' . filesize($doc_name)); // 告诉浏览器，文件大小
        @readfile($doc_name);//输出文件;
    }else{
        return $doc_url;
    }  
}
/**
 * 纯数字加密
 * @date: 2018年3月13日上午8:59:03
 * @author: Hhb
 * @param mixed $num
 * @param array $arr
 * @return string
 */
function cmf_encode($num, $arr = array()){
    if(empty($arr)){
        $arr = array('w','t','r','p','j','h','q','z','m','y');
    }
    /* for ($i=0;$i<10;$i++){
     $num = str_replace($i, $arr[$i], $num);
     } */
    $num = (string)$num;
    $str = '';
    for($i=0;$i<strlen($num);$i++){
        if(isset($arr[$num[$i]])){
            $str .= $arr[$num[$i]];
        }else{
            $str .= $num[$i];
        }

    }
    return $str;
}
/**
 * 纯数字解密
 * @date: 2018年3月13日上午8:59:03
 * @author: Hhb
 * @param string $str
 * @param array $arr
 * @return Ambigous <string, mixed>
 */
function cmf_decode($str, $arr = array()){
    if(empty($arr)){
        $arr = array('w','t','r','p','j','h','q','z','m','y');
    }
    $num = '';
    for ($i=0;$i<strlen($str);$i++){
        $k = array_search($str[$i], $arr);
        if($k !== false){
            $num .= array_search($str[$i], $arr);
        }else{
            $num .= $str[$i];
        }
    }
    return $num;
}

/**
 *根据id查询地址
 * @param $id
 * @return mixed
 * @Author: Lcs
 * @Date: 2018/6/5 10:47
 */
function address($id){
    $result=\think\Db::name('regions')->where(array('id'=>$id))->find();
    return $result['name'];
}
