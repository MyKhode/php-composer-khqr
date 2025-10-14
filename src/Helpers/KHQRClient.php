<?php
namespace App\Helpers;


use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use GuzzleHttp\Client;


class KHQRClient
{
public static function generate(string $transactionId, float $amount): array
{
$info = new IndividualInfo(
bakongAccountID: getenv('BAKONG_BANK_ACCOUNT'),
merchantName: getenv('BAKONG_MERCHANT_NAME'),
merchantCity: getenv('BAKONG_MERCHANT_CITY'),
currency: KHQRData::CURRENCY_USD,
amount: $amount,
billNumber: $transactionId,
purposeOfTransaction: 'License Payment'
);
$res = BakongKHQR::generateIndividual($info);
if ($res->status['code'] !== 0 || empty($res->data['qr'])) {
throw new \RuntimeException('Failed to generate KHQR');
}
$qr = new QrCode($res->data['qr']);
$writer = new PngWriter();
$png = $writer->write($qr)->getString();
return [
'qr_png_data_uri' => 'data:image/png;base64,' . base64_encode($png),
'md5' => $res->data['md5'] ?? null
];
}


public static function check(string $md5): bool
{
$client = new Client();
$r = $client->post('https://api-bakong.nbc.gov.kh/v1/check_transaction_by_md5', [
'headers' => [
'Authorization' => 'Bearer ' . getenv('BAKONG_DEV_TOKEN'),
'Content-Type' => 'application/json'
],
'json' => ['md5' => $md5]
]);
$body = json_decode($r->getBody(), true);
return ($body['responseCode'] ?? 1) === 0 && !empty($body['data']['hash']);
}
}