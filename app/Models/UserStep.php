<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStep extends Model
{
    protected $fillable = [
        'chat_id',
        'step',
        'language',
        'from_country',
        'from_city',
        'to_country',
        'to_city',
        'transport_type',
        'ref_mode',
        'temperature_range',
        'pallet_type',
        'pallet_count',
        'pallet_size',
        'custom_size',
        'contact_phone',
        'contact_name',
        'gross_weight' // Yangi brutto vazn maydoni qo'shildi
    ];

    public function isAtStep(string $step): bool
    {
        return $this->step === $step;
    }

    public function getFullDetails(): string
    {
        // Foydalanuvchi tilini o'rnatish
        if ($this->language) {
            app()->setLocale($this->language);
        }

        $details = "🆕 " . __('messages.new_transport_request') . "\n\n";

        // Jo'nash joyi
        $fromCountryKey = strtolower($this->from_country);
        $fromCountry = __("messages.country.{$fromCountryKey}");
        $details .= "📍 " . __('messages.select_origin_country') . ": {$fromCountry}, {$this->from_city}\n";

        // Yetib borish joyi
        $toCountryKey = strtolower($this->to_country);
        $toCountry = __("messages.country.{$toCountryKey}");
        $details .= "📍 " . __('messages.select_destination_country') . ": {$toCountry}, {$this->to_city}\n\n";

        // Transport turi
        if ($this->transport_type === 'ref') {
            $details .= "🚛 " . __('messages.btn_ref') . "\n";
            if ($this->ref_mode === 's_mode') {
                $details .= "❄️ " . __('messages.btn_s_mode') . "\n";
                if ($this->temperature_range) {
                    $details .= "🌡 " . __('messages.your_temperature') . ": {$this->temperature_range}°C\n";
                }
            } else {
                $details .= "🌡 " . __('messages.btn_bez_mode') . "\n";
            }
        } else {
            $details .= "🚚 " . __('messages.btn_tent') . "\n";
        }

        // Pallet ma'lumotlari
        $details .= "\n📦 " . __('messages.select_pallet_type') . ": " . __("messages.pallet.{$this->pallet_type}") . "\n";
        $details .= "📦 " . __('messages.enter_pallet_count') . ": {$this->pallet_count}\n";

        if ($this->pallet_type === 'no_standard' && $this->custom_size) {
            $details .= "📐 " . __('messages.enter_custom_size') . ": {$this->custom_size}\n";
        } elseif ($this->pallet_size) {
            $details .= "📐 " . __('messages.enter_pallet_size') . ": {$this->pallet_size}\n";
        }

        // Brutto vazn
        if ($this->gross_weight) {
            $details .= "⚖️ " . __('messages.enter_gross_weight') . ": {$this->gross_weight}\n";
        }

        // Kontakt ma'lumotlari
        $details .= "\n👤 " . __('messages.share_contact') . "\n";
        $details .= "📱 {$this->contact_name}: {$this->contact_phone}";

        return $details;
    }
}
