<?php
namespace App\Controllers;

use App\Auth;
use App\Db;
use App\Helpers\ImageUtil;
use App\Helpers\Response;

class AdminController
{
    /* ---------------- Auth ---------------- */

    public function loginForm(): void {
        if (Auth::check()) { header('Location: /admin'); return; }
        Response::view('admin/login.php');
    }

    public function login(): void {
        $u = trim($_POST['username'] ?? '');
        $p = (string)($_POST['password'] ?? '');

        if ($u === '' || $p === '') {
            Response::view('admin/login.php', ['error' => 'Username and password are required.']);
            return;
        }

        $pdo = Db::pdo();
        $stmt = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE username=?');
        $stmt->execute([$u]);
        $row = $stmt->fetch();

        if ($row && password_verify($p, $row['password_hash'])) {
            Auth::login((int)$row['id']);
            header('Location: /admin');
            return;
        }

        Response::view('admin/login.php', ['error' => 'Invalid credentials.']);
    }

    public function logout(): void {
        Auth::logout();
        header('Location: /admin/login');
    }

    /* ---------------- Dashboard ---------------- */

    public function dashboard(): void {
        Auth::require();
        $pdo = Db::pdo();

        $stats = [
            'products'     => (int)$pdo->query('SELECT COUNT(*) c FROM products')->fetch()['c'],
            'keys_instock' => (int)$pdo->query('SELECT COUNT(*) c FROM license_keys WHERE is_sold=0')->fetch()['c'],
            'orders_paid'  => (int)$pdo->query("SELECT COUNT(*) c FROM orders WHERE status='paid'")->fetch()['c'],
            'revenue'      => (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) s FROM orders WHERE status='paid'")->fetch()['s'],
        ];

        Response::view('admin/dashboard.php', compact('stats'));
    }

    /* ---------------- Products & Keys ---------------- */

    public function products(): void {
        Auth::require();
        $pdo = Db::pdo();

        $q = trim($_GET['q'] ?? '');
        // Hide sold-out products from the admin list
        $sql = "SELECT * FROM products WHERE COALESCE(is_sold_out,0)=0";
        $params = [];

        if ($q !== '') {
            $sql .= " AND (name LIKE ? OR category LIKE ?)";
            $params = ["%$q%", "%$q%"];
        }
        $sql .= " ORDER BY created_at DESC";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $products = $st->fetchAll();

        Response::view('admin/products.php', compact('products','q'));
    }

    public function productForm(array $params = []): void {
        Auth::require();
        $pdo = Db::pdo();
        $product = null;
        $existingKeys = [];

        if (!empty($params['id'])) {
            $st = $pdo->prepare('SELECT * FROM products WHERE id=?');
            $st->execute([$params['id']]);
            $product = $st->fetch();
            if (!$product) { header('Location: /admin/products'); return; }

            // load ALL keys (including sold? you asked to show what admin put in; we show UNSOLD so they manage inventory)
            $k = $pdo->prepare('SELECT license_key FROM license_keys WHERE product_id=? AND is_sold=0 ORDER BY id ASC');
            $k->execute([(int)$product['id']]);
            $existingKeys = array_column($k->fetchAll(), 'license_key');
        }

        Response::view('admin/product_form.php', compact('product','existingKeys'));
    }

public function productSave(): void {
    Auth::require();
    $pdo = Db::pdo();

    $id   = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
    $data = [
        'category'      => trim($_POST['category'] ?? ''),
        'name'          => trim($_POST['name'] ?? ''),
        'description'   => (string)($_POST['description'] ?? ''),
        'price'         => (float)($_POST['price'] ?? 0),
        'link'          => trim($_POST['link'] ?? ''),
        'delivery_type' => trim($_POST['delivery_type'] ?? ''),
    ];

    if ($data['name'] === '' || $data['price'] <= 0) {
        Response::view('admin/product_form.php', [
            'product' => array_merge(['id' => $id], $data),
            'error'   => 'Name and positive price are required.'
        ]);
        return;
    }

    $photoBase64 = null;
    if (!empty($_FILES['photo']['tmp_name'])) {
        try {
            $photoBase64 = ImageUtil::compressToBase64($_FILES['photo']);
        } catch (\Throwable $e) {
            Response::view('admin/product_form.php', [
                'product' => array_merge(['id' => $id], $data),
                'error'   => 'Image upload failed: ' . $e->getMessage()
            ]);
            return;
        }
    }

    if ($id) {
        $sql = 'UPDATE products
                SET category=?, name=?, description=?, price=?, link=?, delivery_type=?, updated_at=NOW()'
             . ($photoBase64 ? ', photo_base64=?' : '')
             . ' WHERE id=?';

        $params = [
            $data['category'], $data['name'], $data['description'],
            $data['price'], $data['link'], $data['delivery_type']
        ];
        if ($photoBase64) $params[] = $photoBase64;
        $params[] = $id;

        $pdo->prepare($sql)->execute($params);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO products (category, name, photo_base64, description, price, link, delivery_type, created_at)
             VALUES (?,?,?,?,?,?,?, NOW())'
        );
        $stmt->execute([
            $data['category'], $data['name'], $photoBase64, $data['description'],
            $data['price'], $data['link'], $data['delivery_type']
        ]);
        $id = (int)$pdo->lastInsertId();
    }

