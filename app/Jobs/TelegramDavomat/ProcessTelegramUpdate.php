<?php

namespace App\Jobs\TelegramDavomat;

use App\Http\Controllers\TelegramDavomatController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTelegramUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private array $update) {}

    public function handle(TelegramDavomatController $controller): void
    {
        if (isset($this->update['message'])) {
            $controller->handleMessage($this->update['message']); // private'dan public'ga o'zgartirish kerak
        }
        if (isset($this->update['callback_query'])) {
            $controller->handleCallback($this->update['callback_query']);
        }
    }
}
