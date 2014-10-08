<?php

namespace Elibrary\Controllers;

use Elibrary\Lib\AdminService;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laju Morrison <morrelinko@gmail.com>
 */
class BookCtrl extends BaseCtrl
{
    public function upload()
    {
        $categories = $this->client->getCategories();
        
        return $this->view->render('book/upload.twig',
            [
                'categories' => $categories
            ]);
    }
}
