<?php
declare(strict_types=1);

class Database
{
    private string $host = '127.0.0.1';
    private string $db = 'mini_social_network';
    private string $user = 'root';
    private string $pass = '';
    private string $charset = 'utf8mb4';

    public function connect(): PDO
    {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        return new PDO($dsn, $this->user, $this->pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
