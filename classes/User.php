<?php
/**
 * User — base class for every account. Owner, Barber and ClientUser extend it,
 * sharing login features while exposing different permissions.
 */
abstract class User
{
    protected int $id;
    protected string $name;
    protected string $username;
    protected string $email;
    protected ?string $phone;
    protected string $role;
    protected string $status;
    protected ?string $avatar;
    protected string $createdAt;
    private string $passwordHash;

    public function __construct(array $row)
    {
        $this->id           = (int) $row['id'];
        $this->name         = $row['name'];
        $this->username     = $row['username'];
        $this->email        = $row['email'];
        $this->phone        = $row['phone'] ?? null;
        $this->role         = $row['role'];
        $this->status       = $row['status'] ?? 'active';
        $this->avatar       = $row['avatar'] ?? null;
        $this->createdAt    = $row['created_at'] ?? date('Y-m-d H:i:s');
        $this->passwordHash = $row['password'] ?? '';
    }

    /** Factory: build the right subclass for a database row. */
    public static function fromRow(array $row): User
    {
        switch ($row['role']) {
            case 'owner':
                return new Owner($row);
            case 'staff':
                return new Barber($row);
            default:
                return new ClientUser($row);
        }
    }

    public static function find(int $id): ?User
    {
        $row = Database::one('SELECT * FROM users WHERE id = ?', [$id]);
        return $row ? self::fromRow($row) : null;
    }

    public static function findByLogin(string $login): ?User
    {
        $row = Database::one('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1', [$login, $login]);
        return $row ? self::fromRow($row) : null;
    }

    public static function usernameTaken(string $username, int $exceptId = 0): bool
    {
        return (bool) Database::value('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?', [$username, $exceptId]);
    }

    public static function emailTaken(string $email, int $exceptId = 0): bool
    {
        return (bool) Database::value('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', [$email, $exceptId]);
    }

    /** Create a login account. Returns the new user id. */
    public static function create(array $data, string $role): int
    {
        $errors = self::validateAccount($data, 0, true);
        if ($errors) {
            throw new ValidationException($errors);
        }
        Database::query(
            'INSERT INTO users (name, username, email, phone, password, role, status, avatar) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                trim($data['name']), trim($data['username']), strtolower(trim($data['email'])),
                trim($data['phone'] ?? '') ?: null, password_hash($data['password'], PASSWORD_DEFAULT),
                $role, $data['status'] ?? 'active', $data['avatar'] ?? null,
            ]
        );
        return Database::lastId();
    }

    public static function validateAccount(array $data, int $exceptId = 0, bool $needPassword = false): array
    {
        $errors = [];
        $name = trim($data['name'] ?? '');
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');

        if ($name === '' || mb_strlen($name) < 2) {
            $errors[] = 'Please enter a full name.';
        }
        if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) {
            $errors[] = 'Username must be 3–30 characters (letters, numbers, dot or underscore).';
        } elseif (self::usernameTaken($username, $exceptId)) {
            $errors[] = 'That username is already taken.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (self::emailTaken($email, $exceptId)) {
            $errors[] = 'That email is already registered.';
        }
        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            $errors[] = 'Please enter a valid phone number.';
        }
        if ($needPassword) {
            $errors = array_merge($errors, self::validatePassword($data['password'] ?? '', $data['password_confirm'] ?? null));
        }
        return $errors;
    }

    public static function validatePassword(string $password, ?string $confirm = null): array
    {
        $errors = [];
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if ($confirm !== null && $password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
        return $errors;
    }

    // ----- Getters (encapsulation: no direct property access) -----
    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getFirstName(): string { return explode(' ', trim($this->name))[0]; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getRole(): string { return $this->role; }
    public function getStatus(): string { return $this->status; }
    public function getAvatar(): ?string { return $this->avatar; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function isActive(): bool { return $this->status === 'active'; }

    public function verifyPassword(string $plain): bool
    {
        return password_verify($plain, $this->passwordHash);
    }

    public function setPassword(string $new): void
    {
        $errors = self::validatePassword($new);
        if ($errors) {
            throw new ValidationException($errors);
        }
        $this->passwordHash = password_hash($new, PASSWORD_DEFAULT);
        Database::query('UPDATE users SET password = ?, remember_token = NULL WHERE id = ?', [$this->passwordHash, $this->id]);
    }

    public function changePassword(string $current, string $new, string $confirm): void
    {
        if (!$this->verifyPassword($current)) {
            throw new ValidationException(['Your current password is incorrect.']);
        }
        $errors = self::validatePassword($new, $confirm);
        if ($errors) {
            throw new ValidationException($errors);
        }
        $this->setPassword($new);
    }

    public function updateProfile(array $data): void
    {
        $data['username'] = $data['username'] ?? $this->username;
        $errors = self::validateAccount($data, $this->id);
        if ($errors) {
            throw new ValidationException($errors);
        }
        $this->name = trim($data['name']);
        $this->username = trim($data['username']);
        $this->email = strtolower(trim($data['email']));
        $this->phone = trim($data['phone'] ?? '') ?: null;
        Database::query(
            'UPDATE users SET name = ?, username = ?, email = ?, phone = ? WHERE id = ?',
            [$this->name, $this->username, $this->email, $this->phone, $this->id]
        );
    }

    public function setAvatar(?string $path): void
    {
        $this->avatar = $path;
        Database::query('UPDATE users SET avatar = ? WHERE id = ?', [$path, $this->id]);
    }

    /** Appointments visible to this user on a date (base: the whole shop). */
    public function appointmentsOn(string $date): array
    {
        return Database::all(
            "SELECT a.*, c.name AS client_name, c.avatar AS client_avatar, s.name AS service_name, s.image AS service_image,
                    u.name AS barber_name, u.avatar AS barber_avatar
             FROM appointments a
             JOIN clients c ON c.id = a.client_id
             JOIN services s ON s.id = a.service_id
             JOIN users u ON u.id = a.barber_id
             WHERE a.appointment_date = ?
             ORDER BY a.start_time",
            [$date]
        );
    }

    /** Only barbers can take bookings; subclasses override. */
    public function isAvailable(string $date, string $start, string $end, ?int $excludeAppointmentId = null): bool
    {
        return false;
    }

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    abstract public function homeUrl(): string;

    abstract public function roleLabel(): string;

    abstract protected function permissions(): array;
}
