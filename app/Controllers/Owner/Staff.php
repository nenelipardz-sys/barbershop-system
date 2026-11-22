<?php

namespace App\Controllers\Owner;

use App\Controllers\BaseController;
use Barber;
use Database;
use User;
use ValidationException;

class Staff extends BaseController
{
    public function index()
    {
        $user = $this->user;

        if (is_post()) {
            $action = input('action');
            try {
                if ($action === 'save') {
                    $id = (int) input('staff_id');
                    $data = [
                        'name' => input('name'), 'username' => input('username'), 'email' => input('email'), 'phone' => input('phone'),
                        'password' => (string) ($_POST['password'] ?? ''), 'password_confirm' => (string) ($_POST['password'] ?? ''),
                    ];
                    $details = [
                        'position' => input('position'), 'salary' => input('salary', '0'), 'rating' => input('rating', '5'),
                        'work_start' => input('work_start', '08:00'), 'work_end' => input('work_end', '19:00'),
                        'work_days' => array_map('intval', (array) ($_POST['work_days'] ?? [])), 'bio' => input('bio'),
                    ];
                    $avatar = upload_image('avatar', 'avatars');
                    Database::begin();
                    if ($id) {
                        $barber = Barber::findBarber($id);
                        if (!$barber) {
                            throw new ValidationException(['Staff member not found.']);
                        }
                        $barber->updateProfile($data);
                    } else {
                        $id = User::create($data, 'staff'); // staff accounts are created by the owner only
                        $barber = Barber::findBarber($id);
                    }
                    $barber->updateProfileDetails($details);
                    if ($avatar) {
                        delete_upload($barber->getAvatar());
                        $barber->setAvatar($avatar);
                    }
                    Database::commit();
                    flash('success', input('staff_id') ? 'Staff details updated.' : 'Staff account created. They can now sign in as ' . $data['username'] . '.');
                } elseif ($action === 'password') {
                    $barber = Barber::findBarber((int) input('staff_id'));
                    if ($barber) {
                        $barber->setPassword((string) ($_POST['password'] ?? ''));
                        flash('success', 'Password reset for ' . $barber->getName() . '.');
                    }
                } elseif ($action === 'toggle') {
                    $barber = Barber::findBarber((int) input('staff_id'));
                    if ($barber) {
                        $new = $barber->isActive() ? 'inactive' : 'active';
                        Database::query('UPDATE users SET status = ?, remember_token = NULL WHERE id = ?', [$new, $barber->getId()]);
                        flash('success', $barber->getName() . ($new === 'active' ? ' can sign in again.' : ' was deactivated and can no longer sign in or take bookings.'));
                    }
                } elseif ($action === 'delete') {
                    $barber = Barber::findBarber((int) input('staff_id'));
                    if ($barber) {
                        $used = (int) Database::value('SELECT COUNT(*) FROM appointments WHERE barber_id = ?', [$barber->getId()]);
                        if ($used) {
                            throw new ValidationException([$barber->getName() . ' has ' . $used . ' appointment records. Deactivate the account instead to keep the history.']);
                        }
                        delete_upload($barber->getAvatar());
                        Database::query('DELETE FROM users WHERE id = ?', [$barber->getId()]);
                        flash('success', 'Staff account deleted.');
                    }
                }
            } catch (ValidationException $e) {
                Database::rollBack();
                flash('error', $e->getErrors());
                if ($action === 'save') {
                    keep_old($_POST + ['_modal' => 'staffModal']);
                }
            }
            return go('owner/staff');
        }

        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $staff = array_map(fn($r) => new Barber($r), Database::all("SELECT * FROM users WHERE role = 'staff' ORDER BY status, id"));
        $stats = [];
        foreach (Database::all(
            "SELECT barber_id,
                    SUM(appointment_date = ? AND status <> 'cancelled') AS today_cnt,
                    SUM(CASE WHEN appointment_date = ? AND status <> 'cancelled' THEN TIME_TO_SEC(TIMEDIFF(end_time, start_time)) / 60 ELSE 0 END) AS today_min,
                    SUM(appointment_date >= ? AND status = 'completed') AS month_cnt,
                    SUM(CASE WHEN appointment_date >= ? AND status = 'completed' THEN total_price ELSE 0 END) AS month_total,
                    SUM(appointment_date >= ? AND status IN ('pending','confirmed')) AS upcoming
             FROM appointments GROUP BY barber_id",
            [$today, $today, $monthStart, $monthStart, $today]
        ) as $r) {
            $stats[(int) $r['barber_id']] = $r;
        }
        $reopen = old('_modal') === 'staffModal';
        $days = day_names();

        $pageTitle = 'Staff';
        $active = 'staff';
        return $this->render('owner/staff', get_defined_vars());
    }
}
