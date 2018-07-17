<?php
/**
 * 微信公众平台SDK开发
 *
 * @date: 2018年7月2日下午2:20:12
 * @author: Hhb
 */
namespace Three;
class EasyWechat extends Wechat{
    private $cachedir = '';
    private $logfile = '';
    public function __construct($options)
    {
        $this->cachedir = isset($options['cachedir']) ? dirname($options['cachedir'].'/.cache') . '/' : 'wx/cache/';
        $this->logfile = isset($options['logfile']) ? $options['logfile'] : '';
        if ($this->cachedir) $this->checkDir($this->cachedir);
        parent::__construct($options);
    }
    /**
     * log overwrite TODO::
     * @param string|array $log
     */
    protected function log($log){
        if (is_array($log)) $log = print_r($log,true);
        if ($this->debug) {
            if (function_exists($this->logcallback)) {
                return call_user_func($this->logcallback,$log);
            }elseif ($this->logfile) {
                return file_put_contents($this->logfile, $log."\n", FILE_APPEND) > 0 ? true : false;
            }
        }
        return false;
    }
    /**
     * 重载设置缓存
     * @param string $cachename
     * @param mixed $value
     * @param int $expired 缓存秒数，如果为0则为长期缓存
     * @return boolean
     */
    protected function setCache($cachename,$value,$expired=0){
        return cache($cachename,$value,['path'=>$this->cachedir,'expire'=>$expired,'cache_subdir'=>false,'prefix'=>'','old_name'=>true]);
        /* $file = $this->cachedir . $cachename . '.cache';
        $data = array(
                'value' => $value,
                'expired' => $expired ? time() + $expired : 0
        );
        return file_put_contents( $file, serialize($data) ) ? true : false; */
    }
    /**
     * 重载获取缓存
     * @param string $cachename
     * @return mixed
     */
    protected function getCache($cachename){
        return cache($cachename,'',['path'=>$this->cachedir,'expire'=>0,'cache_subdir'=>false,'prefix'=>'','old_name'=>true]);
        /* $file = $this->cachedir . $cachename . '.cache';
        if (!is_file($file)) {
           return false;
        }
        $data = unserialize(file_get_contents( $file ));
        if (!is_array($data) || !isset($data['value']) || (!empty($data['value']) && $data['expired']<time())) {
            @unlink($file);
            return false;
        }
        return $data['value']; */
    }
    /**
     * 重载清除缓存
     * @param string $cachename
     * @return boolean
     */
    protected function removeCache($cachename){
        return cache($cachename,null,['path'=>$this->cachedir,'expire'=>0,'cache_subdir'=>false,'prefix'=>'','old_name'=>true]);
        /* $file = $this->cachedir . $cachename . '.cache';
        if ( is_file($file) ) {
            @unlink($file);
        }
        return true; */
    }
    private function checkDir($dir, $mode=0777) {
        if (!$dir)  return false;
        if(!is_dir($dir)) {
            if (!file_exists($dir) && @mkdir($dir, $mode, true))
                return true;
            return false;
        }
        return true;
    }
}



