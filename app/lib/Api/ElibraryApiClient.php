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

    /**
     * @param $uniqueId
     * @param $password
     * @return mixed
     */
    public function authenticate($uniqueId, $password)
    {
        $accessTokenData = $this->send(
            $this->buildRequest(
                'POST',
                '/oauth2/token',
                [
                    'body' => [
                        'grant_type' => 'password',
                        'user_unique_id' => $uniqueId,
                        'user_password' => $password,
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret
                    ]
                ]
            )
        );

        $this->session->set(
            'api.token',
            [
                'access_token' => $accessTokenData['access_token'],
                'expire_time' => $accessTokenData['expire_time'],
            ]
        );

        $user = $this->getUser($accessTokenData['user_id']);

        $this->session->set('api.user', $user);

        return $user;
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
        $categories = $this->send($this->buildRequest('GET', '/books/categories'));

        foreach ($categories as $index => $category) {
            $categories[$index] = $this->prepareBook($category);
        }

        return $category;
    }

    /**
     * @param int $bookId
     * @return array
     */
    public function getBook($bookId)
    {
        return $this->prepareBook($this->send($this->buildRequest('GET', sprintf('/books/%d', $bookId))));
    }

    /**
     * @return ResponseInterface
     */
    public function getRandomBook()
    {
        return $this->prepareBook($this->send($this->buildRequest('GET', '/books/random')));
    }

    /**
     * @param $param
     *  - user_id: ID of user creating the print job
     *  - name: Job name
     *
     * @return object
     */
    public function createPrintJob($param)
    {
        if (!isset($param['user_id'])) {
            // If the user_id param is not passed along with the parameters,
            // try to use the currently authenticated user;
            $user = $this->getSessionUser();
            $param['user_id'] = $user['id'];
        }

        return $this->send(
            $this->buildRequest(
                'POST',
                '/print-jobs',
                [
                    'body' => json_encode(
                        [
                            'name' => $param['name'],
                            'user_id' => $param['user_id']
                        ]
                    )
                ]
            )
        );
    }

    /**
     * Returns a list of the print jobs the
     * current authenticated user has created.
     *
     * @return ResponseInterface
     */
    public function getPrintJobs()
    {
        $user = $this->getSessionUser();

        return $this->send($this->buildRequest('GET', sprintf('/users/%s/print-jobs', $user['id'])));
    }

    /**
     * @param $jobId Print Job ID
     * @return ResponseInterface
     */
    public function getPrintJob($jobId)
    {
        $user = $this->getSessionUser();

        return $this->send($this->buildRequest('GET', sprintf('/users/%s/print-jobs/%s', $user['id'], $jobId)));
    }

    /**
     * @param int $jobId
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $document
     */
    public function uploadPrintJobDocument($jobId, $document)
    {
        $user = $this->getSessionUser();

        $request = $this->buildRequest('POST', sprintf('/users/%s/print-jobs/%s/documents', $user['id'], $jobId));
        $request->getBody()->setField('name', $document->getClientOriginalName());
        $request->getBody()->addFile(new PostFile('document', fopen($document->getRealPath(), 'r')));

        return $this->send($request);
    }

    public function deletePrintJobDocument($jobId, $documentId)
    {
        $user = $this->getSessionUser();

        return $this->send(
            $this->buildRequest(
                'DELETE',
                sprintf(
                    '/users/%s/print-jobs/%s/documents/%s',
                    $user['id'],
                    $jobId,
                    $documentId
                )
            )
        );
    }

    /**
     */
    public function invalidateToken()
    {
        $accessToken = $this->getAccessToken();
        if ($accessToken == null) {
            return true;
        }

        return $this->send(
            $this->buildRequest('POST', sprintf('/oauth2/invalidate-token?access_token=%s', $accessToken))
        );
    }

    /**
     * @return null|string
     */
    public function getAccessToken()
    {
        if (($accessTokenData = $this->session->get('api.token')) != null) {
            return $accessTokenData['access_token'];
        }

        return null;
    }

    public function clearSessionUser()
    {
        if (($response = $this->invalidateToken()) && ((bool)$response['invalidated'])) {
            $this->session->remove('api.token');
            $this->session->remove('api.user');
        }
    }

    public function getSessionUser()
    {
        if ($this->session->has('api.user')) {
            return $this->session->get('api.user');
        }

        return false;
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

        if (($accessTokenData = $this->session->get('api.token')) != null) {
            // Check if the access token data is available in the session.
            // If there is access token information, retrieve it and
            // pass it along the request sent to the api server
            // instead of having to do this manually for every api request.
            $request->setHeader('Authorization', sprintf('Bearer %s', $accessTokenData['access_token']));
        }

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
        if ($book['preview_image'] == null) {
            $book['preview_image'] = $this->app['base_url'] . 'assets/img/sample-book-preview.png';
        }

        return $book;
    }
}
