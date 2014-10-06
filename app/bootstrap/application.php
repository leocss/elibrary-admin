<?php

use Elibrary\Lib\Exception\ApiException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Elibrary\Controllers;

require_once __DIR__ . '/../../vendor/autoload.php';

$app = new Silex\Application();

/**
 * Register Handlers
 */
$app->before(
    function (Request $request) use ($app) {
        $app['base_url'] = $request->getUriForPath('/');
    },
    Silex\Application::EARLY_EVENT
);

$app->before(
    function (Request $request) use ($app) {
        // Register a global 'errors' variable that will be available in all
        // views of this library application...
        $app['twig']->addGlobal('errors', $app['session']->getFlashBag()->get('errors'));
    },
    Silex\Application::LATE_EVENT
);

/**
 * Define App Config
 */
$app['debug'] = true;

/**
 * Register Services
 */

$app['app.lib.ElibraryApiClient'] = $app->share(
    function () use ($app) {
        $client = new \Elibrary\Lib\ElibraryApiClient($app['session'], [
            'endpoint' => 'http://127.0.0.1:4000'
        ]);
        $client->setClientId('testclient');
        $client->setClientSecret('testsecret');

        return $client;
    }
);

$app->register(new \Silex\Provider\ServiceControllerServiceProvider());
$app->register(new \Silex\Provider\SessionServiceProvider());
$app->register(new \Silex\Provider\UrlGeneratorServiceProvider());

$app->register(
    new \Silex\Provider\TwigServiceProvider(),
    [
        'twig.path' => __DIR__ . '/../views'
    ]
);

// When a route does not exists
$app->error(
    function (NotFoundHttpException $exception) use ($app) {
        return $app['twig']->render('error/not-found.twig');
    }
);

// All other exceptions
$app->error(
    function (Exception $exception) use ($app) {
        return $app['twig']->render(
            'error/server.twig',
            [
                'exception' => $exception
            ]
        );
    }
);

$app['app.GlobalCtrlDependencies'] = $app->share(
    function () use ($app) {
        return [
            'app' => $app,
            'view' => $app['twig'],
            'client' => $app['app.lib.ElibraryApiClient'],
        ];
    }
);

/**
 * Register Controllers
 */
$app['app.controllers.Admin'] = $app->share(
    function () use ($app) {
        return new Controllers\AdminCtrl($app['app.GlobalCtrlDependencies']);
    }
);

// Application Routes

$app->match('/', 'app.controllers.Admin:main')->method('GET|POST')->bind('backend.main');
$app->match('/dashboard', 'app.controllers.Admin:dashboard')->method('GET|POST')->bind('user.dashboard');
$app->match('/register', 'app.controllers.Admin:register')->method('GET|POST')->bind('user.register');
$app->match('/books/upload', 'app.controllers.Admin:upload')->method('GET|POST')->bind('user.upload');
$app->match('/logout', 'app.controllers.Admin:logout')->method('GET|POST')->bind('user.logout');

return $app;