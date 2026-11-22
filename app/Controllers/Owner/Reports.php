<?php

namespace App\Controllers\Owner;

use App\Controllers\BaseController;
use Barber;
use Database;
use Report;
use Service;

/**
 * Sales & earnings reports. Staff see the same screen (staff/reports), locked to their own completed services.
 */
class Reports extends BaseController
{
    public function index()
    {
        $user = $this->user;
        $isStaff = $user->getRole() === 'staff';

        $period = in_array(input('period'), ['daily', 'weekly', 'monthly', 'custom'], true) ? input('period') : 'monthly';
        $ref = valid_date(input('ref'), date('Y-m-d'));
        if ($period === 'custom') {
            $from = valid_date(input('from'), date('Y-m-01'));
            $to = valid_date(input('to'), date('Y-m-d'));
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
            $rangeLabel = fmt_date($from) . ' – ' . fmt_date($to);
        } else {
            [$from, $to, $rangeLabel] = Report::period($period, $ref);
        }
        $serviceF = (int) input('service') ?: null;
        $barberF = $isStaff ? $user->getId() : ((int) input('barber') ?: null);

        $report = new Report($from, $to, $serviceF, $barberF);
        $summary = $report->summary();

        if (input('export') === 'csv') {
            return $this->csv($report, $summary, $from, $to);
        }

        // Chart range: a day is shown with the 6 days before it for context.
        $chartFrom = $period === 'daily' ? date('Y-m-d', strtotime('-6 day', strtotime($ref))) : $from;
        $series = (new Report($chartFrom, $to, $serviceF, $barberF))->dailySeries();
        $labelFmt = count($series) <= 7 ? 'D j' : 'j';
        $chart = ['labels' => array_map(fn($p) => date($labelFmt, strtotime($p['date'])), $series), 'values' => array_map(fn($p) => $p['total'], $series)];
        $byService = $report->byService();
        $byBarber = $report->byBarber();
        $transactions = $report->transactions(50);
        $services = Service::all(false);
        $barbers = array_map(fn($r) => new Barber($r), Database::all("SELECT * FROM users WHERE role = 'staff' ORDER BY name"));

        $step = ['daily' => '1 day', 'weekly' => '1 week', 'monthly' => '1 month'][$period] ?? null;
        $nav = function (string $dir) use ($period, $ref, $step) {
            $q = $_GET;
            $q['period'] = $period;
            $q['ref'] = date('Y-m-d', strtotime(($dir === 'prev' ? '-' : '+') . $step, strtotime($ref)));
            return '?' . http_build_query($q);
        };
        $periodUrl = function (string $p) {
            $q = $_GET;
            $q['period'] = $p;
            unset($q['export']);
            return '?' . http_build_query($q);
        };
        $exportQ = $_GET;
        $exportQ['export'] = 'csv';

        $pageTitle = $isStaff ? 'My Reports' : 'Reports';
        $active = 'reports';
        return $this->render('owner/reports', get_defined_vars());
    }

    /** CSV download of every completed service in the range. */
    private function csv(Report $report, array $summary, string $from, string $to)
    {
        $out = fopen('php://temp', 'w+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Date', 'Time', 'Client', 'Service', 'Barber', 'Rate', 'Base Price', 'Discount', 'Total']);
        foreach ($report->transactions(100000) as $t) {
            fputcsv($out, [$t['appointment_date'], fmt_time($t['start_time']), $t['client_name'], $t['service_name'], $t['barber_name'],
                discount_label($t['discount_type']), $t['base_price'], $t['discount_amount'], $t['total_price']]);
        }
        fputcsv($out, []);
        fputcsv($out, ['Total', '', '', '', '', '', $summary['gross'], $summary['discounts'], $summary['earnings']]);
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response
            ->setContentType('text/csv', 'utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="earnings_' . $from . '_to_' . $to . '.csv"')
            ->setBody($csv);
    }
}
