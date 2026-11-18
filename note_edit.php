<?php
session_start();
require_once __DIR__.'/auth_guard.php';
header("Content-type:text/html;charset=utf-8");
$chatDir = __DIR__.'/chat_data/';
$noteFile = $chatDir."note.txt";
!is_dir($chatDir) && mkdir($chatDir,0755,true);

//未登录拦截
if(!isset($_SESSION['admin_login']) || $_SESSION['admin_login']!==1){
    header("Location:admin.php");
    exit;
}

$ip = isset($_GET['ip'])?trim($_GET['ip']):'';
if($ip===''){
    echo "参数错误";exit;
}

//读取原有备注
$noteArr = [];
if(file_exists($noteFile)){
    $noteLines = file($noteFile);
    foreach($noteLines as $line){
        $line = trim($line);
        if(empty($line))continue;
        list($nip,$ntitle) = explode("||",$line,2);
        $noteArr[$nip] = $ntitle;
    }
}
$oldNote = isset($noteArr[$ip])?$noteArr[$ip]:'';

//保存提交
$msg='';
if(isset($_POST['save'])){
    $newNote = trim($_POST['newnote']);
    if($newNote===''){
        unset($noteArr[$ip]);
    }else{
        $noteArr[$ip] = $newNote;
    }
    $outStr = '';
    foreach($noteArr as $k=>$v){
        $outStr .= "{$k}||{$v}\n";
    }
    file_put_contents($noteFile,$outStr);
    $msg="保存成功！<a href='admin.php'>返回后台列表</a>";
    $oldNote = $newNote;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>编辑备注</title>
<style>
body{background:#f4f4f4;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:10px;}
.box{background:#fff;padding:20px;border-radius:8px;width:100%;max-width:500px;}
textarea{width:100%;height:120px;padding:8px;margin:10px 0;resize:none;font-size:14px;}
button{padding:8px 16px;background:#07c160;color:#fff;border:none;cursor:pointer;}
.info{color:green;margin-bottom:10px;}
</style>
</head>
<body>
<div class="box">
<h3 style="font-size:16px;">编辑IP：<?php echo htmlspecialchars($ip);?> 的备注</h3>
<?php if($msg):?><div class="info"><?php echo $msg;?></div><?php endif;?>
<form method="post">
<textarea name="newnote" placeholder="输入备注内容"><?php echo htmlspecialchars($oldNote);?></textarea>
<br>
<button type="submit" name="save">保存备注</button>
</form>
</div>
</body>
</html>
