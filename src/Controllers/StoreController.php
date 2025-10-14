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

        // Fetch all categories for dropdown
        $categories = $pdo->query("
            SELECT DISTINCT category FROM products
            WHERE category IS NOT NULL AND category <> ''
            ORDER BY category
        ")->fetchAll(\PDO::FETCH_COLUMN);

        // Group products by name to show only one card per group
        $sql = "
            SELECT 
                MIN(id) AS id,
                name,
                category,
                MIN(price) AS price,
                MAX(price) AS max_price,
                photo_base64,
                COUNT(*) AS count_same_name
            FROM products
            WHERE COALESCE(is_hidden,0)=0
            AND COALESCE(is_sold_out,0)=0
        ";

        $params = [];

        if ($category !== '') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if ($search !== '') {
            $sql .= " AND (name LIKE ? OR category LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " GROUP BY name, category, photo_base64
                ORDER BY MAX(created_at) DESC";

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

        Response::view('store/home.php', compact('products', 'categories', 'category', 'search'));
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
