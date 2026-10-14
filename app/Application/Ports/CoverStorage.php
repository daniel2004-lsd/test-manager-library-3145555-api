<?php

namespace App\Application\Ports;

interface CoverStorage
{
    /** Valida el contenido (jpeg/png) y devuelve la clave opaca. */
    public function store(string $tmpPath, int $sizeBytes): string;

    public function delete(?string $key): void;

    /** @return array{path: string, mime: string}|null */
    public function read(string $key): ?array;
}
