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

    public function deleteArticle($id)
    {
        $response = $this->client->deletePost($id);
        if (isset($response['error'])) {
            return json_encode(['error_message' => $response['error']['message']]);
        }

        return json_encode(['id' => $id, 'success' => true]);
    }

    public function deleteEtestCourse($id)
    {
        $response = $this->client->deleteEtestCourse($id);
        if (isset($response['error'])) {
            return json_encode(['error_message' => $response['error']['message']]);
        }

        return json_encode(['id' => $id, 'success' => true]);
    }

    public function resolveDebt(Request $request, $id)
    {
        $this->client->logTransaction($id, [
            'transaction_id' => uniqid(15),
            'amount' => $request->request->get('amount'),
            'type' => 'cashout',
            'description' => 'Debt',
            'status' => 'OK',
            'message' => 'Debt Payment'
        ]);

        $response = $this->client->payBill($id, ['amount' => $request->request->get('amount')]);

        if (isset($response['error'])) {
            return json_encode(['error_message' => $response['error']['message']]);
        }
        return json_encode(['id' => $id, 'success' => true]);

    }
}