    // Auto mark sold-out if hidden
    $pdo->prepare("
        UPDATE products
        SET is_sold_out = CASE WHEN COALESCE(is_hidden,0)=1 THEN 1 ELSE is_sold_out END,
            updated_at = NOW()
    ")->execute();

    // Bulk license key insert
    if (!empty($_POST['license_keys'])) {
        $blob = str_replace(["\r", ","], ["", "\n"], (string)$_POST['license_keys']);
        $lines = preg_split('/\n+/', $blob);

        $ins = $pdo->prepare('INSERT IGNORE INTO license_keys (product_id, license_key, is_sold) VALUES (?,?,0)');
        foreach ($lines as $key) {
            $key = trim($key);
            if ($key !== '') $ins->execute([$id, $key]);
        }
    }

    header('Location: /admin/products');
}


public function productDelete(array $params): void {
    Auth::require();
    if (empty($params['id'])) { header('Location: /admin/products'); return; }
    $pdo = Db::pdo();
    $id  = (int)$params['id'];

    // Mark as hidden & sold out (soft delete)
    $pdo->prepare("UPDATE products SET is_hidden=1, is_sold_out=1, updated_at=NOW() WHERE id=?")->execute([$id]);

    header('Location: /admin/products');
}


    /* ---------------- Sales / Deliveries ---------------- */

    // Paid orders + items; search and preparing tab
  // ---------------- Sales Record ----------------
public function sales(): void {
    Auth::require();
    $pdo = Db::pdo();

    $tab = ($_GET['tab'] ?? '') === 'preparing' ? 'preparing' : 'all';
    $search = trim($_GET['q'] ?? '');
    $from = trim($_GET['from'] ?? '');
    $to   = trim($_GET['to'] ?? '');

    $sql = "
        SELECT 
            o.id AS order_id,
            o.transaction_id,
            o.buyer_name,
            o.buyer_phone,
            o.buyer_telegram,
            o.total_amount,
            o.created_at,
            oi.id AS item_id,
            oi.delivery_status,
            oi.proof_image_base64,
            p.name AS product_name_current,
            lk.license_key
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN products p ON p.id = oi.product_id
        LEFT JOIN license_keys lk ON lk.id = oi.license_key_id
        WHERE o.status='paid'
    ";

    $params = [];

    // Search (TX ID, buyer, phone)
    if ($search !== '') {
        $sql .= " AND (o.transaction_id LIKE ? OR o.buyer_name LIKE ? OR o.buyer_phone LIKE ?)";
        $params = ["%$search%", "%$search%", "%$search%"];
    }

    // Date filter
    if ($from !== '') {
        $sql .= " AND DATE(o.created_at) >= ?";
        $params[] = $from;
    }
    if ($to !== '') {
        $sql .= " AND DATE(o.created_at) <= ?";
        $params[] = $to;
    }

    $sql .= " ORDER BY o.created_at DESC, oi.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Group by order_id
    $orders = [];
    foreach ($rows as $r) {
        $oid = $r['order_id'];
        if (!isset($orders[$oid])) {
            $orders[$oid] = [
                'id' => $oid,
                'transaction_id' => $r['transaction_id'],
                'buyer_name' => $r['buyer_name'],
                'buyer_phone' => $r['buyer_phone'],
                'buyer_telegram' => $r['buyer_telegram'],
                'total_amount' => $r['total_amount'],
                'created_at' => $r['created_at'],
                'items' => [],
            ];
        }
        // optional preparing filter
        if ($tab === 'preparing' && $r['delivery_status'] !== 'preparing') continue;
        $orders[$oid]['items'][] = $r;
    }

    // remove empty after preparing filter
    if ($tab === 'preparing') {
        $orders = array_filter($orders, fn($o) => !empty($o['items']));
    }

    Response::view('admin/sales.php', compact('orders', 'tab', 'search', 'from', 'to'));
}


