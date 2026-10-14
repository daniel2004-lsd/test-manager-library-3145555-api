<?php

namespace App\Infrastructure\Storage;

use App\Application\Ports\CoverStorage;
use App\Domain\Exceptions\ValidationException;

final class DiskCoverStorage implements CoverStorage
{
    private const int MAX_BYTES = 2 * 1024 * 1024;

    public function __construct(private readonly string $directory) {}

    public function store(string $tmpPath, int $sizeBytes): string
    {
        if ($sizeBytes > self::MAX_BYTES) {
            throw new ValidationException('file', 'La portada no puede superar 2 MB.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo === false ? '' : (string) finfo_file($finfo, $tmpPath);

        if ($finfo !== false) {
            finfo_close($finfo);
        }

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => throw new ValidationException('file', 'Formato de portada no soportado: use JPEG o PNG.'),
        };

        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }

        $key = bin2hex(random_bytes(16)).'.'.$extension;
        $destination = $this->directory.'/'.$key;

        if (! @rename($tmpPath, $destination)) {
            copy($tmpPath, $destination);
        }

        return $key;
    }

    public function delete(?string $key): void
    {
        if ($key === null || ! $this->isValidKey($key)) {
            return;
        }

        $path = $this->directory.'/'.$key;

        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function read(string $key): ?array
    {
        if (! $this->isValidKey($key)) {
            return null;
        }

        $path = $this->directory.'/'.$key;

        if (! is_file($path)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo === false ? '' : (string) finfo_file($finfo, $path);

        if ($finfo !== false) {
            finfo_close($finfo);
        }

        return ['path' => $path, 'mime' => $mime];
    }

    private function isValidKey(string $key): bool
    {
        return preg_match('/^[a-f0-9]{32}\.(jpg|png)$/', $key) === 1;
    }
}
