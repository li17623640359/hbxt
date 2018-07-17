/**
 * 公共JS函数文件
 * @date: 2018年5月12日下午4:40:37
 * @author: Hhb
 */

/**
 * 正整数检测
 */
function js_check_integer(val){
	if(val == ''){
		return false;
	}
	if((/^\+?[1-9][0-9]*$/.test(val)) && val > 0){
		return true;
	}else{
		return false;
	}
}
/**
 * 检测客户端是否为微信
 * @returns {Boolean}
 */
function js_check_is_wechat(){
	var ua = window.navigator.userAgent.toLowerCase();
	if(ua.match(/MicroMessenger/i) == 'micromessenger'){
		return true;
	}else{
		return false;
	}
}
/**
 * 检测金额合法性，精确到分
 * @param money
 * @returns {Boolean}
 */
function js_check_fix_amount(money){
    var fix_amountTest=/^(([1-9]\d*)|\d)(\.\d{1,2})?$/;
    if(fix_amountTest.test(money)==false){
        return false;
    }else {
        return true;
    }
}
/**
 * 延迟跳转
 * @param url
 * @param time
 */
function js_delay_jump(url,time){
    setTimeout("window.location.href = '" + url + "'",time);
}