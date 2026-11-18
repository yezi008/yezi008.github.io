<?php
session_start();
require_once __DIR__.'/auth_guard.php';
header("Content-type:text/html;charset=utf-8");
$chatDir = __DIR__.'/chat_data/';
$newFile = $chatDir."greeting_new.txt";
$dailyFile = $chatDir."greeting_daily.txt";
!is_dir($chatDir) && mkdir($chatDir,0755,true);

// 未登录拦截
if(!isset($_SESSION['admin_login']) || $_SESSION['admin_login']!==1){
    header("Location:admin.php");
    exit;
}

$type = isset($_GET['type']) ? trim($_GET['type']) : 'new';
if($type !== 'new' && $type !== 'daily'){
    $type = 'new';
}

$file = ($type === 'daily') ? $dailyFile : $newFile;
$title = ($type === 'daily') ? '每天第一句话打招呼' : '新客打招呼';
$desc = ($type === 'daily')
    ? '游客每天第一次打开在线客服窗口时，自动发送以下内容：'
    : '新IP游客第一次打开在线客服窗口时，自动发送以下内容：';

$oldContent = file_exists($file) ? file_get_contents($file) : '';

$msg = '';
if(isset($_POST['save'])){
    $newContent = trim($_POST['content']);
    if($newContent === ''){
        if(file_exists($file)) unlink($file);
    }else{
        file_put_contents($file, $newContent);
    }
    $msg = "保存成功！<a href='admin.php'>返回后台列表</a>";
    $oldContent = $newContent;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title><?php echo htmlspecialchars($title);?></title>
<style>
body{background:#f4f4f4;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:10px;}
.box{background:#fff;padding:20px;border-radius:8px;width:100%;max-width:520px;}
textarea{width:100%;height:160px;padding:8px;margin:10px 0;resize:vertical;font-size:14px;font-family:inherit;}
button{padding:8px 16px;background:#07c160;color:#fff;border:none;cursor:pointer;border-radius:4px;}
.info{color:green;margin-bottom:10px;}
.tip{color:#888;font-size:13px;margin-bottom:8px;line-height:1.6;}
</style>
</head>
<body>
<div class="box">
<h3 style="font-size:16px;"><?php echo htmlspecialchars($title);?></h3>
<div class="tip"><?php echo htmlspecialchars($desc);?></div>
<div class="tip">提示：内容中的网址会自动变成可点击链接（http:// 或 https:// 开头）。留空并保存则关闭该问候。</div>
<?php if($msg):?><div class="info"><?php echo $msg;?></div><?php endif;?>
<form method="post">
<textarea name="content" placeholder="输入自动发送的内容，可以包含网址"><?php echo htmlspecialchars($oldContent);?></textarea>
<br>
<button type="submit" name="save">保存</button>
</form>
</div>
</body>
</html>