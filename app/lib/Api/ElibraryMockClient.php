<?php

namespace Elibrary\Lib\Api;

use Elibrary\Lib\Exception\ApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @author Laju Morrison <morrelinko@gmail.com>
 */
class ElibraryMockClient extends ElibraryApiClient
{
    /**
     * @param string $dummyDataPath
     */
    public function __construct($dummyDataPath)
    {
        $this->dummyDataPath = $dummyDataPath;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate($studentId, $password)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getSessionUser()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getUser($id)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getBooks()
    {
        return json_decode(file_get_contents($this->dummyDataPath . '/books.json'), true);
    }

    /**
     * {@inheritDoc}
     */
    public function getBook($bookId)
    {
        foreach ($this->getBooks() as $book) {
            if ($book['id'] == $bookId) {
                return $book;
            }
        }

        throw new ApiException('Book not found.');
    }
}
