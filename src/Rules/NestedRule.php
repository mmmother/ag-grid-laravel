<?php

namespace Clickbar\AgGrid\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator;

abstract class NestedRule implements ValidationRule, ValidatorAwareRule
{
    protected bool $excludeUnvalidated = false;

    /**
     * The root validator instance.
     */
    protected Validator $validator;

    /**
     * The nested validator instance.
     */
    protected ?Validator $nestedValidator = null;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Make sure the input is an array.
        $data = (array) $value;
        $this->validateNested($attribute, $data);
    }

    abstract public function rules(string $attribute, array $data): array;

    abstract public function attributes(): array;

    final protected function validateNested(string $attribute, array $data): void
    {
        $this->nestedValidator = Validator::make(
            $data,
            $this->rules($attribute, $data),
            [],
            $this->attributes()
        );

        $errors = $this->nestedValidator->errors();

        if ($errors->isNotEmpty()) {
            // If the key is part of an array, e.g. key.1.nested, the correct prefix will be key.*.nested.
            // The following regex produces that.
            $parentKey = preg_replace('/\.\d+$/', '.*', $attribute);

            // Check if the prefix key is set.
            $messagePrefix = isset($this->validator->customAttributes[$parentKey])
                ? $this->validator->customAttributes[$parentKey] . ' '
                : '';

            $messages = collect($errors->messages())->mapWithKeys(function ($messages, $key) use ($attribute, $messagePrefix) {
                $key = $attribute . '.' . $key;
                $messages[0] = $messagePrefix . $messages[0];

                return [$key => $messages];
            })->all();

            $this->validator->messages()->merge($messages);
        } elseif ($this->excludeUnvalidated) {
            $this->validator->setValue($attribute, $this->nestedValidator->validated());
        }
    }

    final public function setValidator(Validator $validator): self
    {
        $this->validator = $validator;

        return $this;
    }

    final public function validated(): ?array
    {
        return $this->nestedValidator?->validated();
    }
}
