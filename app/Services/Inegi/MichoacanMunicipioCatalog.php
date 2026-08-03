<?php

namespace App\Services\Inegi;

class MichoacanMunicipioCatalog
{
    /**
     * Catálogo 2026 proporcionado para el formato INEGI.
     *
     * @var array<int, string>
     */
    private const MUNICIPIOS = [
        1 => 'Acuitzio',
        2 => 'Aguililla',
        3 => 'Álvaro Obregón',
        4 => 'Angamacutiro',
        5 => 'Angangueo',
        6 => 'Apatzingán',
        7 => 'Aporo',
        8 => 'Aquila',
        9 => 'Ario',
        10 => 'Arteaga',
        11 => 'Briseñas',
        12 => 'Buenavista',
        13 => 'Carácuaro',
        14 => 'Coahuayana',
        15 => 'Coalcomán de Vázquez Pallares',
        16 => 'Coeneo',
        17 => 'Contepec',
        18 => 'Copándaro',
        19 => 'Cotija',
        20 => 'Cuitzeo',
        21 => 'Charapan',
        22 => 'Charo',
        23 => 'Chavinda',
        24 => 'Cherán',
        25 => 'Chilchota',
        26 => 'Chinicuila',
        27 => 'Chucándiro',
        28 => 'Churintzio',
        29 => 'Churumuco',
        30 => 'Ecuandureo',
        31 => 'Epitacio Huerta',
        32 => 'Erongarícuaro',
        33 => 'Gabriel Zamora',
        34 => 'Hidalgo',
        35 => 'La Huacana',
        36 => 'Huandacareo',
        37 => 'Huaniqueo',
        38 => 'Huetamo',
        39 => 'Huiramba',
        40 => 'Indaparapeo',
        41 => 'Irimbo',
        42 => 'Ixtlán',
        43 => 'Jacona',
        44 => 'Jiménez',
        45 => 'Jiquilpan',
        46 => 'Juárez',
        47 => 'Jungapeo',
        48 => 'Lagunillas',
        49 => 'Madero',
        50 => 'Maravatío',
        51 => 'Marcos Castellanos',
        52 => 'Lázaro Cárdenas',
        53 => 'Morelia',
        54 => 'Morelos',
        55 => 'Múgica',
        56 => 'Nahuatzen',
        57 => 'Nocupétaro',
        58 => 'Nuevo Parangaricutiro',
        59 => 'Nuevo Urecho',
        60 => 'Numarán',
        61 => 'Ocampo',
        62 => 'Pajacuarán',
        63 => 'Panindícuaro',
        64 => 'Parácuaro',
        65 => 'Paracho',
        66 => 'Pátzcuaro',
        67 => 'Penjamillo',
        68 => 'Peribán',
        69 => 'La Piedad',
        70 => 'Purépero',
        71 => 'Puruándiro',
        72 => 'Queréndaro',
        73 => 'Quiroga',
        74 => 'Cojumatlán de Régules',
        75 => 'Los Reyes',
        76 => 'Sahuayo',
        77 => 'San Lucas',
        78 => 'Santa Ana Maya',
        79 => 'Salvador Escalante',
        80 => 'Senguio',
        81 => 'Susupuato',
        82 => 'Tacámbaro',
        83 => 'Tancítaro',
        84 => 'Tangamandapio',
        85 => 'Tangancícuaro',
        86 => 'Tanhuato',
        87 => 'Taretan',
        88 => 'Tarímbaro',
        89 => 'Tepalcatepec',
        90 => 'Tingambato',
        91 => 'Tingüindín',
        92 => 'Tiquicheo de Nicolás Romero',
        93 => 'Tlalpujahua',
        94 => 'Tlazazalca',
        95 => 'Tocumbo',
        96 => 'Tumbiscatío',
        97 => 'Turicato',
        98 => 'Tuxpan',
        99 => 'Tuzantla',
        100 => 'Tzintzuntzan',
        101 => 'Tzitzio',
        102 => 'Uruapan',
        103 => 'Venustiano Carranza',
        104 => 'Villamar',
        105 => 'Vista Hermosa',
        106 => 'Yurécuaro',
        107 => 'Zacapu',
        108 => 'Zamora',
        109 => 'Zináparo',
        110 => 'Zinapécuaro',
        111 => 'Ziracuaretiro',
        112 => 'Zitácuaro',
        113 => 'José Sixto Verduzco',
    ];

