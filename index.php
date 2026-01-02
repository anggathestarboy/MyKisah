<?php
require 'db.php';
session_start();

$pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id=?")
    ->execute([$_SESSION['user_id']]);


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$me = $_SESSION['user_id'];

/* ========= LOGOUT ========= */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

/* ========= CREATE / GET CHAT ========= */
if (isset($_GET['with'])) {
    $other = $_GET['with'];

    $stmt = $pdo->prepare("
        SELECT * FROM chats
        WHERE (user_one=? AND user_two=?)
           OR (user_one=? AND user_two=?)
    ");
    $stmt->execute([$me,$other,$other,$me]);
    $chat = $stmt->fetch();

    if (!$chat) {
        $pdo->prepare("
            INSERT INTO chats (user_one,user_two,created_at)
            VALUES (?,?,NOW())
        ")->execute([$me,$other]);
        $chat_id = $pdo->lastInsertId();
    } else {
        $chat_id = $chat['id'];
    }
}

/* ========= SEND MESSAGE (AJAX) ========= */
if (isset($_POST['send'])) {
    $pdo->prepare("
        INSERT INTO messages (chat_id,sender_id,message,created_at)
        VALUES (?,?,?,NOW())
    ")->execute([
        $_POST['chat_id'],
        $me,
        $_POST['message']
    ]);
    exit;
}

/* ========= FETCH MESSAGE (AJAX) ========= */
if (isset($_GET['fetch'])) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.name 
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE m.chat_id=?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$_GET['chat_id']]);
    echo json_encode($stmt->fetchAll());
    exit;
}

?>

<h3>Halo User <?= $me ?> | <a href="?logout">Logout</a></h3>

<div style="display:flex">

<!-- USER LIST -->
<div style="width:200px;border-right:1px solid #000">
<h4>User</h4>
<?php
$users = $pdo->query("
    SELECT id, name, avatar, bio, last_seen 
    FROM users 
    WHERE id != $me
");

foreach ($users as $u) {
    echo "
    <div style='margin-bottom:10px'>
        <a href='?with={$u['id']}'>
            <b>{$u['name']}</b>
        </a><br>
        <small>{$u['bio']}</small><br>
        <small>Last seen: {$u['last_seen']}</small>
    </div>
    ";
}


?>
</div>

<!-- CHAT AREA -->
<div style="flex:1;padding:10px">
<?php if (isset($chat_id)): ?>
    <div id="chat" style="height:300px;border:1px solid #000;overflow:auto"></div>
    <input id="msg">
    <button onclick="send()">Kirim</button>

<script>
let chatId = <?= $chat_id ?>;

function load() {
    fetch('?fetch=1&chat_id=' + chatId)
        .then(r => r.json())
        .then(d => {
            let html = '';
           d.forEach(m => {
    if (m.sender_id == <?= $me ?>) {
        html += `<div style="text-align:right"><b>Saya</b>: ${m.message}</div>`;
    } else {
        html += `<div><b>${m.name}</b>: ${m.message}</div>`;
    }
});

            chat.innerHTML = html;
            chat.scrollTop = chat.scrollHeight;
        });
}

function send() {
    fetch('', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'send=1&chat_id='+chatId+'&message='+msg.value
    }).then(()=>{
        msg.value='';
        load();
    });
}

setInterval(load, 1000);
load();
</script>
<?php endif; ?>
</div>

</div>
