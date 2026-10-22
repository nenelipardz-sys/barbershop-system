<?php
/**
 * Scheduleable — consistent booking methods for anything that occupies a time slot.
 */
interface Scheduleable
{
    /** Persist a new booking. Throws ValidationException on invalid data or conflict. */
    public function book(): int;

    /** Move the booking to a new date/time (and optionally a different barber). */
    public function reschedule(string $date, string $startTime, ?int $barberId = null): void;

    /** Cancel the booking. */
    public function cancel(): void;

    /** True when the slot overlaps another active booking. */
    public function hasConflict(): bool;
}
