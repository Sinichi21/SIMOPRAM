<?php

namespace App\Enums;

enum ScoutAdministrationType: string
{
    case Male = 'male';
    case Female = 'female';
    case Mabigus = 'mabigus';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Gudep Putra',
            self::Female => 'Gudep Putri',
            self::Mabigus => 'Mabigus',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Male => 'Putra',
            self::Female => 'Putri',
            self::Mabigus => 'Mabigus',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
