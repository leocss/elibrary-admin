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
    /**
     * Creates a new user
     *
     * @param Request $request
     * @return string|\Symfony\Component\HttpFoundation\RedirectResponse
     */
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

    /**
     * List / Manage users
     *
     * @return string
     */
    public function index(Request $request)
    {
        $params = ['query' => []];
        if ($request->query->has('filter')) {
            $params['query']['filter'] = $request->query->get('filter');
        }

        $usersBill = $this->client->getUsers();

        foreach($usersBill as $users){
            $debt[] = $users['debt'];
        }

        $totalDebt = array_sum($debt);

        $users = $this->client->getUsers($params);

        return $this->view->render(
            'user/index.twig',
            [
                'users' => $users,
                'totalDebt' => $totalDebt
            ]
        );
    }

    /**
     * View / Edit a user
     *
     * @param $id User ID
     * @return string
     */
    public function edit(Request $request, $id)
    {
        $params = [];
        $user = $this->client->getUser($id);
        $params['user'] = $user;

        if ($request->isMethod('post')) {
            $post = $request->request->get('user');

            try {
                if ($this->client->updateUser($id, $post)) {
                    return $this->app->redirect($this->app['url_generator']->generate('user.edit', ['id' => $id]));
                }
            } catch (ApiException $e) {
                $params['errors'][] = $e->getMessage();
            }
        }

        return $this->view->render('user/edit.twig', $params);
    }

    public function logout()
    {

    }
}
