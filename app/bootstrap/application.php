<?php

use Elibrary\Lib\Exception\ApiException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Elibrary\Controllers;

require_once __DIR__ . '/../../vendor/autoload.php';

$app = new Silex\Application();

/**
 * Define App Config
 */
$app['debug'] = true;
$app['app.lib.api.elibrary_client_id'] = '9d81c76533b0407d7c52e0ebd5ba2dcf';
$app['app.lib.api.elibrary_client_secret'] = 'ebe661a508c4fc56a69643cb8087b005';

/**
 * Register Services
 */
$app->register(new \Silex\Provider\ServiceControllerServiceProvider());
$app->register(new \Silex\Provider\SessionServiceProvider());
$app->register(new \Silex\Provider\UrlGeneratorServiceProvider());

$app->register(
    new \Silex\Provider\TwigServiceProvider(),
    [
        'twig.path' => __DIR__ . '/../views'
    ]
);

/**
 * Register Error Handlers
 */

// When an Api error occurs
$app->error(
    function (ApiException $exception) use ($app) {
        if ($exception->getErrorCode() == 'invalid_token') {
            // If the api erro is an 'invalid_token' response, then the user
            // needs to be logged out so we can generate another valid token.
            //return $app->redirect($app['url_generator']->generate('backend.main'));
        }

        return $app['twig']->render(
            'error/api.twig',
            [
                'exception' => $exception
            ]
        );
    }
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

/**
 * Register App Services
 */
$app['app.lib.ElibraryApiClient'] = $app->share(
    function () use ($app) {
        $client = new \Elibrary\Lib\Api\ElibraryApiClient($app, $app['session'], [
            'endpoint' => 'http://127.0.0.1:4000'
        ]);
        $client->setClientId($app['app.lib.api.elibrary_client_id']);
        $client->setClientSecret($app['app.lib.api.elibrary_client_secret']);

        return $client;
    }
);

$app['app.GlobalCtrlDependencies'] = $app->share(
    function () use ($app) {
        return [
            'app' => $app,
            'view' => $app['twig'],
            'client' => $app['app.lib.ElibraryApiClient']
        ];
    }
);

/**
 * Register Handlers
 */
$app->before(
    function (Request $request) use ($app) {
        $app['base_url'] = $request->getUriForPath('/');

        $elibraryClient = $app['app.lib.ElibraryApiClient'];
        // Ensure that the user is logged in...
        if ($request->getPathInfo() != '/') { // Prevent from auth check if on main page
            /*if (!$elibraryClient->getSessionUser()) {
                return $app->redirect($app['url_generator']->generate('backend.main'));
            }*/
        }
    },
    Silex\Application::LATE_EVENT
);

$app->before(
    function (Request $request) use ($app) {
        $app['url_segments'] = array_filter(explode('/', trim($request->getPathInfo(), '/ ')));

        // Register a global 'errors' variable that will be available in all
        // views of this library application...
        $app['twig']->addGlobal('errors', $app['session']->getFlashBag()->get('errors'));
    },
    Silex\Application::LATE_EVENT
);

/**
 * Register Controllers
 */
$app['app.controllers.Admin'] = $app->share(
    function () use ($app) {
        return new Controllers\AdminCtrl($app['app.GlobalCtrlDependencies']);
    }
);

$app['app.controllers.Book'] = $app->share(
    function () use ($app) {
        return new Controllers\BookCtrl($app['app.GlobalCtrlDependencies']);
    }
);

// Application Routes

$app->match('/', 'app.controllers.Admin:main')->method('GET|POST')->bind('backend.main');
$app->match('/dashboard', 'app.controllers.Admin:dashboard')->method('GET|POST')->bind('user.dashboard');
$app->match('/register', 'app.controllers.Admin:register')->method('GET|POST')->bind('user.register');
$app->match('/books/upload', 'app.controllers.Book:upload')->method('GET|POST')->bind('book.upload');
$app->match('/logout', 'app.controllers.Admin:logout')->method('GET|POST')->bind('user.logout');

return $app;