    // ✅ new: view detail page
    public function saleDetail(array $params): void {
        Auth::require();
        $id = (int)($params['id'] ?? 0);
        $pdo = Db::pdo();

        $o = $pdo->prepare("SELECT * FROM orders WHERE id=?");
        $o->execute([$id]);
        $order = $o->fetch();

        if (!$order) {
            http_response_code(404);
            echo "<h1 style='color:white;text-align:center;margin-top:100px;'>Order not found</h1>";
            return;
        }

        $items = $pdo->prepare("
            SELECT oi.*, p.name AS product_name, lk.license_key
            FROM order_items oi
            LEFT JOIN products p ON p.id=oi.product_id
            LEFT JOIN license_keys lk ON lk.id=oi.license_key_id
            WHERE oi.order_id=?
            ORDER BY oi.id ASC
        ");
        $items->execute([$id]);
        $items = $items->fetchAll();

        Response::view('admin/sale_detail.php', compact('order','items'));
    }


    // Change delivery status (per order_item)
    public function updateItemStatus(array $params): void {
        Auth::require();
        $id = (int)($params['id'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['preparing','completed'], true) ? $_POST['status'] : 'preparing';

        $pdo = Db::pdo();
        $pdo->prepare('UPDATE order_items SET delivery_status=?, updated_at=NOW() WHERE id=?')->execute([$status, $id]);

        header('Location: /admin/sales?tab=' . ($status === 'preparing' ? 'preparing' : 'all'));
    }

 public function orders(): void {
    Auth::require();
    $pdo = Db::pdo();

    $search = trim($_GET['q'] ?? '');
    $filter = trim($_GET['status'] ?? '');

    $sql = "
        SELECT 
            o.id AS order_id,
            o.transaction_id,
            o.buyer_name,
            o.buyer_phone,
            o.total_amount,
            o.created_at,
            oi.id AS item_id,
            oi.delivery_status,
            p.name AS product_name,
            lk.license_key,
            oi.proof_image_base64
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN products p ON p.id = oi.product_id
        LEFT JOIN license_keys lk ON lk.id = oi.license_key_id
        WHERE o.status = 'paid'
    ";

    $params = [];

    if ($search !== '') {
        $sql .= " AND (o.buyer_name LIKE ? OR o.buyer_phone LIKE ?)";
        $params = ["%$search%", "%$search%"];
    }

    if ($filter !== '') {
        $sql .= " AND oi.delivery_status = ?";
        $params[] = $filter;
    }

    $sql .= " ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    Response::view('admin/orders.php', compact('orders', 'search', 'filter'));
}


    // Upload proof image (compressed) for an order_item
 public function uploadItemProof(array $params): void {
    Auth::require();
    $id = (int)($params['id'] ?? 0);

    // If no file -> JSON error for modal / regular fallback
    if (empty($_FILES['proof']['tmp_name'])) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => 0, 'error' => 'No file uploaded']);
            return;
        }
        header('Location: /admin/sales');
        return;
    }

    try {
        // ✅ Use your ImageUtil exactly
        $base64 = \App\Helpers\ImageUtil::compressToBase64(
            $_FILES['proof'],
            45 * 1024,  // target ~45KB (fits safely in TEXT column)
            1024,
            82
        );


        $pdo = Db::pdo();
        $pdo->prepare('UPDATE order_items SET proof_image_base64=?, updated_at=NOW() WHERE id=?')
            ->execute([$base64, $id]);

        // If the request is AJAX (modal), return JSON so frontend updates preview
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => 1, 'base64' => $base64, 'item_id' => $id]);
            return;
        }

    } catch (\Throwable $e) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => 0, 'error' => $e->getMessage()]);
            return;
        }
    }

    header('Location: /admin/sales');
}


}
