<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    public static function getDefaults(): array
    {
        $flat = [];

        foreach (self::getDefaultsByGroup() as $settings) {
            foreach ($settings as $key => $value) {
                $flat[$key] = $value;
            }
        }

        return $flat;
    }

    public static function getDefaultsByGroup(): array
    {
        return [
            'orders' => [
                'order_prefix' => 'SHP',
                'order_digits' => '5',
            ],

            'working_hours' => [
                'working_hours_orders_start' => '08:00',
                'working_hours_orders_end' => '22:00',
                'working_hours_pickups_start' => '08:00',
                'working_hours_pickups_end' => '22:00',
                'working_hours_material_requests_start' => '08:00',
                'working_hours_material_requests_end' => '22:00',
            ],

            'collections' => [
                'require_shipper_collection_first' => 'yes',
                'order_follow_up_hours' => '48',
            ],

            'plans' => [
                'welcome_plans' => 'all',
            ],

            'site_identity' => [
                'site_maintenance_mode' => 'false',
                'site_name' => 'Shipping Platform',
                'site_phone' => '',
                'site_address' => '',
                'site_email' => '',
            ],

            'site_logos' => [
                'site_logo_32_light' => '',
                'site_logo_32_dark' => '',
                'site_logo_512_light' => '',
                'site_logo_512_dark' => '',
            ],

            'site_theme' => [
                'site_color_primary_light' => '#2563EB',
                'site_color_primary_dark' => '#60A5FA',
                'site_color_secondary_light' => '#10B981',
                'site_color_secondary_dark' => '#34D399',
                'site_color_background_light' => '#FFFFFF',
                'site_color_background_dark' => '#0F172A',
                'site_color_text_light' => '#111827',
                'site_color_text_dark' => '#F8FAFC',
            ],

            'social_media' => [
                'social_facebook' => '',
                'social_instagram' => '',
                'social_x' => '',
                'social_tiktok' => '',
                'social_youtube' => '',
                'social_linkedin' => '',
                'social_whatsapp' => '',
                'social_telegram' => '',
            ],

            'whatsapp' => [
                'whatsapp_enabled' => 'false',
                'whatsapp_group_id' => '',
                'whatsapp_replace_order_notifications' => 'yes',
                'whatsapp_service_url' => 'http://127.0.0.1:3001',
            ],
        ];
    }

    public static function getGroupForKey(string $key): ?string
    {
        foreach (self::getDefaultsByGroup() as $group => $settings) {
            if (array_key_exists($key, $settings)) {
                return $group;
            }
        }

        return null;
    }

    public static function getValue(string $key, $default = null): mixed
    {
        $setting = self::query()->where('key', $key)->first();

        if ($setting) {
            return $setting->value;
        }

        if ($default !== null) {
            return $default;
        }

        $allDefaults = self::getDefaults();

        return $allDefaults[$key] ?? null;
    }
}
