<?php
namespace App\Controllers;

use App\Db;
use App\Helpers\Response;

class StoreController
{
    public function home(): void
    {
        $pdo = Db::pdo();

        $category = trim($_GET['category'] ?? '');
        $search   = trim($_GET['q'] ?? '');
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = max(1, min(30, (int)($_GET['per_page'] ?? 9))); // 9 fits 3x3 grid nicely

        // Fetch all categories for dropdown
        $categories = $pdo->query("
            SELECT DISTINCT category FROM products
            WHERE category IS NOT NULL AND category <> ''
            ORDER BY category
        ")->fetchAll(\PDO::FETCH_COLUMN);

        // Build filters for visible, in-stock products
        $where = "WHERE COALESCE(is_hidden,0)=0 AND COALESCE(is_sold_out,0)=0";
        $params = [];
        if ($category !== '') {
            $where .= " AND category = ?";
            $params[] = $category;
        }
        if ($search !== '') {
            $where .= " AND (name LIKE ? OR category LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        // Count distinct product groups for pagination
        $countSql = "SELECT COUNT(*) AS cnt FROM (SELECT 1 FROM products $where GROUP BY name, category, photo_base64) t";
        $c = $pdo->prepare($countSql);
        $c->execute($params);
        $total = (int)($c->fetch()['cnt'] ?? 0);
        $pages = max(1, (int)ceil($total / $perPage));
        if ($page > $pages) { $page = $pages; }
        $offset = ($page - 1) * $perPage;

        // Fetch a page of grouped products
        $sql = "
            SELECT 
                MIN(id) AS id,
                name,
                category,
                MIN(price) AS price,
                MAX(price) AS max_price,
                photo_base64,
                COUNT(*) AS count_same_name,
                MAX(created_at) AS latest_created
            FROM products
            $where
            GROUP BY name, category, photo_base64
            ORDER BY latest_created DESC
            LIMIT $perPage OFFSET $offset
        ";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $products = $st->fetchAll();

        // Recompute categories to include only those with visible (not hidden, not sold-out) products
        $categories = $pdo->query("
            SELECT DISTINCT category FROM products
            WHERE category IS NOT NULL AND category <> ''
              AND COALESCE(is_hidden,0)=0
              AND COALESCE(is_sold_out,0)=0
            ORDER BY category
        ")->fetchAll(\PDO::FETCH_COLUMN);

        Response::view('store/home.php', compact('products', 'categories', 'category', 'search', 'page', 'perPage', 'pages', 'total'));
    }


    public function product(array $params): void
    {
        $pdo = Db::pdo();

        $stmt = $pdo->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
        $stmt->execute([$params['id']]);
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(404);
            echo "<h1 style='text-align:center;color:white;margin-top:100px;'>Product not found</h1>";
            return;
        }

        if (!empty($product['is_sold_out']) || !empty($product['is_hidden'])) {
            http_response_code(410);
            echo "<h1 style='text-align:center;color:white;margin-top:100px;'>This product is no longer available.</h1>";
            return;
        }

        Response::view('store/product.php', compact('product'));
    }
}
