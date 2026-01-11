
<?php
class Auth
{
  public static function start()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
  }
  public static function check()
  {
    self::start();
    return isset($_SESSION['user']);
  }
  public static function user()
  {
    self::start();
    return $_SESSION['user'] ?? null;
  }
  public static function requireLogin()
  {
    if (!self::check()) {
      header('Location: ' . APP_BASE_URL . 'login.php');
      exit;
    }
  }
  public static function requireRole($roles = [])
  {
    self::requireLogin();
    if ($roles && !in_array($_SESSION['user']['role'], $roles)) {
      http_response_code(403);
      echo '<h3>Akses ditolak</h3>';
      exit;
    }
  }
  public static function login($email, $password, $tahun)
  {
    $pdo = Database::getConnection();
    $st = $pdo->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
    $st->execute([$email]);
    $u = $st->fetch();

    if ($u && password_verify($password, $u['password_hash'])) {
      self::start();
      // Simpan tahun ke dalam session user
      $_SESSION['user'] = [
        'id'    => $u['id'],
        'name'  => $u['name'],
        'email' => $u['email'],
        'role'  => $u['role'],
        'tahun' => (int)$tahun // <--- Data Baru
      ];
      return true;
    }
    return false;
  }
  public static function logout()
  {
    self::start();
    session_destroy();
  }
}
?>
