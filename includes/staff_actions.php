<?php
/**
 * Staff actions on their own appointments: start, complete, add a visit note.
 * Staff may only touch appointments assigned to them.
 */
function handle_staff_appointment_action(Barber $barber): void
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
                Notification::toOwners('Appointment completed', $client->getName() . ' — ' . $label . ' was completed by ' . $barber->getName() . '.', 'owner/appointments.php');
                Notification::toClient($appt->getClientId(), 'Thank you for visiting!', 'Your ' . $appt->getService()->name . ' with ' . $barber->getName() . ' is complete.', 'client/history.php');
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
