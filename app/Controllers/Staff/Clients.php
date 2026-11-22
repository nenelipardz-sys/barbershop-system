<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use Database;

/**
 * Clients this barber has served or will serve. Profiles open in staff/client_view (shared with the owner).
 */
class Clients extends BaseController
{
    public function index()
    {
        $user = $this->user;

        $q = input('q');
        $params = [$user->getId()];
        $search = '';
        if ($q !== '') {
            $search = ' AND (c.name LIKE ? OR c.phone LIKE ?)';
            array_push($params, "%$q%", "%$q%");
        }
        $total = (int) Database::value("SELECT COUNT(DISTINCT c.id) FROM clients c JOIN appointments a ON a.client_id = c.id WHERE a.barber_id = ?$search", $params);
        [$offset, $pages, $page] = paginate($total, 15);
        $rows = Database::all(
            "SELECT c.id, c.name, c.phone, c.avatar, c.discount_type,
                    SUM(a.status = 'completed') AS visits,
                    MAX(CASE WHEN a.status = 'completed' THEN a.appointment_date END) AS last_visit,
                    MIN(CASE WHEN a.status IN ('pending','confirmed') AND a.appointment_date >= CURDATE() THEN a.appointment_date END) AS next_visit,
                    (SELECT COUNT(*) FROM client_notes n WHERE n.client_id = c.id) AS notes
             FROM clients c JOIN appointments a ON a.client_id = c.id
             WHERE a.barber_id = ?$search
             GROUP BY c.id, c.name, c.phone, c.avatar, c.discount_type
             ORDER BY last_visit DESC LIMIT $offset, 15",
            $params
        );

        $pageTitle = 'Clients';
        $active = 'clients';
        return $this->render('staff/clients', get_defined_vars());
    }
}
