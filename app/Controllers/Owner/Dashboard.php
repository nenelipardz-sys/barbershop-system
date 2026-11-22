<?php

namespace App\Controllers\Owner;

use App\Controllers\BaseController;
use Database;

class Dashboard extends BaseController
{
    public function index()
    {
        $user = $this->user;
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $apptToday = (int) Database::value("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status <> 'cancelled'", [$today]);
        $apptYesterday = (int) Database::value("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status <> 'cancelled'", [$yesterday]);
        $clientsTotal = (int) Database::value('SELECT COUNT(*) FROM clients');
        $clientsLastWeek = (int) Database::value('SELECT COUNT(*) FROM clients WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)');
        $earnToday = (float) Database::value("SELECT COALESCE(SUM(total_price),0) FROM appointments WHERE appointment_date = ? AND status = 'completed'", [$today]);
        $earnYesterday = (float) Database::value("SELECT COALESCE(SUM(total_price),0) FROM appointments WHERE appointment_date = ? AND status = 'completed'", [$yesterday]);
        $pending = (int) Database::value("SELECT COUNT(*) FROM appointments WHERE status = 'pending' AND appointment_date >= ?", [$today]);

        $recent = $user->appointmentsOn($today);
        $recent = array_values(array_filter($recent, fn($a) => $a['status'] !== 'cancelled'));

        $topServices = Database::all(
            "SELECT s.id, s.name, s.image, COUNT(a.id) AS cnt FROM services s
             LEFT JOIN appointments a ON a.service_id = s.id AND a.status = 'completed' AND a.appointment_date >= ?
             GROUP BY s.id, s.name, s.image ORDER BY cnt DESC, s.sort_order LIMIT 4",
            [date('Y-m-01')]
        );
        $recentClients = Database::all(
            "SELECT c.id, c.name, c.avatar, COUNT(a.id) AS visits, MAX(a.appointment_date) AS last_visit
             FROM clients c JOIN appointments a ON a.client_id = c.id AND a.status = 'completed'
             GROUP BY c.id, c.name, c.avatar ORDER BY last_visit DESC, MAX(a.completed_at) DESC LIMIT 4"
        );

        $delta = function (string $label, string $suffix) {
            $cls = str_starts_with($label, '-') ? 'down' : 'up';
            return '<span class="stat-delta ' . $cls . '">' . e($label) . ' ' . e($suffix) . '</span>';
        };

        $pageTitle = 'Dashboard';
        $fitScreen = true; // whole dashboard fits on one screen, like the prototype
        $active = 'dashboard';
        return $this->render('owner/dashboard', get_defined_vars());
    }
}
