<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class DynamicFormSchema
{
    /** @var list<string> */
    public const FIELD_TYPES = ['text', 'textarea', 'number', 'date', 'time', 'datetime-local', 'select', 'items'];

    /** Nombre maximal de lignes d'une liste d'articles. */
    public const MAX_ITEMS = 50;

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
            // Liste d'articles : une ligne par article, avec sa quantité.
            if ($field['type'] === 'items') {
                $rules['data.'.$field['key']] = [($field['required'] ?? false) ? 'required' : 'nullable', 'array', 'min:1', 'max:'.self::MAX_ITEMS];
                $rules['data.'.$field['key'].'.*.article'] = ['required', 'string', 'max:255'];
                $rules['data.'.$field['key'].'.*.quantite'] = ['required', 'integer', 'min:1', 'max:100000'];

                continue;
            }

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
     * Retire les lignes vides des listes d'articles avant la validation.
     *
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepare(array $fields, array $data): array
    {
        foreach ($fields as $field) {
            if ($field['type'] !== 'items' || ! is_array($data[$field['key']] ?? null)) {
                continue;
            }
            $data[$field['key']] = array_values(array_filter(
                array_map(static fn ($row): array => [
                    'article' => is_array($row) ? trim((string) ($row['article'] ?? '')) : '',
                    'quantite' => is_array($row) ? ($row['quantite'] ?? null) : null,
                ], $data[$field['key']]),
                static fn (array $row): bool => $row['article'] !== ''
            ));
            if ($data[$field['key']] === []) {
                unset($data[$field['key']]);
            }
        }

        return $data;
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
                'options' => in_array($field['type'], ['select', 'items'], true)
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
