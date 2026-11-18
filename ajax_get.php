<?php
require_once __DIR__.'/auth_guard.php';
header("Content-type:text/html;charset=utf-8");
$chatDir = __DIR__.'/chat_data/';
$ip = isset($_GET['ip'])?trim($_GET['ip']):'';
$view = isset($_GET['view'])?trim($_GET['view']):'user';
$todayYmd = date("Ymd");
$todayShow = date("Y-m-d");
$chatFile = $chatDir.$ip."_{$todayYmd}.txt";

$html = '';
if(file_exists($chatFile)){
    $lines = file($chatFile);
    $first = true;
    foreach($lines as $line){
        $line = trim($line);
        if(empty($line))continue;
        $arr = explode("|",$line,4);
        if(count($arr)!=4)continue;
        list($ctime,$sender,$msgType,$data) = $arr;

        if($first){
            $html .= "<div class='date-split'><span>{$todayShow}</span></div>";
            $first=false;
        }

        $isSelf = false;
        if($view === 'admin'){
            $isSelf = ($sender === 'admin');
        }else{
            $isSelf = ($sender === 'user');
        }

        if($isSelf){
            $wrapClass = "msg-row row-right";
            $bubbleClass = "bubble-right";
        }else{
            $wrapClass = "msg-row row-left";
            $bubbleClass = "bubble-left";
        }

        if($msgType=="text"){
            // 修复：把存储时的字面 \n 还原成真实换行，转义后再转成 <br> 显示
            $data = str_replace("\\n", "\n", $data);
            $escaped = htmlspecialchars($data);
            $escaped = nl2br($escaped);
            // 把 http(s) 网址自动转成可点击链接
            $linked = preg_replace_callback(
                '/(https?:\/\/[^\s<>"\']+)/i',
                function($m){
                    $raw = $m[1];
                    $url = rtrim($raw, '.,;:!?)');
                    $trailing = substr($raw, strlen($url));
                    return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer">'.$url.'</a>'.$trailing;
                },
                $escaped
            );
            $html .= "<div class='{$wrapClass}'><div class='msg-time'>{$ctime}</div><div class='{$bubbleClass}'>".$linked."</div></div>";
        }elseif($msgType=="image"){
            $html .= "<div class='{$wrapClass}'><div class='msg-time'>{$ctime}</div><div class='{$bubbleClass}'><img class='chat-img' src='".htmlspecialchars($data)."' /></div></div>";
        }
    }
}else{
    $html = "<div style='text-align:center;color:#888;margin-top:30px;'>暂无聊天记录，请发送消息</div>";
}
echo $html;
?>