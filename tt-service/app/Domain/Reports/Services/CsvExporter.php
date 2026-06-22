<?php

namespace App\Domain\Reports\Services;

use Illuminate\Support\Collection;

/**
 * Простой CSV-экспортёр.
 * Возвращает строку CSV — вызывающий сам решает куда её сохранить.
 */
class CsvExporter
{
    /**
     * Преобразовать коллекцию строк в CSV-строку.
     *
     * @param Collection|array $rows     Строки данных (массивы или объекты)
     * @param array|null       $headers  Заголовки колонок; если null — берём из ключей первой строки
     */
    public function export(Collection|array $rows, ?array $headers = null): string
    {
        if ($rows instanceof Collection) {
            $rows = $rows->toArray();
        }

        if (empty($rows)) {
            return '';
        }

        $firstRow = (array) $rows[0];
        $headers ??= array_keys($firstRow);

        $buffer = fopen('php://memory', 'r+');

        // UTF-8 BOM для корректного открытия в Excel
        fwrite($buffer, "\xEF\xBB\xBF");

        fputcsv($buffer, $headers, ';');

        foreach ($rows as $row) {
            fputcsv($buffer, array_values((array) $row), ';');
        }

        rewind($buffer);
        $content = stream_get_contents($buffer);
        fclose($buffer);

        return $content;
    }

    /**
     * Сохранить CSV в файл и вернуть путь.
     */
    public function exportToFile(
        Collection|array $rows,
        string           $directory,
        string           $filename,
        ?array           $headers = null,
    ): string {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = rtrim($directory, '/') . '/' . $filename;
        file_put_contents($path, $this->export($rows, $headers));

        return $path;
    }
}
