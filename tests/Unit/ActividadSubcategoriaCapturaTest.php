<?php

namespace Tests\Unit;

use App\Models\ActividadSubcategoria;
use App\Support\ActividadSubcategoriaCaptura;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ActividadSubcategoriaCapturaTest extends TestCase
{
    public function test_identifica_las_opciones_genericas_otros_y_otras(): void
    {
        $this->assertTrue(ActividadSubcategoriaCaptura::esOpcionOtros(
            'OTROS REPORTES (Especificar en las novedades relevantes)'
        ));
        $this->assertTrue(ActividadSubcategoriaCaptura::esOpcionOtros(
            'Otras campañas (Especificar en observaciones)'
        ));
        $this->assertTrue(ActividadSubcategoriaCaptura::esOpcionOtros(' OTROS '));
    }

    public function test_no_confunde_subcategorias_que_solo_mencionan_otros_en_su_descripcion(): void
    {
        $this->assertFalse(ActividadSubcategoriaCaptura::esOpcionOtros(
            'APOYOS A OTRAS DEPENDENCIAS (Publicas o privadas)'
        ));
        $this->assertFalse(ActividadSubcategoriaCaptura::esOpcionOtros(
            'ACOMPAÑAMIENTO A CARAVANAS U OTROS'
        ));
    }

    public function test_delegaciones_no_puede_seleccionar_otros_sin_importar_el_rol(): void
    {
        $subcategoria = new ActividadSubcategoria([
            'nombre' => 'OTROS AUXILIOS (Especificar en las novedades relevantes)',
        ]);

        foreach (['Administrador', 'Subdirector', 'Delegado'] as $rol) {
            $usuario = $this->usuario(2, $rol);

            $this->assertFalse(
                ActividadSubcategoriaCaptura::permitidaParaUsuario($subcategoria, $usuario)
            );
        }
    }

    public function test_filtra_otros_del_catalogo_de_delegaciones_y_conserva_las_demas_opciones(): void
    {
        $subcategorias = new Collection([
            new ActividadSubcategoria(['id' => 1, 'nombre' => 'OTROS TIPOS DE OBSTRUCCIÓN']),
            new ActividadSubcategoria(['id' => 2, 'nombre' => 'VÍAS FÉRREAS']),
            new ActividadSubcategoria(['id' => 3, 'nombre' => 'APOYOS A OTRAS DEPENDENCIAS']),
        ]);

        $resultado = ActividadSubcategoriaCaptura::filtrarParaUsuario(
            $subcategorias,
            $this->usuario(2, 'Subdirector')
        );

        $this->assertSame(
            ['VÍAS FÉRREAS', 'APOYOS A OTRAS DEPENDENCIAS'],
            $resultado->pluck('nombre')->all()
        );
    }

    public function test_otras_unidades_conservan_la_opcion_otros(): void
    {
        $subcategoria = new ActividadSubcategoria([
            'nombre' => 'OTROS OPERATIVOS (Especificar en las novedades relevantes)',
        ]);

        $this->assertTrue(
            ActividadSubcategoriaCaptura::permitidaParaUsuario(
                $subcategoria,
                $this->usuario(3, 'Administrador')
            )
        );
    }

    public function test_mensaje_humano_para_version_vieja_de_delegaciones(): void
    {
        $subcategoria = new ActividadSubcategoria([
            'nombre' => 'OTROS MONITOREOS (Especificar en las novedades relevantes)',
        ]);

        $this->assertSame(
            'La opción "Otros" ya no está disponible para Delegaciones. Actualiza la aplicación y selecciona una subcategoría específica.',
            ActividadSubcategoriaCaptura::mensajeRechazoParaUsuario(
                $subcategoria,
                $this->usuario(2, 'Administrador')
            )
        );
    }

    private function usuario(int $unidadId, string $rol)
    {
        return new class($unidadId, $rol) {
            public int $unidad_id;
            public string $rol;

            public function __construct(int $unidadId, string $rol)
            {
                $this->unidad_id = $unidadId;
                $this->rol = $rol;
            }
        };
    }
}
