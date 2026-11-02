<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThermalPrinter extends Model
{
    use HasFactory;

    protected $fillable = [
        'display_name',
        'mac_address',
        'hardware_name',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function normalizeMac(string $value): string
    {
        $compact = strtoupper((string) preg_replace('/[^0-9A-Fa-f]/', '', $value));
        if (strlen($compact) !== 12) {
            return strtoupper(trim($value));
        }

        return implode(':', str_split($compact, 2));
    }
}