    /**
     * Nombres de cabeceras y variantes históricas comunes.
     *
     * @var array<string, int>
     */
    private const ALIASES = [
        'ACUITZIO DEL CANJE' => 1,
        'APATZINGAN DE LA CONSTITUCION' => 6,
        'ARIO DE ROSALES' => 9,
        'BRISENAS DE MATAMOROS' => 11,
        'BUENAVISTA TOMATLAN' => 12,
        'CARACUARO DE MORELOS' => 13,
        'COENEO DE LA LIBERTAD' => 16,
        'COPANDARO DE GALEANA' => 18,
        'COTIJA DE LA PAZ' => 19,
        'CUITZEO DEL PORVENIR' => 20,
        'GABRIEL ZAMORA LOMBARDIA' => 33,
        'LOMBARDIA' => 33,
        'CIUDAD HIDALGO' => 34,
        'HUANIQUEO DE MORALES' => 37,
        'HUETAMO DE NUNEZ' => 38,
        'IXTLAN DE LOS HERVORES' => 42,
        'JACONA DE PLANCARTE' => 43,
        'JUNGAPEO DE JUAREZ' => 47,
        'VILLA MADERO' => 49,
        'MARAVATIO DE OCAMPO' => 50,
        'CIUDAD LAZARO CARDENAS' => 52,
        'NUEVA ITALIA' => 55,
        'NUEVA ITALIA DE RUIZ' => 55,
        'NOCUPETARO DE MORELOS' => 57,
        'PARACHO DE VERDUZCO' => 65,
        'PENJAMILLO DE DEGOLLADO' => 67,
        'PERIBAN DE RAMOS' => 68,
        'LA PIEDAD DE CABADAS' => 69,
        'PUREPERO DE ECHAIZ' => 70,
        'LOS REYES DE SALGADO' => 75,
        'SAHUAYO DE MORELOS' => 76,
        'SANTA CLARA DEL COBRE' => 79,
        'TACAMBARO DE CODALLOS' => 82,
        'SANTIAGO TANGAMANDAPIO' => 84,
        'TANGANCICUARO DE ARISTA' => 85,
        'ZAMORA DE HIDALGO' => 108,
        'ZINAPECUARO DE FIGUEROA' => 110,
        'HEROICA ZITACUARO' => 112,
        'PASTOR ORTIZ' => 113,
    ];

    /** @var array<string, int>|null */
    private static ?array $indice = null;

    public static function codigo($municipio): ?int
    {
        $nombre = self::normalizar($municipio);

        if ($nombre === '') {
            return null;
        }

        $nombre = preg_replace('/^(?:MPIO\.?|MUNICIPIO(?: DE)?)\s+/', '', $nombre) ?? $nombre;
        $nombre = preg_replace('/(?:,?\s+(?:MICHOACAN|MICH)\.?)$/', '', $nombre) ?? $nombre;
        $nombre = trim($nombre, " -|,.;");

        return self::indice()[$nombre] ?? self::ALIASES[$nombre] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public static function todos(): array
    {
        return self::MUNICIPIOS;
    }

    /**
     * @return array<string, int>
     */
    private static function indice(): array
    {
        if (self::$indice === null) {
            self::$indice = [];

            foreach (self::MUNICIPIOS as $codigo => $nombre) {
                self::$indice[self::normalizar($nombre)] = $codigo;
            }
        }

        return self::$indice;
    }

    private static function normalizar($valor): string
    {
        $texto = mb_strtoupper(trim((string) $valor), 'UTF-8');
        $texto = strtr($texto, [
            'Á' => 'A',
            'À' => 'A',
            'Ä' => 'A',
            'Â' => 'A',
            'É' => 'E',
            'È' => 'E',
            'Ë' => 'E',
            'Ê' => 'E',
            'Í' => 'I',
            'Ì' => 'I',
            'Ï' => 'I',
            'Î' => 'I',
            'Ó' => 'O',
            'Ò' => 'O',
            'Ö' => 'O',
            'Ô' => 'O',
            'Ú' => 'U',
            'Ù' => 'U',
            'Ü' => 'U',
            'Û' => 'U',
            'Ñ' => 'N',
        ]);

        return preg_replace('/\s+/', ' ', $texto) ?? $texto;
    }
}
