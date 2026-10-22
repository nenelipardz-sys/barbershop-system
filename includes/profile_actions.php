<?php
/**
 * Handles the shared "profile" and "password" POST actions for the signed-in user.
 * Returns true when the action was handled.
 */
function handle_profile_actions(User $user): bool
{
    $action = input('action');
    if (!in_array($action, ['profile', 'password', 'remove_avatar'], true)) {
        return false;
    }
    try {
        if ($action === 'profile') {
            $pdo = Database::conn();
            $avatar = upload_image('avatar', 'avatars');
            $pdo->beginTransaction();
            $user->updateProfile([
                'name' => input('name'), 'username' => input('username'), 'email' => input('email'), 'phone' => input('phone'),
            ]);
            if ($avatar) {
                delete_upload($user->getAvatar());
                $user->setAvatar($avatar);
            }
            if ($user instanceof ClientUser) {
                $profile = $user->profile();
                $profile->fill(['name' => $user->getName(), 'email' => $user->getEmail(), 'phone' => $user->getPhone()]);
                $profile->fill(array_intersect_key($_POST, array_flip(['preferred_barber_id', 'preferred_service_id'])));
                $profile->save();
                $profile->setAvatar($user->getAvatar());
            }
            $pdo->commit();
            flash('success', 'Your profile has been updated.');
        } elseif ($action === 'remove_avatar') {
            delete_upload($user->getAvatar());
            $user->setAvatar(null);
            if ($user instanceof ClientUser) {
                $user->profile()->setAvatar(null);
            }
            flash('success', 'Profile photo removed.');
        } else {
            $user->changePassword((string) ($_POST['current_password'] ?? ''), (string) ($_POST['password'] ?? ''), (string) ($_POST['password_confirm'] ?? ''));
            flash('success', 'Your password has been changed.');
        }
    } catch (ValidationException $e) {
        if (Database::conn()->inTransaction()) {
            Database::conn()->rollBack();
        }
        flash('error', $e->getErrors());
    }
    return true;
}
