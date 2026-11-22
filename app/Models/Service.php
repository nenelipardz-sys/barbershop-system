<?php
/**
 * Service — an item in the service catalog. Fields are public for service listings.
 */
class Service
{
    public ?int $id = null;
    public string $name = '';
    public ?string $description = null;
    public float $price = 0;
    public int $duration = 30;
    public ?string $image = null;
    public string $status = 'active';
    public int $sortOrder = 0;

    public static function fromRow(array $row): Service
    {
        $s = new Service();
        $s->id = (int) $row['id'];
        $s->name = $row['name'];
        $s->description = $row['description'];
        $s->price = (float) $row['price'];
        $s->duration = (int) $row['duration'];
        $s->image = $row['image'];
        $s->status = $row['status'];
        $s->sortOrder = (int) $row['sort_order'];
        return $s;
    }

    public static function find(int $id): ?Service
    {
        $row = Database::one('SELECT * FROM services WHERE id = ?', [$id]);
        return $row ? self::fromRow($row) : null;
    }

    /** @return Service[] */
    public static function all(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM services' . ($activeOnly ? " WHERE status = 'active'" : '') . ' ORDER BY status, sort_order, name';
        return array_map([self::class, 'fromRow'], Database::all($sql));
    }

    public function fill(array $data): self
    {
        $this->name = trim($data['name'] ?? $this->name);
        $this->description = trim($data['description'] ?? '') ?: null;
        $this->price = (float) ($data['price'] ?? $this->price);
        $this->duration = (int) ($data['duration'] ?? $this->duration);
        $this->sortOrder = (int) ($data['sort_order'] ?? $this->sortOrder);
        return $this;
    }

    public function validate(): array
    {
        $errors = [];
        if ($this->name === '') {
            $errors[] = 'Service name is required.';
        }
        if ($this->price <= 0) {
            $errors[] = 'Price must be greater than zero.';
        }
        if ($this->duration < 5 || $this->duration > 240) {
            $errors[] = 'Duration must be between 5 and 240 minutes.';
        }
        $dup = Database::value('SELECT COUNT(*) FROM services WHERE name = ? AND id <> ?', [$this->name, $this->id ?? 0]);
        if ($dup) {
            $errors[] = 'A service with that name already exists.';
        }
        return $errors;
    }

    public function save(): int
    {
        $errors = $this->validate();
        if ($errors) {
            throw new ValidationException($errors);
        }
        if ($this->id === null) {
            Database::query(
                'INSERT INTO services (name, description, price, duration, image, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$this->name, $this->description, $this->price, $this->duration, $this->image, $this->status, $this->sortOrder]
            );
            $this->id = Database::lastId();
        } else {
            Database::query(
                'UPDATE services SET name = ?, description = ?, price = ?, duration = ?, image = ?, status = ?, sort_order = ? WHERE id = ?',
                [$this->name, $this->description, $this->price, $this->duration, $this->image, $this->status, $this->sortOrder, $this->id]
            );
        }
        return $this->id;
    }

    public function archive(): void
    {
        $this->status = 'archived';
        Database::query("UPDATE services SET status = 'archived' WHERE id = ?", [$this->id]);
    }

    public function restore(): void
    {
        $this->status = 'active';
        Database::query("UPDATE services SET status = 'active' WHERE id = ?", [$this->id]);
    }

    public function usageCount(): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM appointments WHERE service_id = ?', [$this->id]);
    }

    /** Price after applying a fee strategy (polymorphism). */
    public function priceFor(Fee $fee): float
    {
        return $fee->calculate($this->price);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
