<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class HexagonalTest extends TestCase
{
    private const array FORBIDDEN_IN_INNER = [
        '/^use\s+Illuminate\\/',
        '/^use\s+App\\(Application|Infrastructure|Http|Providers)\\/',
        '/[^\w\$>\\\\]app\s*\(/',
    ];

    public function test_el_dominio_no_depende_del_framework_ni_de_las_capas_de_afuera(): void
    {
        $this->assertFilesMatch(base_path('app/Domain'), self::FORBIDDEN_IN_INNER);
    }

    public function test_la_aplicacion_no_depende_del_framework_ni_de_infraestructura_o_http(): void
    {
        $this->assertFilesMatch(base_path('app/Application'), self::FORBIDDEN_IN_INNER);
    }

    public function test_infraestructura_no_se_importa_a_si_misma_desde_el_dominio(): void
    {
        $files = $this->phpFiles(base_path('app/Infrastructure'));
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/^use\s+App\\Http\\/', $content, $file);
        }
    }

    /** @param list<string> $patterns */
    private function assertFilesMatch(string $directory, array $patterns): void
    {
        $files = $this->phpFiles($directory);
        $this->assertNotEmpty($files, "Sin archivos en {$directory}");

        foreach ($files as $file) {
            $content = (string) file_get_contents($file);

            foreach ($patterns as $pattern) {
                $this->assertDoesNotMatchRegularExpression($pattern, $content, "Violación de arquitectura en {$file}");
            }
        }
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        $files = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
