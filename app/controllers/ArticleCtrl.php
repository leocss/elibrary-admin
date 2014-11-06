<?php

namespace Elibrary\Controllers;

use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        $categories = $this->client->getPostCategories();

        $data = [];
        if ($request->isMethod('post')) {
            $post = $request->request->get('article');
            $user = $this->session->get('auth.user');
            foreach (['title', 'content', 'is_featured', 'category_id'] as $required) {
                if (isset($post[$required])) {
                    if ($required == 'is_featured') {
                        $post[$required] = 1;
                    }

                    $data[$required] = $post[$required];
                }
            }

            $data['author_id'] = $user['id'];
            $data['format'] = 'html';

            $article = $this->client->createPost($data);

            if (($image = $request->files->get('featured_image')) && ($image instanceof UploadedFile)) {
                if ($image->isValid()) {
                    $this->client->uploadPostFeaturedImage($id, $image);
                }
            }

            return $this->app->redirect($this->app['url_generator']->generate('article.index'));
        }

        return $this->view->render('article/create.twig', [
            'categories' => $categories
        ]);
    }

    public function edit(Request $request, $id)
    {
        $categories = $this->client->getPostCategories();
        $article = $this->client->getPost($id);

        if ($request->isMethod('post')) {
            $post = $request->request->get('article');
            $user = $this->session->get('auth.user');
            $data = [];
            foreach (['title', 'content', 'is_featured', 'category_id'] as $required) {
                if (isset($post[$required])) {
                    if ($required == 'is_featured') {
                        $post[$required] = 1;
                    }

                    $data[$required] = $post[$required];
                }
            }

            $data['updated_by'] = $user['id'];
            $data['format'] = 'html';

            $article = $this->client->updatePost($id, $data);

            if (($image = $request->files->get('featured_image')) && ($image instanceof UploadedFile)) {
                if ($image->isValid()) {
                    $this->client->uploadPostFeaturedImage($id, $image);
                }
            }

            return $this->app->redirect($this->app['url_generator']->generate('article.index'));
        }

        return $this->view->render('article/edit.twig', [
            'article' => $article,
            'categories' => $categories
        ]);
    }


} 