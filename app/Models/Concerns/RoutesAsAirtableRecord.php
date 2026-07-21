<?php

namespace App\Models\Concerns;

/**
 * Les modèles Airtable ne sont pas Eloquent : route() doit utiliser getRouteKey() (id rec…).
 *
 * @property-read string $id
 */
trait RoutesAsAirtableRecord
{
    /**
     * @return class-string<object{find(string): ?static}>
     */
    abstract protected static function routeBindingRepository(): string;

    public function getRouteKey(): string
    {
        return $this->id;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return app(static::routeBindingRepository())->find((string) $value);
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        return null;
    }
}
