<?php
require_once __DIR__.'/auth_guard.php';
header("Content-type:text/html;charset=utf-8");
$chatDir = __DIR__.'/chat_data/';
$uploadDir = __DIR__.'/upload/';
$webUploadPath = "./upload/";
!is_dir($chatDir) && mkdir($chatDir,0755,true);
!is_dir($uploadDir) && mkdir($uploadDir,0755,true);

$ip = isset($_POST['ip'])?trim($_POST['ip']):'';
$type = isset($_POST['type'])?trim($_POST['type']):'text';
$who = isset($_POST['who'])?trim($_POST['who']):'user';
$today = date("Ymd");
$chatFile = $chatDir.$ip."_{$today}.txt";
$nowTime = date("H:i:s");

if($type == 'text'){
    $content = isset($_POST['content'])?trim($_POST['content']):'';
    // 修复：先把各种换行统一成 \n，再把真实换行转成字面 \n，避免破坏“一行一条消息”的格式
    $content = str_replace(["\r\n", "\r"], "\n", $content);
    $content = str_replace("\n", "\\n", $content);
    $line = "{$nowTime}|{$who}|text|".htmlspecialchars($content)."\n";
    file_put_contents($chatFile,$line,FILE_APPEND);
    exit("ok");
}

if($type == 'image'){
    $allowImg = ['image/jpeg','image/png','image/gif','image/jpg'];
    $f = $_FILES['imgfile'];
    if(!in_array($f['type'],$allowImg)){
        exit("err:not image");
    }
    $ext = pathinfo($f['name'],PATHINFO_EXTENSION);
    $saveName = md5(microtime(true).rand(1000,9999)).".".$ext;
    $saveFull = $uploadDir."/".$saveName;
    move_uploaded_file($f['tmp_name'],$saveFull);
    $imgUrl = $webUploadPath.$saveName;
    $line = "{$nowTime}|{$who}|image|{$imgUrl}\n";
    file_put_contents($chatFile,$line,FILE_APPEND);
    exit("ok");
}
exit("fail");
?>