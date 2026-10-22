<?php
/**
 * Owner — full system access.
 */
class Owner extends User
{
    public function homeUrl(): string
    {
        return 'owner/dashboard.php';
    }

    public function roleLabel(): string
    {
        return 'Owner';
    }

    protected function permissions(): array
    {
        return [
            'appointments.manage', 'appointments.approve', 'clients.manage', 'staff.manage',
            'services.manage', 'reports.view', 'settings.manage', 'salary.view',
        ];
    }

    /** The owner has every permission. */
    public function can(string $permission): bool
    {
        return true;
    }
}
