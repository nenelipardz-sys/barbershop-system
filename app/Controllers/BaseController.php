<?php

namespace App\Controllers;

use Appointment;
use Auth;
use Barber;
use ClientProfile;
use ClientUser;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Database;
use Notification;
use Psr\Log\LoggerInterface;
use User;
use ValidationException;

/**
 * BaseController — every page controller extends this.
 * Holds the signed-in user and the form actions shared by several pages.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * Signed-in user (the "role" filter has already checked it), or null for guests.
     */
    protected ?User $user = null;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        $this->user = Auth::user();
    }

    /** Render a page view; the view and its layout get every variable in $data. */
    protected function render(string $view, array $data = []): string
    {
        return view($view, $data);
    }

    /**
     * The shared "profile", "password" and "remove_avatar" POST actions for the signed-in user.
     * Returns true when the action was handled.
     */
    protected function handleProfileActions(User $user): bool
    {
        $action = input('action');
        if (!in_array($action, ['profile', 'password', 'remove_avatar'], true)) {
            return false;
        }
        try {
            if ($action === 'profile') {
                $avatar = upload_image('avatar', 'avatars');
                Database::begin();
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
                Database::commit();
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
            Database::rollBack();
            flash('error', $e->getErrors());
        }
        return true;
    }

    /**
     * Staff actions on their own appointments: start, complete, add a visit note.
     * Staff may only touch appointments assigned to them.
     */
    protected function handleStaffAppointmentAction(Barber $barber): void
    {
        $appt = Appointment::find((int) input('appointment_id'));
        if (!$appt || $appt->getBarberId() !== $barber->getId()) {
            flash('error', 'That appointment is not assigned to you.');
            return;
        }
        $label = $appt->getService()->name . ' (' . fmt_date($appt->getDate(), 'M j') . ', ' . fmt_time($appt->getStartTime()) . ')';
        $client = ClientProfile::find($appt->getClientId());
        try {
            switch (input('action')) {
                case 'start':
                    $appt->start();
                    flash('success', 'Service started for ' . $client->getName() . '.');
                    break;
                case 'complete':
                    $appt->complete();
                    if (input('note') !== '') {
                        $client->addNote(input('note'), $barber->getId(), $appt->getId());
                    }
                    Notification::toOwners('Appointment completed', $client->getName() . ' — ' . $label . ' was completed by ' . $barber->getName() . '.', 'owner/appointments');
                    Notification::toClient($appt->getClientId(), 'Thank you for visiting!', 'Your ' . $appt->getService()->name . ' with ' . $barber->getName() . ' is complete.', 'client/history');
                    flash('success', 'Service completed for ' . $client->getName() . '.');
                    break;
                case 'note':
                    $client->addNote(input('note'), $barber->getId(), $appt->getId());
                    flash('success', 'Note saved to ' . $client->getName() . '’s profile.');
                    break;
                default:
                    flash('error', 'Unknown action.');
            }
        } catch (ValidationException $e) {
            flash('error', $e->getErrors());
        }
    }
}
