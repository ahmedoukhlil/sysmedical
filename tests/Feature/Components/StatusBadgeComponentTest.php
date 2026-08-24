<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class StatusBadgeComponentTest extends TestCase
{
    private function render(string $status, string $domain = 'rdv', ?array $map = null): string
    {
        return Blade::render(
            '<x-status-badge :status="$status" :domain="$domain" :map="$map" />',
            ['status' => $status, 'domain' => $domain, 'map' => $map]
        );
    }

    public function test_case_variants_produce_the_same_class()
    {
        $lower = $this->render('En attente');
        $upper = $this->render('En Attente');

        $this->assertStringContainsString('bg-yellow-100 text-yellow-800', $lower);
        $this->assertStringContainsString('bg-yellow-100 text-yellow-800', $upper);
    }

    public function test_en_cours_is_green_not_purple()
    {
        $html = $this->render('En cours');

        $this->assertStringContainsString('bg-green-100 text-green-800', $html);
        $this->assertStringNotContainsString('purple', $html);
    }

    public function test_unknown_or_empty_status_falls_back_without_error()
    {
        $unknown = $this->render('StatutInexistant');
        $empty = $this->render('');

        $this->assertStringContainsString('bg-yellow-100 text-yellow-800', $unknown);
        $this->assertStringContainsString('bg-yellow-100 text-yellow-800', $empty);
    }

    public function test_generic_domain_uses_provided_map()
    {
        $html = $this->render('actif', 'generic', [
            'actif' => ['class' => 'bg-green-100 text-green-800', 'label' => 'Actif', 'icon' => null],
        ]);

        $this->assertStringContainsString('bg-green-100 text-green-800', $html);
        $this->assertStringContainsString('Actif', $html);
    }
}
