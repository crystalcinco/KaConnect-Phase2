<?php
declare(strict_types=1);

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $url): never { header('Location: ' . $url); exit; }

function safe_return_url(string $fallback = 'index.php?page=feed'): string {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer === '') return $fallback;
    $path = parse_url($referer, PHP_URL_PATH) ?: '';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($path !== $script && basename($path) !== basename($script)) return $fallback;
    $query = parse_url($referer, PHP_URL_QUERY);
    return 'index.php' . ($query ? '?' . $query : '');
}

function flash(string $key, ?string $message = null): ?string {
    if ($message !== null) { $_SESSION['_flash'][$key] = $message; return null; }
    $value = $_SESSION['_flash'][$key] ?? null; unset($_SESSION['_flash'][$key]); return $value;
}

function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}

function verify_csrf(): void {
    $token = (string)($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!isset($_SESSION['_csrf']) || !hash_equals((string)$_SESSION['_csrf'], $token)) {
        if (is_ajax()) {
            json_response(['success' => false, 'error' => 'Your session expired. Please refresh the page.'], 419);
        }
        http_response_code(419); exit('Your form session expired. Refresh the page and try again.');
    }
}

function require_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        if (is_ajax()) json_response(['success' => false, 'error' => 'Method not allowed.'], 405);
        http_response_code(405); header('Allow: POST'); exit('Method not allowed.');
    }
}

function auth_user(): ?array { return $_SESSION['user'] ?? null; }
function require_auth(): void {
    if (!auth_user()) {
        if (is_ajax()) json_response(['success' => false, 'error' => 'Authentication required.'], 401);
        redirect('index.php?page=login');
    }
}
function current_user_id(): ?int { return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null; }

function is_ajax(): bool {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
        || ((string)($_GET['ajax'] ?? '') === '1')
        || ((string)($_POST['ajax'] ?? '') === '1');
}

function json_response(array $data, int $statusCode = 200): never {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function upload_image(array $file, string $folder, array $allowed = ['image/jpeg','image/png','image/gif','image/webp']): ?string {
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) return null;
    if ($error !== UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed. Please try again.');
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Invalid uploaded file.');
    if ((int)($file['size'] ?? 0) < 1 || (int)$file['size'] > 5*1024*1024) throw new RuntimeException('Image must be between 1 byte and 5 MB.');
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) throw new RuntimeException('The selected file is not a valid image.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) throw new RuntimeException('Only JPG, PNG, GIF, and WEBP images are allowed.');
    $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $targetDir = __DIR__ . '/../public/uploads/' . $folder;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) throw new RuntimeException('Upload folder could not be created.');
    $target = $targetDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) throw new RuntimeException('Unable to save the uploaded image.');
    return 'uploads/' . $folder . '/' . $filename;
}

function upload_base64_image(?string $base64Data, string $folder): ?string {
    if (!$base64Data || !str_starts_with($base64Data, 'data:image/')) return null;
    if (!preg_match('~^data:image/(jpeg|png|webp|gif);base64,(.+)~i', $base64Data, $matches)) {
        throw new RuntimeException('Invalid image data format.');
    }
    $type = strtolower($matches[1]);
    $extensions = ['jpeg'=>'jpg', 'jpg'=>'jpg', 'png'=>'png', 'webp'=>'webp', 'gif'=>'gif'];
    $ext = $extensions[$type] ?? 'jpg';
    $raw = base64_decode($matches[2], true);
    if ($raw === false) throw new RuntimeException('Failed to decode cropped image.');
    if (strlen($raw) > 5 * 1024 * 1024) throw new RuntimeException('Image must be 5 MB or fewer.');

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetDir = __DIR__ . '/../public/uploads/' . $folder;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) throw new RuntimeException('Upload folder could not be created.');
    $target = $targetDir . '/' . $filename;
    if (file_put_contents($target, $raw) === false) throw new RuntimeException('Unable to save cropped image.');
    return 'uploads/' . $folder . '/' . $filename;
}

function delete_uploaded_image(?string $relativePath): void {
    if (!$relativePath || !preg_match('~^uploads/(posts|profiles|covers)/[a-f0-9]{24,32}\.(jpg|png|gif|webp)$~i', $relativePath)) return;
    $root = realpath(__DIR__ . '/../public/uploads');
    $file = realpath(__DIR__ . '/../public/' . $relativePath);
    if ($root && $file && str_starts_with($file, $root . DIRECTORY_SEPARATOR) && is_file($file)) @unlink($file);
}

function avatar_markup(?array $user, string $class = 'avatar'): string {
    $name = trim((string)($user['full_name'] ?? 'K')) ?: 'K';
    $image = $user['profile_image'] ?? null;
    if ($image) return '<img class="' . e($class) . ' avatar-photo" src="' . e($image) . '" alt="' . e($name) . ' profile picture">';
    $initial = function_exists('mb_substr') ? mb_substr($name,0,1,'UTF-8') : substr($name,0,1);
    return '<span class="' . e($class) . '" aria-label="' . e($name) . '">' . e(strtoupper($initial)) . '</span>';
}

function friendly_date(?string $date): string {
    if (!$date) return '';
    $time = strtotime($date); return $time ? date('M j, Y · g:i A', $time) : $date;
}

function time_ago(?string $datetime): string {
    if (!$datetime) return '';
    $time = strtotime($datetime);
    if (!$time) return '';
    $diff = time() - $time;
    if ($diff < 5) return 'Just now';
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return (int)($diff / 60) . 'm ago';
    if ($diff < 86400) return (int)($diff / 3600) . 'h ago';
    if ($diff < 604800) return (int)($diff / 86400) . 'd ago';
    return date('M j, Y', $time);
}

function text_length(string $value): int { return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value); }

function ensure_schema_up_to_date(PDO $db): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $cols = $db->query("SHOW COLUMNS FROM users LIKE 'cover_image'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN cover_image VARCHAR(255) NULL AFTER profile_image");
        }
    } catch (Throwable $e) {}

    try {
        $db->exec("CREATE TABLE IF NOT EXISTS follows (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            follower_id INT UNSIGNED NOT NULL,
            following_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_follow (follower_id, following_id),
            CONSTRAINT fk_follows_follower FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_follows_following FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    } catch (Throwable $e) {}

    try {
        $db->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            actor_id INT UNSIGNED NOT NULL,
            type VARCHAR(30) NOT NULL,
            entity_id INT UNSIGNED NULL,
            message VARCHAR(255) NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_notifications_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    } catch (Throwable $e) {}

    try {
        $db->exec("CREATE TABLE IF NOT EXISTS messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sender_id INT UNSIGNED NOT NULL,
            receiver_id INT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    } catch (Throwable $e) {}
}
