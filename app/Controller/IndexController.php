<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\Response;

class IndexController extends AbstractController
{
    public function index()
    {
        $target = Response::redirectMergeDomain($this->request, '/admin/');

        return $this->response->redirect($target);
    }

    public function admin()
    {
        return $this->response->raw('等待前端部署')->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
