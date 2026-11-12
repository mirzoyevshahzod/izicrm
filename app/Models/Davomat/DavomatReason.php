<?php

namespace App\Models\Davomat;

use Illuminate\Database\Eloquent\Model;

/**
 * Xodim botda yozgan sabab. CRM uni id bo'yicha olib ketadi (GET /api/davomat/reasons).
 *
 * @property int $id
 * @property int $crm_user_id
 * @property int $telegram_id
 * @property string $type late | absent
 * @property string $reason
 * @property \Illuminate\Support\Carbon $date
 */
class DavomatReason extends Model
{
    public const TYPES = ['late', 'absent'];

    protected $fillable = ['crm_user_id', 'telegram_id', 'type', 'reason', 'date'];

    protected function casts(): array
    {
        return [
            'crm_user_id' => 'integer',
            'telegram_id' => 'integer',
            'date' => 'date',
        ];
    }
}
