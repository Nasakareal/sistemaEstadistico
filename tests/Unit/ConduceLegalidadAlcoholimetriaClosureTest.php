<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\ConduceLegalidadOperativo;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use ReflectionMethod;
use Tests\TestCase;

class ConduceLegalidadAlcoholimetriaClosureTest extends TestCase
{
    public function test_alcoholimetria_closes_at_exactly_eight_hours(): void
    {
        $createdAt = CarbonImmutable::parse('2026-07-22 10:00:00', 'America/Mexico_City');
        $operativo = $this->operativo('alcoholimetria', $createdAt);

        $this->assertFalse($this->isClosed($operativo, $createdAt->addHours(8)->subSecond()));
        $this->assertTrue($this->isClosed($operativo, $createdAt->addHours(8)));
        $this->assertTrue($this->isClosed($operativo, $createdAt->addHours(8)->addSecond()));
    }

    public function test_other_operatives_are_not_closed_by_this_rule(): void
    {
        $createdAt = CarbonImmutable::parse('2026-07-22 10:00:00', 'America/Mexico_City');
        $operativo = $this->operativo('conduce_legalidad', $createdAt);

        $this->assertFalse($this->isClosed($operativo, $createdAt->addDay()));
    }

    public function test_only_superadmin_can_feed_after_the_deadline(): void
    {
        $createdAt = CarbonImmutable::parse('2026-07-22 10:00:00', 'America/Mexico_City');
        $operativo = $this->operativo('alcoholimetria', $createdAt);
        Carbon::setTestNow($createdAt->addHours(8));

        try {
            $this->assertFalse($this->canFeed($operativo, false));
            $this->assertTrue($this->canFeed($operativo, true));
        } finally {
            Carbon::setTestNow();
        }
    }

    private function operativo(string $tipo, CarbonImmutable $createdAt): ConduceLegalidadOperativo
    {
        $operativo = new ConduceLegalidadOperativo();
        $operativo->forceFill([
            'tipo_operativo' => $tipo,
            'nombre' => $tipo === 'alcoholimetria' ? 'Operativo Alcoholimetria' : 'Operativo conduce con legalidad',
            'estado' => 'activo',
            'created_at' => $createdAt,
        ]);

        return $operativo;
    }

    private function isClosed(ConduceLegalidadOperativo $operativo, CarbonImmutable $instant): bool
    {
        $method = new ReflectionMethod(ConduceLegalidadController::class, 'alimentacionAlcoholimetriaCerrada');
        $method->setAccessible(true);

        return $method->invoke(new ConduceLegalidadController(), $operativo, $instant);
    }

    private function canFeed(ConduceLegalidadOperativo $operativo, bool $superadmin): bool
    {
        $user = new class ($superadmin) {
            private $superadmin;

            public function __construct(bool $superadmin)
            {
                $this->superadmin = $superadmin;
            }

            public function hasRole(string $role): bool
            {
                return $this->superadmin && $role === 'Superadmin';
            }
        };
        $method = new ReflectionMethod(ConduceLegalidadController::class, 'puedeAlimentarOperativo');
        $method->setAccessible(true);

        return $method->invoke(new ConduceLegalidadController(), $operativo, $user);
    }
}
