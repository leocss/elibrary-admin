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

        return $this->view->render('etest/index.twig', [
            'courses' => $courses
        ]);
    }


    public function createCourse(Request $request)
    {
        $data = [];
        if ($request->isMethod('post')) {
            $post = $request->request->get('course');
            foreach (['name', 'description', 'time_length'] as $required) {
                if (isset($post[$required])) {
                    $data[$required] = $post[$required];
                }
            }

            $course = $this->client->createEtestCourse($data);

            return $this->app->redirect($this->app['url_generator']->generate('etest.index'));
        }

        return $this->view->render('etest/create-course.twig');
    }

    /**
     * @param Request $request
     * @param $course_id
     * @return string
     */
    public function viewCourse(Request $request, $course_id)
    {
        $course = $this->client->getEtestCourse($course_id);

        $data = [];
        if ($request->isMethod('post')) {
            $post = $request->request->get('course');
            foreach (['name', 'description', 'time_length'] as $required) {
                if (isset($post[$required])) {
                    $data[$required] = $post[$required];
                }
            }

            if ($this->client->updateEtestCourse($course_id, $data)) {
                return $this->app->redirect($this->app['url_generator']->generate('etest.index'));
            }
        }

        return $this->view->render('etest/view-course.twig', [
            'course' => $course
        ]);
    }

    public function courseQuestions(Request $request, $course_id)
    {
        $course = $this->client->getEtestCourse($course_id, [
            'query' => ['include' => 'questions']
        ]);

        if ($request->isMethod('post')) {
            $post = $request->request->get('question');
            $this->client->createEtestQuestion($course_id, $post);

            return $this->app->redirect($this->app['url_generator']->generate('etest.course-questions', [
                    'course_id' => $course_id
                ]
            ));
        }

        return $this->view->render('etest/course-questions.twig', [
            'course' => $course
        ]);
    }
}
