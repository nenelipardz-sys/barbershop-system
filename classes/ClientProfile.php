<?php
/**
 * ClientProfile — client record: contact details, preferences and visit history.
 */
class ClientProfile extends Record
{
    private ?int $userId = null;
    private string $name = '';
    private ?string $email = null;
    private ?string $phone = null;
    private string $discountType = 'none';
    private ?int $preferredBarberId = null;
    private ?int $preferredServiceId = null;
    private ?string $notes = null;
    private ?string $avatar = null;

    protected static function table(): string
    {
        return 'clients';
    }

    public static function fromRow(array $row): ClientProfile
    {
        $c = new ClientProfile();
        $c->id = (int) $row['id'];
        $c->userId = $row['user_id'] !== null ? (int) $row['user_id'] : null;
        $c->name = $row['name'];
        $c->email = $row['email'];
        $c->phone = $row['phone'];
        $c->discountType = $row['discount_type'];
        $c->preferredBarberId = $row['preferred_barber_id'] !== null ? (int) $row['preferred_barber_id'] : null;
        $c->preferredServiceId = $row['preferred_service_id'] !== null ? (int) $row['preferred_service_id'] : null;
        $c->notes = $row['notes'];
        $c->avatar = $row['avatar'];
        $c->createdAt = $row['created_at'];
        $c->updatedAt = $row['updated_at'];
        return $c;
    }

    public static function find(int $id): ?ClientProfile
    {
        $row = Database::one('SELECT * FROM clients WHERE id = ?', [$id]);
        return $row ? self::fromRow($row) : null;
    }

    public static function findByUser(int $userId): ?ClientProfile
    {
        $row = Database::one('SELECT * FROM clients WHERE user_id = ?', [$userId]);
        return $row ? self::fromRow($row) : null;
    }

    public static function createForUser(User $user): ClientProfile
    {
        $c = new ClientProfile();
        $c->fill(['name' => $user->getName(), 'email' => $user->getEmail(), 'phone' => $user->getPhone()]);
        $c->userId = $user->getId();
        $c->avatar = $user->getAvatar();
        $c->save();
        return $c;
    }

    /** Assign editable fields from form data. */
    public function fill(array $data): self
    {
        if (array_key_exists('name', $data)) $this->name = trim((string) $data['name']);
        if (array_key_exists('email', $data)) $this->email = trim((string) $data['email']) ?: null;
        if (array_key_exists('phone', $data)) $this->phone = trim((string) $data['phone']) ?: null;
        if (array_key_exists('discount_type', $data)) {
            $this->discountType = in_array($data['discount_type'], ['none', 'senior', 'pwd'], true) ? $data['discount_type'] : 'none';
        }
        if (array_key_exists('preferred_barber_id', $data)) $this->preferredBarberId = ((int) $data['preferred_barber_id']) ?: null;
        if (array_key_exists('preferred_service_id', $data)) $this->preferredServiceId = ((int) $data['preferred_service_id']) ?: null;
        if (array_key_exists('notes', $data)) $this->notes = trim((string) $data['notes']) ?: null;
        return $this;
    }

    public function validate(): array
    {
        $errors = [];
        if (mb_strlen($this->name) < 2) {
            $errors[] = 'Client name is required.';
        }
        if ($this->email === null && $this->phone === null) {
            $errors[] = 'Please provide at least a phone number or an email address.';
        }
        if ($this->email !== null && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if ($this->phone !== null && !preg_match('/^[0-9+\-\s()]{7,20}$/', $this->phone)) {
            $errors[] = 'Please enter a valid phone number.';
        }
        return $errors;
    }

    public function save(): int
    {
        $errors = $this->validate();
        if ($errors) {
            throw new ValidationException($errors);
        }
        $params = [$this->userId, $this->name, $this->email, $this->phone, $this->discountType, $this->preferredBarberId, $this->preferredServiceId, $this->notes, $this->avatar];
        if ($this->id === null) {
            Database::query(
                'INSERT INTO clients (user_id, name, email, phone, discount_type, preferred_barber_id, preferred_service_id, notes, avatar)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                $params
            );
            $this->id = Database::lastId();
        } else {
            $this->touch();
            $params[] = $this->id;
            Database::query(
                'UPDATE clients SET user_id = ?, name = ?, email = ?, phone = ?, discount_type = ?, preferred_barber_id = ?,
                 preferred_service_id = ?, notes = ?, avatar = ? WHERE id = ?',
                $params
            );
        }
        return $this->id;
    }

    public function setAvatar(?string $path): void
    {
        $this->avatar = $path;
        if ($this->id !== null) {
            Database::query('UPDATE clients SET avatar = ? WHERE id = ?', [$path, $this->id]);
        }
    }

    public function getUserId(): ?int { return $this->userId; }
    public function getName(): string { return $this->name; }
    public function getEmail(): ?string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getDiscountType(): string { return $this->discountType; }
    public function getPreferredBarberId(): ?int { return $this->preferredBarberId; }
    public function getPreferredServiceId(): ?int { return $this->preferredServiceId; }
    public function getNotes(): ?string { return $this->notes; }
    public function getAvatar(): ?string { return $this->avatar; }

    public function visitCount(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM appointments WHERE client_id = ? AND status = 'completed'", [$this->id]);
    }

    public function lastVisit(): ?string
    {
        return Database::value("SELECT MAX(appointment_date) FROM appointments WHERE client_id = ? AND status = 'completed'", [$this->id]);
    }

    public function totalSpent(): float
    {
        return (float) Database::value("SELECT COALESCE(SUM(total_price),0) FROM appointments WHERE client_id = ? AND status = 'completed'", [$this->id]);
    }

    /** Most frequently completed service (name) or null. */
    public function favoriteService(): ?array
    {
        return Database::one(
            "SELECT s.id, s.name, COUNT(*) AS times FROM appointments a JOIN services s ON s.id = a.service_id
             WHERE a.client_id = ? AND a.status = 'completed' GROUP BY s.id, s.name ORDER BY times DESC, s.sort_order LIMIT 1",
            [$this->id]
        );
    }

    public function visits(int $limit = 0): array
    {
        $sql = "SELECT a.*, s.name AS service_name, s.image AS service_image, u.name AS barber_name, u.avatar AS barber_avatar
                FROM appointments a JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id
                WHERE a.client_id = ? AND a.status = 'completed'
                ORDER BY a.appointment_date DESC, a.start_time DESC";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        return Database::all($sql, [$this->id]);
    }

    public function notesHistory(): array
    {
        return Database::all(
            'SELECT n.*, u.name AS staff_name FROM client_notes n LEFT JOIN users u ON u.id = n.staff_id
             WHERE n.client_id = ? ORDER BY n.created_at DESC',
            [$this->id]
        );
    }

    public function addNote(string $note, ?int $staffId, ?int $appointmentId = null): void
    {
        $note = trim($note);
        if ($note === '') {
            throw new ValidationException(['Note cannot be empty.']);
        }
        Database::query(
            'INSERT INTO client_notes (client_id, staff_id, appointment_id, note) VALUES (?, ?, ?, ?)',
            [$this->id, $staffId, $appointmentId, mb_substr($note, 0, 1000)]
        );
    }
}
