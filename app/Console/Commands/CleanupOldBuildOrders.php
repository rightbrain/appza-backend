<?php

namespace App\Console\Commands;

use App\Enums\BuildStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CleanupOldBuildOrders extends Command
{
    protected $signature = 'build-orders:cleanup';
    protected $description = 'Keep last 5 build_orders per app group and cleanup older ones';

    public function handle()
    {
        $this->info('Starting build orders cleanup...');

        // Group definition
        $groups = DB::table('build_orders')
            ->where('status','!=', BuildStatus::Delete->value)
            ->select(
                'package_name',
                'domain',
                'build_target',
                'build_plugin_slug',
                'license_key'
            )
            ->groupBy(
                'package_name',
                'domain',
                'build_target',
                'build_plugin_slug',
                'license_key'
            )
            ->get();

        foreach ($groups as $group) {

            $orders = DB::table('build_orders')
                ->where('package_name', $group->package_name)
                ->where('domain', $group->domain)
                ->where('build_target', $group->build_target)
                ->where('build_plugin_slug', $group->build_plugin_slug)
                ->where('license_key', $group->license_key)
                ->orderByDesc('id')
                ->get();

            // Skip latest 5
            $oldOrders = $orders->slice(5);

            foreach ($oldOrders as $order) {
                $this->cleanupR2Files($order);
                $this->cleanupHistory($order->history_id);

                DB::table('build_orders')
                    ->where('id', $order->id)
                    ->update(array_merge(
                        $this->nullifyBuildOrderFields(),
                        [
                            'status' => BuildStatus::Delete->value,
                            'updated_at' => now(),
                        ]
                    ));

                $this->line("Cleaned build_order ID: {$order->id}");
            }
        }

        $this->info('Cleanup completed successfully.');
        return CommandAlias::SUCCESS;
    }

    /**
     * Delete R2 files and return fields to null
     */
    private function cleanupR2Files($order)
    {
        $disk = Storage::disk('r2');

        foreach ($this->r2Fields() as $field) {
            if (!empty($order->$field)) {
                $path = $this->extractPathFromUrl($order->$field);
                if ($path && $disk->exists($path)) {
                    $disk->delete($path);
                }
            }
        }
    }

    /**
     * Cleanup related build history
     */
    private function cleanupHistory($historyId)
    {
        if (!$historyId) return;

        DB::table('appfiy_apk_build_history')
            ->where('id', $historyId)
            ->update([
//                'app_logo' => null,
//                'app_splash_screen_image' => null,
                'ios_p8_file_content' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Fields to set null in build_orders
     */
    private function nullifyBuildOrderFields()
    {
        return collect($this->r2Fields())
//            ->mapWithKeys(fn ($f) => [$f => null])
            ->mapWithKeys(fn ($f) => [$f => BuildStatus::Delete->value])
            ->toArray();
    }

    /**
     * All R2 related fields
     */
    private function r2Fields()
    {
        return [
            'apk_url',
            'aab_url',
            'android_output_url',
            'ios_output_url',
            'android_push_notification_url',
            'ios_push_notification_url',
            'build_zip_url',
            'runner_url',
            'icon_url',
            'splash_screen',
            'jks_url',
            'key_properties_url',
            'api_key_url',
        ];
    }

    /**
     * Convert full URL to R2 path
     */
    private function extractPathFromUrl($url)
    {
        $parsed = parse_url($url);
        return ltrim($parsed['path'] ?? '', '/');
    }
}
