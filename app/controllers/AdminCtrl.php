<?php

namespace Elibrary\Controllers;

use Elibrary\Lib\Exception\ApiException;
use Elibrary\lib\Service\AdminService;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laju Morrison <morrelinko@gmail.com>
 */
class AdminCtrl extends BaseCtrl
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function main(Request $request)
    {
        if ($request->isMethod('post')) {
            try {
                $user = $this->client->send($this->client->buildRequest('GET', '/users/check', [
                    'query' => [
                        'unique_id' => $request->request->get('id'),
                        'password' => $request->request->get('password')
                    ]
                ]));

                if ($user['type'] != 'admin') {
                    return $this->app->redirect($this->app['url_generator']->generate('backend.main'));
                }

                $this->session->set('auth.user', $user);
                return $this->app->redirect($this->app['url_generator']->generate('admin.dashboard'));
            } catch (ApiException $e) {

                $this->alerts->set('errors', $e->getMessage());
            }
        }

        return $this->view->render('admin/main.twig');
    }

    /**
     * @return string
     */
    public function dashboard()
    {
        $stats = $this->client->getStats();
        $recentUsers = $this->client->getUsers([
            'query' => [
                'limit' => 5
            ]
        ]);

        return $this->view->render('admin/dashboard.twig', [
            'stats' => $stats,
            'recentUsers' => $recentUsers
        ]);
    }

    public function register()
    {
        return $this->view->render('admin/register.twig');
    }

    public function logout()
    {
        if ($this->session->has('auth.user')) {
            $this->session->remove('auth.user');
            return $this->app->redirect($this->app['url_generator']->generate('backend.main'));
        } else {
            return $this->app->redirect($this->app['url_generator']->generate('backend.main'));
        }
    }
}
