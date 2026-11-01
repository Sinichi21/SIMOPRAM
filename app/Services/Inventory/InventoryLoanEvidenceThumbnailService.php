<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InventoryLoanEvidenceThumbnailService
{
    /**
     * Build small embedded images from verified private originals.
     * Keys are evidence IDs; missing GD support leaves a hash-only appendix.
     *
     * @return array<int, string>
     */
    public function forEvents(Collection $events): array
    {
        $result = [];
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagecreatetruecolor')) {
            return $result;
        }

        $disk = Storage::disk('local');
        foreach ($events as $event) {
            foreach ($event->evidence as $photo) {
                if ($photo->disk !== 'local' || ! $disk->exists($photo->path)) {
                    throw ValidationException::withMessages(['document' => 'Bukti foto transaksi tidak tersedia.']);
                }
                $bytes = $disk->get($photo->path);
                if (! is_string($bytes) || ! hash_equals((string) $photo->sha256, hash('sha256', $bytes))) {
                    throw ValidationException::withMessages(['document' => 'Integritas bukti foto transaksi tidak sesuai.']);
                }
                if (strlen($bytes) > 5 * 1024 * 1024) {
                    throw ValidationException::withMessages(['document' => 'Ukuran bukti foto melebihi batas.']);
                }
                $dimensions = @getimagesizefromstring($bytes);
                if ($dimensions === false || $dimensions[0] < 1 || $dimensions[1] < 1
                    || $dimensions[0] * $dimensions[1] > 20_000_000) {
                    throw ValidationException::withMessages(['document' => 'Dimensi bukti foto tidak valid.']);
                }
                $source = @imagecreatefromstring($bytes);
                if ($source === false) {
                    throw ValidationException::withMessages(['document' => 'Bukti foto tidak dapat diproses.']);
                }
                try {
                    $ratio = min(1, 360 / $dimensions[0], 240 / $dimensions[1]);
                    $width = max(1, (int) round($dimensions[0] * $ratio));
                    $height = max(1, (int) round($dimensions[1] * $ratio));
                    $thumbnail = imagecreatetruecolor($width, $height);
                    try {
                        imagefill($thumbnail, 0, 0, imagecolorallocate($thumbnail, 255, 255, 255));
                        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $dimensions[0], $dimensions[1]);
                        ob_start();
                        imagejpeg($thumbnail, null, 65);
                        $jpeg = ob_get_clean();
                        if (! is_string($jpeg) || $jpeg === '') {
                            throw ValidationException::withMessages(['document' => 'Thumbnail bukti foto gagal dibuat.']);
                        }
                        $result[(int) $photo->id] = 'data:image/jpeg;base64,'.base64_encode($jpeg);
                    } finally {
                        imagedestroy($thumbnail);
                    }
                } finally {
                    imagedestroy($source);
                }
            }
        }

        return $result;
    }
}
