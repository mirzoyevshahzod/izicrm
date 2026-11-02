<?php

namespace App\Console\Commands;

use App\Services\HhService;
use Illuminate\Console\Command;

class HhStatus extends Command
{
    protected $signature = 'hh:status {--search : Bitta sinov qidiruvini ham bajaradi (limitdan yeyishi mumkin)}';
    protected $description = 'hh.uz ulanishi, tarif va pullik metodlarga kirishni tekshiradi';

    public function handle(HhService $hh)
    {
        $token = $hh->token();
        $this->info("Kompaniya: {$token->employer_name} (employer_id: {$token->employer_id}, manager_id: {$token->manager_id})");
        $this->line("Token amal qiladi: {$token->expires_at}");

        $me = $hh->me();
        $this->line('Foydalanuvchi: ' . trim(($me['last_name'] ?? '') . ' ' . ($me['first_name'] ?? '')) . ' | ' . ($me['email'] ?? ''));

        $this->newLine();
        $this->info('API xizmatlari (tarif):');
        $this->runSafely(function () use ($hh) {
            $items = $hh->apiServices()['items'] ?? [];
            if (empty($items)) {
                $this->warn('  Faol API xizmati yo\'q');
            }
            foreach ($items as $item) {
                $balance = isset($item['balance'])
                    ? "{$item['balance']['actual']} / {$item['balance']['initial']}"
                    : 'cheksiz';
                $this->line("  {$item['service_type']['id']} — {$item['service_type']['name']} | qoldiq: $balance | {$item['activated_at']} → {$item['expires_at']}");
            }
        });

        $this->newLine();
        $this->info('Pullik metodlarga kirish:');
        $this->runSafely(function () use ($hh) {
            foreach ($hh->methodAccess()['items'] ?? [] as $item) {
                $mark = ($item['access']['has_access'] ?? false) ? '✅' : '❌';
                $this->line("  $mark [{$item['id']}] {$item['description']}");
            }
        });

        $this->newLine();
        $this->info('Saqlangan qidiruvlar (hh.uz):');
        $this->runSafely(function () use ($hh) {
            $items = $hh->savedSearches()['items'] ?? [];
            if (empty($items)) {
                $this->line('  yo\'q');
            }
            foreach ($items as $item) {
                $this->line("  #{$item['id']} {$item['name']} — jami: {$item['items']['count']}, yangi: {$item['new_items']['count']}");
            }
        });

        if ($this->option('search')) {
            $this->newLine();
            $this->info('Sinov qidiruvi (per_page=1):');
            $this->runSafely(function () use ($hh) {
                $result = $hh->searchResumes(['per_page' => 1]);
                $this->line("  Topildi: {$result['found']}");
                $this->line('  Birinchi natija maydonlari: ' . implode(', ', array_keys($result['items'][0] ?? [])));
            });
        }

        return self::SUCCESS;
    }

    private function runSafely(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            $this->error('  ' . $e->getMessage());
        }
    }
}
