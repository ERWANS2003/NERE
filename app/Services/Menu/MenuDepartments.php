<?php

namespace App\Services\Menu;

use App\Models\Department;
use App\Models\MenuGuest;
use App\Models\MenuRegistration;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MenuDepartments
{
    public const OTHER = '__autre';

    /** Sites that are not intranet departments but always appear in the list. */
    public const SITES = ['Zone du bois'];

    /**
     * Intranet departments and sites first, then the other names already used in the menus
     * (subcontractors, sites), one entry per name whatever its case or accents.
     *
     * @return Collection<int, string>
     */
    public static function options(): Collection
    {
        $official = Department::active()->pluck('name')
            ->merge(self::SITES)
            ->unique(fn (string $name): string => self::key($name))
            ->sort(fn (string $a, string $b): int => strcasecmp(Str::ascii($a), Str::ascii($b)))
            ->values();

        $used = MenuRegistration::query()->pluck('department')
            ->merge(MenuGuest::query()->pluck('department'))
            ->map(fn (string $name): string => self::clean($name))
            ->filter()
            ->groupBy(fn (string $name): string => self::key($name))
            ->map(fn (Collection $spellings): string => $spellings->countBy()->sortDesc()->keys()->first());

        $officialKeys = $official->map(fn (string $name): string => self::key($name))->all();

        return $official->merge(
            $used->reject(fn (string $name, string $key): bool => in_array($key, $officialKeys, true))
                ->sort(fn (string $a, string $b): int => strcasecmp(Str::ascii($a), Str::ascii($b)))
                ->values()
        )->values();
    }

    /**
     * @param  Collection<int, string>|null  $options
     */
    public static function canonical(string $value, ?Collection $options = null): string
    {
        $value = self::clean($value);

        return ($options ?? self::options())->first(fn (string $name): bool => self::key($name) === self::key($value)) ?? $value;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'department' => ['required', 'string', 'max:150'],
            'department_other' => ['nullable', 'required_if:department,'.self::OTHER, 'string', 'max:150'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromInput(array $validated): string
    {
        return self::canonical($validated['department'] === self::OTHER
            ? (string) $validated['department_other']
            : $validated['department']);
    }

    private static function clean(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    private static function key(string $value): string
    {
        return mb_strtolower(Str::ascii(self::clean($value)));
    }
}
