<?php

namespace Elibrary\Lib\Api;

use Elibrary\Lib\Exception\ApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Post\PostFile;
use Silex\Application;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Api Client for elibrary
 * Note: This elibrary api client is injected in all
 * controller instances that extends the BaseCtrl controller
 * and can be accessed using
 * <code>
 *  $this->client->someMethod();
 * </code>
 *
 * @author Laju Morrison <morrelinko@gmail.com>
 */
class ElibraryApiClient extends Client
{
    /**
     * @var Session
     */
    protected $session;

    protected $app;

    protected $apiEndpoint;

    protected $clientId = null;

    protected $clientSecret = null;

    protected $accessToken = null;

    public function __construct(Application $app, Session $session, $options)
    {
        $this->session = $session;
        $this->app = $app;
        parent::__construct(['base_url' => $options['endpoint']]);
    }

    public function setApiEndpoint($url)
    {
        $this->apiUrl = $url;
    }

    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * Helper method to manually set the access token.
     *
     * @param $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function addUser($data)
    {

        return $this->send($this->buildRequest('GET', '/users'));
    }

    public function getUsers()
    {
        return $this->send($this->buildRequest('GET', '/users'));
    }

    /**
     * @param int $id The user id.
     *  Note: Passing 'me' as the ID will return the user that was
     *  authenticated during this session.
     * @return mixed
     */
    public function getUser($id)
    {
        return $this->send($this->buildRequest('GET', sprintf('/users/%s', $id)));
    }

    public function updateUser($id, $data)
    {
        return $this->send($this->buildRequest('POST', sprintf('/users/%s', $id), [
            'body' => [
                'first_name' => 'Laju',
                'last_name' => 'Morrison'
            ]
        ]));
    }

    public function deleteUser($id)
    {
        return $this->send($this->buildRequest('DELETE', sprintf('/users/%s', $id)));
    }

    public function getPosts($params = array())
    {
        return $this->send($this->buildRequest('GET', '/posts'));
    }

    public function getPost($id)
    {
        return $this->send($this->buildRequest('GET', sprintf('/posts/%s', $id)));
    }

    /**
     * @return array
     */
    public function getBooks()
    {
        $books = $this->send($this->buildRequest('GET', '/books'));

        foreach ($books as $index => $book) {
            $books[$index] = $this->prepareBook($book);
        }

        return $books;
    }

    public function getCategories()
    {
        return $this->send($this->buildRequest('GET', '/books/categories'));
    }

    /**
     * @param int $bookId
     * @return array
     */
    public function getBook($bookId)
    {
        return $this->prepareBook($this->send($this->buildRequest('GET', sprintf('/books/%d', $bookId))));
    }

    public function addBook($data)
    {
        $request = $this->buildRequest('POST', '/books');
        $request->getBody()->setField('category_id', $data['category']);
        $request->getBody()->setField('title', $data['title']);
        $request->getBody()->setField('author', $data['author']);
        $request->getBody()->setField('edition', $data['edition']);
        $request->getBody()->setField('overview', $data['overview']);
        $request->getBody()->setField('file_name', $data['file']['name']);
        $request->getBody()->setField('has_soft_copy', $data['has_soft_copy']);
        $request->getBody()->setField('has_hard_copy', $data['has_hard_copy']);
        $request->getBody()->setField('created_at', date('Y-m-d H:i:s', time()));
        $request->getBody()->setField('updated_at', date('Y-m-d H:i:s', time()));

        return $this->send($request);
    }

    /**
     * @return ResponseInterface
     */
    public function getRandomBook()
    {
        return $this->prepareBook($this->send($this->buildRequest('GET', '/books/random')));
    }

    /**
     * @param $method
     * @param $endpoint
     * @param $opts
     * @return \GuzzleHttp\Message\RequestInterface
     */
    protected function buildRequest($method, $endpoint, $opts = [])
    {
        $request = $this->createRequest($method, $endpoint, $opts);

        $request->setHeader('Authorization',
            'Bearer ' . $this->app['app.lib.api.elibrary_client_id'] . ':' . $this->app['app.lib.api.elibrary_client_secret']
        );

        // Set Content-Type header to application/json
        $request->setHeader('Content-Type', 'application/json');

        return $request;
    }

    /**
     * @param RequestInterface $request
     * @throws \Elibrary\Lib\Exception\ApiException
     * @returns ResponseInterface
     */
    public function send(RequestInterface $request)
    {
        try {
            $response = parent::send($request)->json();
            if (isset($response['error'])) {
                // If the api server returns an error, throw an api exception
                // with the message of the error returned by the api server
                throw new ApiException($response['error']['message']);
            }
        } catch (TransferException $e) {
            // Catch all exceptions thrown by the guzzle http client library
            // converting the exception to our own ApiException for easier
            // error handling
            $message = $e->getMessage();
            $code = $e->getCode();
            if ($e instanceof RequestException && ($e->getResponse() instanceof ResponseInterface)) {
                $responseData = $e->getResponse()->json();
                if (isset($responseData['error']['message'])) {
                    $message = $responseData['error']['message'];
                    $code = $responseData['error']['code'];
                }
            }

            throw new ApiException($message, $code);
        }

        return $response['data'];
    }

    protected function prepareBook($book)
    {
        if (isset($book['preview_image']) && ($book['preview_image'] == null)) {
            $book['preview_image'] = $this->app['base_url'] . 'assets/img/sample-book-preview.png';
        }

        return $book;
    }
}
