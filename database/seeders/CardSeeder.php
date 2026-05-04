<?php
namespace Database\Seeders;
use App\Models\IdentifyBot\Card;
use Illuminate\Database\Seeder;

class CardSeeder extends Seeder {
    public function run() {
        Card::firstOrCreate(['number'=>'9860 1002 2596 1940'], ['label'=>'Default card 1','active'=>true]);
        // Card::firstOrCreate(['number'=>'8600 1111 2222 3333'], ['label'=>'Backup card','active'=>true]);
    }
}
