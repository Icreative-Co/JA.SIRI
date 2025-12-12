<?php
/**
 * db.php – Secure MySQL PDO for Render + Aiven
 * Auto-creates tables + loads sample products safely.
 */

/* ---------------------- ENV LOADER ---------------------- */
function env($key, $default = null) {
    $val = getenv($key);
    if ($val !== false) return $val;

    static $ini = null;
    if ($ini === null && file_exists('.env')) {
        $ini = parse_ini_file('.env');
    }

    return $ini[$key] ?? $default;
}

/* ---------------------- DATABASE CONFIG ---------------------- */
$databaseUrl = env('DATABASE_URL');

$host = env('DB_HOST', 'localhost');
$port = env('DB_PORT', '3306');
$db   = env('DB_NAME', 'ksa_lipa');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$charset = 'utf8mb4';

$db_ssl_mode = env('DB_SSL_MODE', 'REQUIRED');
$db_ssl_cert_env = env('DB_SSL_CERT', '');
$db_ssl_ca = env('DB_SSL_CA_PATH', '');

/* Render's CA workaround */
if (empty($db_ssl_ca) && !empty($db_ssl_cert_env)) {
    $tmp = sys_get_temp_dir() . '/aiven-ca.pem';
    file_put_contents($tmp, $db_ssl_cert_env);
    $db_ssl_ca = $tmp;
}

/* DATABASE_URL override (Render/Aiven format) */
if ($databaseUrl) {
    $u = parse_url($databaseUrl);
    if ($u !== false) {
        $host = $u['host'] ?? $host;
        $port = $u['port'] ?? $port;
        $db   = ltrim($u['path'], '/') ?: $db;
        $user = $u['user'] ?? $user;
        $pass = $u['pass'] ?? $pass;
    }
}

/* ---------------------- PDO CONNECTION ---------------------- */
$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

if (!empty($db_ssl_ca) && defined('PDO::MYSQL_ATTR_SSL_CA')) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $db_ssl_ca;
}

try {
    $db = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("[DB ERROR] ".$e->getMessage());
    http_response_code(500);
    die("Database connection failed.");
}

/* ---------------------- TABLE CREATION ---------------------- */
$db->exec("
    CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(15) UNIQUE NOT NULL,
        total_paid DECIMAL(10,2) DEFAULT 0.00,
        target_amount DECIMAL(10,2) DEFAULT 3500.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(15) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        receipt VARCHAR(50),
        paid_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        category ENUM('Uniforms', 'Badges', 'Essentials') DEFAULT 'Uniforms',
        image VARCHAR(100) DEFAULT 'placeholder.jpg',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

/* ---------------------- SAFE SAMPLE PRODUCTS ---------------------- */
$sampleProducts = [
    ['Scout Uniform Shirt', 'Official long-sleeve shirt with KSA badge. 100% cotton.', 1500.00, 'Uniforms'],
    ['Scout Neckerchief', 'Triangular scarf in green for all ranks.', 300.00, 'Uniforms'],
    ['Cubs 110th Anniversary Badge', 'Commemorative badge for Cubs milestone.', 100.00, 'Badges'],
    ['Personalized Name Badge', 'Custom embroidered badge with scout name.', 200.00, 'Badges'],
    ['FDL Beanie Hat', 'Embroidered beanie for cold weather camps.', 800.00, 'Uniforms'],
    ['Forest Skills Handbook', 'Guide to outdoor crafts and survival skills.', 1200.00, 'Essentials'],
    ['Rechargeable Hand Warmers', 'Dual-palm warmers for night hikes.', 2500.00, 'Essentials'],
    ['Thermal Insulated Mug', 'One-touch mug for hot chai on treks.', 1500.00, 'Essentials'],
    ['Triple Badge Set', 'Set of three birthday/activity badges.', 250.00, 'Badges'],
    ['Scout Bobble Hat Kids', 'Fun bobble hat with FDL emblem for juniors.', 700.00, 'Uniforms'],
];

$check = $db->prepare("SELECT COUNT(*) FROM products WHERE name = ?");
$insert = $db->prepare("
    INSERT INTO products (name, description, price, category) 
    VALUES (?, ?, ?, ?)
");

/* Prevent duplicate product loading */
foreach ($sampleProducts as $p) {
    $check->execute([$p[0]]);
    if ($check->fetchColumn() == 0) {
        $insert->execute($p);
    }
}
?>
