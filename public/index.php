<?php
declare(strict_types=1);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);
ini_set('session.use_strict_mode', '1');
session_unset();

require_once __DIR__ . '/../.../database.php';
require_once __DIR__ . '/../app/helpers.php';

foreach (glob(__DIR__ . '/../app/models/*.php') as $file) require_once $file;
foreach (glob(__DIR__ . '/../app/controllers/*.php') as $file) require_once $file;

try {
    $db = (new Database())->connect();
    ensure_schema_up_to_date($db);
} catch (Throwable $e) {
    if (is_ajax()) {
        json_response(['success' => false, 'error' => 'Database connection failed. Ensure MySQL is running and sql/social_app.sql is imported.'], 500);
    }
    http_response_code(500);
    exit('Database connection failed. Check config/database.php, import sql/social_app.sql, and make sure MySQL is running.');
}

$users = new UserModel($db);
$posts = new PostModel($db);
$comments = new CommentModel($db);
$likes = new LikeModel($db);
$follows = new FollowModel($db);
$notifications = new NotificationModel($db);
$messages = new MessageModel($db);

$auth = new AuthController($users);
$profile = new ProfileController($users, $posts, $follows, $notifications);
$post = new PostController($posts, $comments, $likes, $users, $notifications, $follows);
$comment = new CommentController($comments, $posts, $notifications);
$follow = new FollowController($follows, $users, $notifications);
$notif = new NotificationController($notifications, $messages);
$msg = new MessageController($messages, $users, $notifications);

$page = (string)($_GET['page'] ?? (auth_user() ? 'feed' : 'login'));

$routes = [
    'register'           => fn() => $auth->register(),
    'login'              => fn() => $auth->login(),
    'logout'             => fn() => $auth->logout(),
    'feed'               => fn() => $post->feed(),
    'members'            => fn() => $profile->members(),
    'post_create'        => fn() => $post->create(),
    'post_edit'          => fn() => $post->edit(),
    'post_delete'        => fn() => $post->delete(),
    'like'               => fn() => $post->like(),
    'comment_create'     => fn() => $comment->create(),
    'comment_edit'       => fn() => $comment->edit(),
    'comment_delete'     => fn() => $comment->delete(),
    'profile'            => fn() => $profile->show(),
    'profile_edit'       => fn() => $profile->edit(),
    'follow_toggle'      => fn() => $follow->toggle(),
    'followers'          => fn() => $follow->followers(),
    'following'          => fn() => $follow->following(),
    'notifications'      => fn() => $notif->index(),
    'notifications_poll' => fn() => $notif->poll(),
    'notifications_read' => fn() => $notif->markRead(),
    'messages'           => fn() => $msg->index(),
    'message_send'       => fn() => $msg->send(),
    'messages_poll'      => fn() => $msg->poll(),
];

if (!isset($routes[$page])) {
    if (is_ajax()) {
        json_response(['success' => false, 'error' => 'Endpoint not found.'], 404);
    }
    http_response_code(404);
    exit('Page not found.');
}

$routes[$page]();
