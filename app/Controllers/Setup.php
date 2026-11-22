<?php

namespace App\Controllers;

use mysqli;
use RuntimeException;
use Throwable;

/**
 * One-click installer: creates the `cut_schedule` database and loads the demo data
 * from database/cut_schedule.sql. (You can also import that file in phpMyAdmin.)
 * Runs without the database check filter, because the database may not exist yet.
 */
class Setup extends BaseController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Skip BaseController's user lookup: it needs the database.
        \CodeIgniter\Controller::initController($request, $response, $logger);
    }

    public function index()
    {
        $db = config('Database')->default;
        $dbName = $db['database'];
        $dbHost = $db['hostname'];
        $message = null;
        $error = null;
        $installed = false;

        try {
            $mysqli = @new mysqli($db['hostname'], $db['username'], $db['password'], '', (int) $db['port']);
            if ($mysqli->connect_errno) {
                throw new RuntimeException('Cannot connect to MySQL: ' . $mysqli->connect_error . '. Start MySQL in the XAMPP Control Panel.');
            }
            $mysqli->set_charset('utf8mb4');
            $res = $mysqli->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . $mysqli->real_escape_string($dbName) . "' AND table_name = 'users'");
            $installed = (int) $res->fetch_row()[0] > 0;

            if (is_post()) {
                if ($installed && empty($_POST['confirm_reset'])) {
                    throw new RuntimeException('Tick the confirmation box to reinstall. This erases all current data.');
                }
                $sql = file_get_contents(ROOTPATH . 'database/cut_schedule.sql');
                if (!$mysqli->multi_query($sql)) {
                    throw new RuntimeException('Import failed: ' . $mysqli->error);
                }
                do {
                    if ($r = $mysqli->store_result()) {
                        $r->free();
                    }
                } while ($mysqli->more_results() && $mysqli->next_result());
                if ($mysqli->errno) {
                    throw new RuntimeException('Import failed: ' . $mysqli->error);
                }
                $installed = true;
                $message = 'Database installed successfully with demo data.';
            }
        } catch (Throwable $t) {
            $error = $t->getMessage();
        }

        return $this->render('setup', compact('dbName', 'dbHost', 'message', 'error', 'installed'));
    }
}
