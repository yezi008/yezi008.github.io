
<script>
var _hmt = _hmt || [];
(function() {
  var hm = document.createElement("script");
  hm.src = "https://hm.baidu.com/hm.js?0eaa2a618867a69af3efeb0ef00ff379";
  var s = document.getElementsByTagName("script")[0]; 
  s.parentNode.insertBefore(hm, s);
})();
</script>


<?php
error_reporting(0);
header('Content-Type: text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache");
include 'txprotect.php';
if($_GET["r"]==""){$target =  'http://baidu.com';}else{$target =  $_GET["r"];}            


function checkmobile() {
	$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
	$ualist = array('android', 'midp', 'nokia', 'mobile', 'iphone', 'ipod', 'blackberry', 'windows phone');
	foreach($ualist as $v) {
		if(strpos($useragent, $v) !== false) {
			return true;
		}
	}
	if(strpos($_SERVER['HTTP_ACCEPT'], "VND.WAP") !== false || strpos($_SERVER['HTTP_VIA'],"wap") !== false){
		return true;
	}
	return false;
}
if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
echo '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" style="font-size: 100px;">
<head id="Head1"><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>请使用浏览器打开</title>
    <!--禁止全屏缩放-->
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no" />
    <!--不显示成手机号-->
    <meta name="format-detection" content="telephone=no" />
    <!--删除默认的苹果工具栏和菜单栏-->
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <!--解决UC手机字体变大的问题-->
    <meta name="wap-font-scale" content="no" />
    <!--控制状态栏显示样式-->    <!--微信客服：XXXXXXXX-->
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
	<link href="css/index.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="//cdn.bootcss.com/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript">
$(function ($) {
    setRootFontSize();
});
window.onresize = function () {
    setRootFontSize();
}
function setRootFontSize() {
    $(\'html\').css(\'font-size\', document.body.clientWidth / 15 + \'px\');
}
    </script>
</head>
<body style="background-color: #f5f5f5;">
    <div id="Pan_WX">
        <!--微信访问-->
        <div class="fc_jt">
            <img src="img/jt.png"></div>
               <div class="fc_wz">
                点击屏幕右上角[...]<br />
                用 浏览器 打开 <br />
            </div>  
          <div class="fc_tp">
            <img src="img/wx_az.png"></div>
<center>
   <p><font size="3">

   </p>
</center>
   <br /> 
   <br /> 

<span align="right">
   <p><font size="1">
   <a></a>
   </p>
</body>
</html>';}
elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'SQ')!==false){
	echo '<!DOCTYPE html>
<html>
 <head>
  <title>正在打开浏览器....</title>
  <script src="https://open.mobile.qq.com/sdk/qqapi.js?_bid=152"></script>
  <script type="text/javascript"> mqq.ui.openUrl({ target: 2,url: "'.$target.'"}); </script>
 </head>
 <body><img border="0" src="img/qXZgp6X_png.png"  width="100%" height="100%"></body>
<br />
<br />
<center>

</center>
   <br /> 
   <br /> 

<span align="right">
   <p><font size="5">
  
   </p>

</span>
<script>
var _hmt = _hmt || [];
(function() {
  var hm = document.createElement("script");
  hm.src = "https://hm.baidu.com/hm.js?0eaa2a618867a69af3efeb0ef00ff379";
  var s = document.getElementsByTagName("script")[0]; 
  s.parentNode.insertBefore(hm, s);
})();
</script>

</html>';
exit;
}
else{
	exit('<script>window.location.href="'.$target.'";</script>');
}
?>
