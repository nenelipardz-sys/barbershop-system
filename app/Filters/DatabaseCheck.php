<?php

namespace App\Filters;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Database;

/**
 * Runs before every page (except setup): starts the session and makes sure MySQL is reachable.
 * A missing database sends the user to the one-click installer.
 */
class DatabaseCheck implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        session();

        try {
            Database::conn();
        } catch (DatabaseException $e) {
            if (str_contains($e->getMessage(), 'Unknown database')) {
                return go('setup');
            }
            $msg = e($e->getMessage());
            return service('response')->setStatusCode(500)->setBody(
                "<!doctype html><meta charset='utf-8'><title>Database error</title>
                 <div style='font-family:system-ui;max-width:560px;margin:80px auto;padding:28px;border:1px solid #e5d9bd;border-radius:12px;background:#fffdf8'>
                 <h2 style='margin-top:0;color:#1b2a41'>Cannot connect to MySQL</h2>
                 <p>Please start <b>MySQL</b> in the XAMPP Control Panel, then refresh this page.</p>
                 <p style='color:#8a8f99;font-size:13px'>Details: {$msg}</p></div>"
            );
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
