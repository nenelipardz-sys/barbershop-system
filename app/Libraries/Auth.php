<?php
/**
 * Auth — login, logout, "remember me" and role checks.
 * The role always comes from the database; users can never pick it themselves.
 */
class Auth
{
    private const COOKIE = 'cs_remember';
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 60;

    private static ?User $user = null;
    private static bool $resolved = false;

    public static function attempt(string $login, string $password, bool $remember = false): User
    {
        $login = trim($login);
        $errors = [];
        if ($login === '') {
            $errors[] = 'Please enter your email or username.';
        }
        if ($password === '') {
            $errors[] = 'Please enter your password.';
        }
        if ($errors) {
            throw new ValidationException($errors);
        }

        $lock = $_SESSION['login_lock'] ?? 0;
        if ($lock > time()) {
            throw new ValidationException(['Too many failed attempts. Please try again in ' . ($lock - time()) . ' seconds.']);
        }

        $user = User::findByLogin($login);
        if (!$user || !$user->verifyPassword($password)) {
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            if ($_SESSION['login_attempts'] >= self::MAX_ATTEMPTS) {
                $_SESSION['login_lock'] = time() + self::LOCK_SECONDS;
                $_SESSION['login_attempts'] = 0;
            }
            throw new ValidationException(['Invalid email/username or password.']);
        }
        if (!$user->isActive()) {
            throw new ValidationException(['Your account is inactive. Please contact the shop owner.']);
        }
        unset($_SESSION['login_attempts'], $_SESSION['login_lock']);
        self::login($user, $remember);
        return $user;
    }

    public static function login(User $user, bool $remember = false): void
    {
        session()->regenerate(true);
        $_SESSION['user_id'] = $user->getId();
        self::$user = $user;
        self::$resolved = true;

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + REMEMBER_DAYS * 86400;
            Database::query(
                'UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?',
                [hash('sha256', $token), date('Y-m-d H:i:s', $expires), $user->getId()]
            );
            setcookie(self::COOKIE, $user->getId() . ':' . $token, [
                'expires' => $expires, 'path' => BASE_URL . '/', 'httponly' => true, 'samesite' => 'Lax',
            ]);
        }
    }

    public static function user(): ?User
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        if (!empty($_SESSION['user_id'])) {
            $user = User::find((int) $_SESSION['user_id']);
            if ($user && $user->isActive()) {
                return self::$user = $user;
            }
            unset($_SESSION['user_id']);
        }

        if (!empty($_COOKIE[self::COOKIE]) && strpos($_COOKIE[self::COOKIE], ':') !== false) {
            [$id, $token] = explode(':', $_COOKIE[self::COOKIE], 2);
            $row = Database::one(
                'SELECT * FROM users WHERE id = ? AND remember_token = ? AND remember_expires > NOW() AND status = ?',
                [(int) $id, hash('sha256', $token), 'active']
            );
            if ($row) {
                $user = User::fromRow($row);
                session()->regenerate(true);
                $_SESSION['user_id'] = $user->getId();
                return self::$user = $user;
            }
            self::forgetCookie();
        }
        return null;
    }

    public static function logout(): void
    {
        if (!empty($_SESSION['user_id'])) {
            Database::query('UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE id = ?', [(int) $_SESSION['user_id']]);
        }
        self::forgetCookie();
        // Wipe the session and move to a fresh session id.
        $_SESSION = [];
        session()->regenerate(true);
        self::$user = null;
    }

    private static function forgetCookie(): void
    {
        setcookie(self::COOKIE, '', ['expires' => time() - 3600, 'path' => BASE_URL . '/', 'httponly' => true, 'samesite' => 'Lax']);
        unset($_COOKIE[self::COOKIE]);
    }

    /** True when the user may open a page limited to the given roles (no roles = any signed-in user). */
    public static function allows(User $user, array $roles): bool
    {
        return !$roles || in_array($user->getRole(), $roles, true);
    }
}
