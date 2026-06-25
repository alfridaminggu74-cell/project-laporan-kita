<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    protected $fillable = [
        'user_id',
        'judul',
        'kategori',
        'kategori_asli_user',
        'deskripsi',
        'lokasi',
        'foto',
        'status',
        'is_training',
    ];

    protected $casts = [
        'foto' => 'array',
        'is_training' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supports()
    {
        return $this->hasMany(Support::class);
    }

    public function supportingUsers()
    {
        return $this->belongsToMany(User::class, 'supports');
    }
}
