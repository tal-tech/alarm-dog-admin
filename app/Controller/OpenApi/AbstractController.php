<?php

declare(strict_types=1);

namespace App\Controller\OpenApi;

use App\Controller\AbstractController as BaseAbstractController;
use App\Exception\AppException;
use App\Model\User;

/**
 * OpenAPI公共抽象类.
 */
abstract class AbstractController extends BaseAbstractController
{
    /**
     * 验证用户是否有效.
     *
     * @param int $uid
     * @throws AppException
     * @return User
     */
    protected function validateUser($uid)
    {
        $user = $this->container->get(User::class)->findUser($uid);
        if (empty($user)) {
            throw new AppException(sprintf('user [%s] not found', $uid), [
                'uid' => $uid,
            ]);
        }

        return $user;
    }
}
