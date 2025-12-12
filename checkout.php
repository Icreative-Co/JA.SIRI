<?php
// checkout.php - improved version
require_once 'db.php';      // your PDO $db connection
require_once 'mpesa.php';   // function stkPush($phone, $amount, $accountRef)
session_start();

// Simple CSRF protection
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['csrf_token'];

// Helper: normalize phone to 2547XXXXXXXX or return false if invalid
function normalizeKenyaPhone($raw) {
    $digits = preg_replace('/\D+/', '', $raw);
    if (strlen($digits) == 9 && preg_match('/^7\d{8}$/', $digits)) {
        return '254' . $digits;
    }
    if (strlen($digits) == 10 && preg_match('/^07\d{8}$/', $digits)) {
        return '254' . substr($digits, 1);
    }
    if (strlen($digits) == 12 && preg_match('/^2547\d{8}$/', $digits)) {
        return $digits;
    }
    return false;
}

// Rebuild cart items from session (support two common shapes):
// 1) $_SESSION['cart'] = [product_id => qty, ...]
// 2) $_SESSION['cart'] = [ ['id'=>..., 'name'=>..., 'price'=>..., 'qty'=>..., 'total'=>...], ... ]
$cart_raw = $_SESSION['cart'] ?? null;
if (empty($cart_raw)) {
    header('Location: shop.php');
    exit;
}

$cart_items = [];
$total = 0;
$cartCount = 0;

if (is_array($cart_raw)) {
    // detect shape 2: numeric-indexed arrays with 'id' inside first element
    $first = reset($cart_raw);
    if (is_array($first) && isset($first['id']) && isset($first['qty'])) {
        foreach ($cart_raw as $it) {
            $price = floatval($it['price'] ?? 0);
            $qty = intval($it['qty'] ?? 1);
            $subtotal = $price * $qty;
            $cart_items[] = [
                'id' => intval($it['id']),
                'name' => $it['name'] ?? 'Item',
                'price' => $price,
                'qty' => $qty,
                'subtotal' => $subtotal,
                'image' => $it['image'] ?? ''
            ];
            $total += $subtotal;
            $cartCount += $qty;
        }
    } else {
        // assume shape 1: map product_id => qty
        $ids = array_map('intval', array_keys($cart_raw));
        if (count($ids) > 0) {
            $placeholders = rtrim(str_repeat('?,', count($ids)), ',');
            $stmt = $db->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $byId = [];
            foreach ($rows as $r) $byId[$r['id']] = $r;
            foreach ($cart_raw as $pid => $qty) {
                $pid = intval($pid);
                $qty = max(0, intval($qty));
                if ($qty <= 0) continue;
                if (!isset($byId[$pid])) continue; // product removed
                $item = $byId[$pid];
                $subtotal = floatval($item['price']) * $qty;
                $cart_items[] = [
                    'id' => $pid,
                    'name' => $item['name'],
                    'price' => floatval($item['price']),
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                    'image' => $item['image'] ?? ''
                ];
                $total += $subtotal;
                $cartCount += $qty;
            }
        }
    }
}

