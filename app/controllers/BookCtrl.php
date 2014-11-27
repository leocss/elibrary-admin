<?php

namespace Elibrary\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laju Morrison <morrelinko@gmail.com>
 */
class BookCtrl extends BaseCtrl
{
    public function index()
    {
        $books = $this->client->getBooks();

        return $this->view->render('book/index.twig', [
            'books' => $books,
        ]);
    }

    public function add(Request $request)
    {
        $categories = $this->client->getCategories();

        if ($request->isMethod('post')) {

            $input = $request->request->all();

            if ($response = $this->client->addBook($input)) {
                if ($request->files->has('book') && $request->files->get('book') != null) {
                    $this->client->uploadBookFile($response['id'], $request->files->get('book'));
                }

                if ($request->files->has('image') && $request->files->get('image') != null) {
                    $this->client->uploadBookImage($response['id'], $request->files->get('image'));
                }

                $this->app['session']->getFlashBag()->add('message', 'Successfully Uploaded');
                exit(var_dump($response));

                return $this->app->redirect($this->app['url_generator']->generate('admin.dashboard'));
            }
        }

        return $this->view->render('book/add.twig', [
            'categories' => $categories
        ]);
    }

    public function manage()
    {
        $books = $this->client->getBooks();

        return $this->view->render('book/manage.twig', [
            'books' => $books,
        ]);
    }

    public function reserved()
    {
        $books = $this->client->getReservedBooks();

        return $this->view->render('book/reserved.twig', [
            'books' => $books,
        ]);
    }

    public function edit(Request $request, $id)
    {
        $book = $this->client->getBook($id);
        $categories = $this->client->getCategories();

        if ($request->isMethod('post')) {
            $post = $request->request->get('user');

            try {
                if ($this->client->updateBook($id, $post)) {
                    return $this->app->redirect($this->app['url_generator']->generate('book.edit', ['id' => $id]));
                }
            } catch (ApiException $e) {
                $params['errors'][] = $e->getMessage();
            }
        }

        return $this->view->render('book/edit.twig',
            [
                'book' => $book,
                'categories' => $categories,
            ]);
    }
}
