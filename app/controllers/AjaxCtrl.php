<?php

namespace Elibrary\Controllers;

use Elibrary\Lib\AdminService;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laju Morrison <morrelinko@gmail.com>
 */
class AjaxCtrl extends BaseCtrl
{
    public function deleteUser($id)
    {
        $response = $this->client->deleteUser($id);

        if (isset($response['error'])) {
          return json_encode(['error_message' => $response['error']['message']]);
        }
        
        return json_encode(['id' => $id, 'success' => true]);
    }
}
