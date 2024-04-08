<?php

namespace ipl\Tests\Validator;

use ipl\Validator\BaseValidator;

class TestValidator extends BaseValidator
{
    protected $options;

    public function __construct($options = null)
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function isValid($value)
    {
        return true;
    }
}
