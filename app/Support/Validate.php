<?php

declare(strict_types=1);

namespace App\Support;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidationException;

class Validate
{
    /**
     * @Inject
     * @var ValidatorFactoryInterface
     */
    protected $validationFactory;

    public function validate(array $params, array $rules, array $errMessage = [])
    {
        $validator = $this->validationFactory->make($params, $rules, $errMessage);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        return $params;
    }
}
