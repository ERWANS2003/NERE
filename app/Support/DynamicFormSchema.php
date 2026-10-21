<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class DynamicFormSchema
{
    /** @var list<string> */
    public const FIELD_TYPES = ['text', 'textarea', 'number', 'date', 'time', 'datetime-local', 'select'];

    /**
     * @param  array{fields?: list<array<string, mixed>>}|null  $schema
     * @return list<array<string, mixed>>
     */
    public static function fields(?array $schema): array
    {
        $fields = $schema['fields'] ?? [];
        $fields = is_array($fields) ? $fields : [];

        return $fields !== [] ? $fields : [[
            'key' => 'details',
            'label' => 'Décrivez votre demande',
            'type' => 'textarea',
            'required' => true,
            'help_text' => null,
            'options' => [],
        ]];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function editorRules(bool $includeDepartment = true): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'fields' => ['required', 'array', 'min:1', 'max:50'],
            'fields.*' => ['required', 'array:key,label,type,required,help_text,options'],
            'fields.*.key' => ['required', 'string', 'alpha_dash', 'max:64', 'distinct'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', Rule::in(self::FIELD_TYPES)],
            'fields.*.required' => ['required', 'boolean'],
            'fields.*.help_text' => ['nullable', 'string', 'max:500'],
            'fields.*.options' => ['nullable', 'array', 'max:50'],
            'fields.*.options.*' => ['required', 'array:value,label'],
            'fields.*.options.*.value' => ['required', 'string', 'max:255'],
            'fields.*.options.*.label' => ['required', 'string', 'max:255'],
        ];

        if ($includeDepartment) {
            $rules['department_id'] = ['required', 'integer', 'exists:departments,id'];
        }

        return $rules;
    }

    /**
     * @param  array{fields?: list<array{key: string, type: string, required?: bool, options?: list<array{value: string}>}>}  $schema
     * @return array<string, array<int, mixed>>
     */
    public static function submissionRules(array $schema): array
    {
        $fields = $schema['fields'] ?? [];
        $fields = is_array($fields) ? $fields : [];
        $keys = array_column($fields, 'key');
        $rules = [
            'data' => ['required', 'array'],
        ];

        foreach ($fields as $field) {
            $fieldRules = ($field['required'] ?? false) ? ['required'] : ['nullable'];

            if ($field['type'] === 'number') {
                $fieldRules[] = 'numeric';
            } elseif ($field['type'] === 'date') {
                $fieldRules[] = 'date';
            } elseif ($field['type'] === 'time') {
                $fieldRules[] = 'date_format:H:i';
            } elseif ($field['type'] === 'datetime-local') {
                $fieldRules[] = 'date_format:Y-m-d\TH:i';
            } elseif ($field['type'] === 'select') {
                $fieldRules[] = Rule::in(array_column($field['options'] ?? [], 'value'));
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:5000';
            }

            $rules['data.'.$field['key']] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return list<array{key: string, label: string, type: string, required: bool, help_text: string|null, options: list<array{value: string, label: string}>}>
     */
    public static function normalizeFields(array $fields): array
    {
        return array_values(array_map(
            static fn (array $field): array => [
                'key' => trim($field['key']),
                'label' => trim($field['label']),
                'type' => $field['type'],
                'required' => (bool) $field['required'],
                'help_text' => filled($field['help_text'] ?? null) ? trim($field['help_text']) : null,
                'options' => $field['type'] === 'select'
                    ? array_values(array_map(
                        static fn (array $option): array => [
                            'value' => trim($option['value']),
                            'label' => trim($option['label']),
                        ],
                        $field['options'] ?? []
                    ))
                    : [],
            ],
            $fields
        ));
    }
}
