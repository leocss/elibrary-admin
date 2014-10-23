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

            $file_name = $input['image']['name'];
            $file_size = $input['image']['size'];
            $file_tmp_name = $input['image']['tmp_name'];
            $explode = explode('.', $file_name);
            $ext = end($explode);;

            if($res = $this->client->addBook($input, $request->files->get('image'))){
                if($this->client->uploadPreviewImage($res['id'], $request->files->get('image'))){
                    //return $this->app->redirect($this->app['url_generator']->generate('admin.dashboard'));
                }
            }

        }
        $categories = $this->client->getCategories();
        
        return $this->view->render('book/add.twig',
            [
                'categories' => $categories
            ]);
    }
}
