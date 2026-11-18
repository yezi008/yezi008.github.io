<?php
require_once __DIR__.'/auth_guard.php';
header("Content-type:text/html;charset=utf-8");
$chatDir = __DIR__.'/chat_data/';
$uploadDir = __DIR__.'/upload/';
!is_dir($chatDir) && mkdir($chatDir,0755,true);
!is_dir($uploadDir) && mkdir($uploadDir,0755,true);

function getClientIp(){
    if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $arr = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($arr[0]);
    }
    return $_SERVER['REMOTE_ADDR'];
}
$visitorIp = getClientIp();
$todayYmd = date("Ymd");
$todayCn = date("Y-m-d");
$chatFile = $chatDir.$visitorIp."_{$todayYmd}.txt";

// ========= 自动打招呼逻辑 =========
$greetingModeFile = $chatDir."greeting_mode.txt";
$greetingMode = file_exists($greetingModeFile) ? trim(file_get_contents($greetingModeFile)) : '';

if($greetingMode === 'new'){
    $history = glob($chatDir.$visitorIp."_*.txt");
    $isNew = true;
    foreach($history as $h){
        if(basename($h) !== $visitorIp."_{$todayYmd}.txt"){
            $isNew = false;
            break;
        }
    }
    if($isNew && !file_exists($chatFile)){
        $greetingFile = $chatDir."greeting_new.txt";
        $greeting = file_exists($greetingFile) ? trim(file_get_contents($greetingFile)) : '';
        // 修复：统一换行，再把真实换行转成字面 \n，保留换行结构
        $greeting = str_replace(["\r\n", "\r"], "\n", $greeting);
        $greeting = str_replace("\n", "\\n", $greeting);
        if($greeting !== ''){
            $nowTime = date("H:i:s");
            $line = $nowTime."|admin|text|".htmlspecialchars($greeting)."\n";
            file_put_contents($chatFile, $line, FILE_APPEND);
        }
    }
}elseif($greetingMode === 'daily'){
    if(!file_exists($chatFile)){
        $greetingFile = $chatDir."greeting_daily.txt";
        $greeting = file_exists($greetingFile) ? trim(file_get_contents($greetingFile)) : '';
        // 修复：统一换行，再把真实换行转成字面 \n，保留换行结构
        $greeting = str_replace(["\r\n", "\r"], "\n", $greeting);
        $greeting = str_replace("\n", "\\n", $greeting);
        if($greeting !== ''){
            $nowTime = date("H:i:s");
            $line = $nowTime."|admin|text|".htmlspecialchars($greeting)."\n";
            file_put_contents($chatFile, $line, FILE_APPEND);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>在线客服</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,"Microsoft Yahei",sans-serif;}
body{background:#ededed;margin:0;padding:6px;max-width:640px;margin:0 auto;}
.wrap{background:#fff;border-radius:8px;overflow:hidden;display:flex;flex-direction:column;height:calc(100vh - 12px);}
.top-bar{background:#f7f7f7;padding:10px 10px;text-align:center;font-size:16px;font-weight:500;border-bottom:1px solid #ddd;}
#chatBox{flex:1;overflow-y:auto;padding:10px;background:#f5f5f5;}
.date-split{text-align:center;margin:10px 0;}
.date-split span{background:#d8d8d8;color:#666;font-size:12px;padding:3px 8px;border-radius:4px;}
.msg-row{margin:10px 0;display:flex;flex-direction:column;}
.row-left{align-items:flex-start;}
.row-right{align-items:flex-end;}
.msg-time{font-size:11px;color:#999;margin-bottom:3px;}
.bubble-left{background:#e5e5e5;border-radius:4px 10px 10px 10px;max-width:72%;padding:8px 10px;word-break:break-all;}
.bubble-right{background:#95ec69;border-radius:10px 4px 10px 10px;max-width:72%;padding:8px 10px;word-break:break-all;}
.bubble-left a,.bubble-right a{color:#0066cc;text-decoration:underline;word-break:break-all;}
img.chat-img{max-width:160px;max-height:160px;border-radius:4px;cursor:pointer;display:block;}
.bottom-area{padding:8px;border-top:1px solid #eee;}
.input-area{display:flex;flex-direction:column;gap:8px;}
#msgInput{width:100%;height:70px;padding:8px;border:1px solid #ccc;border-radius:4px;resize:none;font-size:14px;font-family:inherit;outline:none;}
.tool-row{display:flex;align-items:center;justify-content:space-between;}
.ip-info{font-size:12px;color:#888;}
.right-buttons{display:flex;align-items:center;justify-content:center;gap:8px;}
#uploadFile{display:none;}
.file-btn,#sendBtn{height:36px;line-height:36px;padding:0 14px;border-radius:4px;font-size:14px;cursor:pointer;white-space:nowrap;border:none;display:inline-flex;align-items:center;justify-content:center;vertical-align:middle;}
.file-btn{background:#f7f7f7;border:1px solid #ccc;color:#333;}
#sendBtn{background:#07c160;color:#fff;}
.sound-btn{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;font-size:20px;cursor:pointer;user-select:none;background:transparent;border:none;padding:0;}
.sound-btn.muted{opacity:0.4;}
#imgMask{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:999;display:none;align-items:center;justify-content:center;}
#imgMask img{max-width:95%;max-height:95%;}
body.mask-open{overflow:hidden;}
</style>
</head>
<body>
<div class="wrap">
    <div class="top-bar">在线客服</div>
    <div id="chatBox"></div>
    <div class="bottom-area">
        <div class="input-area">
            <textarea id="msgInput" placeholder="输入消息..."></textarea>
            <div class="tool-row">
                <span class="ip-info">你的IP：<?php echo htmlspecialchars($visitorIp);?></span>
                <div class="right-buttons">
                    <span id="soundToggle" class="sound-btn" title="声音开关">🔊</span>
                    <input type="file" id="uploadFile" accept="image/*">
                    <label for="uploadFile" class="file-btn">选择图片</label>
                    <button id="sendBtn" onclick="doSend()">发送</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="imgMask" onclick="closeImgMask()">
    <img id="maskImg">
</div>
<audio id="newMsgSound" src="newmass.mp3" preload="auto"></audio>

<script>
const visitorIp = "<?php echo htmlspecialchars($visitorIp);?>";
const imgMask = document.getElementById('imgMask');
const maskImg = document.getElementById('maskImg');

let soundOn = localStorage.getItem('index_sound_on') !== '0';
const soundToggle = document.getElementById('soundToggle');
function updateSoundIcon() {
    soundToggle.textContent = soundOn ? '🔊' : '🔇';
    soundToggle.classList.toggle('muted', !soundOn);
}
soundToggle.onclick = function() {
    soundOn = !soundOn;
    localStorage.setItem('index_sound_on', soundOn ? '1' : '0');
    updateSoundIcon();
};
updateSoundIcon();

document.getElementById('msgInput').addEventListener('keydown',function(e){
    if(e.keyCode ===13 && !e.shiftKey){
        e.preventDefault();
        doSend();
    }
});

document.getElementById('uploadFile').addEventListener('change', function() {
    const file = this.files[0];
    const msgInput = document.getElementById('msgInput');
    if (file) {
        if (msgInput.value.trim() === '') {
            msgInput.value = '已选择图片：' + file.name;
        } else {
            msgInput.value += '\n已选择图片：' + file.name;
        }
    }
});

function openImg(src){
    maskImg.src = src;
    imgMask.style.display = 'flex';
    document.body.classList.add('mask-open');
}
function closeImgMask(){
    imgMask.style.display = 'none';
    document.body.classList.remove('mask-open');
}

let lastMsgCount = -1;

function loadChat(){
    fetch("ajax_get.php?ip="+encodeURIComponent(visitorIp)+"&view=user")
    .then(res=>res.text())
    .then(html=>{
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        const newCount = tempDiv.querySelectorAll('.msg-row').length;

        document.getElementById("chatBox").innerHTML = html;
        document.querySelectorAll('.chat-img').forEach(img=>{
            img.onclick = function(){ openImg(this.src); }
        });

        if (lastMsgCount >= 0 && newCount > lastMsgCount) {
            const lastMsg = tempDiv.querySelector('.msg-row:last-child');
            if (lastMsg && lastMsg.classList.contains('row-left')) {
                if (soundOn) {
                    const audio = document.getElementById('newMsgSound');
                    if (audio) audio.play().catch(e => console.log('播放失败', e));
                }
            }
        }
        lastMsgCount = newCount;

        const box = document.getElementById("chatBox");
        box.scrollTop = box.scrollHeight;
    })
}

function doSend(){
    const txt = document.getElementById("msgInput").value.trim();
    const fileObj = document.getElementById("uploadFile").files[0];
    if(!txt && !fileObj){
        alert("请输入文字或者选择图片");
        return;
    }
    const fd = new FormData();
    fd.append("ip",visitorIp);
    if(fileObj){
        fd.append("type","image");
        fd.append("who","user");
        fd.append("imgfile",fileObj);
    }else{
        fd.append("type","text");
        fd.append("who","user");
        fd.append("content",txt);
    }
    fetch("send.php",{method:"POST",body:fd}).then(()=>{
        document.getElementById("msgInput").value="";
        document.getElementById("uploadFile").value="";
        loadChat();
    })
}

loadChat();
setInterval(loadChat,3000);
</script>
</body>
</html>