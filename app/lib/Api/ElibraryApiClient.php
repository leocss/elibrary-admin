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
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    public function __construct(Session $session, $options)
    {
        $this->session = $session;
        parent::__construct(['base_url' => $options['base_url']]);
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

    public function getStats($params = [])
    {
        return $this->send($this->buildRequest('GET', '/stats', $params));
    }

    public function createUser($data)
    {
        return $this->send(
            $this->buildRequest(
                'POST',
                '/users',
                [
                    'body' => $data
                ]
            )
        );
    }

    public function getUsers($params = [])
    {
        return $this->send($this->buildRequest('GET', '/users', $params));
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

    /**
     * @param $id
     * @param $data
     * @return ResponseInterface
     */
    public function updateUser($id, $data)
    {
        return $this->send($this->buildRequest('POST', sprintf('/users/%s', $id), [
            'body' => $data
        ]));
    }

    /**
     * @param $id
     * @return ResponseInterface
     */
    public function deleteUser($id)
    {
        return $this->send($this->buildRequest('DELETE', sprintf('/users/%s', $id)));
    }

    /**
     * @param $data
     * @param array $params
     * @return ResponseInterface
     */
    public function createPost($data, $params = [])
    {
        return $this->send($this->buildRequest('POST', '/posts', array_merge($params, [
            'body' => $data
        ])));
    }

    /**
     * @param $id
     * @param $data
     * @param array $params
     * @return ResponseInterface
     */
    public function updatePost($id, $data, $params = [])
    {
        return $this->send($this->buildRequest('POST', sprintf('/posts/%s', $id), array_merge($params, [
            'body' => $data
        ])));
    }

    /**
     * @param $id
     * @param UploadedFile $image
     * @return ResponseInterface
     */
    public function uploadPostFeaturedImage($id, UploadedFile $image)
    {
        $request = $this->buildRequest('POST', sprintf('/posts/%s/image', $id));
        $request->getBody()->setField('name', $image->getClientOriginalName());
        $request->getBody()->addFile(new PostFile('image', fopen($image->getRealPath(), 'r')));

        return $this->send($request);
    }

    /**
     * @param array $params
     * @return ResponseInterface
     */
    public function getPosts($params = array())
    {
        return $this->send($this->buildRequest('GET', '/posts'));
    }

    public function getPost($id)
    {
        return $this->send($this->buildRequest('GET', sprintf('/posts/%s', $id)));
    }

    public function deletePost($id)
    {
        return $this->send($this->buildRequest('DELETE', sprintf('/posts/%s', $id)));
    }

    public function getPostCategories()
    {
        return $this->send($this->buildRequest('GET', '/posts/categories'));
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
    public function buildRequest($method, $endpoint, $opts = [])
    {
        $request = $this->createRequest($method, $endpoint, $opts);

        $request->setHeader(
            'Authorization',
            'Bearer ' . $this->clientId . ':' . $this->clientSecret
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
