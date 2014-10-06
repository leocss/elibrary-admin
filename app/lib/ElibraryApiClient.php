<?php

namespace Elibrary\Lib;

use Elibrary\Lib\Exception\ApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
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

    protected $apiEndpoint;

    protected $clientId = null;

    protected $clientSecret = null;

    protected $accessToken = null;

    public function __construct(Session $session, $options)
    {
        $this->session = $session;
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
        return $this->send($this->buildRequest('GET', '/books'));
    }

    /**
     * @param int $bookId
     * @return array
     */
    public function getBook($bookId)
    {
        return $this->send($this->buildRequest('GET', sprintf('/books/%d', $bookId)));
    }

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
            if ($e instanceof RequestException && ($e->getResponse() instanceof ResponseInterface)) {
                $responseData = $e->getResponse()->json();
                if (isset($responseData['error']['message'])) {
                    $message = $responseData['error']['message'];
                }
            }

            throw new ApiException($message);
        }

        return $response['data'];
    }
}
