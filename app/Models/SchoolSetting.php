<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = [
        'full_name',
        'full_name_en', //Devin
        'short_name',
        'logo',
        'signature_seal',
        'address',
        'address_en',   //Devin
        'postal_box',
        'city',
        'region',
        'email',
        'website',
        'motto',
        'motto_en',     //Devin
        'order_type',
        'ministry',
        'ministry_en',      //Devin
    ];

    // Récupère l'unique enregistrement de paramètres
    public static function instance(): static
    {
        return static::firstOrCreate([], [
            'full_name'  => 'LA PERSEVERANCE PLUS',
            'short_name' => 'PERSEVERANCE PLUS',
        ]);
    }
}
//Devin
