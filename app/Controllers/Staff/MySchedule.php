<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use Database;
use Schedule;

/**
 * The barber's week view (staff/schedule).
 */
class MySchedule extends BaseController
{
    public function index()
    {
        /** @var \Barber $user */
        $user = $this->user;

        if (is_post()) {
            $this->handleStaffAppointmentAction($user);
            return go_back('staff/schedule');
        }

        $ref = valid_date(input('week'), date('Y-m-d'));
        $week = Schedule::weekDates($ref);
        $rows = Database::all(
            "SELECT a.*, c.name AS client_name, c.phone AS client_phone, c.avatar AS client_avatar, s.name AS service_name, s.image AS service_image, u.name AS barber_name
             FROM appointments a JOIN clients c ON c.id = a.client_id JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id
             WHERE a.barber_id = ? AND a.appointment_date BETWEEN ? AND ? ORDER BY a.appointment_date, a.start_time",
            [$user->getId(), $week[0], $week[6]]
        );
        $byDay = [];
        foreach ($rows as $r) {
            $byDay[$r['appointment_date']][] = $r;
        }
        $notesMap = client_notes_map(array_column($rows, 'client_id'));
        $active_rows = array_filter($rows, fn($r) => $r['status'] !== 'cancelled');
        $bookedMin = array_sum(array_map(fn($r) => (strtotime($r['end_time']) - strtotime($r['start_time'])) / 60, $active_rows));
        $workDays = array_filter($week, fn($d) => $user->worksOn($d) && Schedule::isShopOpen($d));
        $shiftMin = (strtotime($user->getWorkEnd()) - strtotime($user->getWorkStart())) / 60;
        $capacity = max(1, count($workDays) * $shiftMin);
        $days = day_names();

        $pageTitle = 'My Schedule';
        $active = 'schedule';
        return $this->render('staff/schedule', get_defined_vars());
    }
}
