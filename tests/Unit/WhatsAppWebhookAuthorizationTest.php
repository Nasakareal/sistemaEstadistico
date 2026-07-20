<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppInboundService;
use App\Services\WhatsApp\WhatsAppMenuService;
use App\Services\WhatsApp\WhatsAppQueryService;
use App\Services\WhatsApp\WhatsAppStateService;
use App\Services\WhatsApp\WhatsAppUserResolverService;
use App\Services\WhatsAppCloudService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WhatsAppWebhookAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_superadmin_con_bloqueo_previo_recibe_respuesta_y_limpia_cooldown(): void
    {
        $from = '5214431234567';
        $user = new User([
            'name' => 'Super Admin',
            'telefono' => $from,
            'unidad_id' => 1,
        ]);
        $user->id = 99;

        $resolver = new class($user) extends WhatsAppUserResolverService {
            public int $lookups = 0;
            private User $user;

            public function __construct(User $user)
            {
                $this->user = $user;
            }

            public function findAuthorizedUserByPhone(string $from): ?User
            {
                $this->lookups++;

                return $this->user;
            }

            public function resolveContext(User $user): array
            {
                return [
                    'acceso_total' => true,
                    'modules' => [
                        'siniestros',
                        'delegaciones',
                        'coordinacion',
                        'carreteras',
                        'vialidades',
                    ],
                    'default_module' => null,
                    'solo_propios' => false,
                    'unidad_slug' => 'siniestros',
                    'unidad_id' => 1,
                ];
            }
        };

        $cloud = new class extends WhatsAppCloudService {
            public array $texts = [];
            public array $interactives = [];

            public function sendText(string $to, string $body): array
            {
                $this->texts[] = compact('to', 'body');

                return ['ok' => true];
            }

            public function sendInteractive(string $to, array $interactive): array
            {
                $this->interactives[] = compact('to', 'interactive');

                return ['ok' => true];
            }
        };

        $controller = new class(
            new WhatsAppInboundService(),
            $resolver,
            new WhatsAppMenuService(),
            new WhatsAppStateService(),
            new class extends WhatsAppQueryService {
                public function __construct()
                {
                }
            },
            $cloud
        ) extends WhatsAppWebhookController {
            public function process(array $message): void
            {
                $this->processIncomingMessage($message);
            }

            public function seedUnauthorizedCooldown(string $from): void
            {
                $this->silenceUnauthorizedSender($from);
            }

            public function unauthorizedState(string $from): array
            {
                return $this->getUnauthorizedState($from);
            }
        };

        $controller->seedUnauthorizedCooldown($from);

        $this->assertNotEmpty($controller->unauthorizedState($from));

        $controller->process([
            'from' => $from,
            'type' => 'text',
            'text' => [
                'body' => 'MENÚ',
            ],
        ]);

        $this->assertSame(1, $resolver->lookups);
        $this->assertNotEmpty($cloud->texts);
        $this->assertNotEmpty($cloud->interactives);
        $this->assertStringContainsString('Selecciona la unidad que deseas consultar.', $cloud->texts[0]['body']);
        $this->assertSame('Menú general', $cloud->interactives[0]['interactive']['header']['text']);
        $this->assertEmpty($controller->unauthorizedState($from));
    }

    public function test_resolver_considera_variantes_mexicanas_de_telefono(): void
    {
        $resolver = new class extends WhatsAppUserResolverService {
            public function variants(string $value): array
            {
                return $this->phoneVariants($value);
            }
        };

        $variants = $resolver->variants('52 443 123 4567');

        $this->assertSame('5214431234567', $variants[0]);
        $this->assertContains('524431234567', $variants);
        $this->assertContains('4431234567', $variants);
    }

    public function test_telefono_operativo_no_autoriza_respuestas_del_bot(): void
    {
        $phone = '521443' . random_int(1000000, 9999999);
        $user = User::factory()->create([
            'telefono' => null,
            'telefono_whatsapp_operativo' => $phone,
        ]);
        $resolver = new WhatsAppUserResolverService();

        $this->assertNull($resolver->findAuthorizedUserByPhone($phone));

        $user->update(['telefono' => $phone]);
        $authorized = $resolver->findAuthorizedUserByPhone($phone);

        $this->assertNotNull($authorized);
        $this->assertSame($user->id, $authorized->id);
    }

    public function test_telefono_secundario_si_autoriza_respuestas_del_bot(): void
    {
        $phone = '521443' . random_int(1000000, 9999999);
        $user = User::factory()->create([
            'telefono' => null,
            'telefono_whatsapp_secundario' => $phone,
            'telefono_whatsapp_operativo' => null,
        ]);

        $authorized = (new WhatsAppUserResolverService())->findAuthorizedUserByPhone($phone);

        $this->assertNotNull($authorized);
        $this->assertSame($user->id, $authorized->id);
    }
}
