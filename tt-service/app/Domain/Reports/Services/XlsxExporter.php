<?php

declare(strict_types=1);

namespace App\Domain\Reports\Services;

use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * XLSX-экспортёр на базе openspout (потоковая запись, экономия памяти).
 *
 * Тот же контракт, что и CsvExporter:
 *   - exportToFile() сохраняет файл и возвращает путь.
 *
 * Потоковая модель openspout подходит для тяжёлых выгрузок из очереди
 * (Reports/Jobs/GenerateReportJob), не держит весь набор в памяти.
 */
class XlsxExporter
{
    /**
     * Записать строки в XLSX-файл и вернуть путь.
     *
     * @param Collection|array $rows    Строки данных (массивы или объекты)
     * @param array|null       $headers Заголовки; если null — берём ключи первой строки
     */
    public function exportToFile(
        Collection|array $rows,
        string           $directory,
        string           $filename,
        ?array           $headers = null,
    ): string {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if ($rows instanceof Collection) {
            $rows = $rows->all();
        }

        $path = rtrim(str_replace('\\', '/', $directory), '/') . '/' . $filename;

        $writer = new Writer();
        $writer->openToFile($path);

        $rowsArr = array_values($rows);

        if (! empty($rowsArr)) {
            $firstRow = (array) $rowsArr[0];
            $headers ??= array_keys($firstRow);

            // Заголовок — жирным
            $headerStyle = (new Style())->withFontBold(true);
            $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

            foreach ($rowsArr as $row) {
                $values = array_map(
                    static fn ($v) => $v === null ? '' : $v,
                    array_values((array) $row),
                );
                $writer->addRow(Row::fromValues($values));
            }
        }

        $writer->close();

        return $path;
    }
}
