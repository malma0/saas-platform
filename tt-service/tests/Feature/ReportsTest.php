<?php

namespace Tests\Feature;

use App\Domain\Analyzer\Models\TableOccupancySession;
use App\Domain\Attendance\Actions\MarkAttendance;
use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\Booking\Models\Booking;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Facilities\Actions\CreateResource;
use App\Domain\Facilities\DTO\CreateBranchDTO;
use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Facilities\Models\ResourceType;
use App\Domain\Payments\Actions\CreatePayment;
use App\Domain\Payments\Actions\MarkPaymentPaid;
use App\Domain\Payments\Actions\RefundPayment;
use App\Domain\Reports\DTO\ReportParams;
use App\Domain\Reports\Jobs\GenerateReportJob;
use App\Domain\Reports\Models\ReportExport;
use App\Domain\Reports\Services\BookingReportService;
use App\Domain\Reports\Services\CsvExporter;
use App\Domain\Reports\Services\OccupancyReportService;
use App\Domain\Reports\Services\RevenueReportService;
use App\Domain\Services\Models\PricingRule;
use App\Domain\Services\Models\ServiceOffering;
use App\Support\Enums\AttendanceStatus;
use App\Support\Enums\BookingStatus;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Тесты Фазы 11 — Reports + Analyzer.
 *
 * ✅ Готово, если: отчёты строятся и выгружаются;
 *                  данные анализатора видны в отчёте загрузки.
 */
