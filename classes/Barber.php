<?php
/**
 * Barber (staff) — views assigned schedules, records completed services and client notes.
 */
class Barber extends User
{
    private float $salary = 0;
    protected string $position = 'Barber';
    protected float $rating = 5.0;
    protected string $workStart = '08:00:00';
    protected string $workEnd = '19:00:00';
    protected array $workDays = [1, 2, 3, 4, 5, 6];
    protected ?string $bio = null;

    public function __construct(array $row)
    {
        parent::__construct($row);
        $profile = Database::one('SELECT * FROM staff_profiles WHERE user_id = ?', [$this->id]);
        if ($profile) {
            $this->salary    = (float) $profile['salary'];
            $this->position  = $profile['position'];
            $this->rating    = (float) $profile['rating'];
            $this->workStart = $profile['work_start'];
            $this->workEnd   = $profile['work_end'];
            $this->workDays  = array_map('intval', array_filter(explode(',', $profile['work_days']), 'strlen'));
            $this->bio       = $profile['bio'];
        }
    }

    /** All active barbers. */
    public static function allActive(): array
    {
        $rows = Database::all("SELECT * FROM users WHERE role = 'staff' AND status = 'active' ORDER BY id");
        return array_map(fn($r) => new Barber($r), $rows);
    }

    public static function findBarber(int $id): ?Barber
    {
        $row = Database::one("SELECT * FROM users WHERE id = ? AND role = 'staff'", [$id]);
        return $row ? new Barber($row) : null;
    }

    public function homeUrl(): string
    {
        return 'staff/dashboard.php';
    }

    public function roleLabel(): string
    {
        return $this->position;
    }

    protected function permissions(): array
    {
        return ['schedule.view', 'appointments.update_status', 'clients.notes', 'services.view', 'reports.own'];
    }

    public function getPosition(): string { return $this->position; }
    public function getRating(): float { return $this->rating; }
    public function getWorkStart(): string { return $this->workStart; }
    public function getWorkEnd(): string { return $this->workEnd; }
    public function getWorkDays(): array { return $this->workDays; }
    public function getBio(): ?string { return $this->bio; }

    /** Salary is private; only the owner may read it. */
    public function getSalary(User $viewer): ?float
    {
        return $viewer->can('salary.view') ? $this->salary : null;
    }

    public function worksOn(string $date): bool
    {
        return in_array((int) date('N', strtotime($date)), $this->workDays, true);
    }

    /** Overridden: a barber only sees the appointments assigned to them. */
    public function appointmentsOn(string $date): array
    {
        return Database::all(
            "SELECT a.*, c.name AS client_name, c.avatar AS client_avatar, s.name AS service_name, s.image AS service_image,
                    u.name AS barber_name, u.avatar AS barber_avatar
             FROM appointments a
             JOIN clients c ON c.id = a.client_id
             JOIN services s ON s.id = a.service_id
             JOIN users u ON u.id = a.barber_id
             WHERE a.appointment_date = ? AND a.barber_id = ?
             ORDER BY a.start_time",
            [$date, $this->id]
        );
    }

    /** Overridden availability check: working day, working hours and no overlapping booking. */
    public function isAvailable(string $date, string $start, string $end, ?int $excludeAppointmentId = null): bool
    {
        if (!$this->isActive() || !$this->worksOn($date)) {
            return false;
        }
        if (strtotime("$date $start") < strtotime("$date {$this->workStart}") || strtotime("$date $end") > strtotime("$date {$this->workEnd}")) {
            return false;
        }
        $overlap = Database::value(
            "SELECT COUNT(*) FROM appointments
             WHERE barber_id = ? AND appointment_date = ? AND status IN ('pending','confirmed','in_progress')
               AND start_time < ? AND end_time > ? AND id <> ?",
            [$this->id, $date, $end, $start, $excludeAppointmentId ?? 0]
        );
        return (int) $overlap === 0;
    }

    public function updateProfileDetails(array $data): void
    {
        $position = trim($data['position'] ?? $this->position) ?: 'Barber';
        $salary = (float) ($data['salary'] ?? $this->salary);
        $rating = min(5, max(0, (float) ($data['rating'] ?? $this->rating)));
        $start = $data['work_start'] ?? substr($this->workStart, 0, 5);
        $end = $data['work_end'] ?? substr($this->workEnd, 0, 5);
        $days = $data['work_days'] ?? $this->workDays;

        $errors = [];
        if ($salary < 0) {
            $errors[] = 'Salary cannot be negative.';
        }
        if (strtotime($start) >= strtotime($end)) {
            $errors[] = 'Work end time must be after the start time.';
        }
        if (!$days) {
            $errors[] = 'Select at least one working day.';
        }
        if ($errors) {
            throw new ValidationException($errors);
        }
        Database::query(
            'INSERT INTO staff_profiles (user_id, position, salary, rating, work_start, work_end, work_days, bio)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE position = VALUES(position), salary = VALUES(salary), rating = VALUES(rating),
               work_start = VALUES(work_start), work_end = VALUES(work_end), work_days = VALUES(work_days), bio = VALUES(bio)',
            [$this->id, $position, $salary, $rating, $start, $end, implode(',', array_map('intval', $days)), trim($data['bio'] ?? $this->bio ?? '') ?: null]
        );
    }
}
