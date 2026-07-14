<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ActividadCategoriasSeeder extends Seeder
{
    private const UNIDAD_VIALIDADES_URBANAS_ID = 5;

    public function run(): void
    {
        $data = [
            'INSTITUCIONES' => [
                'APOYO A EVENTOS PÚBLICOS',
                'APOYO A EVENTOS DEPORTIVOS',
                'APOYO A EVENTOS CULTURALES',
                'APOYO A EVENTOS RELIGIOSOS',
                'APOYOS A OTRAS DEPENDENCIAS (Publicas o privadas)',
                'ESCUELAS',
                'DILIGENCIAS',
                'OTROS TIPOS (Especificar en las novedades relevantes)',
            ],
            'REPORTES C5i' => [
                'OBSTRUCCIÓN DE COCHERAS',
                'OTROS TIPOS DE OBSTRUCCIÓN',
                'ACTOS DELICTIVOS',
                'SINIESTROS',
                'HECHOS DE TRÁNSITO',
                'CONSENTRACION PERSONAS',
                'OTROS REPORTES (Especificar en las novedades relevantes)',
            ],
            'ABANDERAMIENTOS' => [
                'CORTES DE CIRCULACIÓN',
                'ACCIDENTES',
                'MARCHAS',
                'MÍTINES',
                'OBRAS PÚBLICAS',
                'ACOMPAÑAMIENTO A CARAVANAS U OTROS',
                'OTROS ABANDERAMIENTOS (Especificar en las novedades relevantes)',
                'BLOQUEO CARRETERO',
            ],
            'OPERATIVOS' => [
                ['nombre' => 'ESCUELA SEGURA', 'unidad_id' => self::UNIDAD_VIALIDADES_URBANAS_ID],
                ['nombre' => 'CONEXIÓN INSTITUCIONAL', 'unidad_id' => self::UNIDAD_VIALIDADES_URBANAS_ID],
                ['nombre' => 'RESPUESTA VIAL INMEDIATA', 'unidad_id' => self::UNIDAD_VIALIDADES_URBANAS_ID],
                ['nombre' => 'ABANDERAMIENTO ACTIVO', 'unidad_id' => self::UNIDAD_VIALIDADES_URBANAS_ID],
                ['nombre' => 'PASO CONTINUO', 'unidad_id' => self::UNIDAD_VIALIDADES_URBANAS_ID],
                'RELÁMPAGO',
                'CARRUSEL',
                'BLINDAJE',
                'CONCIENTIZACIÓN USO DE CASCO',
                'PUESTO DE REVISIÓN',
                'PUESTO DE CONTROL',
                'APOYO COCOTRA',
                'BLINDAJE CON ESTADOS COLINDANTES',
                'BASES DE OPERACIONES INTERINSTITUCIONAL',
                'OTROS OPERATIVOS (Especificar en las novedades relevantes)',
                'ALCOHOLIMETRÍA',
                'CONDUCE CON LEGALIDAD',
            ],
            'PROGRAMAS' => [
                'CONDUCE SIN ALCOHOL (ALCOHOLÍMETRO)',
                'OTROS PROGRAMAS (Especificar en las novedades relevantes)',
            ],
            'MONITOREOS' => [
                'VÍAS FÉRREAS',
                'PERIFÉRICOS',
                'AVENIDAS',
                'TIENDAS DEPARTAMENTALES',
                'BANCOS',
                'GASOLINERAS',
                'OFICINAS GUBERNAMENTALES',
                'MANIFESTACIONES',
                'OTROS MONITOREOS (Especificar en las novedades relevantes)',
                'CARRETERAS',
                'CASETAS',
            ],
            'AUXILIO VIAL A CONDUCTORES' => [
                'FALLAS MECÁNICAS',
                'PEATÓN',
                'ESCOLTA EN SITUACIONES DE EMERGENCIA',
                'AGRICOLAS',
                'OTROS AUXILIOS (Especificar en las novedades relevantes)',
            ],
            'DISPOSITIVOS DE SEGURIDAD VIAL' => [
                'APOYO A LA VIALIDAD',
                'PASO LIBRE DE FUNCIONARIOS',
                'ZONAS DE MAYOR PASE DE TRANSEÚNTES',
                'PASOS PEATONALES',
                'MEDIDAS DE PROTECCIÓN',
                'PATRULLAJES',
                'SERVICIOS DE ESCOLTAS',
                'OTROS (Especificar en las novedades relevantes)',
                'RESGUARDO DE VEHÍCULO POR OBSTRUCCIÓN O ABANDONO',
            ],
            'CAPACITACIONES' => [
                'TALLER EDUCACIÓN SEGURIDAD VIAL',
                'CAMPAÑA EDUCACIÓN SEGURIDAD VIAL',
                'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL',
                'MÓDULOS EDUCACIÓN SEGURIDAD VIAL',
                'SSP',
                'CALEA',
                'OTRAS (Especificar en las novedades relevantes)',
            ],
            'CAMPAÑAS' => [
                'CONCIENTIZACIÓN Y PREVENCIÓN',
                'REPARTICIÓN DE TRÍPTICOS',
                'ESTACIONALES (SEMANA SANTA, NAVIDAD ETC.)',
                'OTRAS (Especificar en las novedades relevantes)',
            ],
            'PROXIMIDAD SOCIAL' => [
                'PREVENCIÓN SOCIAL',
                'RECORRIDOS DE PROXIMIDAD',
                'APOYO A TURISTAS',
                'APOYO A PERSONAS DE LA TERCERA EDAD',
                'APOYO A PERSONAS PERDIDAS',
                'RECUPERACIÓN DE ESPACIOS',
                'OTRAS (Especificar en las novedades relevantes)',
            ],
        ];

        DB::transaction(function () use ($data) {

            foreach ($data as $categoriaNombre => $subcategorias) {
                $categoriaSlug = Str::slug($categoriaNombre);

                DB::table('actividad_categorias')->updateOrInsert(
                    ['slug' => $categoriaSlug],
                    [
                        'nombre'     => $categoriaNombre,
                        'activo'     => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            foreach ($data as $categoriaNombre => $subcategorias) {
                $categoriaSlug = Str::slug($categoriaNombre);

                $categoria = DB::table('actividad_categorias')
                    ->select('id')
                    ->where('slug', $categoriaSlug)
                    ->first();

                if (!$categoria) {
                    continue;
                }

                foreach ($subcategorias as $subcategoria) {
                    $subNombre = is_array($subcategoria) ? $subcategoria['nombre'] : $subcategoria;
                    $subSlug = Str::slug($subNombre);
                    $payload = [
                        'nombre'     => $subNombre,
                        'activo'     => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];

                    if (is_array($subcategoria) && array_key_exists('unidad_id', $subcategoria)) {
                        $payload['unidad_id'] = $subcategoria['unidad_id'];
                    }

                    DB::table('actividad_subcategorias')->updateOrInsert(
                        [
                            'actividad_categoria_id' => $categoria->id,
                            'slug' => $subSlug,
                        ],
                        $payload
                    );
                }
            }
        });
    }
}
