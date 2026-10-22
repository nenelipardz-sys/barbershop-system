<?php
/**
 * Schedule — computes open time slots for a barber, service and date.
 */
class Schedule
{
    public static function isShopOpen(string $date): bool
    {
        return in_array((int) date('N', strtotime($date)), Settings::openDays(), true);
    }

    public static function interval(): int
    {
        return max(10, (int) Settings::get('slot_interval', 30));
    }

    /**
     * @return array<int, array{time:string,label:string,available:bool}>
     */
    public static function availableSlots(Barber $barber, Service $service, string $date, ?int $excludeAppointmentId = null): array
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date || $date < date('Y-m-d') || !self::isShopOpen($date) || !$barber->worksOn($date)) {
            return [];
        }
        $open = max(strtotime("$date " . Settings::get('open_time', '08:00')), strtotime("$date " . $barber->getWorkStart()));
        $close = min(strtotime("$date " . Settings::get('close_time', '19:00')), strtotime("$date " . $barber->getWorkEnd()));
        $step = self::interval() * 60;
        $length = $service->duration * 60;

        $slots = [];
        for ($t = $open; $t + $length <= $close; $t += $step) {
            $start = date('H:i:s', $t);
            $end = date('H:i:s', $t + $length);
            $available = $t > time() && $barber->isAvailable($date, $start, $end, $excludeAppointmentId);
            $slots[] = ['time' => date('H:i', $t), 'label' => date('g:i A', $t), 'available' => $available];
        }
        return $slots;
    }

    /** Monday-based week containing $date. */
    public static function weekDates(string $date): array
    {
        $monday = strtotime('monday this week', strtotime($date));
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = date('Y-m-d', strtotime("+$i day", $monday));
        }
        return $days;
    }
}
