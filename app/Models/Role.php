<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'unidad_id',
    ];

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }
}
