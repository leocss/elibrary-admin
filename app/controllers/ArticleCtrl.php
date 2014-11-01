<?php

namespace Elibrary\Controllers;

use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laju Morrison <morrelinko@gmail.com>
 */
class ArticleCtrl extends BaseCtrl
{
    public function index(Request $request)
    {
        $articles = $this->client->getPosts();

        return $this->view->render('article/index.twig', [
            'articles' => $articles
        ]);
    }

    public function create(Request $request)
    {
        if ($request->isMethod('post')) {
            $postData = $request->request->get('article');
        }

        return $this->view->render('article/create.twig');
    }
} 