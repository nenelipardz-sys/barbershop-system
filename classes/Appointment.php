<?php
/**
 * Appointment — a booking of one service with one barber for one client.
 * New appointments always start as 'pending' with the current date captured.
 */
class Appointment extends Record implements Scheduleable
{
    public const STATUSES = [
        'pending'     => 'Pending',
        'confirmed'   => 'Confirmed',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ];
    public const ACTIVE = ['pending', 'confirmed', 'in_progress'];

    private int $clientId;
    private int $barberId;
    private Service $service;
    private string $date;
    private string $startTime;
    private string $endTime;
    private string $status;
    private Fee $fee;
    private float $basePrice;
    private ?string $notes;
    private ?int $createdBy;
    private ?string $completedAt = null;
    private bool $scheduleChanged = true;

    public function __construct(int $clientId, int $barberId, Service $service, string $date, string $startTime, ?Fee $fee = null, ?string $notes = null, ?int $createdBy = null)
    {
        parent::__construct();
        $this->status    = 'pending';
        $this->clientId  = $clientId;
        $this->barberId  = $barberId;
        $this->service   = $service;
        $this->date      = $date;
        $this->startTime = self::normalizeTime($startTime);
        $this->fee       = $fee ?? new StandardRate();
        $this->basePrice = $service->price;
        $this->notes     = $notes !== null && trim($notes) !== '' ? trim($notes) : null;
        $this->createdBy = $createdBy;
        $this->computeEnd();
    }

    protected static function table(): string
    {
        return 'appointments';
    }

    public static function find(int $id): ?Appointment
    {
        $row = Database::one('SELECT * FROM appointments WHERE id = ?', [$id]);
        if (!$row) {
            return null;
        }
        $service = Service::find((int) $row['service_id']);
        $a = new Appointment((int) $row['client_id'], (int) $row['barber_id'], $service, $row['appointment_date'], $row['start_time'],
            Fee::for($row['discount_type']), $row['notes'], $row['created_by'] !== null ? (int) $row['created_by'] : null);
        $a->id = (int) $row['id'];
        $a->status = $row['status'];
        $a->endTime = $row['end_time'];
        $a->basePrice = (float) $row['base_price'];
        $a->completedAt = $row['completed_at'];
        $a->createdAt = $row['created_at'];
        $a->updatedAt = $row['updated_at'];
        $a->scheduleChanged = false;
        return $a;
    }

    private static function normalizeTime(string $time): string
    {
        $ts = strtotime($time);
        return $ts === false ? '00:00:00' : date('H:i:s', $ts);
    }

    private function computeEnd(): void
    {
        $this->endTime = date('H:i:s', strtotime('2000-01-01 ' . $this->startTime) + $this->service->duration * 60);
    }

    // ----- Validation -----
    public function validate(): array
    {
        $errors = [];
        if (!Database::value('SELECT COUNT(*) FROM clients WHERE id = ?', [$this->clientId])) {
            $errors[] = 'Please select a valid client.';
        }
        $barber = Barber::findBarber($this->barberId);
        if (!$barber || !$barber->isActive()) {
            $errors[] = 'Please select an available barber.';
        }
        if ($this->scheduleChanged && !$this->service->isActive()) {
            $errors[] = 'The selected service is no longer offered.';
        }
        $d = DateTime::createFromFormat('Y-m-d', $this->date);
        if (!$d || $d->format('Y-m-d') !== $this->date) {
            $errors[] = 'Please choose a valid date.';
            return $errors;
        }
        if ($this->notes !== null && mb_strlen($this->notes) > 500) {
            $errors[] = 'Notes must be 500 characters or fewer.';
        }
        if (!$this->scheduleChanged) {
            return $errors;
        }

        $start = strtotime("{$this->date} {$this->startTime}");
        if ($this->date < date('Y-m-d')) {
            $errors[] = 'You cannot book an appointment on a past date.';
        } elseif ($start <= time()) {
            $errors[] = 'That time has already passed. Please choose a later time slot.';
        }
        if (!in_array((int) date('N', strtotime($this->date)), Settings::openDays(), true)) {
            $errors[] = 'The shop is closed on ' . date('l', strtotime($this->date)) . 's.';
        }
        $open = Settings::get('open_time', '08:00');
        $close = Settings::get('close_time', '19:00');
        if ($this->startTime < date('H:i:s', strtotime($open)) || $this->endTime > date('H:i:s', strtotime($close)) || $this->endTime <= $this->startTime) {
            $errors[] = 'Appointments must be within shop hours (' . fmt_time($open) . ' – ' . fmt_time($close) . ').';
        }
        if ($barber && !$errors) {
            if (!$barber->worksOn($this->date)) {
                $errors[] = $barber->getName() . ' is not working on that day.';
            } elseif ($this->startTime < $barber->getWorkStart() || $this->endTime > $barber->getWorkEnd()) {
                $errors[] = $barber->getName() . ' works from ' . fmt_time($barber->getWorkStart()) . ' to ' . fmt_time($barber->getWorkEnd()) . '.';
            }
        }
        return $errors;
    }