// Handle POST actions: update quantity, remove item, pay installment
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Basic CSRF check
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf'])) {
        $errors[] = "Session validation failed. Please refresh and try again.";
    } else {

        // Update quantity action
        if (isset($_POST['action']) && $_POST['action'] === 'update_qty' && isset($_POST['product_id'])) {
            $pid = intval($_POST['product_id']);
            $qty = max(0, intval($_POST['qty'] ?? 0));
            // Update in session depending on shape
            if (is_array($cart_raw) && isset($cart_raw[array_key_first($cart_raw)]) && is_array(reset($cart_raw)) && isset(reset($cart_raw)['id'])) {
                // shape 2
                foreach ($_SESSION['cart'] as $k => $it) {
                    if (intval($it['id']) === $pid) {
                        if ($qty <= 0) unset($_SESSION['cart'][$k]);
                        else $_SESSION['cart'][$k]['qty'] = $qty;
                        break;
                    }
                }
                $_SESSION['cart'] = array_values($_SESSION['cart']);
            } else {
                // shape 1
                if ($qty <= 0) unset($_SESSION['cart'][$pid]);
                else $_SESSION['cart'][$pid] = $qty;
            }
            header('Location: checkout.php');
            exit;
        }

        // Remove item action
        if (isset($_POST['action']) && $_POST['action'] === 'remove_item' && isset($_POST['product_id'])) {
            $pid = intval($_POST['product_id']);
            if (is_array($cart_raw) && isset($cart_raw[array_key_first($cart_raw)]) && is_array(reset($cart_raw)) && isset(reset($cart_raw)['id'])) {
                foreach ($_SESSION['cart'] as $k => $it) {
                    if (intval($it['id']) === $pid) { unset($_SESSION['cart'][$k]); break; }
                }
                $_SESSION['cart'] = array_values($_SESSION['cart']);
            } else {
                unset($_SESSION['cart'][$pid]);
            }
            header('Location: checkout.php');
            exit;
        }

        // Pay (installment) action
        if (isset($_POST['pay_installment'])) {
            $phone_raw = $_POST['phone'] ?? '';
            $normalized = normalizeKenyaPhone($phone_raw);
            $amount = intval($_POST['amount'] ?? 0);

            if ($normalized === false) {
                $errors[] = "Please enter a valid Kenyan M-Pesa phone number (e.g. 07XXXXXXXX or 2547XXXXXXXX).";
            }
            if ($amount < 50) {
                $errors[] = "Minimum payment is KES 50.";
            }
            if ($amount > $total) {
                $errors[] = "Amount cannot exceed total KES " . number_format($total) . ".";
            }

            if (empty($errors)) {
                // Insert order skeleton to let you track attempts
                try {
                    $db->beginTransaction();

                    $account_ref = 'KSA-ORDER-' . time() . '-' . bin2hex(random_bytes(3));
                    // create customer record (or update)
                    $stmt = $db->prepare("INSERT INTO customers (phone, last_seen) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()");
                    $stmt->execute([$normalized]);

                    // create orders
                    $stmt = $db->prepare("INSERT INTO orders (customer_phone, account_ref, total_amount, status, created_at, payment_type) VALUES (?, ?, ?, ?, NOW(), ?)");
                    $stmt->execute([$normalized, $account_ref, $total, 'initiated', 'mpesa_installment']);
                    $order_id = $db->lastInsertId();

                    // insert items
                    $ins = $db->prepare("INSERT INTO order_items (order_id, product_id, name, price, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                    foreach ($cart_items as $ci) {
                        $ins->execute([$order_id, $ci['id'], $ci['name'], $ci['price'], $ci['qty'], $ci['subtotal']]);
                    }

                    // record an mpesa_logs entry for traceability
                    $logStmt = $db->prepare("INSERT INTO mpesa_logs (order_id, phone, amount, account_ref, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $logStmt->execute([$order_id, $normalized, $amount, $account_ref, 'initiating']);

                    $db->commit();
                } catch (Exception $e) {
                    $db->rollBack();
                    $errors[] = "Internal error creating order. Please try again or contact support.";
                    error_log("[CHECKOUT_ORDER_ERROR] " . $e->getMessage());
                }

                // If order created, attempt STK push
                if (empty($errors) && !empty($order_id)) {
                    $result = stkPush($normalized, $amount, $account_ref); // assumes associative return
                    // store the full result back to mpesa_logs
                    try {
                        $upd = $db->prepare("UPDATE mpesa_logs SET response = ?, status = ?, updated_at = NOW() WHERE order_id = ?");
                        $upd->execute([json_encode($result), ($result['ResponseCode'] ?? $result['errorCode'] ?? 'error'), $order_id]);
                    } catch (Exception $e) {
                        error_log("[MPESA_LOG_UPDATE_ERROR] " . $e->getMessage());
                    }

                    $respCode = isset($result['ResponseCode']) ? (string)$result['ResponseCode'] : (string)($result['errorCode'] ?? 'ERR');
                    if ($respCode === '0' || $respCode === 0) {
                        // payment initiation OK
                        // update order status
                        $db->prepare("UPDATE orders SET status = ?, mpesa_response = ? WHERE id = ?")
                           ->execute(['pending_payment', json_encode($result), $order_id]);

                        // clear cart for this session (optional: keep until payment confirmed)
                        unset($_SESSION['cart']);

                        // redirect to check.php with order id to allow tracking
                        header('Location: check.php?order=' . urlencode($order_id) . '&msg=initiated');
                        exit;
                    } else {
                        // error returned by M-Pesa: surface message if available
                        $msg = $result['CustomerMessage'] ?? $result['errorMessage'] ?? 'Payment initiation failed. Please try again.';
                        $errors[] = "M-Pesa Error: " . htmlspecialchars($msg);
                        error_log("[MPESA_RESPONSE] order:$order_id phone:$normalized amount:$amount result:" . json_encode($result));
                    }
                }
            }
        } // end pay_installment
    } // end csrf ok
} // end POST

