<?php

namespace App\Support;

final class MarineAreas
{
    /**
     * @return array<string, array{label: string, zone_prefixes: array<int, string>}>
     */
    public static function all(): array
    {
        /** @var array<string, array{label: string, zone_prefixes: array<int, string>}> $areas */
        $areas = config('marine_areas.areas', []);

        return $areas;
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::all() as $code => $data) {
            $options[] = [
                'code' => (string) $code,
                'label' => (string) ($data['label'] ?? $code),
            ];
        }

        usort($options, static fn (array $a, array $b): int => strcmp($a['code'], $b['code']));

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public static function zonePrefixes(?string $marineAreaCode): array
    {
        $code = strtoupper((string) $marineAreaCode);

        if ($code === '') {
            return [];
        }

        $areas = self::all();

        return $areas[$code]['zone_prefixes'] ?? [];
    }

    public static function isValid(?string $marineAreaCode): bool
    {
        return self::zonePrefixes($marineAreaCode) !== [];
    }
}