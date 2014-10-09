<?php

namespace Elibrary\Controllers;

use Elibrary\Lib\AdminService;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laju Morrison <morrelinko@gmail.com>
 */
class UserCtrl extends BaseCtrl
{
    public function create()
    {
        return $this->view->render('user/create.twig');
    }

    public function index()
    {
        $users = $this->client->getUsers();

        return $this->view->render('user/index.twig', [
            'users' => $users
        ]);
    }

    public function view($id)
    {
        $user = $this->client->getUser($id);

        return $this->view->render('user/view.twig', [
            'user' => $user
        ]);
    }
}