// Recalculate totals after possible session changes
// (quick rebuild)
$cartCount = 0; $total = 0;
foreach ($cart_items as $ci) {
    $cartCount += $ci['qty'];
    $total += $ci['subtotal'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Checkout - KSA Shop</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-50 text-gray-800">

<!-- Navbar (minimal) -->
<nav class="bg-white shadow sticky top-0 z-40">
  <div class="container mx-auto px-4 py-4 flex items-center justify-between">
    <div class="flex items-center gap-4">
      <a href="index.php"><img src="assets/img/ksa_logo.png" alt="KSA" class="h-12"></a>
      <div class="hidden md:block">
        <div class="text-lg font-bold text-green-800">Kenya Scouts Association</div>
        <div class="text-sm text-gray-600">Be Prepared • Kuwa Tayari</div>
      </div>
    </div>
    <div>
      <a href="shop.php" class="px-4 py-2 rounded-full bg-yellow-400 text-green-900 font-semibold">Back to shop</a>
    </div>
  </div>
</nav>

<!-- Page header -->
<header class="bg-gradient-to-r from-green-800 to-green-900 text-white py-12 text-center">
  <div class="container mx-auto">
    <h1 class="text-3xl md:text-4xl font-black">Checkout</h1>
    <p class="mt-2 text-lg">Complete your order — pay now with M-Pesa (Lipa Mdogo Mdogo supported)</p>
  </div>
</header>

<main class="container mx-auto px-4 py-10 max-w-6xl">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- ORDER SUMMARY -->
    <section class="lg:col-span-2 space-y-6">
      <div class="bg-white shadow rounded-2xl p-6 border-t-8 border-green-700">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-2xl font-bold text-gray-900">Your Order (<?php echo $cartCount; ?> items)</h2>
          <div class="text-lg font-semibold text-green-700">Total KES <?php echo number_format($total); ?></div>
        </div>

        <?php if (empty($cart_items)): ?>
          <div class="text-center py-10 text-gray-500">Your cart is empty. <a href="shop.php" class="text-green-700 underline">Browse products</a></div>
        <?php else: ?>
          <div class="space-y-4">
            <?php foreach ($cart_items as $ci): ?>
              <div class="flex gap-4 items-center p-4 rounded-lg border">
                <img src="<?php echo htmlspecialchars($ci['image'] ? 'uploads/'.$ci['image'] : 'assets/img/placeholder.png'); ?>" alt="" class="w-24 h-24 object-cover rounded">
                <div class="flex-1">
                  <div class="flex justify-between items-start">
                    <div>
                      <div class="font-bold text-lg"><?php echo htmlspecialchars($ci['name']); ?></div>
                      <div class="text-sm text-gray-600">KSh <?php echo number_format($ci['price']); ?> each</div>
                    </div>
                    <div class="text-right font-bold text-lg text-green-700">KSh <?php echo number_format($ci['subtotal']); ?></div>
                  </div>

                  <div class="mt-3 flex items-center gap-3">
                    <!-- Update qty form -->
                    <form method="POST" class="flex items-center gap-2">
                      <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                      <input type="hidden" name="action" value="update_qty">
                      <input type="hidden" name="product_id" value="<?php echo $ci['id']; ?>">
                      <input type="number" name="qty" value="<?php echo $ci['qty']; ?>" min="0" class="w-20 px-2 py-2 border rounded">
                      <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded">Update</button>
                    </form>

                    <!-- Remove -->
                    <form method="POST" onsubmit="return confirm('Remove this item?');">
                      <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                      <input type="hidden" name="action" value="remove_item">
                      <input type="hidden" name="product_id" value="<?php echo $ci['id']; ?>">
                      <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded">Remove</button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Delivery & notes -->
      <div class="bg-white p-6 rounded-2xl shadow border-l-4 border-green-300">
        <h3 class="font-bold text-lg mb-3">Delivery & Pickup</h3>
        <p class="text-gray-700 mb-2"><strong>Pickup:</strong> KSA Headquarters, Rowallan Scouts Camp, Nairobi (free)</p>
        <p class="text-gray-700 mb-2"><strong>Delivery:</strong> Nationwide via partner courier — KES 300–600 (calculated at dispatch)</p>
        <p class="text-sm text-gray-600">Processing starts once we receive payment confirmation. For large orders contact <a href="mailto:info@kenyascouts.org" class="underline">info@kenyascouts.org</a></p>
      </div>

    </section>

    <!-- PAYMENT / MPESA -->
    <aside class="space-y-6">
      <div class="bg-white p-6 rounded-2xl shadow border-t-8 border-yellow-400 sticky top-20">
        <h3 class="text-2xl font-bold text-center text-green-800 mb-4">Lipa Mdogo Mdogo</h3>

        <?php if (!empty($errors)): ?>
          <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded mb-4">
            <?php foreach ($errors as $er) echo '<div class="text-sm">'.htmlspecialchars($er).'</div>'; ?>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded mb-4">
            <?php echo htmlspecialchars($success); ?>
          </div>
        <?php endif; ?>

        <div class="bg-green-50 p-4 rounded mb-6 text-center">
          <div class="text-3xl font-bold text-green-700">KES <?php echo number_format($total); ?></div>
          <div class="text-sm text-gray-700 mt-1">Total order amount</div>
        </div>

        <form method="POST" id="payForm" class="space-y-4">
          <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
          <label class="text-sm font-semibold">M-Pesa Phone</label>
          <input type="text" name="phone" placeholder="07XXXXXXXX or 2547XXXXXXXX" required class="w-full px-3 py-2 border rounded" />

          <label class="text-sm font-semibold">Amount to pay now (min KES 50)</label>
          <input type="number" name="amount" min="50" max="<?php echo intval($total); ?>" required value="<?php echo min(50, intval($total)); ?>" class="w-full px-3 py-2 border rounded" />

          <button type="submit" name="pay_installment" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded" onclick="return confirm('You will receive an M-Pesa prompt. Proceed?')">
            Pay with M-Pesa
          </button>
        </form>

        <p class="text-xs text-gray-500 mt-3">After initiating payment, track at <a href="check.php" class="underline">check.php</a> or use the order id returned on success.</p>
      </div>

      <!-- Support / contact -->
      <div class="bg-white p-4 rounded shadow text-center">
        <div class="font-semibold text-green-800">Need help?</div>
        <div class="text-sm text-gray-600">Call: 0733 919 333 or email <a href="mailto:info@kenyascouts.org" class="underline">info@kenyascouts.org</a></div>
      </div>

    </aside>

  </div>
</main>

<footer class="bg-gray-900 text-gray-200 py-8 text-center">
  <div class="container mx-auto">
    <img src="assets/img/ksa_logo.png" alt="KSA" class="h-16 mx-auto mb-2">
    <div>© <?php echo date('Y'); ?> Kenya Scouts Association</div>
  </div>
</footer>

<script>
  // prevent double-submit
  document.getElementById('payForm')?.addEventListener('submit', function(e){
    const btn = this.querySelector('button[type="submit"]');
    if (btn) {
      btn.disabled = true;
      btn.innerText = 'Processing...';
    }
  });
</script>
</body>
</html>
