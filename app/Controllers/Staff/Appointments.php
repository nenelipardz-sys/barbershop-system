<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use Database;

class Appointments extends BaseController
{
    public function index()
    {
        /** @var \Barber $user */
        $user = $this->user;

        if (is_post()) {
            $this->handleStaffAppointmentAction($user);
            return go_back('staff/appointments');
        }

        $today = date('Y-m-d');
        $tabs = ['today' => 'Today', 'upcoming' => 'Upcoming', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'all' => 'All'];
        $date = valid_date(input('date'));
        $tab = array_key_exists(input('tab'), $tabs) ? input('tab') : ($date ? 'all' : 'today');
        $q = input('q');

        $conds = [
            'today'       => ['a.appointment_date = ?', [$today]],
            'upcoming'    => ["a.appointment_date >= ? AND a.status IN ('pending','confirmed')", [$today]],
            'in_progress' => ["a.status = 'in_progress'", []],
            'completed'   => ["a.status = 'completed'", []],
            'cancelled'   => ["a.status = 'cancelled'", []],
            'all'         => ['1=1', []],
        ];
        $base = 'a.barber_id = ?';
        $baseParams = [$user->getId()];
        if ($date) { $base .= ' AND a.appointment_date = ?'; $baseParams[] = $date; }
        if ($q !== '') { $base .= ' AND c.name LIKE ?'; $baseParams[] = "%$q%"; }

        $counts = [];
        foreach ($conds as $k => [$sql, $p]) {
            $counts[$k] = (int) Database::value("SELECT COUNT(*) FROM appointments a JOIN clients c ON c.id = a.client_id WHERE $base AND $sql", array_merge($baseParams, $p));
        }
        [$sql, $p] = $conds[$tab];
        $total = $counts[$tab];
        [$offset, $pages, $page] = paginate($total, 15);
        $order = in_array($tab, ['today', 'upcoming', 'in_progress'], true) ? 'ASC' : 'DESC';
        $rows = Database::all(
            "SELECT a.*, c.name AS client_name, c.phone AS client_phone, c.avatar AS client_avatar, s.name AS service_name, s.image AS service_image, u.name AS barber_name
             FROM appointments a JOIN clients c ON c.id = a.client_id JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id
             WHERE $base AND $sql ORDER BY a.appointment_date $order, a.start_time $order LIMIT $offset, 15",
            array_merge($baseParams, $p)
        );
        $notesMap = client_notes_map(array_column($rows, 'client_id'));
        $tabUrl = function (string $t) {
            $q = $_GET;
            unset($q['page']);
            $q['tab'] = $t;
            return '?' . http_build_query($q);
        };

        $pageTitle = 'Appointments';
        $active = 'appointments';
        return $this->render('staff/appointments', get_defined_vars());
    }
}
