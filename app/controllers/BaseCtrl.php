<?php

namespace Elibrary\controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig_Environment;

/**
 * @author Laju Morrison <morrelinko@gmail.com>
 */
class BaseCtrl
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Twig_Environment
     */
    protected $view;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface
     */
    protected $alerts;

    /**
     * @var \Elibrary\Lib\Api\ElibraryApiClient
     */
    protected $client;

    public function __construct($controllerDependencies)
    {
        $this->app = $controllerDependencies['app'];
        $this->view = $controllerDependencies['view'];
        $this->client = $controllerDependencies['client'];

        $this->session = $this->app['session'];
        $this->alerts = $this->session->getFlashBag();
    }
}
