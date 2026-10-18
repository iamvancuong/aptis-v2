<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'key',
        'value',
        'label',
    ];

    /**
     * Read a setting as a boolean. Returns $default when the key is absent, so
     * a feature can ship "on" before its row exists.
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::where('key', $key)->value('value');

        if ($value === null) {
            return $default;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Read a setting as an integer. Returns $default when the key is absent or
     * not numeric, so an admin-editable limit can fall back to its config value.
     */
    public static function int(string $key, int $default): int
    {
        $value = static::where('key', $key)->value('value');

        return is_numeric($value) ? (int) $value : $default;
    }
}
