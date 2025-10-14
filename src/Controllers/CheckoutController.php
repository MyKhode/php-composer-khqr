<?php
namespace App\Controllers;

use App\Db;
use App\Helpers\Response;
use App\Helpers\KHQRClient;

class CheckoutController
{
    public function createOrder(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $productId = (int)($input['product_id'] ?? 0);
        $buyer = [
            'buyer_name'     => trim($input['buyer_name'] ?? 'Guest'),
            'buyer_phone'    => $input['buyer_phone'] ?? null,
            'buyer_telegram' => $input['buyer_telegram'] ?? null,
            'buyer_address'  => $input['buyer_address'] ?? null,
            'buyer_message'  => $input['buyer_message'] ?? null,
        ];

        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $p = $pdo->prepare('SELECT id, price FROM products WHERE id=?');
            $p->execute([$productId]);
            $product = $p->fetch();
            if (!$product) {
                throw new \RuntimeException('Invalid product');
            }

            $tx = 'TX' . time() . rand(1000, 9999);
            $stmt = $pdo->prepare('INSERT INTO orders (transaction_id, buyer_name, buyer_phone, buyer_telegram, buyer_address, buyer_message, total_amount, status, created_at) VALUES (?,?,?,?,?,?,?,"pending",NOW())');
            $stmt->execute([
                $tx,
                $buyer['buyer_name'],
                $buyer['buyer_phone'],
                $buyer['buyer_telegram'],
                $buyer['buyer_address'],
                $buyer['buyer_message'],
                $product['price']
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $pdo->prepare('INSERT INTO order_items (order_id, product_id, price) VALUES (?,?,?)')
                ->execute([$orderId, $productId, $product['price']]);

            // KHQR generate
            $khqr = KHQRClient::generate($tx, (float)$product['price']);
            $pdo->prepare('UPDATE orders SET khqr_md5=? WHERE id=?')->execute([$khqr['md5'], $orderId]);
            $pdo->commit();

            Response::json([
                'order_id'       => $orderId,
                'transaction_id' => $tx,
                'qrCodeData'     => $khqr['qr_png_data_uri'],
                'md5'            => $khqr['md5']
            ]);
            return;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Response::json(['error' => $e->getMessage()], 400);
            return;
        }
    }

    public function generateKHQR(): void
    {
        $input  = json_decode(file_get_contents('php://input'), true) ?: [];
        $tx     = $input['transactionId'] ?? null;
        $amount = (float)($input['amount'] ?? 0);

        if (!$tx || $amount <= 0) {
            Response::json(['error' => 'Invalid input'], 400);
            return;
        }

        try {
            $khqr = KHQRClient::generate($tx, $amount);
            Response::json([
                'qrCodeData'     => $khqr['qr_png_data_uri'],
                'md5'            => $khqr['md5'],
                'transactionId'  => $tx
            ]);
            return;
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 400);
            return;
        }
    }

