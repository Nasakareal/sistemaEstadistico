<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MichoacanAlVolantePublicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');
    }

    public function test_public_can_open_michoacan_al_volante_without_authentication(): void
    {
        $response = $this->get('/michoacan-al-volante');

        $response->assertOk()
            ->assertSee('Michoacán al Volante')
            ->assertSee('Simular examen')
            ->assertSee('Detecta el peligro')
            ->assertSee('¿Quién pasa primero?')
            ->assertSee('Un segundo antes');
    }

    public function test_question_bank_contains_valid_training_questions(): void
    {
        $questions = json_decode(
            file_get_contents(public_path('michoacan-al-volante-assets/questions.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertCount(120, $questions);

        foreach ($questions as $question) {
            $this->assertNotEmpty($question['id']);
            $this->assertNotEmpty($question['category']);
            $this->assertNotEmpty($question['prompt']);
            $this->assertGreaterThanOrEqual(3, count($question['options']));
            $this->assertArrayHasKey($question['correct'], $question['options']);
            $this->assertNotEmpty($question['explanation']);
            $this->assertFileExists(public_path(
                'michoacan-al-volante-assets/academia/' . $question['illustration'] . '.png'
            ));
        }
    }
}
