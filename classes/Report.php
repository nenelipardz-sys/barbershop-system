<?php
/**
 * Report — sales and earnings summaries (daily, weekly, monthly),
 * filterable by service type or staff member.
 */
class Report
{
    private string $from;
    private string $to;
    private ?int $serviceId;
    private ?int $barberId;

    public function __construct(string $from, string $to, ?int $serviceId = null, ?int $barberId = null)
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $this->from = $from;
        $this->to = $to;
        $this->serviceId = $serviceId ?: null;
        $this->barberId = $barberId ?: null;
    }

    /** Resolve a named period to a date range: [from, to, label]. */
    public static function period(string $period, ?string $ref = null): array
    {
        $ref = $ref ?: date('Y-m-d');
        $t = strtotime($ref);
        switch ($period) {
            case 'weekly':
                $from = date('Y-m-d', strtotime('monday this week', $t));
                $to = date('Y-m-d', strtotime('sunday this week', $t));
                return [$from, $to, date('M j', strtotime($from)) . ' – ' . date('M j, Y', strtotime($to))];
            case 'monthly':
                return [date('Y-m-01', $t), date('Y-m-t', $t), date('F Y', $t)];
            default:
                return [$ref, $ref, date('l, M j, Y', $t)];
        }
    }

    public function getFrom(): string { return $this->from; }
    public function getTo(): string { return $this->to; }

    private function where(string $status = 'completed'): array
    {
        $sql = 'a.status = ? AND a.appointment_date BETWEEN ? AND ?';
        $params = [$status, $this->from, $this->to];
        if ($this->serviceId) {
            $sql .= ' AND a.service_id = ?';
            $params[] = $this->serviceId;
        }
        if ($this->barberId) {
            $sql .= ' AND a.barber_id = ?';
            $params[] = $this->barberId;
        }
        return [$sql, $params];
    }

    public function summary(): array
    {
        [$w, $p] = $this->where();
        $row = Database::one(
            "SELECT COUNT(*) AS services, COALESCE(SUM(a.total_price),0) AS earnings, COALESCE(SUM(a.discount_amount),0) AS discounts,
                    COALESCE(SUM(a.base_price),0) AS gross, COUNT(DISTINCT a.client_id) AS clients
             FROM appointments a WHERE $w",
            $p
        );
        [$wc, $pc] = $this->where('cancelled');
        $row['cancelled'] = (int) Database::value("SELECT COUNT(*) FROM appointments a WHERE $wc", $pc);
        $row['average'] = $row['services'] > 0 ? $row['earnings'] / $row['services'] : 0;
        return $row;
    }

    /** Earnings per day across the range (zero-filled). */
    public function dailySeries(): array
    {
        [$w, $p] = $this->where();
        $rows = Database::all("SELECT a.appointment_date AS d, SUM(a.total_price) AS total, COUNT(*) AS cnt FROM appointments a WHERE $w GROUP BY a.appointment_date", $p);
        $map = [];
        foreach ($rows as $r) {
            $map[$r['d']] = $r;
        }
        $series = [];
        for ($t = strtotime($this->from); $t <= strtotime($this->to); $t += 86400) {
            $d = date('Y-m-d', $t);
            $series[] = ['date' => $d, 'total' => (float) ($map[$d]['total'] ?? 0), 'count' => (int) ($map[$d]['cnt'] ?? 0)];
        }
        return $series;
    }

    public function byService(): array
    {
        [$w, $p] = $this->where();
        return Database::all(
            "SELECT s.id, s.name, s.image, COUNT(*) AS cnt, SUM(a.total_price) AS total
             FROM appointments a JOIN services s ON s.id = a.service_id WHERE $w GROUP BY s.id, s.name, s.image ORDER BY total DESC",
            $p
        );
    }

    public function byBarber(): array
    {
        [$w, $p] = $this->where();
        return Database::all(
            "SELECT u.id, u.name, u.avatar, COUNT(*) AS cnt, SUM(a.total_price) AS total
             FROM appointments a JOIN users u ON u.id = a.barber_id WHERE $w GROUP BY u.id, u.name, u.avatar ORDER BY total DESC",
            $p
        );
    }

    public function transactions(int $limit = 200): array
    {
        [$w, $p] = $this->where();
        return Database::all(
            "SELECT a.*, c.name AS client_name, s.name AS service_name, u.name AS barber_name
             FROM appointments a JOIN clients c ON c.id = a.client_id JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id
             WHERE $w ORDER BY a.appointment_date DESC, a.start_time DESC LIMIT " . (int) $limit,
            $p
        );
    }

    /**
     * Chart data for the dashboard "Sales Overview" widget.
     * daily: last 7 days, weekly: last 8 weeks, monthly: last 6 months.
     */
    public static function salesOverview(string $mode, ?int $barberId = null): array
    {
        $labels = [];
        $values = [];
        $filter = $barberId ? ' AND barber_id = ' . (int) $barberId : '';
        $sum = fn(string $from, string $to) => (float) Database::value(
            "SELECT COALESCE(SUM(total_price),0) FROM appointments WHERE status = 'completed' AND appointment_date BETWEEN ? AND ?$filter",
            [$from, $to]
        );

        if ($mode === 'weekly') {
            $monday = strtotime('monday this week');
            for ($i = 7; $i >= 0; $i--) {
                $from = date('Y-m-d', strtotime("-$i week", $monday));
                $to = date('Y-m-d', strtotime('+6 day', strtotime($from)));
                $labels[] = date('M j', strtotime($from));
                $values[] = $sum($from, $to);
            }
            $headline = end($values);
            $caption = 'This Week\'s Sales';
        } elseif ($mode === 'monthly') {
            for ($i = 5; $i >= 0; $i--) {
                $first = strtotime(date('Y-m-01') . " -$i month");
                $labels[] = date('M', $first);
                $values[] = $sum(date('Y-m-01', $first), date('Y-m-t', $first));
            }
            $headline = end($values);
            $caption = 'This Month\'s Sales';
        } else {
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i day"));
                $labels[] = date('D', strtotime($d));
                $values[] = $sum($d, $d);
            }
            $headline = end($values);
            $caption = 'Today\'s Sales';
        }
        return ['labels' => $labels, 'values' => $values, 'headline' => $headline, 'caption' => $caption];
    }
}
