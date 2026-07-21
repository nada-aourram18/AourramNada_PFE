<?php

namespace App\Support;

class AirtableFieldMap
{
    public static function patientColumn(string $logical): string
    {
        $map = config('services.airtable.patient_columns', []);

        return (string) ($map[$logical] ?? $logical);
    }

    public static function userColumn(string $logical): string
    {
        $map = config('services.airtable.user_columns', []);

        return (string) ($map[$logical] ?? $logical);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public static function patientValue(array $fields, string $logical): mixed
    {
        $col = self::patientColumn($logical);
        if (array_key_exists($col, $fields)) {
            return $fields[$col];
        }

        return $fields[$logical] ?? null;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public static function userValue(array $fields, string $logical): mixed
    {
        $col = self::userColumn($logical);
        if (array_key_exists($col, $fields)) {
            return $fields[$col];
        }

        return $fields[$logical] ?? null;
    }

    /**
     * @param  array<string, mixed>  $logicalFields  clés logiques (name, phone, …)
     * @return array<string, mixed>  clés = noms de colonnes Airtable
     */
    public static function mapUserFieldsToAirtable(array $logicalFields): array
    {
        $out = [];
        foreach ($logicalFields as $logical => $value) {
            $out[self::userColumn($logical)] = $value;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $logicalFields
     * @return array<string, mixed>
     */
    public static function mapPatientFieldsToAirtable(array $logicalFields): array
    {
        $out = [];
        foreach ($logicalFields as $logical => $value) {
            $out[self::patientColumn($logical)] = $value;
        }

        return $out;
    }
}
