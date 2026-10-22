<?php

namespace App\Support;

use App\Models\AppSetting;
use InvalidArgumentException;

class IntranetModuleRegistry
{
    /**
     * @var array<string, string>
     */
    private const MODULES = [
        'time_off' => 'Congés',
        'resources' => 'Ressources',
        'knowledge_base' => 'Base de connaissances',
        'forms' => 'Formulaires et services',
        'tickets' => 'Support informatique',
    ];

    /**
     * @return array<string, array{label: string, enabled: bool}>
     */
    public function all(): array
    {
        $settings = AppSetting::query()
            ->whereIn('key', array_map($this->settingKey(...), array_keys(self::MODULES)))
            ->pluck('value', 'key');

        $modules = [];

        foreach (self::MODULES as $key => $label) {
            $storedValue = $settings->get($this->settingKey($key));

            if ($storedValue !== null && ! in_array($storedValue, ['0', '1'], true)) {
                throw new \UnexpectedValueException("Invalid enabled state stored for intranet module [{$key}].");
            }

            $modules[$key] = [
                'label' => $label,
                'enabled' => $storedValue === null || $storedValue === '1',
            ];
        }

        return $modules;
    }

    public function has(string $module): bool
    {
        return array_key_exists($module, self::MODULES);
    }

    public function enabled(string $module): bool
    {
        if (! $this->has($module)) {
            throw new InvalidArgumentException("Unknown intranet module [{$module}].");
        }

        return $this->all()[$module]['enabled'];
    }

    public function setEnabled(string $module, bool $enabled): void
    {
        if (! $this->has($module)) {
            throw new InvalidArgumentException("Unknown intranet module [{$module}].");
        }

        AppSetting::query()->updateOrCreate(
            ['key' => $this->settingKey($module)],
            ['value' => $enabled ? '1' : '0'],
        );
    }

    private function settingKey(string $module): string
    {
        return 'intranet_module_'.$module.'_enabled';
    }
}
