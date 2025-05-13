<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
        'type',
        'is_public',
    ];

    /**
     * Get a setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return Cache::remember('system_setting.' . $key, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }

            return self::formatValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @return SystemSetting
     */
    public static function set($key, $value)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return null;
        }

        // Format value based on type
        if ($setting->type === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        } elseif ($setting->type === 'integer') {
            $value = (int) $value;
        } elseif ($setting->type === 'json') {
            $value = is_array($value) ? json_encode($value) : $value;
        }

        $setting->value = $value;
        $setting->save();

        // Clear cache
        Cache::forget('system_setting.' . $key);

        return $setting;
    }

    /**
     * Format a value based on type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected static function formatValue($value, $type)
    {
        if ($type === 'boolean') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } elseif ($type === 'integer') {
            return (int) $value;
        } elseif ($type === 'json') {
            return json_decode($value, true);
        }

        return $value;
    }
}
