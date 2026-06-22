<?php

namespace App\Domain\Reports\Actions;

use App\Domain\Reports\DTO\ReportParams;
use App\Domain\Reports\Jobs\GenerateReportJob;
use App\Domain\Reports\Models\ReportExport;

class RequestReportExport
{
    /**
     * Создать задачу на экспорт и поставить в очередь.
     * Возвращает ReportExport в статусе pending/processing.
     */
    public function handle(
        ReportParams $params,
        string       $reportType,
        int          $requestedBy,
        string       $fileFormat = 'csv',
    ): ReportExport {
        $export = ReportExport::create([
            'club_id'      => $params->clubId,
            'requested_by' => $requestedBy,
            'report_type'  => $reportType,
            'params'       => $params->toArray(),
            'status'       => 'pending',
            'file_format'  => $fileFormat,
        ]);

        GenerateReportJob::dispatch($export->id);

        return $export;
    }
}
