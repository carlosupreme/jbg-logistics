<?php

namespace App\Utils;

class Countries
{
    /**
     * Get all available countries.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'MEX' => 'México',
            'USA' => 'Estados Unidos',
            'CAN' => 'Canadá',
            'GTM' => 'Guatemala',
            'BLZ' => 'Belice',
            'SLV' => 'El Salvador',
            'HND' => 'Honduras',
            'NIC' => 'Nicaragua',
            'CRI' => 'Costa Rica',
            'PAN' => 'Panamá',
            'COL' => 'Colombia',
            'VEN' => 'Venezuela',
            'GUY' => 'Guyana',
            'SUR' => 'Suriname',
            'GUF' => 'Guayana Francesa',
            'BRA' => 'Brasil',
            'ECU' => 'Ecuador',
            'PER' => 'Perú',
            'BOL' => 'Bolivia',
            'PRY' => 'Paraguay',
            'URY' => 'Uruguay',
            'ARG' => 'Argentina',
            'CHL' => 'Chile',
            'ESP' => 'España',
            'FRA' => 'Francia',
            'DEU' => 'Alemania',
            'ITA' => 'Italia',
            'GBR' => 'Reino Unido',
            'PRT' => 'Portugal',
            'NLD' => 'Países Bajos',
            'BEL' => 'Bélgica',
            'CHE' => 'Suiza',
            'AUT' => 'Austria',
            'SWE' => 'Suecia',
            'NOR' => 'Noruega',
            'DNK' => 'Dinamarca',
            'FIN' => 'Finlandia',
            'POL' => 'Polonia',
            'CZE' => 'República Checa',
            'HUN' => 'Hungría',
            'SVK' => 'Eslovaquia',
            'SVN' => 'Eslovenia',
            'HRV' => 'Croacia',
            'ROU' => 'Rumania',
            'BGR' => 'Bulgaria',
            'GRC' => 'Grecia',
            'TUR' => 'Turquía',
            'RUS' => 'Rusia',
            'UKR' => 'Ucrania',
            'CHN' => 'China',
            'JPN' => 'Japón',
            'KOR' => 'Corea del Sur',
            'IND' => 'India',
            'THA' => 'Tailandia',
            'VNM' => 'Vietnam',
            'IDN' => 'Indonesia',
            'MYS' => 'Malasia',
            'SGP' => 'Singapur',
            'PHL' => 'Filipinas',
            'AUS' => 'Australia',
            'NZL' => 'Nueva Zelanda',
            'ZAF' => 'Sudáfrica',
            'EGY' => 'Egipto',
            'MAR' => 'Marruecos',
            'NGA' => 'Nigeria',
            'KEN' => 'Kenia',
            'ETH' => 'Etiopía',
            'ISR' => 'Israel',
            'SAU' => 'Arabia Saudí',
            'ARE' => 'Emiratos Árabes Unidos',
            'QAT' => 'Catar',
            'KWT' => 'Kuwait',
            'JOR' => 'Jordania',
            'LBN' => 'Líbano',
            'IRQ' => 'Irak',
            'IRN' => 'Irán',
        ];
    }

    /**
     * Get country name by code.
     */
    public static function getName(string $code): ?string
    {
        return self::all()[$code] ?? null;
    }

    /**
     * Get all country codes.
     *
     * @return array<int, string>
     */
    public static function getCodes(): array
    {
        return array_keys(self::all());
    }

    /**
     * Get countries for specific regions.
     */
    public static function getByRegion(string $region): array
    {
        return match ($region) {
            'north_america' => [
                'USA' => 'Estados Unidos',
                'CAN' => 'Canadá',
                'MEX' => 'México',
            ],
            'central_america' => [
                'GTM' => 'Guatemala',
                'BLZ' => 'Belice',
                'SLV' => 'El Salvador',
                'HND' => 'Honduras',
                'NIC' => 'Nicaragua',
                'CRI' => 'Costa Rica',
                'PAN' => 'Panamá',
            ],
            'south_america' => [
                'COL' => 'Colombia',
                'VEN' => 'Venezuela',
                'GUY' => 'Guyana',
                'SUR' => 'Suriname',
                'GUF' => 'Guayana Francesa',
                'BRA' => 'Brasil',
                'ECU' => 'Ecuador',
                'PER' => 'Perú',
                'BOL' => 'Bolivia',
                'PRY' => 'Paraguay',
                'URY' => 'Uruguay',
                'ARG' => 'Argentina',
                'CHL' => 'Chile',
            ],
            'europe' => [
                'ESP' => 'España',
                'FRA' => 'Francia',
                'DEU' => 'Alemania',
                'ITA' => 'Italia',
                'GBR' => 'Reino Unido',
                'PRT' => 'Portugal',
                'NLD' => 'Países Bajos',
                'BEL' => 'Bélgica',
                'CHE' => 'Suiza',
                'AUT' => 'Austria',
                'SWE' => 'Suecia',
                'NOR' => 'Noruega',
                'DNK' => 'Dinamarca',
                'FIN' => 'Finlandia',
                'POL' => 'Polonia',
                'CZE' => 'República Checa',
                'HUN' => 'Hungría',
                'SVK' => 'Eslovaquia',
                'SVN' => 'Eslovenia',
                'HRV' => 'Croacia',
                'ROU' => 'Rumania',
                'BGR' => 'Bulgaria',
                'GRC' => 'Grecia',
                'TUR' => 'Turquía',
                'RUS' => 'Rusia',
                'UKR' => 'Ucrania',
            ],
            'asia' => [
                'CHN' => 'China',
                'JPN' => 'Japón',
                'KOR' => 'Corea del Sur',
                'IND' => 'India',
                'THA' => 'Tailandia',
                'VNM' => 'Vietnam',
                'IDN' => 'Indonesia',
                'MYS' => 'Malasia',
                'SGP' => 'Singapur',
                'PHL' => 'Filipinas',
            ],
            'oceania' => [
                'AUS' => 'Australia',
                'NZL' => 'Nueva Zelanda',
            ],
            'africa' => [
                'ZAF' => 'Sudáfrica',
                'EGY' => 'Egipto',
                'MAR' => 'Marruecos',
                'NGA' => 'Nigeria',
                'KEN' => 'Kenia',
                'ETH' => 'Etiopía',
            ],
            'middle_east' => [
                'ISR' => 'Israel',
                'SAU' => 'Arabia Saudí',
                'ARE' => 'Emiratos Árabes Unidos',
                'QAT' => 'Catar',
                'KWT' => 'Kuwait',
                'JOR' => 'Jordania',
                'LBN' => 'Líbano',
                'IRQ' => 'Irak',
                'IRN' => 'Irán',
            ],
            default => self::all(),
        };
    }

    /**
     * Check if country code exists.
     */
    public static function exists(string $code): bool
    {
        return array_key_exists($code, self::all());
    }

    /**
     * Get validation rule for countries.
     */
    public static function getValidationRule(): string
    {
        return 'in:' . implode(',', self::getCodes());
    }
}
