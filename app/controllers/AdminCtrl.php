<?php

namespace Elibrary\Controllers;

use Elibrary\Lib\AdminService;
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
        if ($post = $request->request->all()) {
            $service = new AdminService();
            if ($service->login($post['id'], $post['password'])) {
                return $this->app->redirect($this->app['url_generator']->generate('user.dashboard'));
            }
        }

        return $this->view->render('admin/main.twig');
    }

    /**
     * @return string
     */
    public function dashboard()
    {
        return $this->view->render('admin/dashboard.twig');
    }

    public function upload()
    {
        $categories = $this->client->getCategories();
var_dump($categories);exit;
        return $this->view->render('admin/upload.twig',
            [
                'categories' => $categories
            ]);
    }

    public function register()
    {
        return $this->view->render('admin/register.twig');
    }

    public function logout()
    {
        if ($_SESSION['uid']) {
            unset($_SESSION['uid']);

            return $this->app->redirect($this->app['url_generator']->generate('backend.main'));
        } else {
            return $this->app->redirect($this->app['url_generator']->generate('backend.main'));
        }
    }
}
