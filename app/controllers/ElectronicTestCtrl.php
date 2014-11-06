<?php

namespace Elibrary\Controllers;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class ElectronicTestCtrl extends BaseCtrl
{

    public function index(Request $request)
    {
        //Getting courses from the database
        $courses = $this->client->getEtestCourses();

        return $this->view->render(
            'etest/index.twig',
            [
                'courses' => $courses
            ]
        );
    }


    public function create(Request $request)
    {
        $courses = $this->client->getEtestCourses();

        $data = [];
        if ($request->isMethod('post')) {
            $post = $request->request->get('course');
           // $user = $this->session->get('auth.user');
            foreach (['name', 'description'] as $required) {
                if (isset($post[$required])) {
                    if ($required == 'name') {
                        $post[$required] = 1;
                    }

                    $data[$required] = $post[$required];
                }
            }

            //$data['author_id'] = $user['id'];
            //$data['format'] = 'html';

           $course = $this->client->createEtestCourses($data);
           // $courses = $this->client-> createEtestCourses($data);

            //if (($image = $request->files->get('featured_image')) && ($image instanceof UploadedFile)) {
               // if ($image->isValid()) {
                   // $this->client->uploadPostFeaturedImage($id, $image);
               // }
          //  }

            return $this->app->redirect($this->app['url_generator']->generate('etest.index'));
        }

        return $this->view->render('etest/create.twig', [
            'courses' => $courses
        ]);
    }





}