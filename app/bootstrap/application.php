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
$app['api_server_url'] = 'http://127.0.0.1:4000';
$app['api_client_id'] = '9d81c76533b0407d7c52e0ebd5ba2dcf';
$app['api_client_secret'] = 'ebe661a508c4fc56a69643cb8087b005';

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
        $client = new \Elibrary\Lib\Api\ElibraryApiClient($app['session'], [
            'base_url' => $app['api_server_url']
        ]);

        $client->setClientId($app['api_client_id']);
        $client->setClientSecret($app['api_client_secret']);

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
        $app['time'] = time();

        $elibraryClient = $app['app.lib.ElibraryApiClient'];
        // Ensure that the user is logged in...
        if ($request->getPathInfo() != '/') { // Prevent from auth check if on main page
            if (!$app['session']->has('auth.user') || (($user = $app['session']->get('auth.user')) && !isset($user['id']))) {
                return $app->redirect($app['url_generator']->generate('backend.main'));
            }
        }

        $app['default_article_image'] = $app['base_url'] . 'assets/img/sample-book-preview.png';
        $app['default_book_image'] = $app['base_url'] . 'assets/img/sample-book-preview.png';
        $app['default_user_image'] = $app['base_url'] . 'assets/img/user/default-user-image.png';
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

$app['app.controllers.User'] = $app->share(
    function () use ($app) {
        return new Controllers\UserCtrl($app['app.GlobalCtrlDependencies']);
    }
);

$app['app.controllers.Article'] = $app->share(
    function () use ($app) {
        return new Controllers\ArticleCtrl($app['app.GlobalCtrlDependencies']);
    }
);

$app['app.controllers.Ajax'] = $app->share(
    function () use ($app) {
        return new Controllers\AjaxCtrl($app['app.GlobalCtrlDependencies']);
    }
);

// Application Routes
$app->match('/', 'app.controllers.Admin:main')->method('GET|POST')->bind('backend.main');
$app->match('/logout', 'app.controllers.Admin:logout')->method('GET|POST')->bind('user.logout');
$app->match('/dashboard', 'app.controllers.Admin:dashboard')->method('GET|POST')->bind('admin.dashboard');

// Users
$app->get('/users', 'app.controllers.User:index')->bind('user.index');
$app->match('/users/create', 'app.controllers.User:create')->method('GET|POST')->bind('user.create');
$app->match('/users/{id}', 'app.controllers.User:edit')->method('GET|POST')->bind('user.edit');

// Books
$app->match('/books', 'app.controllers.Book:index')->method('GET|POST')->bind('book.index');
$app->match('/books/add', 'app.controllers.Book:add')->method('GET|POST')->bind('book.add');
$app->match('/books/reserved', 'app.controllers.Book:reserved')->method('GET|POST')->bind('book.reserved');

// Articles
$app->get('/articles', 'app.controllers.Article:index')->bind('article.index');
$app->get('/articles/create', 'app.controllers.Article:create')->bind('article.create');

// Ajax Routes
$app->delete('/ajax/users/{id}', 'app.controllers.Ajax:deleteUser');
$app->delete('/ajax/articles/{id}', 'app.controllers.Ajax:deleteArticle');

return $app;