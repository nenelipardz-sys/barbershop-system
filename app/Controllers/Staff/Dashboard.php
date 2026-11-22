<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use Database;

class Dashboard extends BaseController
{
    public function index()
    {
        /** @var \Barber $user */
        $user = $this->user;

        if (is_post()) {
            $this->handleStaffAppointmentAction($user);
            return go('staff/dashboard');
        }

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $mine = $user->appointmentsOn($today); // overridden: only appointments assigned to this barber
        $mine = array_values(array_filter($mine, fn($a) => $a['status'] !== 'cancelled'));
        $phones = [];
        foreach (Database::all('SELECT c.id, c.phone FROM clients c JOIN appointments a ON a.client_id = c.id WHERE a.barber_id = ? AND a.appointment_date = ?', [$user->getId(), $today]) as $r) {
            $phones[$r['id']] = $r['phone'];
        }
        foreach ($mine as &$a) {
            $a['client_phone'] = $phones[$a['client_id']] ?? '—';
        }
        unset($a);

        $countYesterday = (int) Database::value("SELECT COUNT(*) FROM appointments WHERE barber_id = ? AND appointment_date = ? AND status <> 'cancelled'", [$user->getId(), $yesterday]);
        $completed = count(array_filter($mine, fn($a) => $a['status'] === 'completed'));
        $completedYesterday = (int) Database::value("SELECT COUNT(*) FROM appointments WHERE barber_id = ? AND appointment_date = ? AND status = 'completed'", [$user->getId(), $yesterday]);
        $pendingCount = count(array_filter($mine, fn($a) => in_array($a['status'], ['pending', 'confirmed'], true)));
        $diff = count($mine) - $countYesterday;

        // Next client: whoever is in the chair, otherwise the next upcoming booking today.
        $next = null;
        foreach ($mine as $a) {
            if ($a['status'] === 'in_progress') { $next = $a; break; }
        }
        if (!$next) {
            foreach ($mine as $a) {
                if (in_array($a['status'], ['pending', 'confirmed'], true) && $a['end_time'] >= date('H:i:s')) { $next = $a; break; }
            }
        }
        if (!$next) {
            $next = Database::one(
                "SELECT a.*, c.name AS client_name, c.phone AS client_phone, c.avatar AS client_avatar, s.name AS service_name, s.image AS service_image, u.name AS barber_name
                 FROM appointments a JOIN clients c ON c.id = a.client_id JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id
                 WHERE a.barber_id = ? AND a.status IN ('pending','confirmed') AND (a.appointment_date > ? OR (a.appointment_date = ? AND a.start_time >= ?))
                 ORDER BY a.appointment_date, a.start_time LIMIT 1",
                [$user->getId(), $today, $today, date('H:i:s')]
            );
        }

        $recentClients = Database::all(
            "SELECT c.id, c.name, c.avatar, COUNT(*) AS visits,
                    SUBSTRING_INDEX(GROUP_CONCAT(s.name ORDER BY a.appointment_date DESC, a.start_time DESC), ',', 1) AS last_service,
                    MAX(a.appointment_date) AS last_visit
             FROM appointments a JOIN clients c ON c.id = a.client_id JOIN services s ON s.id = a.service_id
             WHERE a.barber_id = ? AND a.status = 'completed'
             GROUP BY c.id, c.name, c.avatar ORDER BY last_visit DESC, MAX(a.completed_at) DESC LIMIT 3",
            [$user->getId()]
        );

        $byService = [];
        foreach ($mine as $a) {
            $byService[$a['service_name']] = ($byService[$a['service_name']] ?? 0) + 1;
        }
        arsort($byService);
        $colors = ['#163a6b', '#d9a441', '#3f7fc4', '#2f9a55', '#8a6ad1', '#d0644a'];
        $totalSvc = array_sum($byService);
        $stops = [];
        $acc = 0;
        $i = 0;
        foreach ($byService as $name => $n) {
            $start = $totalSvc ? $acc / $totalSvc * 360 : 0;
            $acc += $n;
            $end = $totalSvc ? $acc / $totalSvc * 360 : 0;
            $stops[] = $colors[$i++ % count($colors)] . " {$start}deg {$end}deg";
        }
        $donut = $stops ? 'conic-gradient(' . implode(', ', $stops) . ')' : 'conic-gradient(#ece6da 0deg 360deg)';
        $notesMap = client_notes_map(array_merge(array_column($mine, 'client_id'), $next ? [$next['client_id']] : []));

        $pageTitle = 'Dashboard';
        $fitScreen = true; // whole dashboard fits on one screen, like the prototype
        $active = 'dashboard';
        return $this->render('staff/dashboard', get_defined_vars());
    }
}
