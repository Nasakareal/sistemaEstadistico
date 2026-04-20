<?php

namespace App\Services;

use App\Models\Hechos;
use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class ParteNovedadesGenerator
{
    protected HechoNovedadesFormatter $hechoFormatter;

    public function __construct(HechoNovedadesFormatter $hechoFormatter)
    {
        $this->hechoFormatter = $hechoFormatter;
    }

    public function generar(string $fecha): string
    {
        $tz = 'America/Mexico_City';

        Settings::setOutputEscapingEnabled(true);

        $inicio = Carbon::parse($fecha, $tz)->setTime(18, 0)->subDay();
        $fin = Carbon::parse($fecha, $tz)->setTime(18, 0);

        $hechos = Hechos::with([
            'vehiculos.conductores',
            'vehiculos.servicios.grua',
            'lesionados'
        ])
            ->where('unidad_org_id', 1)
            ->whereBetween('created_at', [$inicio, $fin])
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->get();

        $this->sanitizeHechos($hechos);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'pageSizeW' => 12175,
            'pageSizeH' => 17860,
            'marginTop' => 1134,
            'marginRight' => 1134,
            'marginBottom' => 1134,
            'marginLeft' => 1134,
        ]);

        $phpWord->addTableStyle('EncabezadoTabla', [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0,
            'alignment' => JcTable::CENTER,
        ]);

        $table = $section->addTable('EncabezadoTabla');

        $table->addRow();
        $table->addCell(5000, ['valign' => 'center'])->addImage(public_path('ssp.jpg'), [
            'width' => 140,
            'alignment' => Jc::LEFT
        ]);
        $table->addCell(5000, ['valign' => 'center'])->addImage(public_path('vialidad.png'), [
            'width' => 70,
            'alignment' => Jc::RIGHT
        ]);

        $table->addRow();
        $table->addCell(5000)->addText('PARTE DE NOVEDADES', ['bold' => true]);
        $table->addCell(5000)->addText('UNIDAD DE ATENCIÓN A SINIESTROS', ['bold' => true], [
            'alignment' => Jc::RIGHT
        ]);

        $section->addTextBreak(1);

        $fechaFormatoOficio = 'Morelia Michoacán, ' . Carbon::parse($fecha, $tz)->format('d') . ' de ' .
            ucfirst(Carbon::parse($fecha, $tz)->translatedFormat('F')) . ' de ' . Carbon::parse($fecha, $tz)->format('Y') . '.';

        $section->addText($fechaFormatoOficio, [], [
            'alignment' => Jc::RIGHT,
            'spaceAfter' => 0,
            'spaceBefore' => 0,
        ]);

        $destinatario = [
            'LIC. LUIS ROBERTO ROSILES SOBERANIS',
            'COORDINADOR DEL AGRUPAMIENTO',
            'DE SEGURIDAD VIAL',
            'P R E S E N T E'
        ];

        foreach ($destinatario as $linea) {
            $section->addText($linea, ['bold' => true], [
                'alignment' => Jc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
        }

        $section->addTextBreak(1);

        $fechaInicioTexto = ucfirst(
            Carbon::parse($fecha, $tz)
                ->subDay()
                ->translatedFormat('d \\d\\e F \\d\\e Y')
        );

        $fechaFinTexto = ucfirst(
            Carbon::parse($fecha, $tz)
                ->translatedFormat('d \\d\\e F \\d\\e Y')
        );

        $textoNovedades = "Hago de su superior conocimiento, lo relacionado a las novedades ocurridas durante el Servicio de las 18:00 horas del día {$fechaInicioTexto}, a las 18:00 horas del día {$fechaFinTexto}, por parte de la Unidad de Atención a Siniestros.";

        $section->addText($textoNovedades, [], [
            'alignment' => Jc::BOTH,
            'spaceAfter' => 0,
            'spaceBefore' => 0,
        ]);

        $section->addText(str_repeat('.', 148), [], [
            'alignment' => Jc::BOTH,
            'spaceAfter' => 0,
            'spaceBefore' => 0
        ]);

        $section->addTextBreak(1);

        $section->addText('HECHOS RELEVANTES', ['bold' => true], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 0,
            'spaceBefore' => 0,
        ]);

        $section->addTextBreak(1);

        $section->addText(str_repeat('.', 148), [], [
            'alignment' => Jc::BOTH,
            'spaceAfter' => 0,
            'spaceBefore' => 0
        ]);

        $section->addTextBreak(1);

        $section->addText('HECHOS DE TRÁNSITO', ['bold' => true], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 0,
            'spaceBefore' => 0,
        ]);

        $section->addTextBreak(1);

        $contador = 1;

        foreach ($hechos as $hecho) {
            $textRun = $section->addTextRun([
                'alignment' => Jc::BOTH,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $resultadoTexto = $this->hechoFormatter->resultadoVictimasTexto($hecho);
            $tituloTipoHecho = $this->hechoFormatter->tituloTipoHecho($hecho->tipo_hecho);

            $textRun->addText("{$contador}.-{$tituloTipoHecho} ({$resultadoTexto}) SECTOR " . strtoupper($hecho->sector) . ".- ", ['bold' => true]);

            $textRun->addText("A las " . Carbon::parse($hecho->hora, $tz)->format('H:i') . " horas en {$hecho->calle}, de la colonia {$hecho->colonia}, lugar donde ");

            $vehiculos = $hecho->vehiculos->loadMissing(['conductores', 'servicios.grua']);

            if ($vehiculos->count() > 0) {
                $textRun->addText("participaron: ");
                $letra = 'A';

                foreach ($vehiculos as $vehiculo) {
                    $textRun->addText("AUTOMÓVIL ({$letra}) ", ['bold' => true]);

                    $partes = [];
                    if ($vehiculo->marca) {
                        $partes[] = "Marca {$vehiculo->marca}";
                    }
                    if ($vehiculo->modelo) {
                        $partes[] = "Modelo {$vehiculo->modelo}";
                    }
                    if ($vehiculo->tipo) {
                        $partes[] = "Tipo {$vehiculo->tipo}";
                    }
                    if ($vehiculo->linea) {
                        $partes[] = "Línea {$vehiculo->linea}";
                    }
                    if ($vehiculo->color) {
                        $partes[] = "Color {$vehiculo->color}";
                    }

                    if ($partes) {
                        $textRun->addText(implode(', ', $partes) . ", ");
                    }

                    if ($vehiculo->placas) {
                        $textRun->addText("Placas ");
                        $textRun->addText($vehiculo->placas, ['bold' => true]);
                        $textRun->addText(" del servicio {$vehiculo->tipo_servicio}, ");
                    }

                    if ($vehiculo->serie) {
                        $textRun->addText("Serie ");
                        $textRun->addText($vehiculo->serie, ['bold' => true]);
                        $textRun->addText(", ");
                    }

                    if ($vehiculo->tarjeta_circulacion_nombre) {
                        $textRun->addText("tarjeta de circulación a nombre de {$vehiculo->tarjeta_circulacion_nombre}, ");
                    }

                    $conductor = $vehiculo->conductores->first();
                    if ($conductor) {
                        $textRun->addText("conducido por el C. ");
                        $textRun->addText($conductor->nombre, ['bold' => true]);
                        if ($conductor->edad) {
                            $textRun->addText(" de {$conductor->edad} años de edad");
                        }
                        if ($conductor->domicilio) {
                            $textRun->addText(", con domicilio en {$conductor->domicilio}");
                        }
                        if ($conductor->estado_licencia) {
                            $textRun->addText(", presentó licencia tipo {$conductor->tipo_licencia}");
                        } else {
                            $textRun->addText(", no presentó licencia");
                        }
                        $textRun->addText("; ");
                    }

                    $letra++;
                }
            } else {
                $textRun->addText("no se encontró información de vehículos. ");
            }

            $lineasVictimas = $this->hechoFormatter->lineasVictimas($hecho);

            if (!empty($lineasVictimas)) {
                foreach ($lineasVictimas as $linea) {
                    $section->addText($linea, [], [
                        'alignment' => Jc::BOTH,
                        'spaceAfter' => 0,
                        'spaceBefore' => 0,
                    ]);
                }
            } else {
                $section->addText("SIN LESIONADOS.", [], [
                    'alignment' => Jc::BOTH,
                    'spaceAfter' => 0,
                    'spaceBefore' => 0,
                ]);
            }

            $section->addText("Intervino el perito {$hecho->perito}.", [], [
                'alignment' => Jc::BOTH,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $section->addText("ID DE REGISTRO {$hecho->id}", [], [
                'alignment' => Jc::BOTH,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $montoTotal = $this->hechoFormatter->montoDanos($hecho);

            $section->addText(
                strtoupper($hecho->situacion) . "\tDAÑOS APROXIMADOS $ " . number_format($montoTotal, 2),
                [],
                ['alignment' => Jc::BOTH, 'spaceAfter' => 0, 'spaceBefore' => 0]
            );

            $lineaCausas = "CAUSAS: {$hecho->causas}";

            $ocupaciones = collect($vehiculos)->flatMap(function ($v) {
                return $v->conductores->pluck('ocupacion')->filter();
            })->unique()->implode(' – ');

            if ($ocupaciones) {
                $lineaCausas .= " ({$ocupaciones})";
            }

            $section->addText($lineaCausas, [], [
                'alignment' => Jc::BOTH,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $detallesResguardo = [];

            foreach ($vehiculos as $index => $vehiculo) {
                $letraVehiculo = chr(65 + $index);
                $serviciosVehiculo = $vehiculo->servicios ?? collect();

                $gruasVehiculo = [];
                foreach ($serviciosVehiculo as $servicio) {
                    if (!empty($servicio->grua_id) && $servicio->grua && !empty($servicio->grua->nombre)) {
                        $gruasVehiculo[] = strtoupper(trim($servicio->grua->nombre));
                    }
                }
                $gruasVehiculo = array_values(array_unique($gruasVehiculo));

                $valorGruaVehiculo = strtoupper(trim((string) ($vehiculo->grua ?? '')));
                $valorCorralonVehiculo = strtoupper(trim((string) ($vehiculo->corralon ?? '')));

                $tieneGruaPorServicio = !empty($gruasVehiculo);
                $tieneGruaPorVehiculo = $valorGruaVehiculo !== '' &&
                    !in_array($valorGruaVehiculo, ['N/A', 'NA', 'NO', 'NO SE UTILIZA', 'NINGUNA', 'NINGUNO', 'NULL']);

                $fueResguardado = $valorCorralonVehiculo !== '' &&
                    !in_array($valorCorralonVehiculo, ['N/A', 'NA', 'NO', 'NO SE UTILIZA', 'NINGUNA', 'NINGUNO', 'NULL']);

                $textoVehiculo = "Vehículo ({$letraVehiculo}): ";

                if ($tieneGruaPorServicio) {
                    $textoVehiculo .= "se utilizó grúa " . implode(', ', $gruasVehiculo);

                    if ($fueResguardado) {
                        $textoVehiculo .= " y quedó resguardado en el corralón {$valorCorralonVehiculo}";
                    }

                    $textoVehiculo .= ".";
                } elseif ($tieneGruaPorVehiculo) {
                    $textoVehiculo .= "se utilizó grúa {$valorGruaVehiculo}";

                    if ($fueResguardado) {
                        $textoVehiculo .= " y quedó resguardado en el corralón {$valorCorralonVehiculo}";
                    }

                    $textoVehiculo .= ".";
                } elseif ($fueResguardado) {
                    $textoVehiculo .= "quedó resguardado en el corralón {$valorCorralonVehiculo}, sin uso de grúa.";
                } else {
                    $textoVehiculo .= "no se utilizó grúa ni quedó resguardado en corralón.";
                }

                $detallesResguardo[] = $textoVehiculo;
            }

            if (!empty($detallesResguardo)) {
                foreach ($detallesResguardo as $detalleResguardo) {
                    $section->addText(
                        $detalleResguardo,
                        [],
                        ['alignment' => Jc::BOTH, 'spaceAfter' => 0, 'spaceBefore' => 0]
                    );
                }
            } else {
                $section->addText(
                    'No se encontró información de resguardo de vehículos.',
                    [],
                    ['alignment' => Jc::BOTH, 'spaceAfter' => 0, 'spaceBefore' => 0]
                );
            }

            if ($hecho->checaron_antecedentes) {
                $section->addText(
                    "Se checaron antecedentes de conductores y vehículos, sin novedad.",
                    [],
                    ['alignment' => Jc::BOTH, 'spaceAfter' => 0, 'spaceBefore' => 0]
                );
            }

            $section->addText(str_repeat('.', 148), [], [
                'alignment' => Jc::BOTH,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $contador++;
        }

        $section->addTextBreak(1);
        $section->addTextBreak(1);
        $section->addText('A T E N T A M E N T E', ['bold' => true], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 0,
            'spaceBefore' => 0,
        ]);
        $section->addTextBreak(1);

        $tableStyleName = 'FirmasSinBordes';
        $phpWord->addTableStyle($tableStyleName, [
            'borderSize' => 0,
            'borderColor' => 'ffffff',
            'cellMargin' => 50,
            'alignment' => JcTable::CENTER,
        ]);
        $tableFirmas = $section->addTable($tableStyleName);

        $cellStyle = [
            'borderSize' => 0,
            'borderColor' => 'ffffff',
            'valign' => 'center',
        ];

        $turnoSvc = app(TurnoService::class);
        $momentoTurno = $fin->copy()->subMinute();
        $turnoActivo = $turnoSvc->turnoActivoEn($momentoTurno);

        $turnoLetra = 'B';
        if ($turnoActivo) {
            $nombreTurno = strtoupper(trim((string) ($turnoActivo->nombre ?? '')));
            $slugTurno = strtoupper(trim((string) ($turnoActivo->slug ?? '')));
            if (strpos($nombreTurno, ' A') !== false || $nombreTurno === 'A' || strpos($slugTurno, 'A') !== false) {
                $turnoLetra = 'A';
            }
        }

        $comandanteCargo = 'COMANDANTE DE TURNO “' . $turnoLetra . '”';

        if ($turnoLetra === 'A') {
            $comandanteLinea1 = 'OFICIAL';
            $comandanteLinea2 = 'LIC. FERNANDO RUBALCAVA RIVERA';
        } else {
            $comandanteLinea1 = 'POL. 3°';
            $comandanteLinea2 = 'JORGE ARMANDO MORALES PÉREZ';
        }

        $tableFirmas->addRow();

        $cellL1 = $tableFirmas->addCell(5000, $cellStyle);
        $runL1 = $cellL1->addTextRun(['alignment' => Jc::CENTER]);
        $runL1->addText('SUBDIRECTOR DE LA UNIDAD DE ATENCIÓN A SINIESTROS.', ['bold' => true]);

        $cellR1 = $tableFirmas->addCell(5000, $cellStyle);
        $runR1 = $cellR1->addTextRun(['alignment' => Jc::CENTER]);
        $runR1->addText($comandanteCargo, ['bold' => true]);

        $tableFirmas->addRow();
        $tableFirmas->addCell(5000, $cellStyle)->addText(str_repeat("\n", 8));
        $tableFirmas->addCell(5000, $cellStyle)->addText(str_repeat("\n", 8));

        $tableFirmas->addRow();

        $cellL2 = $tableFirmas->addCell(5000, $cellStyle);
        $cellL2->addText('LIC. JULIO ERNESTO BAUTISTA JIMENEZ.', ['bold' => true], ['alignment' => Jc::CENTER]);

        $cellR2 = $tableFirmas->addCell(5000, $cellStyle);
        $cellR2->addText($comandanteLinea1, ['bold' => true], ['alignment' => Jc::CENTER]);
        $cellR2->addText($comandanteLinea2, ['bold' => true], ['alignment' => Jc::CENTER]);

        $section->addTextBreak(6);

        $filename = "parte_novedades_{$fecha}.docx";
        $tempPath = storage_path("app/tmp/{$filename}");

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return $tempPath;
    }

    private function sanitizeHechos($hechos): void
    {
        foreach ($hechos as $hecho) {
            $this->sanitizeModelStrings($hecho);

            foreach ($hecho->vehiculos as $vehiculo) {
                $this->sanitizeModelStrings($vehiculo);

                foreach ($vehiculo->conductores as $conductor) {
                    $this->sanitizeModelStrings($conductor);
                }

                foreach ($vehiculo->servicios as $servicio) {
                    $this->sanitizeModelStrings($servicio);

                    if ($servicio->grua) {
                        $this->sanitizeModelStrings($servicio->grua);
                    }
                }
            }

            foreach ($hecho->lesionados as $lesionado) {
                $this->sanitizeModelStrings($lesionado);
            }
        }
    }

    private function sanitizeModelStrings($model): void
    {
        foreach ($model->getAttributes() as $attribute => $value) {
            if (is_string($value)) {
                $model->setAttribute($attribute, $this->sanitizeWordText($value));
            }
        }
    }

    private function sanitizeWordText(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        return $sanitized ?? $value;
    }
}
