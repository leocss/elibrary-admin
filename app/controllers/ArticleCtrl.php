<?php
/**
 * @author Laju Morrison <morrelinko@gmail.com>
 */

namespace Elibrary\Controllers;

use Symfony\Component\HttpFoundation\Request;

class ArticleCtrl extends BaseCtrl
{
    public function index(Request $request)
    {
        $articles = $this->client->getPosts();

        return $this->view->render(
            'article/index.twig',
            [
                'articles' => $articles
            ]
        );
    }

    public function create(Request $request)
    {
        return $this->view->render('article/create.twig');
    }
} 