<?php

namespace Elibrary\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laju Morrison <morrelinko@gmail.com>
 */
class BookCtrl extends BaseCtrl
{

    public function add(Request $request)
    {
        if($post = $request->request->all()){
            $input = array_merge($post, $_FILES);

            $file_name = $input['file']['name'];
            $file_size = $input['file']['size'];
            $file_tmp_name = $input['file']['tmp_name'];
            $explode = explode('.', $file_name);
            $ext = end($explode);;

            if($this->client->addBook($input)){
                //return $this->app->redirect($this->app['url_generator']->generate('admin.dashboard'));
            }

        }
        $categories = $this->client->getCategories();
        
        return $this->view->render('book/add.twig',
            [
                'categories' => $categories
            ]);
    }
}
