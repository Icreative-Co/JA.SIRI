<?php
// db.php – MySQL connection using environment variables (suitable for Render / Aiven)

// Helper: load env from real env vars first, fallback to .env file if present
function env($key, $default = null) {
    $val = getenv($key);
    if ($val !== false) return $val;
    $ini = @parse_ini_file('.env');
    if ($ini && array_key_exists($key, $ini)) return $ini[$key];
    return $default;
}

// If a DATABASE_URL is provided (mysql://user:pass@host:port/dbname), parse it
$databaseUrl = env('DATABASE_URL');
$host = env('DB_HOST', 'localhost');
$port = env('DB_PORT', '3306');
$db   = env('DB_NAME', 'ksa_lipa');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$charset = 'utf8mb4';
$db_ssl_mode = env('DB_SSL_MODE', '');
$db_ssl_ca = env('DB_SSL_CA_PATH', '');

if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    if ($parts !== false) {
        if (!empty($parts['host'])) $host = $parts['host'];
        if (!empty($parts['port'])) $port = $parts['port'];
        if (!empty($parts['path'])) $db = ltrim($parts['path'], '/');
        if (!empty($parts['user'])) $user = $parts['user'];
        if (!empty($parts['pass'])) $pass = $parts['pass'];
    }
}

// Build DSN using TCP host and port (avoid socket lookup on localhost)
$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// If SSL CA path is provided and PDO constant exists, set SSL CA option
if (!empty($db_ssl_ca) && defined('PDO::MYSQL_ATTR_SSL_CA')) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $db_ssl_ca;
}

try {
    $db = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Log full error details for debugging (Render logs capture this)
    error_log("Database connection failed: " . $e->getMessage());
    // Show generic message to user and include the connection hint
    http_response_code(500);
    die("Database connection failed. Please contact support.\nService URI\n    " . ($databaseUrl ?: "mysql://{$user}:****@{$host}:{$port}/{$db}"));
}

// Customers & Payments (existing)
$db->exec("CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(15) UNIQUE NOT NULL,
    total_paid DECIMAL(10,2) DEFAULT 0.00,
    target_amount DECIMAL(10,2) DEFAULT 3500.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(15) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    receipt VARCHAR(50),
    paid_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// New: Products table (auto-populate with sample Scout items)
$db->exec("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category ENUM('Uniforms', 'Badges', 'Essentials') DEFAULT 'Uniforms',
    image VARCHAR(100) DEFAULT 'placeholder.jpg',  -- Add real images later
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Insert sample products (inspired by UK Scout Store – adapted for KSA)
$stmt = $db->prepare("INSERT IGNORE INTO products (name, description, price, category) VALUES (?, ?, ?, ?)");
$products = [
    ['Scout Uniform Shirt', 'Official long-sleeve shirt with KSA badge. 100% cotton.', 1500.00, 'Uniforms'],
    ['Scout Neckerchief', 'Triangular scarf in green for all ranks.', 300.00, 'Uniforms'],
    ['Cubs 110th Anniversary Badge', 'Commemorative badge for Cubs milestone.', 100.00, 'Badges'],
    ['Personalized Name Badge', 'Custom embroidered badge with scout name.', 200.00, 'Badges'],
    ['FDL Beanie Hat', 'Embroidered beanie for cold weather camps.', 800.00, 'Uniforms'],
    ['Forest Skills Handbook', 'Guide to outdoor crafts and survival skills.', 1200.00, 'Essentials'],
    ['Rechargeable Hand Warmers', 'Dual-palm warmers for night hikes.', 2500.00, 'Essentials'],
    ['Thermal Insulated Mug', 'One-touch mug for hot chai on treks.', 1500.00, 'Essentials'],
    ['Triple Badge Set', 'Set of three birthday/activity badges.', 250.00, 'Badges'],
    ['Scout Bobble Hat Kids', 'Fun bobble hat with FDL emblem for juniors.', 700.00, 'Uniforms']
];
foreach ($products as $p) {
    $stmt->execute($p);
}
?>