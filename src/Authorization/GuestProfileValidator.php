<?php

namespace LimenAi\Authorization;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GuestProfileValidator
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validate(array $input): array
    {
        $fields = $this->config->get('limen-ai.ui.guest.form', []);
        $rules = [];

        foreach ($fields as $key => $field) {
            $rule = [];
            $rule[] = ($field['required'] ?? false) ? 'required' : 'nullable';
            $rule[] = 'string';
            $rule[] = 'max:'.(int) ($field['max'] ?? 255);

            if ($key === 'email') {
                $rule[] = 'email';
            }

            $rules[$key] = $rule;
        }

        if ($rules === []) {
            return $input;
        }

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
