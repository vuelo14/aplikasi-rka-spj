
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';
$pdo = Database::getConnection();
$count = $pdo->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
$can_setup = ($count==0);
$msg=''; $err='';
if (!$can_setup) { $err='Admin sudah ada. Gunakan akun yang tersedia.'; }
if ($can_setup && $_SERVER['REQUEST_METHOD']==='POST') {
  $name = trim($_POST['name']??'');
  $email= trim($_POST['email']??'');
  $password = $_POST['password']??'';
  if(!$name||!$email||!$password){ $err='Lengkapi semua field.'; }
  else {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = $pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,"ADMIN")');
    try { $st->execute([$name,$email,$hash]); $msg='Admin berhasil dibuat. Silakan login.'; }
    catch(Exception $e){ $err='Gagal membuat admin: '.$e->getMessage(); }
  }
}
?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="css/style.css"><title>Setup Admin</title></head>
<body>
<div class="container" style="max-width:480px">
  <div class="card">
    <h2>Setup Admin Pertama</h2>
    <?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="alert success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($can_setup): ?>
    <form method="post">
      <label>Nama</label><input type="text" name="name" required>
      <label>Email</label><input type="email" name="email" required>
      <label>Password</label><input type="password" name="password" required>
      <button type="submit">Buat Admin</button>
    </form>
    <?php else: ?>
    <p>Admin sudah ada. <a href="login.php">Masuk</a></p>
    <?php endif; ?>
  </div>
</div>
</body></html>
