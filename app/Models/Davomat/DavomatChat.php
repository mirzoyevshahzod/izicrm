<?php

namespace App\Models\Davomat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Botga yozayotgan Telegram akkaunt: qaysi CRM xodimiga bog'langan va
 * qaysi tugmani bosib, sabab kutilayotgani (pending_reason: late / absent).
 *
 * @property int $telegram_id
 * @property string|null $phone
 * @property int|null $crm_user_id
 * @property string|null $pending_reason
 */
class DavomatChat extends Model
{
    protected $fillable = ['telegram_id', 'phone', 'crm_user_id', 'pending_reason'];

    protected function casts(): array
    {
        return [
            'telegram_id' => 'integer',
            'crm_user_id' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(DavomatEmployee::class, 'crm_user_id', 'crm_user_id');
    }
}
