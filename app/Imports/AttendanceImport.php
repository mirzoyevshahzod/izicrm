<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToArray;

class AttendanceImport implements ToArray
{
    /**
    * @param array $array
    */
    public function array(array $array)
    {
        return $array;
    }
}
