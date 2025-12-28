<?php

namespace App\Console\Commands;

use App\Enums\BuildStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CleanupOldBuildOrders extends Command
{
    protected $signature = 'build-orders:cleanup';
    protected $description = 'Keep last 5 build_orders per app group and cleanup older ones';

    public function handle()
    {
        $this->info('Starting build orders cleanup...');

        $groups = DB::table('build_orders')
            ->where('status', '!=', BuildStatus::Delete->value)
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

            // Keep latest 5
            $oldOrders = $orders->slice(5);

            foreach ($oldOrders as $order) {

                // Delete R2 files
                if (!$this->cleanupR2Files($order)) {
                    $this->error("R2 cleanup failed, skipping DB update (ID: {$order->id})");
                    continue;
                }

                // Cleanup history table
                $this->cleanupHistory($order->history_id);

                // Update DB ONLY if R2 cleanup succeeded
                DB::table('build_orders')
                    ->where('id', $order->id)
                    ->update(array_merge(
//                        $this->nullifyBuildOrderFields(),
                        [
                            'status' => BuildStatus::Delete->value,
                            'updated_at' => now(),
                        ]
                    ));

                $this->line(" Cleaned build_order ID: {$order->id}");
            }
        }

        $this->info('Cleanup completed successfully.');
        return CommandAlias::SUCCESS;
    }

    /**
     * Delete all R2 files for a build_order
     * Returns false if ANY delete fails
     */
    private function cleanupR2Files($order): bool
    {
        $disk = Storage::disk('r2');

        foreach ($this->r2Fields() as $field) {

            if (empty($order->$field)) {
                continue;
            }

            $path = $this->extractPathFromUrl($order->$field);

            if (!$path) {
                Log::warning('R2 delete skipped: invalid URL', [
                    'build_order_id' => $order->id,
                    'field' => $field,
                    'url' => $order->$field,
                ]);
                return false;
            }

            try {
                if ($disk->exists($path)) {

                    if (!$disk->delete($path)) {
                        Log::error('R2 delete failed', [
                            'build_order_id' => $order->id,
                            'field' => $field,
                            'path' => $path,
                        ]);
                        return false;
                    }

                } else {
                    Log::warning('R2 file not found', [
                        'build_order_id' => $order->id,
                        'field' => $field,
                        'path' => $path,
                    ]);
                    return false;
                }
            } catch (\Throwable $e) {
                Log::error('R2 exception during delete', [
                    'build_order_id' => $order->id,
                    'field' => $field,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Cleanup related build history
     */
    private function cleanupHistory($historyId): void
    {
        if (!$historyId) {
            return;
        }

        DB::table('appfiy_apk_build_history')
            ->where('id', $historyId)
            ->update([
                'ios_p8_file_content' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Fields to NULL after successful cleanup
     */
    private function nullifyBuildOrderFields(): array
    {
        return collect($this->r2Fields())
            ->mapWithKeys(fn ($field) => [$field => null])
            ->toArray();
    }

    /**
     * All R2-backed URL fields
     */
    private function r2Fields(): array
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
     * Extract storage path from full URL
     */
    private function extractPathFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        return ltrim($parsed['path'] ?? '', '/');
    }
}