    public function hasConflict(): bool
    {
        return $this->barberConflict() || $this->clientConflict();
    }

    private function barberConflict(): bool
    {
        return (bool) Database::value(
            "SELECT COUNT(*) FROM appointments WHERE barber_id = ? AND appointment_date = ? AND status IN ('pending','confirmed','in_progress')
             AND start_time < ? AND end_time > ? AND id <> ?",
            [$this->barberId, $this->date, $this->endTime, $this->startTime, $this->id ?? 0]
        );
    }

    private function clientConflict(): bool
    {
        return (bool) Database::value(
            "SELECT COUNT(*) FROM appointments WHERE client_id = ? AND appointment_date = ? AND status IN ('pending','confirmed','in_progress')
             AND start_time < ? AND end_time > ? AND id <> ?",
            [$this->clientId, $this->date, $this->endTime, $this->startTime, $this->id ?? 0]
        );
    }

    private function assertValid(): void
    {
        $errors = $this->validate();
        if (!$errors && $this->scheduleChanged) {
            if ($this->barberConflict()) {
                $errors[] = 'Schedule conflict: the barber already has a booking from ' . fmt_time($this->startTime) . ' to ' . fmt_time($this->endTime) . '. Please pick another time slot.';
            } elseif ($this->clientConflict()) {
                $errors[] = 'Schedule conflict: this client already has an appointment at that time.';
            }
        }
        if ($errors) {
            throw new ValidationException($errors);
        }
    }

