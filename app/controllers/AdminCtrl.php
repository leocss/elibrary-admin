<?php

namespace Elibrary\Controllers;

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
        if ($post = $request->request->all()) {

            $credentials = json_decode(file_get_contents(__DIR__ . '/../storage/data/users.json'), true);

            if (array_key_exists($post['id'], $credentials)) {
                if ($credentials[$post['id']]['password'] == $post['password']) {
                    $_SESSION['uid'] = $credentials[$post['id']];
                    return $this->app->redirect($this->app['url_generator']->generate('admin.dashboard'));
                }
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
