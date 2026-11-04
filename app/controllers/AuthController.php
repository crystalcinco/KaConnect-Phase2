<?php
declare(strict_types=1);
class AuthController {
    public function __construct(private UserModel $users) {}
    public function register(): void {
        if (auth_user()) redirect('index.php?page=feed');
        if (($_SERVER['REQUEST_METHOD']??'GET')==='GET') { $errors=[]; require __DIR__.'/../views/auth/register.php'; return; }
        require_post(); verify_csrf();
        $fullName=trim((string)($_POST['full_name']??'')); $username=trim((string)($_POST['username']??'')); $password=(string)($_POST['password']??''); $confirm=(string)($_POST['password_confirmation']??''); $errors=[];
        if (text_length($fullName)<2 || text_length($fullName)>100) $errors[]='Full name must be between 2 and 100 characters.';
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/',$username)) $errors[]='Username must be 3–30 characters using letters, numbers, or underscore.';
        if (strlen($password)<8) $errors[]='Password must be at least 8 characters.';
        if ($password!==$confirm) $errors[]='Password confirmation does not match.';
        if (!$errors && $this->users->findByUsername($username)) $errors[]='Username is already taken.';
        if ($errors) { require __DIR__.'/../views/auth/register.php'; return; }
        try { $id=$this->users->create($username,password_hash($password,PASSWORD_DEFAULT),$fullName); session_regenerate_id(true); $_SESSION['user']=$this->users->findById($id); flash('success','Account created successfully. Welcome to KaConnect!'); redirect('index.php?page=feed'); }
        catch (PDOException $e) { if ((string)$e->getCode()==='23000') $errors[]='That username is already registered. Please choose another.'; else throw $e; require __DIR__.'/../views/auth/register.php'; }
    }
    public function login(): void {
        if (auth_user()) redirect('index.php?page=feed');
        if (($_SERVER['REQUEST_METHOD']??'GET')==='GET') { $errors=[]; require __DIR__.'/../views/auth/login.php'; return; }
        require_post(); verify_csrf(); $username=trim((string)($_POST['username']??'')); $password=(string)($_POST['password']??''); $user=$this->users->findByUsername($username); $errors=[];
        if (!$user || !password_verify($password,(string)$user['password'])) $errors[]='Invalid username or password.';
        if ($errors) { require __DIR__.'/../views/auth/login.php'; return; }
        if (password_needs_rehash($user['password'],PASSWORD_DEFAULT)) { /* A password rehash can be added without changing the login flow. */ }
        session_regenerate_id(true); unset($user['password']); $_SESSION['user']=$user; flash('success','Welcome back!'); redirect('index.php?page=feed');
    }
    public function logout(): void { require_auth(); require_post(); verify_csrf(); $_SESSION=[]; if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',(int)(time()-42000),$p['path'],$p['domain'],$p['secure'],$p['httponly']); } session_destroy(); redirect('index.php?page=login'); }
}
