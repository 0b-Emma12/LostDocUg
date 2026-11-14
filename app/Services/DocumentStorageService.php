<?php

namespace App\Services;

use App\Enums\DocumentKind;
use App\Enums\ImageScanStatus;
use App\Models\DocumentImage;
use App\Models\Report;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;
use RuntimeException;
use Throwable;

class DocumentStorageService
{
    private const array ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    private const int MAX_SIZE_BYTES = 5 * 1024 * 1024;

    private const int MAX_DIMENSION = 8000;

    private const int MAX_OUTPUT_DIMENSION = 1920;

    private const int WEBP_QUALITY = 80;

    private const int MAX_IMAGES_PER_REPORT = 3;

    public function storeImage(Report $report, UploadedFile $file): DocumentImage
    {
        if ($report->document_type === DocumentKind::BankCard) {
            throw new RuntimeException('Images are not accepted for bank-card reports.');
        }

        $this->validateFileIntegrity($file);
        $contentHash = hash_file('sha256', $file->getRealPath());

        if (! is_string($contentHash)) {
            throw new RuntimeException('Unable to verify uploaded image.');
        }

        $processed = $this->processImage($file);
        $disk = Storage::disk(config('filesystems.private_documents_disk'));
        $storageKey = sprintf('reports/%s/%s.webp', $report->getKey(), Str::random(40));

        return DB::transaction(function () use ($report, $contentHash, $disk, $storageKey, $processed): DocumentImage {
            $lockedReport = Report::query()->lockForUpdate()->findOrFail($report->getKey());

            if ($lockedReport->images()->count() >= self::MAX_IMAGES_PER_REPORT) {
                throw new RuntimeException('Maximum number of images reached for this report.');
            }

            if ($lockedReport->images()->where('content_hash', $contentHash)->exists()) {
                throw new RuntimeException('This image has already been uploaded to this report.');
            }

            try {
                $disk->put($storageKey, $processed['data'], ['visibility' => 'private']);

                return $lockedReport->images()->create([
                    'storage_key' => $storageKey,
                    'content_hash' => $contentHash,
                    'mime_type' => 'image/webp',
                    'size_bytes' => strlen($processed['data']),
                    'width' => $processed['width'],
                    'height' => $processed['height'],
                    'scan_status' => ImageScanStatus::Pending,
                    'retention_deadline' => now()->addDays(90),
                ]);
            } catch (Throwable $exception) {
                $disk->delete($storageKey);
                throw $exception;
            }
        });
    }

    /** @return array{mime: string, width: int, height: int} */
    private function validateFileIntegrity(UploadedFile $file): array
    {
        if (! $file->isValid() || $file->getSize() > self::MAX_SIZE_BYTES) {
            throw new RuntimeException('Image upload is invalid or exceeds 5MB.');
        }

        $path = $file->getRealPath();
        $mime = $file->getMimeType();
        $imageSize = @getimagesize($path);

        if (! in_array($mime, self::ALLOWED_MIMES, true) || $imageSize === false || $imageSize['mime'] !== $mime) {
            throw new RuntimeException('Only valid JPEG, PNG, and WebP images are accepted.');
        }

        [$width, $height] = $imageSize;
        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            throw new RuntimeException('Image dimensions exceed the allowed limit.');
        }

        return ['mime' => $mime, 'width' => $width, 'height' => $height];
    }

    /** @return array{data: string, width: int, height: int} */
    private function processImage(UploadedFile $file): array
    {
        try {
            $image = Image::decodePath($file->getRealPath());
            $image->scaleDown(self::MAX_OUTPUT_DIMENSION, self::MAX_OUTPUT_DIMENSION);
            $encoded = (string) $image->encode(new WebpEncoder(self::WEBP_QUALITY, true));

            return [
                'data' => $encoded,
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        } catch (Throwable $exception) {
            throw new RuntimeException('Image processing is unavailable or the image could not be decoded.', previous: $exception);
        }
    }

    public function deleteImage(DocumentImage $image): void
    {
        $image->loadMissing('report');
        $this->authorizeImageReport($image->report);
        $disk = Storage::disk(config('filesystems.private_documents_disk'));
        $disk->delete($image->storage_key);
        $image->delete();
    }

    /** @param list<string> $storageKeys */
    public function deleteStoredObjects(array $storageKeys): void
    {
        $disk = Storage::disk(config('filesystems.private_documents_disk'));

        foreach ($storageKeys as $storageKey) {
            $disk->delete($storageKey);
        }
    }

    private function authorizeImageReport(Report $report): void
    {
        if (! auth()->user()?->can('update', $report)) {
            abort(404);
        }
    }

    /** @return array{mime: string, width: int, height: int} */
    public function inspect(UploadedFile $file): array
    {
        return $this->validateFileIntegrity($file);
    }
}
