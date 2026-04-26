<?php
// config/db.php
if (!function_exists('load_dotenv_file')) {
    function load_dotenv_file(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $pair = explode('=', $trimmed, 2);
            if (count($pair) !== 2) {
                continue;
            }

            $key = trim($pair[0]);
            $value = trim($pair[1]);

            if ($key === '') {
                continue;
            }

            $len = strlen($value);
            if ($len >= 2) {
                $first = $value[0];
                $last = $value[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
            }

            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
            }

            if (!isset($_SERVER[$key])) {
                $_SERVER[$key] = $value;
            }
        }
    }
}

if (!function_exists('env_value')) {
    function env_value(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}

load_dotenv_file(__DIR__ . '/../../.env');

$host = '127.0.0.1';
$db   = 'roadside_assistance';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Basic error handling for pure PHP
    // Ensure we don't output anything if this file is included before headers are sent
    die("Database connection failed: " . $e->getMessage());
}

function ensureAgreedAmountColumn(PDO $pdo): void
{
    $stmt = $pdo->query("\n        SELECT COUNT(*)\n        FROM INFORMATION_SCHEMA.COLUMNS\n        WHERE TABLE_SCHEMA = DATABASE()\n          AND TABLE_NAME = 'requests'\n          AND COLUMN_NAME = 'agreed_amount'\n    ");

    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `requests` ADD COLUMN `agreed_amount` decimal(10,2) DEFAULT NULL AFTER `status`");
    }
}

function ensurePaymentStatusColumn(PDO $pdo): void
{
    $stmt = $pdo->query("\n        SELECT COUNT(*)\n        FROM INFORMATION_SCHEMA.COLUMNS\n        WHERE TABLE_SCHEMA = DATABASE()\n          AND TABLE_NAME = 'requests'\n          AND COLUMN_NAME = 'payment_status'\n    ");

    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `requests` ADD COLUMN `payment_status` enum('Unpaid','Paid') NOT NULL DEFAULT 'Unpaid' AFTER `agreed_amount`");
    }
}

ensureAgreedAmountColumn($pdo);
ensurePaymentStatusColumn($pdo);
?>
