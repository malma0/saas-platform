<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\ClubCore\Models\Club;
use App\Domain\Reports\DTO\ReportParams;
use App\Domain\Reports\Services\BookingReportService;
use App\Domain\Reports\Services\CsvExporter;
use App\Domain\Reports\Services\OccupancyReportService;
use App\Domain\Reports\Services\RevenueReportService;
use App\Domain\Reports\Services\XlsxExporter;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Синхронная выгрузка отчётов CSV/XLSX из админ-панели (Фаза 11 / ТЗ Блок 9).
 *
 * Tenant-scope в сервисах опирается на club_id; для не-суперадмина берём
 * клуб текущего пользователя, для суперадмина — первый клуб (демо).
 */
class ReportsController extends Controller
{
    public function export(
        Request                $request,
        BookingReportService   $bookingReport,
        RevenueReportService   $revenueReport,
        OccupancyReportService $occupancyReport,
        CsvExporter            $csv,
        XlsxExporter           $xlsx,
    ): BinaryFileResponse {
        $data = $request->validate([
            'type'      => 'required|in:bookings,revenue,occupancy',
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
            'format'    => 'required|in:csv,xlsx',
        ]);

        $user   = auth()->user();
        $clubId = $user?->club_id ?? Club::withoutGlobalScopes()->value('id');

        $params = new ReportParams(
            clubId:   (int) $clubId,
            dateFrom: Carbon::parse($data['date_from']),
            dateTo:   Carbon::parse($data['date_to']),
        );

        $rows = match ($data['type']) {
            'bookings'  => $bookingReport->rows($params),
            'revenue'   => $revenueReport->byDay($params),
            'occupancy' => $occupancyReport->byResource($params),
        };

        $dir      = storage_path('app/reports');
        $filename = "{$data['type']}_" . now()->format('Ymd_His') . ".{$data['format']}";

        $path = $data['format'] === 'xlsx'
            ? $xlsx->exportToFile($rows, $dir, $filename)
            : $csv->exportToFile($rows, $dir, $filename);

        return response()->download($path, $filename)->deleteFileAfterSend();
    }
}
