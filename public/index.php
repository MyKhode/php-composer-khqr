<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Router;
use App\Controllers\StoreController;
use App\Controllers\CheckoutController;
use App\Controllers\AdminController;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

session_start();

$router = new Router();

// Storefront
$router->get('/', [StoreController::class, 'home']);
$router->get('/product/(?P<id>\d+)', [StoreController::class, 'product']);

// Checkout + KHQR
$router->post('/checkout', [CheckoutController::class, 'createOrder']);
$router->post('/khqr/generate', [CheckoutController::class, 'generateKHQR']);
$router->post('/khqr/check', [CheckoutController::class, 'checkPayment']);
$router->post('/order/keys', [CheckoutController::class, 'getKeys']); // keep

// Admin auth
$router->get('/admin/login', [AdminController::class, 'loginForm']);
$router->post('/admin/login', [AdminController::class, 'login']);
$router->get('/admin/logout', [AdminController::class, 'logout']);

// Admin pages
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/products', [AdminController::class, 'products']);
$router->get('/admin/products/new', [AdminController::class, 'productForm']);
$router->get('/admin/products/(?P<id>\d+)/edit', [AdminController::class, 'productForm']);
$router->post('/admin/products/save', [AdminController::class, 'productSave']);

// product delete -> soft delete
$router->post('/admin/products/(?P<id>\d+)/delete', [AdminController::class, 'productDelete']);

// Sales / Deliveries
$router->get('/admin/sales', [AdminController::class, 'sales']);
// Admin: sales detail + per-item actions
$router->get('/admin/sales/detail/(?P<id>\d+)', [AdminController::class, 'saleDetail']);
$router->post('/admin/order-items/(?P<id>\d+)/status', [AdminController::class, 'updateItemStatus']);
$router->post('/admin/order-items/(?P<id>\d+)/proof', [AdminController::class, 'uploadItemProof']);

// Admin orders (search & filter)
$router->get('/admin/orders', [AdminController::class, 'orders']);


$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
