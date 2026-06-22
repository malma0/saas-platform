<?php

namespace App\Domain\Reports\Jobs;

use App\Domain\Reports\DTO\ReportParams;
use App\Domain\Reports\Models\ReportExport;
use App\Domain\Reports\Services\BookingReportService;
use App\Domain\Reports\Services\CsvExporter;
use App\Domain\Reports\Services\OccupancyReportService;
use App\Domain\Reports\Services\RevenueReportService;
use App\Domain\Reports\Services\XlsxExporter;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Job для генерации тяжёлых отчётов в фоне.
 *
 * Диспатчится из контроллера/action, статус обновляется в ReportExport.
 * Когда готово — файл доступен по report_export.file_path.
 */
class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300; // 5 минут

    public function __construct(
        public readonly int $reportExportId,
    ) {}

    public function handle(
        BookingReportService   $bookingReport,
        RevenueReportService   $revenueReport,
        OccupancyReportService $occupancyReport,
        CsvExporter            $csvExporter,
        XlsxExporter           $xlsxExporter,
    ): void {
        $export = ReportExport::withoutGlobalScopes()->findOrFail($this->reportExportId);
        $export->update(['status' => 'processing']);

        try {
            $p = $export->params;
            $params = new ReportParams(
                clubId:     $export->club_id,
                dateFrom:   Carbon::parse($p['date_from']),
                dateTo:     Carbon::parse($p['date_to']),
                branchId:   $p['branch_id'] ?? null,
                resourceId: $p['resource_id'] ?? null,
            );

            $rows = match ($export->report_type) {
                'bookings'   => $bookingReport->rows($params),
                'revenue'    => $revenueReport->byDay($params),
                'occupancy'  => $occupancyReport->byResource($params),
                default      => throw new \InvalidArgumentException("Unknown report type: {$export->report_type}"),
            };

            // Формат выгрузки: csv (по умолчанию) или xlsx
            $format    = in_array($export->file_format, ['csv', 'xlsx'], true)
                ? $export->file_format
                : 'csv';
            $filename  = "{$export->report_type}_{$export->public_id}.{$format}";
            $directory = storage_path('app/reports');

            $path = $format === 'xlsx'
                ? $xlsxExporter->exportToFile($rows, $directory, $filename)
                : $csvExporter->exportToFile($rows, $directory, $filename);

            // Относительный путь для хранения
            $relativePath = 'reports/' . $filename;
            $export->markReady($relativePath);

        } catch (\Throwable $e) {
            $export->markFailed($e->getMessage());
            Log::error("[GenerateReport] Export #{$this->reportExportId} failed: {$e->getMessage()}");
            throw $e;
        }
    }
}
