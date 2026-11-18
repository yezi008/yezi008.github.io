<?php
session_start();
require_once __DIR__.'/auth_guard.php';
header("Content-type:text/html;charset=utf-8");
$chatDir = __DIR__.'/chat_data/';
$uploadDir = __DIR__.'/upload/';
$noteFile = $chatDir."note.txt";
$readFlagFile = $chatDir."readflag.txt";
$greetingModeFile = $chatDir."greeting_mode.txt";
!is_dir($chatDir) && mkdir($chatDir,0755,true);
!is_dir($uploadDir) && mkdir($uploadDir,0755,true);

// ========= 修改管理员密码 =========
$admin_password = "123456";

$loginErr = '';
if(isset($_POST['dologin'])){
    if($_POST['pwd'] === $admin_password){
        $_SESSION['admin_login'] = 1;
    }else{
        $loginErr = "密码错误";
    }
}

if(!isset($_SESSION['admin_login']) || $_SESSION['admin_login']!==1){
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>管理员登录</title>
<style>
body{background:#f4f4f4;display:flex;align-items:center;justify-content:center;height:100vh;padding:10px;}
.box{background:#fff;padding:24px;border-radius:8px;width:100%;max-width:320px;}
input{padding:8px;width:100%;margin:8px 0;}
button{padding:8px 16px;background:#07c160;color:#fff;border:none;cursor:pointer;width:100%;}
.err{color:red;}
</style>
</head>
<body>
<div class="box">
<h3>管理员登录</h3>
<?php if($loginErr):?><div class="err"><?php echo htmlspecialchars($loginErr);?></div><?php endif;?>
<form method="post">
<input type="password" name="pwd" placeholder="输入管理员密码">
<br>
<button type="submit" name="dologin">登录</button>
</form>
</div>
</body>
</html>
<?php
exit;
}

// ========= 新增：设置问候模式接口 =========
if(isset($_POST['set_greeting_mode'])){
    $mode = isset($_POST['mode']) ? trim($_POST['mode']) : '';
    if($mode === 'new' || $mode === 'daily'){
        file_put_contents($greetingModeFile, $mode);
    }else{
        if(file_exists($greetingModeFile)) unlink($greetingModeFile);
    }
    exit('ok');
}

//标记已读接口
if(isset($_POST['markread'])){
    $rip = trim($_POST['markip']);
    $readData = [];
    if(file_exists($readFlagFile)){
        $rdlines = file($readFlagFile);
        foreach($rdlines as $r){
            $r = trim($r);
            if(empty($r)) continue;
            if(strpos($r, '|') !== false){
                list($tag, $readMtime) = explode('|', $r, 2);
                $readData[$tag] = intval($readMtime);
            }else{
                $readData[$r] = PHP_INT_MAX;
            }
        }
    }
    $today = date("Ymd");
    $tag = $rip."_".$today;
    $chatFile = $chatDir.$rip."_{$today}.txt";
    $currentMtime = file_exists($chatFile) ? filemtime($chatFile) : time();
    $readData[$tag] = $currentMtime;

    $outLines = [];
    foreach($readData as $t => $m){
        $outLines[] = $t."|".$m;
    }
    file_put_contents($readFlagFile, implode("\n", $outLines)."\n");
    exit("ok");
}

//读取备注
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

// 读取当前问候模式
$greetingMode = '';
if(file_exists($greetingModeFile)){
    $greetingMode = trim(file_get_contents($greetingModeFile));
}

$today = date("Ymd");
$ipList = [];
$files = glob($chatDir."*_{$today}.txt");

// 读取已读标记
$readData = [];
if(file_exists($readFlagFile)){
    $rdlines = file($readFlagFile);
    foreach($rdlines as $r){
        $r = trim($r);
        if(empty($r)) continue;
        if(strpos($r, '|') !== false){
            list($tag, $readMtime) = explode('|', $r, 2);
            $readData[$tag] = intval($readMtime);
        }else{
            $readData[$r] = PHP_INT_MAX;
        }
    }
}

foreach($files as $f){
    $basename = basename($f);
    $ip = str_replace("_{$today}.txt","",$basename);
    $mtime = filemtime($f);
    $msgCount = 0;
    $lines = file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if($lines !== false){
        $msgCount = count($lines);
    }
    $tag = $ip."_".$today;
    $readMtime = isset($readData[$tag]) ? $readData[$tag] : 0;
    $isRead = ($mtime <= $readMtime);
    $ipList[] = [
        'ip'=>$ip,
        'file'=>$f,
        'mtime'=>$mtime,
        'msgCount'=>$msgCount,
        'isRead'=>$isRead,
        'note'=>isset($noteArr[$ip])?$noteArr[$ip]:''
    ];
}
usort($ipList,function($a,$b){
    return $b['mtime'] - $a['mtime'];
});
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>管理员聊天后台</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:"Microsoft Yahei",system-ui;}
body{padding:10px;background:#f4f4f4;}
.box{max-width:1200px;margin:0 auto;background:#fff;padding:15px;border-radius:6px;position:relative;}
.header-bar{display:flex;justify-content:space-between;align-items:center;font-size:14px;gap:10px;flex-wrap:wrap;}
.header-left{flex:1;min-width:200px;}
.header-right{display:flex;align-items:center;gap:14px;flex-wrap:wrap;}
.greeting-label{display:inline-flex;align-items:center;gap:4px;font-size:13px;color:#333;cursor:pointer;user-select:none;}
.greeting-label input[type="radio"]{cursor:pointer;margin:0;}
.sound-btn{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;font-size:22px;cursor:pointer;user-select:none;line-height:1;background:transparent;border:none;padding:0;}
.sound-btn.muted{opacity:0.4;}
.item{padding:10px 6px;border-bottom:1px solid #eee;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.seq{color:#666;min-width:24px;}
.unread-tag{color:red;font-weight:bold;font-size:12px;}
.read-tag{color:#090;font-size:12px;}
.ip-link{font-weight:bold;font-size:14px;color:#0066cc;text-decoration:underline;}
.time-txt{color:#999;font-size:12px;}
.edit-note-link{color:#0066cc;text-decoration:underline;font-size:14px;}
.note-text{color:#444;font-size:14px;}
</style>
</head>
<body>
<div class="box">
    <div class="header-bar">
        <div class="header-left">管理员后台｜每3秒自动刷新；点击IP新标签页打开对话；点击编辑备注新开标签维护备注</div>
        <div class="header-right">
            <label class="greeting-label">
                <input type="radio" name="greetingMode" value="new" <?php echo $greetingMode === 'new' ? 'checked' : ''; ?>>
                新客打招呼
            </label>
            <label class="greeting-label">
                <input type="radio" name="greetingMode" value="daily" <?php echo $greetingMode === 'daily' ? 'checked' : ''; ?>>
                每天第一句话打招呼
            </label>
            <span id="soundToggle" class="sound-btn" title="声音开关">🔊</span>
        </div>
    </div>
    <hr style="margin:8px 0;">
    <?php if(empty($ipList)):?>
        <div style="padding:10px;color:#777;">暂无今日访客会话</div>
    <?php endif;?>
    <?php $seq=1;foreach($ipList as $row):?>
    <div class="item" data-ip="<?php echo htmlspecialchars($row['ip']);?>" data-msgcount="<?php echo $row['msgCount']; ?>">
        <span class="seq"><?php echo $seq;?></span>
        <a class="ip-link" data-ip="<?php echo htmlspecialchars($row['ip']);?>" href="chat.php?ip=<?php echo urlencode($row['ip']);?>" target="_blank">
            <?php echo htmlspecialchars($row['ip']);?>
        </a>
        <?php if($row['isRead']):?>
            <span class="read-tag">【已读】</span>
        <?php else:?>
            <span class="unread-tag">【新消息】</span>
        <?php endif;?>
        <span class="time-txt"><?php echo date('H:i:s',$row['mtime']);?></span>
        <a class="edit-note-link" target="_blank" href="note_edit.php?ip=<?php echo urlencode($row['ip']);?>">编辑备注</a>
        <?php if($row['note']!==''):?>
            <span class="note-text">：<?php echo htmlspecialchars($row['note']);?></span>
        <?php endif;?>
    </div>
    <?php $seq++;endforeach;?>
</div>

<!-- 新消息提示音 -->
<audio id="newMsgSound" src="newmass.mp3" preload="auto"></audio>

<script>
// ========== 声音开关 ==========
let soundOn = localStorage.getItem('admin_sound_on') !== '0';
const soundToggle = document.getElementById('soundToggle');
function updateSoundIcon() {
    soundToggle.textContent = soundOn ? '🔊' : '🔇';
    soundToggle.classList.toggle('muted', !soundOn);
}
soundToggle.onclick = function() {
    soundOn = !soundOn;
    localStorage.setItem('admin_sound_on', soundOn ? '1' : '0');
    updateSoundIcon();
};
updateSoundIcon();

// ========== 新增：问候模式单选项 ==========
document.querySelectorAll('input[name="greetingMode"]').forEach(function(radio){
    radio.addEventListener('change', function(){
        if(!this.checked) return;
        const mode = this.value;
        const fd = new FormData();
        fd.append('set_greeting_mode', '1');
        fd.append('mode', mode);
        fetch('admin.php', {method:'POST', body: fd, keepalive: true}).then(function(){
            window.open('greeting_edit.php?type=' + encodeURIComponent(mode), '_blank');
        }).catch(function(){
            window.open('greeting_edit.php?type=' + encodeURIComponent(mode), '_blank');
        });
    });
});

// ========== 新消息检测（对比本次抓取与上次抓取的消息数） ==========
(function() {
    let lastSeen = {};
    try {
        lastSeen = JSON.parse(sessionStorage.getItem('admin_last_seen_msgs') || '{}');
    } catch(e) { lastSeen = {}; }

    let anyNew = false;
    let updated = false;

    document.querySelectorAll('.item').forEach(function(item) {
        const ip = item.dataset.ip;
        const msgCount = parseInt(item.dataset.msgcount) || 0;
        const tag = item.querySelector('.unread-tag, .read-tag');

        if (lastSeen[ip] === undefined) {
            lastSeen[ip] = msgCount;
            updated = true;
        } else if (msgCount > lastSeen[ip]) {
            anyNew = true;
            lastSeen[ip] = msgCount;
            updated = true;
            if (tag) {
                tag.textContent = '【新消息】';
                tag.className = 'unread-tag';
            }
        }
    });

    if (updated) {
        sessionStorage.setItem('admin_last_seen_msgs', JSON.stringify(lastSeen));
    }

    if (anyNew && soundOn) {
        const audio = document.getElementById('newMsgSound');
        if (audio) {
            audio.play().catch(e => console.log('播放失败（请确认 newmass.mp3 已放置）', e));
        }
    }
})();

// ========== IP 链接点击：标记已读 + 更新 sessionStorage ==========
document.querySelectorAll('.ip-link').forEach(function(el) {
    el.addEventListener('click', function() {
        const ip = this.dataset.ip;
        const item = this.closest('.item');
        const msgCount = parseInt(item.dataset.msgcount) || 0;

        let lastSeen = {};
        try {
            lastSeen = JSON.parse(sessionStorage.getItem('admin_last_seen_msgs') || '{}');
        } catch(e) { lastSeen = {}; }
        lastSeen[ip] = msgCount;
        sessionStorage.setItem('admin_last_seen_msgs', JSON.stringify(lastSeen));

        const tag = item.querySelector('.unread-tag, .read-tag');
        if (tag) {
            tag.textContent = '【已读】';
            tag.className = 'read-tag';
        }

        const fd = new FormData();
        fd.append("markread", "1");
        fd.append("markip", ip);
        fetch("admin.php", {method: "POST", body: fd});
    });
});

// 3 秒刷新列表
setInterval(function(){
    location.reload();
},3000);
</script>
</body>
</html>