    // ----- Scheduleable -----
    public function book(): int
    {
        if ($this->id !== null) {
            throw new LogicException('Appointment already booked.');
        }
        $this->assertValid();
        Database::query(
            'INSERT INTO appointments (client_id, barber_id, service_id, appointment_date, start_time, end_time, status, base_price,
             discount_type, discount_amount, total_price, notes, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$this->clientId, $this->barberId, $this->service->id, $this->date, $this->startTime, $this->endTime, $this->status,
             $this->basePrice, $this->fee->type(), $this->getDiscountAmount(), $this->getTotal(), $this->notes, $this->createdBy, $this->createdAt]
        );
        $this->id = Database::lastId();
        $this->scheduleChanged = false;
        return $this->id;
    }

    public function reschedule(string $date, string $startTime, ?int $barberId = null): void
    {
        if (!in_array($this->status, ['pending', 'confirmed'], true)) {
            throw new ValidationException(['Only pending or confirmed appointments can be rescheduled.']);
        }
        $this->date = $date;
        $this->startTime = self::normalizeTime($startTime);
        if ($barberId) {
            $this->barberId = $barberId;
        }
        $this->computeEnd();
        $this->scheduleChanged = true;
        $this->status = 'pending'; // schedule changes need the owner's approval
        $this->save();
    }

    public function cancel(): void
    {
        if (!in_array($this->status, ['pending', 'confirmed'], true)) {
            throw new ValidationException(['This appointment can no longer be cancelled.']);
        }
        $this->updateStatus('cancelled');
    }

    // ----- Status workflow -----
    public function confirm(): void
    {
        if ($this->status !== 'pending') {
            throw new ValidationException(['Only pending appointments can be confirmed.']);
        }
        $this->updateStatus('confirmed');
    }

    public function start(): void
    {
        if (!in_array($this->status, ['pending', 'confirmed'], true)) {
            throw new ValidationException(['Only upcoming appointments can be started.']);
        }
        if ($this->date !== date('Y-m-d')) {
            throw new ValidationException(['You can only start appointments scheduled for today.']);
        }
        $this->updateStatus('in_progress');
    }

    public function complete(): void
    {
        if (!in_array($this->status, ['confirmed', 'in_progress', 'pending'], true)) {
            throw new ValidationException(['This appointment cannot be marked as completed.']);
        }
        if ($this->date > date('Y-m-d')) {
            throw new ValidationException(['You cannot complete an appointment scheduled in the future.']);
        }
        $this->completedAt = date('Y-m-d H:i:s');
        $this->updateStatus('completed');
    }

    private function updateStatus(string $status): void
    {
        $this->status = $status;
        $this->touch();
        Database::query(
            'UPDATE appointments SET status = ?, completed_at = ? WHERE id = ?',
            [$this->status, $this->status === 'completed' ? $this->completedAt : null, $this->id]
        );
    }

    /** Persist changes to an existing appointment. */
    public function save(): void
    {
        if ($this->id === null) {
            $this->book();
            return;
        }
        $this->assertValid();
        $this->touch();
        Database::query(
            'UPDATE appointments SET client_id = ?, barber_id = ?, service_id = ?, appointment_date = ?, start_time = ?, end_time = ?,
             status = ?, base_price = ?, discount_type = ?, discount_amount = ?, total_price = ?, notes = ? WHERE id = ?',
            [$this->clientId, $this->barberId, $this->service->id, $this->date, $this->startTime, $this->endTime, $this->status,
             $this->basePrice, $this->fee->type(), $this->getDiscountAmount(), $this->getTotal(), $this->notes, $this->id]
        );
        $this->scheduleChanged = false;
    }

    // ----- Editing -----
    public function changeService(Service $service): void
    {
        if ($service->id !== $this->service->id) {
            $this->service = $service;
            $this->basePrice = $service->price;
            $this->computeEnd();
            $this->scheduleChanged = true;
        }
    }

    public function setFee(Fee $fee): void
    {
        $this->fee = $fee;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes !== null && trim($notes) !== '' ? trim($notes) : null;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true);
    }

    // ----- Getters -----
    public function getClientId(): int { return $this->clientId; }
    public function getBarberId(): int { return $this->barberId; }
    public function getService(): Service { return $this->service; }
    public function getDate(): string { return $this->date; }
    public function getStartTime(): string { return $this->startTime; }
    public function getEndTime(): string { return $this->endTime; }
    public function getStatus(): string { return $this->status; }
    public function getNotes(): ?string { return $this->notes; }
    public function getFee(): Fee { return $this->fee; }
    public function getBasePrice(): float { return $this->basePrice; }
    public function getTotal(): float { return $this->fee->calculate($this->basePrice); }
    public function getDiscountAmount(): float { return $this->fee->discount($this->basePrice); }
    public function getCompletedAt(): ?string { return $this->completedAt; }

    /** Status label; staff see pending/confirmed as "Upcoming". */
    public static function statusLabel(string $status, bool $staffView = false): string
    {
        if ($staffView && in_array($status, ['pending', 'confirmed'], true)) {
            return 'Upcoming';
        }
        return self::STATUSES[$status] ?? ucfirst($status);
    }
}