class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private Club            $club;
    private Branch          $branch;
    private Resource        $table;
    private ServiceOffering $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->club   = Club::factory()->create();
        $this->branch = (new \App\Domain\Facilities\Actions\CreateBranch())->handle(
            new CreateBranchDTO(clubId: $this->club->id, name: 'Тест', timezone: 'Europe/Moscow')
        );
        $type = ResourceType::create(['club_id' => $this->club->id, 'slug' => 'table', 'name' => 'Стол']);
        $this->table = (new CreateResource())->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            resourceTypeId: $type->id, name: 'Стол 1',
        ));
        $this->service = ServiceOffering::factory()->create([
            'club_id' => $this->club->id, 'duration_minutes' => 60,
        ]);
        PricingRule::create([
            'service_offering_id' => $this->service->id,
            'amount_minor' => 50000, 'currency_code' => 'RUB', 'priority' => 0,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeBooking(?Carbon $startAt = null, int $amount = 50000): Booking
    {
        $start = $startAt ?? Carbon::yesterday()->setTime(10, 0)->utc();
        return (new CreateBooking())->handle(new CreateBookingDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            serviceOfferingId: $this->service->id, resourceIds: [$this->table->id],
            startAt: $start, endAt: $start->copy()->addHour(),
            amountMinor: $amount,
        ));
    }

    private function params(?Carbon $from = null, ?Carbon $to = null): ReportParams
    {
        return new ReportParams(
            clubId:   $this->club->id,
            dateFrom: $from ?? Carbon::yesterday()->startOfDay(),
            dateTo:   $to   ?? Carbon::today()->endOfDay(),
        );
    }

    // -------------------------------------------------------------------------
    // Отчёт по бронированиям
    // -------------------------------------------------------------------------

    public function test_booking_report_summary(): void
    {
        $this->makeBooking(Carbon::yesterday()->setTime(10, 0)->utc());
        $this->makeBooking(Carbon::yesterday()->setTime(12, 0)->utc());

        $summary = (new BookingReportService())->summary($this->params());

        $this->assertEquals(2, $summary['total']);
        $this->assertEquals(2, $summary['confirmed']);
        $this->assertEquals(100000, $summary['total_amount_minor']);
    }

    public function test_booking_report_excludes_other_clubs(): void
    {
        $otherClub = Club::factory()->create();
        $this->makeBooking(Carbon::yesterday()->setTime(10, 0)->utc());

        // Отчёт другого клуба
        $params = new ReportParams(
            clubId:   $otherClub->id,
            dateFrom: Carbon::yesterday()->startOfDay(),
            dateTo:   Carbon::today()->endOfDay(),
        );
        $summary = (new BookingReportService())->summary($params);
        $this->assertEquals(0, $summary['total']);
    }

    public function test_booking_report_by_day(): void
    {
        $this->makeBooking(Carbon::yesterday()->setTime(10, 0)->utc());
        $this->makeBooking(Carbon::yesterday()->setTime(12, 0)->utc());

        $byDay = (new BookingReportService())->byDay($this->params());

        $this->assertGreaterThanOrEqual(1, $byDay->count());
        $yesterday = $byDay->firstWhere('date', Carbon::yesterday()->toDateString());
        $this->assertNotNull($yesterday);
        $this->assertEquals(2, $yesterday['count']);
    }

    public function test_booking_report_rows_for_csv(): void
    {
        $this->makeBooking(Carbon::yesterday()->setTime(10, 0)->utc());

        $rows = (new BookingReportService())->rows($this->params());
        $this->assertCount(1, $rows);
        $this->assertObjectHasProperty('service_name', $rows->first());
    }

    // -------------------------------------------------------------------------
    // Отчёт по выручке
    // -------------------------------------------------------------------------

    public function test_revenue_report_summary(): void
    {
        $b = $this->makeBooking(Carbon::yesterday()->setTime(10, 0)->utc(), 80000);
        $p = (new CreatePayment())->handle($b);
        (new MarkPaymentPaid())->handle($p);

        $summary = (new RevenueReportService())->summary($this->params());

        $this->assertEquals(80000, $summary['total_paid_minor']);
        $this->assertEquals(0, $summary['total_refunded_minor']);
        $this->assertEquals(80000, $summary['net_minor']);
        $this->assertEquals(1, $summary['payments_count']);
    }

    public function test_revenue_report_with_refund(): void
    {
        $b = $this->makeBooking(Carbon::yesterday()->setTime(11, 0)->utc(), 60000);
        $p = (new CreatePayment())->handle($b);
        (new MarkPaymentPaid())->handle($p);
        (new RefundPayment())->handle($p->fresh(), 20000, 'Частичный возврат');

        $summary = (new RevenueReportService())->summary($this->params());

        $this->assertEquals(60000, $summary['total_paid_minor']);
        $this->assertEquals(20000, $summary['total_refunded_minor']);
        $this->assertEquals(40000, $summary['net_minor']);
    }

    // -------------------------------------------------------------------------
    // Отчёт загрузки + данные анализатора
    // -------------------------------------------------------------------------

    public function test_occupancy_report_shows_booking_minutes(): void
    {
        $this->makeBooking(Carbon::yesterday()->setTime(10, 0)->utc()); // 60 мин

        $rows = (new OccupancyReportService())->byResource($this->params());

        $row = $rows->firstWhere('resource_id', $this->table->id);
        $this->assertNotNull($row);
        $this->assertEquals(60, $row['booking_minutes']);
        $this->assertEquals(0, $row['analyzer_minutes']);
    }

    public function test_occupancy_report_includes_analyzer_data(): void
    {
        // Данные анализатора: 90 минут
        $start = Carbon::yesterday()->setTime(14, 0)->utc();
        TableOccupancySession::create([
            'club_id'     => $this->club->id,
            'branch_id'   => $this->branch->id,
            'resource_id' => $this->table->id,
            'start_at'    => $start,
            'end_at'      => $start->copy()->addMinutes(90),
            'source'      => 'analyzer',
        ]);

        $rows = (new OccupancyReportService())->byResource($this->params());

        $row = $rows->firstWhere('resource_id', $this->table->id);
        $this->assertNotNull($row);
        $this->assertEquals(90, $row['analyzer_minutes']);
    }

    public function test_occupancy_unmatched_minutes(): void
    {
        // Бронь: 60 мин
        $this->makeBooking(Carbon::yesterday()->setTime(10, 0)->utc());

        // Анализатор видит 90 мин — 30 мин без брони
        $start = Carbon::yesterday()->setTime(10, 0)->utc();
        TableOccupancySession::create([
            'club_id'     => $this->club->id,
            'branch_id'   => $this->branch->id,
            'resource_id' => $this->table->id,
            'start_at'    => $start,
            'end_at'      => $start->copy()->addMinutes(90),
            'source'      => 'analyzer',
        ]);

        $rows = (new OccupancyReportService())->byResource($this->params());

        $row = $rows->firstWhere('resource_id', $this->table->id);
        $this->assertEquals(60, $row['booking_minutes']);
        $this->assertEquals(90, $row['analyzer_minutes']);
        $this->assertEquals(30, $row['unmatched_minutes']); // подозрительно — не захвачено бронью
    }

    public function test_analyzer_data_deduplication_by_external_id(): void
    {
        $start = Carbon::yesterday()->setTime(16, 0)->utc();
        $data  = [
            'club_id' => $this->club->id, 'branch_id' => $this->branch->id,
            'resource_id' => $this->table->id, 'source' => 'analyzer',
            'start_at' => $start, 'end_at' => $start->copy()->addHour(),
            'external_id' => 'evt-analyzer-001',
        ];

        TableOccupancySession::create($data);

        // Повторная вставка с тем же external_id должна игнорироваться
        $inserted = \Illuminate\Support\Facades\DB::table('table_occupancy_sessions')
            ->insertOrIgnore(array_merge($data, [
                'created_at' => now(), 'updated_at' => now(),
                'start_at'   => $start->toDateTimeString(),
                'end_at'     => $start->copy()->addHour()->toDateTimeString(),
            ]));

        $this->assertEquals(
            1,
            TableOccupancySession::withoutGlobalScopes()->where('external_id', 'evt-analyzer-001')->count()
        );
    }

    // -------------------------------------------------------------------------
    // CSV-экспорт
    // -------------------------------------------------------------------------

    public function test_csv_exporter(): void
    {
        $rows = collect([
            ['date' => '2026-06-01', 'count' => 5, 'amount_minor' => 250000],
            ['date' => '2026-06-02', 'count' => 3, 'amount_minor' => 150000],
        ]);

        $csv = (new CsvExporter())->export($rows, ['Дата', 'Кол-во', 'Сумма (коп.)']);

        $this->assertStringContainsString('Дата', $csv);
        $this->assertStringContainsString('2026-06-01', $csv);
        $this->assertStringContainsString('250000', $csv);
    }

    public function test_csv_empty_rows_returns_empty_string(): void
    {
        $csv = (new CsvExporter())->export([]);
        $this->assertEquals('', $csv);
    }

    // -------------------------------------------------------------------------
    // ReportExport (очередь)
    // -------------------------------------------------------------------------

    public function test_generate_report_job_dispatched(): void
    {
        Queue::fake();

        $user = \App\Models\User::factory()->create(['club_id' => $this->club->id]);
        $params = $this->params();

        $export = (new \App\Domain\Reports\Actions\RequestReportExport())->handle(
            params:      $params,
            reportType:  'bookings',
            requestedBy: $user->id,
        );

        $this->assertEquals('pending', $export->status);
        Queue::assertPushed(GenerateReportJob::class, fn($job) => $job->reportExportId === $export->id);
    }

    public function test_generate_report_job_runs_and_creates_file(): void
    {
        $this->makeBooking(Carbon::yesterday()->setTime(10, 0)->utc());

        $user   = \App\Models\User::factory()->create(['club_id' => $this->club->id]);
        $export = ReportExport::create([
            'club_id'      => $this->club->id,
            'requested_by' => $user->id,
            'report_type'  => 'bookings',
            'params'       => $this->params()->toArray(),
            'status'       => 'pending',
            'file_format'  => 'csv',
        ]);

        // Запускаем job синхронно
        (new GenerateReportJob($export->id))->handle(
            new BookingReportService(),
            new RevenueReportService(),
            new OccupancyReportService(),
            new CsvExporter(),
            new \App\Domain\Reports\Services\XlsxExporter(),
        );

        $export->refresh();
        $this->assertEquals('ready', $export->status);
        $this->assertNotNull($export->file_path);
        $this->assertStringEndsWith('.csv', $export->file_path);
        $this->assertFileExists(storage_path('app/' . $export->file_path));
    }

    public function test_generate_report_job_creates_xlsx_file(): void
    {
        $this->makeBooking(Carbon::yesterday()->setTime(10, 0)->utc());

        $user   = \App\Models\User::factory()->create(['club_id' => $this->club->id]);
        $export = ReportExport::create([
            'club_id'      => $this->club->id,
            'requested_by' => $user->id,
            'report_type'  => 'bookings',
            'params'       => $this->params()->toArray(),
            'status'       => 'pending',
            'file_format'  => 'xlsx',
        ]);

        (new GenerateReportJob($export->id))->handle(
            new BookingReportService(),
            new RevenueReportService(),
            new OccupancyReportService(),
            new CsvExporter(),
            new \App\Domain\Reports\Services\XlsxExporter(),
        );

        $export->refresh();
        $this->assertEquals('ready', $export->status);
        $this->assertStringEndsWith('.xlsx', $export->file_path);

        $fullPath = storage_path('app/' . $export->file_path);
        $this->assertFileExists($fullPath);

        // XLSX — это ZIP-архив, начинается с сигнатуры "PK"
        $this->assertSame('PK', substr((string) file_get_contents($fullPath), 0, 2));
    }

    public function test_xlsx_exporter_writes_headers_and_rows(): void
    {
        $exporter = new \App\Domain\Reports\Services\XlsxExporter();
        $dir      = storage_path('app/reports');
        $path     = $exporter->exportToFile(
            [['name' => 'Стол 1', 'count' => 5], ['name' => 'Стол 2', 'count' => 3]],
            $dir,
            'test_' . uniqid() . '.xlsx',
        );

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));

        @unlink($path);
    }
}
