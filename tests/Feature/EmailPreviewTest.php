<?php

use App\Http\Controllers\EmailPreviewController;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('the email preview gallery renders every transactional email', function () {
    $response = $this->get('/emails/preview');

    $response->assertOk();
    $response->assertSee('Transactional email previews');

    $controller = new EmailPreviewController;
    $method = new ReflectionMethod($controller, 'renderAll');
    $method->setAccessible(true);

    foreach ($method->invoke($controller) as $key => $mail) {
        $response->assertSee($mail['label'], false);
        $response->assertSee(e($mail['subject']), false);
    }
});

test('a single email preview renders the full mailable html', function () {
    $response = $this->get('/emails/preview/welcome');

    $response->assertOk();
    $response->assertSee('Welcome to ProoDev, Ava Builds!', false);
    $response->assertSee('Start building', false);
});

test('emails embed the logo inline so it never appears broken', function () {
    $this->get('/emails/preview/welcome')
        ->assertOk()
        ->assertSee('data:image/png;base64,', false)
        ->assertDontSee('images/logo-black.png', false);
});

test('the chat reminder email preview renders and links to the conversation', function () {
    $response = $this->get('/emails/preview/chat-reminder');

    $response->assertOk();
    $response->assertSee('You have an unread message', false);
    $response->assertSee('Dana Okafor messaged you on ProoDev', false);
    $response->assertSee('Open chat', false);
    $response->assertSee('Latest message', false);
});

test('the payment-received acknowledgment renders in the preview', function () {
    $this->get('/emails/preview/payment-received')
        ->assertOk()
        ->assertSee('Payment received — we\'re on it', false)
        ->assertSee('What happens next', false)
        ->assertSee('View billing history', false)
        ->assertSee('This is an acknowledgment only', false)
        ->assertSee('proodev.com | Aletheia Uganda Software Company Limited', false)
        ->assertSee('Tel: +256 786 634 306', false)
        ->assertSee('Tax ID UG 1016550521', false);

    $this->get('/emails/preview')
        ->assertOk()
        ->assertSee('Payment received (buyer acknowledgment)', false);
});

test('the new job and evidence emails render in the preview', function () {
    $this->get('/emails/preview/new-job-posted')
        ->assertOk()
        ->assertSee('A new role is open', false)
        ->assertSee('View role', false);

    $this->get('/emails/preview/evidence-analyzed')
        ->assertOk()
        ->assertSee('Your scan is ready', false)
        ->assertSee('Evidence score', false);

    $this->get('/emails/preview/evidence-added')
        ->assertOk()
        ->assertSee('Evidence added', false);
});

test('an unknown email preview returns 404', function () {
    $this->get('/emails/preview/does-not-exist')->assertNotFound();
});

test('previewing emails never persists sample records', function () {
    $before = User::where(function ($q) {
        $q->where('username', 'like', 'ava-builds-%')
            ->orWhere('username', 'like', 'noah-mwangi-%')
            ->orWhere('username', 'like', 'dana-okafor-%');
    })->count();

    $this->get('/emails/preview')->assertOk();
    $this->get('/emails/preview/payout-worldremit')->assertOk();

    $after = User::where(function ($q) {
        $q->where('username', 'like', 'ava-builds-%')
            ->orWhere('username', 'like', 'noah-mwangi-%')
            ->orWhere('username', 'like', 'dana-okafor-%');
    })->count();

    expect($after)->toBe($before);
});

test('previewing the chat reminder never persists conversations or messages', function () {
    $beforeConversations = DB::table('wirechat_conversations')->count();
    $beforeMessages = DB::table('wirechat_messages')->count();

    $this->get('/emails/preview/chat-reminder')->assertOk();

    expect(DB::table('wirechat_conversations')->count())->toBe($beforeConversations);
    expect(DB::table('wirechat_messages')->count())->toBe($beforeMessages);
});
