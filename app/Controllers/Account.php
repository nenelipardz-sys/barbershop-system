<?php

namespace App\Controllers;

use Auth;
use ClientProfile;
use Database;
use Notification;
use User;
use ValidationException;

/**
 * Account — sign in, register, reset password and sign out.
 */
class Account extends BaseController
{
    public function login()
    {
        // Already signed in? Go straight to the right home page.
        if ($this->user) {
            return go($this->user->homeUrl());
        }

        $errors = [];
        if (is_post()) {
            try {
                $user = Auth::attempt(input('login'), (string) ($_POST['password'] ?? ''), !empty($_POST['remember']));
                flash('success', greeting() . ', ' . $user->getFirstName() . '! Welcome back.');
                return go($user->homeUrl()); // the system checks the role and redirects
            } catch (ValidationException $e) {
                $errors = $e->getErrors();
                keep_old(['login' => input('login'), 'remember' => !empty($_POST['remember'])]);
            }
        }

        $authTitle = 'Sign In';
        return $this->render('account/login', compact('errors', 'authTitle'));
    }

    public function register()
    {
        if ($this->user) {
            return go($this->user->homeUrl());
        }

        $errors = [];
        if (is_post()) {
            $data = [
                'name' => input('name'), 'username' => input('username'), 'email' => input('email'), 'phone' => input('phone'),
                'password' => (string) ($_POST['password'] ?? ''), 'password_confirm' => (string) ($_POST['password_confirm'] ?? ''),
            ];
            if (empty($_POST['terms'])) {
                $errors[] = 'Please agree to the shop policies to continue.';
            }
            if (!$errors) {
                try {
                    Database::begin();
                    // Clients register themselves; the role is always "client".
                    $userId = User::create($data, 'client');
                    $user = User::find($userId);
                    // Link to an existing walk-in profile with the same email, otherwise create one.
                    $existing = Database::one('SELECT id FROM clients WHERE email = ? AND user_id IS NULL LIMIT 1', [strtolower($data['email'])]);
                    if ($existing) {
                        Database::query('UPDATE clients SET user_id = ?, name = ?, phone = COALESCE(?, phone) WHERE id = ?', [$userId, $data['name'], $data['phone'] ?: null, $existing['id']]);
                    } else {
                        ClientProfile::createForUser($user);
                    }
                    Database::commit();
                    Notification::toOwners('New client registered', $data['name'] . ' created a client account.', 'owner/clients');
                    Auth::login($user);
                    flash('success', 'Welcome to Lumié Talent, ' . $user->getFirstName() . '! Your account is ready.');
                    return go($user->homeUrl());
                } catch (ValidationException $e) {
                    Database::rollBack();
                    $errors = $e->getErrors();
                }
            }
            keep_old($data);
        }

        $authTitle = 'Create Account';
        $compact = true;
        return $this->render('account/register', compact('errors', 'authTitle', 'compact'));
    }

    /*
     * Two-step reset without email:
     *  1) verify the account with email/username + the registered phone number
     *  2) set a new password
     */
    public function forgotPassword()
    {
        if ($this->user) {
            return go($this->user->homeUrl());
        }

        $errors = [];
        $step = isset($_SESSION['reset_user']) ? 2 : 1;

        if (is_post()) {
            if (input('action') === 'cancel') {
                unset($_SESSION['reset_user']);
                return go('forgot_password');
            }
            if ($step === 1) {
                $login = input('login');
                $phone = preg_replace('/\D+/', '', input('phone'));
                $user = $login !== '' ? User::findByLogin($login) : null;
                if ($login === '' || $phone === '') {
                    $errors[] = 'Please enter your email/username and phone number.';
                } elseif (!$user || preg_replace('/\D+/', '', (string) $user->getPhone()) !== $phone) {
                    $errors[] = 'We could not verify those details. Please check and try again, or contact the shop.';
                } elseif (!$user->isActive()) {
                    $errors[] = 'This account is inactive. Please contact the shop owner.';
                } else {
                    $_SESSION['reset_user'] = $user->getId();
                    $_SESSION['reset_expires'] = time() + 600;
                    return go('forgot_password');
                }
                keep_old(['login' => $login]);
            } else {
                if (($_SESSION['reset_expires'] ?? 0) < time()) {
                    unset($_SESSION['reset_user']);
                    flash('error', 'Your reset session expired. Please verify your account again.');
                    return go('forgot_password');
                }
                $errors = User::validatePassword((string) ($_POST['password'] ?? ''), (string) ($_POST['password_confirm'] ?? ''));
                if (!$errors) {
                    $user = User::find((int) $_SESSION['reset_user']);
                    $user->setPassword((string) $_POST['password']);
                    unset($_SESSION['reset_user'], $_SESSION['reset_expires']);
                    flash('success', 'Your password has been reset. You can now sign in.');
                    return go('login');
                }
            }
        }

        $authTitle = 'Reset Password';
        $compact = true;
        return $this->render('account/forgot_password', compact('errors', 'step', 'authTitle', 'compact'));
    }

    public function logout()
    {
        // Logout links carry the CSRF token so other sites cannot sign users out.
        if (hash_equals(csrf_hash(), (string) ($_GET['t'] ?? ''))) {
            Auth::logout();
            flash('success', 'You have been signed out.');
        }
        return go('login');
    }
}
