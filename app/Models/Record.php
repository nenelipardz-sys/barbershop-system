<?php
/**
 * Record — abstract base for persisted entities that carry timestamps.
 * Appointment and ClientProfile inherit from it.
 */
abstract class Record
{
    protected ?int $id = null;
    protected string $createdAt;
    protected ?string $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = date('Y-m-d H:i:s');
    }

    /** Name of the database table that stores this record. */
    abstract protected static function table(): string;

    /** Validate the record; returns a list of error messages (empty = valid). */
    abstract public function validate(): array;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    protected function touch(): void
    {
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }
        Database::query('DELETE FROM ' . static::table() . ' WHERE id = ?', [$this->id]);
        return true;
    }
}
