<?php

namespace Elibrary\Controllers;

use Elibrary\Lib\AdminService;
use Elibrary\Lib\Exception\ApiException;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laju Morrison <morrelinko@gmail.com>
 */
class UserCtrl extends BaseCtrl
{
    public function create(Request $request)
    {
        $params = [];
        if ($request->isMethod('post')) {
            $data = $request->request->get('user');

            try {
                $response = $this->client->createUser($data);

                return $this->app->redirect($this->app['url_generator']->generate('admin.dashboard'));
            } catch (ApiException $e) {
                $params['errors'][] = $e->getMessage();
            }
        }

        return $this->view->render('user/create.twig', $params);
    }

    public function index()
    {
        $users = $this->client->getUsers();

        return $this->view->render(
            'user/index.twig',
            [
                'users' => $users
            ]
        );
    }

    public function view($id)
    {
        $user = $this->client->getUser($id);

        return $this->view->render(
            'user/view.twig',
            [
                'user' => $user
            ]
        );
    }
}
