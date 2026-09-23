<?php

namespace LimenAi\Exceptions;

class ToolValidationException extends ToolException
{
    public function __construct(string $message, private readonly array $errors = [])
    {
        parent::__construct($message);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
