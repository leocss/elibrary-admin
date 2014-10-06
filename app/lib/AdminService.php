<?php

namespace Elibrary\lib;

/**
 * @author Tosin Akomolafe <gettosin4me@gmail.com>
 */
class AdminService
{
    public function login($id, $password)
    {
        $credentials = json_decode(file_get_contents(__DIR__ . '/../storage/data/users.json'), true);

        if (array_key_exists($id, $credentials)) {
            if ($credentials[$id]['password'] == $password) {

                $_SESSION['uid'] = $credentials[$id];

                return true;
            }
        }
    }

    public function getBookCategory()
    {
        //api to get all books
    }
}

 