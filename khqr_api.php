<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use GuzzleHttp\Client;
use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function logMessage($msg) {
    error_log("[" . date('Y-m-d H:i:s') . "] $msg\n", 3, __DIR__ . '/payment.log');
}

if ($uri === '/generate-khqr' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $amount = $input['amount'] ?? 0;
    $transactionId = $input['transactionId'] ?? uniqid('TX');

    try {
        $info = new IndividualInfo(
            bakongAccountID: $_ENV['BAKONG_BANK_ACCOUNT'],
            merchantName: $_ENV['BAKONG_MERCHANT_NAME'],
            merchantCity: $_ENV['BAKONG_MERCHANT_CITY'],
            currency: KHQRData::CURRENCY_USD,
            amount: floatval($amount),
            billNumber: $transactionId,
            purposeOfTransaction: 'License Payment'
        );

        $res = BakongKHQR::generateIndividual($info);
        if ($res->status['code'] === 0 && isset($res->data['qr'])) {
            $qr = new QrCode($res->data['qr']);
            $writer = new PngWriter();
            $png = $writer->write($qr);
            echo json_encode([
                'qrCodeData' => 'data:image/png;base64,' . base64_encode($png->getString()),
                'md5' => $res->data['md5'],
                'transactionId' => $transactionId
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to generate QR']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($uri === '/check-payment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $md5 = $input['md5'] ?? '';
    if (!$md5) { echo json_encode(['error' => 'Missing md5']); exit; }

    $client = new Client();
    try {
        $r = $client->post('https://api-bakong.nbc.gov.kh/v1/check_transaction_by_md5', [
            'headers' => [
                'Authorization' => 'Bearer ' . $_ENV['BAKONG_DEV_TOKEN'],
                'Content-Type' => 'application/json'
            ],
            'json' => ['md5' => $md5]
        ]);
        $body = json_decode($r->getBody(), true);
        if ($body['responseCode'] === 0 && isset($body['data']['hash'])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'pending']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($uri === '/' || $uri === '/index.html') {
    readfile(__DIR__ . '/index.html');
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not Found']);
