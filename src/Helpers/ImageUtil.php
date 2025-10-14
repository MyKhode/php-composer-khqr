<?php
namespace App\Helpers;


class ImageUtil
{
    /**
     * Compress uploaded image and convert to JPEG base64 under a target size.
     * - Target size: ~100KB (configurable via $maxBytes)
     * - Starts at width 1024px and quality 82, then lowers quality and width until under target
     * - Flattens transparency onto white background
     */
    public static function compressToBase64(array $file, int $maxBytes = 100 * 1024, int $startWidth = 1024, int $startQuality = 82): string
    {
        $srcPath = $file['tmp_name'] ?? null;
        if (!$srcPath || !file_exists($srcPath)) {
            throw new \RuntimeException('No uploaded file');
        }

        [$w, $h, $type] = getimagesize($srcPath);
        if (!$w || !$h) throw new \RuntimeException('Invalid image');

        switch ($type) {
            case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($srcPath); break;
            case IMAGETYPE_PNG:  $src = imagecreatefrompng($srcPath); break;
            case IMAGETYPE_WEBP: $src = imagecreatefromwebp($srcPath); break;
            default: throw new \RuntimeException('Unsupported image type');
        }
        if (!$src) throw new \RuntimeException('Failed to read image');

        $targetWidth = min($startWidth, $w);
        $minWidth = 320;         // don’t go below this
        $quality = $startQuality; // JPEG quality 0..100
        $minQuality = 45;

        $bestData = null; // keep last attempt even if > target

        for ($i = 0; $i < 12; $i++) {
            $ratio = $w > 0 ? ($targetWidth / $w) : 1;
            if ($ratio > 1) $ratio = 1; // never upscale
            $nw = max(1, (int)round($w * $ratio));
            $nh = max(1, (int)round($h * $ratio));

            $dst = imagecreatetruecolor($nw, $nh);
            // flatten onto white background for PNG/WebP transparency
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

            ob_start();
            imagejpeg($dst, null, $quality);
            $data = ob_get_clean();
            imagedestroy($dst);

            if ($data !== false) {
                $bestData = $data;
                if (strlen($data) <= $maxBytes) {
                    imagedestroy($src);
                    return 'data:image/jpeg;base64,' . base64_encode($data);
                }
            }

            // adjust knobs: decrease quality, then decrease width
            if ($quality > $minQuality) {
                $quality = max($minQuality, $quality - 7);
            } elseif ($targetWidth > $minWidth) {
                $targetWidth = (int)max($minWidth, floor($targetWidth * 0.85));
                $quality = min($startQuality, $quality + 4); // bump quality a bit after resizing
            } else {
                break; // can't reduce more
            }
        }

        // return best effort if target could not be reached
        imagedestroy($src);
        if ($bestData === null) throw new \RuntimeException('Failed to compress image');
        return 'data:image/jpeg;base64,' . base64_encode($bestData);
    }
}
