<?php
/**
 * Notification — in-app messages shown under the bell icon.
 */
class Notification
{
    public static function send(int $userId, string $title, string $message, ?string $link = null): void
    {
        Database::query(
            'INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)',
            [$userId, mb_substr($title, 0, 120), mb_substr($message, 0, 255), $link]
        );
    }

    public static function toOwners(string $title, string $message, ?string $link = null): void
    {
        foreach (Database::all("SELECT id FROM users WHERE role = 'owner' AND status = 'active'") as $row) {
            self::send((int) $row['id'], $title, $message, $link);
        }
    }

    /** Notify the client account linked to a client profile, if any. */
    public static function toClient(int $clientId, string $title, string $message, ?string $link = 'client/appointments.php'): void
    {
        $userId = Database::value('SELECT user_id FROM clients WHERE id = ?', [$clientId]);
        if ($userId) {
            self::send((int) $userId, $title, $message, $link);
        }
    }

    public static function forUser(int $userId, int $limit = 8): array
    {
        return Database::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . (int) $limit, [$userId]);
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$userId]);
    }

    public static function markAllRead(int $userId): void
    {
        Database::query('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$userId]);
    }

    public static function markRead(int $id, int $userId): ?string
    {
        Database::query('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', [$id, $userId]);
        return Database::value('SELECT link FROM notifications WHERE id = ? AND user_id = ?', [$id, $userId]);
    }
}
