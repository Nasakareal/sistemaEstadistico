<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tutorial extends Model
{
    use HasFactory;

    public const PLATAFORMA_APP_MOVIL = 'app_movil';
    public const PLATAFORMA_WEB = 'web';
    public const PLATAFORMA_AMBAS = 'ambas';

    protected $table = 'tutoriales';

    protected $fillable = [
        'tutorial_categoria_id',
        'unidad_id',
        'titulo',
        'descripcion',
        'youtube_url',
        'youtube_video_id',
        'plataforma',
        'orden',
        'activo',
    ];

    protected $casts = [
        'unidad_id' => 'integer',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function categoria()
    {
        return $this->belongsTo(TutorialCategoria::class, 'tutorial_categoria_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function scopeParaAppMovil($query)
    {
        return $query
            ->where('activo', true)
            ->whereIn('plataforma', [self::PLATAFORMA_APP_MOVIL, self::PLATAFORMA_AMBAS]);
    }

    public function scopeVisiblesParaUnidad($query, ?int $unidadId)
    {
        return $query->where(function ($inner) use ($unidadId) {
            $inner->whereNull('unidad_id');

            if ($unidadId) {
                $inner->orWhere('unidad_id', $unidadId);
            }
        });
    }

    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        if (!$this->youtube_video_id) {
            return null;
        }

        return 'https://www.youtube.com/embed/' . $this->youtube_video_id;
    }

    public function getYoutubeThumbnailUrlAttribute(): ?string
    {
        if (!$this->youtube_video_id) {
            return null;
        }

        return 'https://img.youtube.com/vi/' . $this->youtube_video_id . '/hqdefault.jpg';
    }
}
