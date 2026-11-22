<?php

namespace App\Filters;

use Auth;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Role check for a route group, e.g. ['filter' => 'role:owner'] or 'role:owner,staff'.
 * Guests go to the login page; users with the wrong role go to their own home page.
 * The role always comes from the database; users can never pick it themselves.
 */
class Role implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Route filters run before the global ones, so start the session and check MySQL here first.
        $check = (new DatabaseCheck())->before($request);
        if ($check) {
            return $check;
        }

        $user = Auth::user();
        if (!$user) {
            flash('error', 'Please sign in to continue.');
            return go('login');
        }
        if (!Auth::allows($user, (array) $arguments)) {
            flash('error', 'You do not have permission to open that page.');
            return go($user->homeUrl());
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
