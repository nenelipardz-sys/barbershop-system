<?php
/**
 * ClientUser — a registered client who books and manages their own appointments.
 */
class ClientUser extends User
{
    private ?ClientProfile $profile = null;

    public function homeUrl(): string
    {
        return 'client/home.php';
    }

    public function roleLabel(): string
    {
        return 'Client';
    }

    protected function permissions(): array
    {
        return ['appointments.book', 'appointments.own', 'favorites.manage', 'profile.manage'];
    }

    /** The client profile linked to this account (created on demand). */
    public function profile(): ClientProfile
    {
        if ($this->profile === null) {
            $this->profile = ClientProfile::findByUser($this->id);
            if ($this->profile === null) {
                $this->profile = ClientProfile::createForUser($this);
            }
        }
        return $this->profile;
    }

    public function clientId(): int
    {
        return (int) $this->profile()->getId();
    }
}
