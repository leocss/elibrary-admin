<?php

namespace Elibrary\Controllers;

use Elibrary\lib\AdminService;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laju Morrison <morrelinko@gmail.com>
 */
class BookCtrl extends BaseCtrl
{

    public function upload(Request $request)
    {
        if($input = $request->request->all()){
            $service = new AdminService();
            if($service->addBook($input)){
                return $this->app->redirect($this->app['url_generator']->generate('user.dashboard'));
            }
        }
        $categories = $this->client->getCategories();
        
        return $this->view->render('book/upload.twig',
            [
                'categories' => $categories
            ]);
    }
}