    public function checkPayment(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $md5 = $input['md5'] ?? null;
        $transactionId = $input['transactionId'] ?? null;

        if (!$md5 || !$transactionId) {
            Response::json(['error' => 'Missing md5/transactionId'], 400);
            return;
        }

        $paid = false;
        try {
            $paid = KHQRClient::check($md5);
        } catch (\Throwable $e) {
            // treat as pending
        }

        if (!$paid) {
            Response::json(['status' => 'pending']);
            return;
        }

        $pdo = Db::pdo();
        $pdo->beginTransaction();
        $keysOut = [];

        try {
            // Lock order row
            $orderStmt = $pdo->prepare('SELECT id FROM orders WHERE transaction_id=? FOR UPDATE');
            $orderStmt->execute([$transactionId]);
            $order = $orderStmt->fetch();

            if ($order) {
                // Fetch order items
                $items = $pdo->prepare('SELECT id, product_id, price FROM order_items WHERE order_id=?');
                $items->execute([$order['id']]);

                foreach ($items as $it) {
                    // Allocate an available key for the product
                    $lk = $pdo->prepare('SELECT id, license_key FROM license_keys WHERE product_id=? AND is_sold=0 LIMIT 1 FOR UPDATE');
                    $lk->execute([$it['product_id']]);
                    $key = $lk->fetch();

                    // Get product info for UX and delivery logic
                    $pstmt = $pdo->prepare('SELECT name, delivery_type FROM products WHERE id=?');
                    $pstmt->execute([$it['product_id']]);
                    $p = $pstmt->fetch();
                    $productName = $p['name'] ?? ('Product #' . (int)$it['product_id']);
                    $deliveryType = strtolower(trim($p['delivery_type'] ?? ''));

                    if ($key) {
                        // mark sold + attach
                        $pdo->prepare('UPDATE license_keys SET is_sold=1, sold_at=NOW(), order_id=? WHERE id=?')
                            ->execute([$order['id'], $key['id']]);
                        $pdo->prepare('UPDATE order_items SET license_key_id=? WHERE id=?')
                            ->execute([$key['id'], $it['id']]);

                        // Auto-complete delivery for instant items
                        if ($deliveryType === 'instant') {
                            $pdo->prepare('UPDATE order_items SET delivery_status=\'completed\', updated_at=NOW() WHERE id=?')
                                ->execute([$it['id']]);
                        }

                        $keysOut[] = [
                            'product' => $productName,
                            'license_key' => $key['license_key'],
                        ];
                    } else {
                        // If no key available, keep delivery as preparing regardless of delivery type
                        $keysOut[] = [
                            'product' => $productName,
                            'license_key' => null,
                            'error' => 'No key available; support will send your key shortly.',
                        ];
                    }
                }

                // mark order paid
                // mark order paid
                $pdo->prepare('UPDATE orders SET status="paid", updated_at=NOW() WHERE id=?')->execute([$order['id']]);

                // ✅ hide & mark product as sold-out once purchased
                $pdo->prepare("
                    UPDATE products
                    SET is_hidden=1, is_sold_out=1, updated_at=NOW()
                    WHERE id IN (SELECT product_id FROM order_items WHERE order_id=?)
                ")->execute([$order['id']]);

            }

            $pdo->commit();

            // Return keys for the frontend modal
            Response::json([
                'status' => 'success',
                'transactionId' => $transactionId,
                'keys' => $keysOut,                         // array of { product, license_key, error? }
                'downloadFileName' => 'license-' . $transactionId . '.txt'
            ]);
            return;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            // Payment confirmed, but allocation failed
            Response::json([
                'status' => 'success',
                'transactionId' => $transactionId,
                'keys' => [],
                'error' => 'Payment confirmed but key allocation failed. Please contact support.',
                'downloadFileName' => 'license-' . $transactionId . '.txt'
            ]);
            return;
        }
    }

    public function getKeys(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $transactionId = $input['transactionId'] ?? null;
        if (!$transactionId) {
            Response::json(['error' => 'Missing transactionId'], 400);
            return;
        }

        $pdo = Db::pdo();

        // Ensure order is paid
        $o = $pdo->prepare("SELECT id, status FROM orders WHERE transaction_id=?");
        $o->execute([$transactionId]);
        $order = $o->fetch();
        if (!$order) {
            Response::json(['error' => 'Order not found'], 404);
            return;
        }
        if ($order['status'] !== 'paid') {
            Response::json(['error' => 'Order not paid yet'], 409);
            return;
        }

        // Collect keys + product names
        $stmt = $pdo->prepare("
            SELECT p.name AS product, lk.license_key
            FROM order_items oi
            LEFT JOIN license_keys lk ON lk.id = oi.license_key_id
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC
        ");
        $stmt->execute([$order['id']]);
        $rows = $stmt->fetchAll();

        $keysOut = [];
        foreach ($rows as $i => $r) {
            $keysOut[] = [
                'product' => $r['product'] ?: ('Item ' . ($i + 1)),
                'license_key' => $r['license_key'] ?: null,
            ];
        }

        Response::json([
            'status' => 'success',
            'transactionId' => $transactionId,
            'keys' => $keysOut,
            'downloadFileName' => 'license-' . $transactionId . '.txt'
        ]);
        return;
    }
}
