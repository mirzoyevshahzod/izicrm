<?php

namespace App\Models\Davomat;

use Illuminate\Database\Eloquent\Model;

/**
 * CRM xodimi (CRM o'zi yuboradi): botga raqamini yuborgan odam shu ro'yxatdagi
 * korporativ raqam bilan solishtiriladi.
 *
 * @property int $crm_user_id
 * @property string $name
 * @property string $phone E.164, masalan +998901234567
 */
class DavomatEmployee extends Model
{
    protected $fillable = ['crm_user_id', 'name', 'phone'];
}
