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
        if($request->isMethod('post')){

            $input = array_merge($request->request->all(), $_FILES);

            $file_name = $input['image']['name'];
            $file_size = $input['image']['size'];
            $file_tmp_name = $input['image']['tmp_name'];
            $explode = explode('.', $file_name);
            $ext = end($explode);;

            if($res = $this->client->addBook($input, $request->files->get('image'))){
                if($this->client->uploadBookFile($res['id'], $request->files->get('book'))) {
                    if ($this->client->uploadPreviewImage($res['id'], $request->files->get('image'))) {
                        $this->app['session']->getFlashBag()->add('message', 'Successfully Uploaded');
                        return $this->app->redirect($this->app['url_generator']->generate('admin.dashboard'));
                    }
                }
            }

        }
        $categories = $this->client->getCategories();

        return $this->view->render('book/add.twig',
            [
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
