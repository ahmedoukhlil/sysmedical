<?php

namespace Tests\Feature;

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    public function test_dry_run_when_credentials_are_absent()
    {
        config(['services.whatsapp.token' => null, 'services.whatsapp.phone_number_id' => null]);
        Http::fake();

        $result = (new WhatsAppService())->send('22212345678', 'Test message');

        $this->assertTrue($result);
        Http::assertNothingSent();
    }

    public function test_sends_via_meta_cloud_api_when_credentials_present()
    {
        config([
            'services.whatsapp.token' => 'fake-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.api_version' => 'v21.0',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200),
        ]);

        $result = (new WhatsAppService())->send('22212345678', 'Test message');

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v21.0/123456789/messages'
                && $request['to'] === '22212345678'
                && $request['text']['body'] === 'Test message';
        });
    }

    public function test_returns_false_on_api_failure()
    {
        config([
            'services.whatsapp.token' => 'fake-token',
            'services.whatsapp.phone_number_id' => '123456789',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => 'bad request'], 400),
        ]);

        $result = (new WhatsAppService())->send('22212345678', 'Test message');

        $this->assertFalse($result);
    }
}
