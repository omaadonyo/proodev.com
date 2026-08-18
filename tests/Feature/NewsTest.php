<?php

use App\Models\News;
use App\Models\User;
use Livewire\Livewire;

test('the admin news page requires an admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.news'))->assertForbidden();
});

test('admins can open the article form from the write button', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.news')
        ->assertOk()
        ->assertSee('Write article')
        ->call('create')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null);
});

test('admins can save an article and it publishes immediately by default', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.news')
        ->call('create')
        ->set('form.title', 'v2.0 of the Passport is here')
        ->set('form.body', 'A changelog entry.')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    $news = News::where('title', 'v2.0 of the Passport is here')->firstOrFail();
    expect($news->status())->toBe('published');
    expect($news->author_id)->toBe($admin->id);
});

test('clearing the publish date keeps an article as a draft', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.news')
        ->call('create')
        ->set('form.title', 'Draft for later')
        ->set('form.body', 'Not ready yet.')
        ->set('form.published_at', '')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    $news = News::where('title', 'Draft for later')->firstOrFail();
    expect($news->status())->toBe('draft');
});

test('the slug auto-fills from the title', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.news')
        ->call('create')
        ->set('form.title', 'Launching Talent Pools')
        ->assertSet('form.slug', 'launching-talent-pools');
});

test('admins can edit an existing article', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $news = News::factory()->create(['title' => 'Old title']);

    Livewire::actingAs($admin)
        ->test('pages::admin.news')
        ->call('edit', $news->id)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $news->id)
        ->set('form.title', 'New title')
        ->set('form.body', 'Updated body.')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect($news->fresh()->title)->toBe('New title');
});

test('admins can schedule an article', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.news')
        ->call('create')
        ->set('form.title', 'Scheduled update')
        ->set('form.body', 'Coming soon.')
        ->set('form.published_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    $news = News::where('title', 'Scheduled update')->firstOrFail();
    expect($news->status())->toBe('scheduled');
});

test('duplicate slugs are rejected', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    News::factory()->create(['slug' => 'duplicate-slug']);

    Livewire::actingAs($admin)
        ->test('pages::admin.news')
        ->call('create')
        ->set('form.title', 'Something')
        ->set('form.slug', 'duplicate-slug')
        ->set('form.body', 'Body.')
        ->call('save')
        ->assertHasErrors(['form.slug']);
});

test('the public news index shows published articles', function () {
    $published = News::factory()->create(['title' => 'Visible article']);
    News::factory()->create(['title' => 'Draft article', 'published_at' => null]);

    $this->get(route('news.index'))
        ->assertOk()
        ->assertSee('Visible article')
        ->assertSee(route('news.show', $published), escape: false)
        ->assertDontSee('Draft article');
});

test('published articles are viewable publicly', function () {
    $news = News::factory()->create(['title' => 'Open article']);

    $this->get(route('news.show', $news))
        ->assertOk()
        ->assertSee('Open article');
});

test('draft and scheduled articles are not publicly viewable', function () {
    $draft = News::factory()->create(['title' => 'Hidden draft', 'published_at' => null]);
    $scheduled = News::factory()->create(['title' => 'Hidden scheduled', 'published_at' => now()->addDay()]);

    $this->get(route('news.show', $draft))->assertNotFound();
    $this->get(route('news.show', $scheduled))->assertNotFound();
});

test('statuses render in the admin news table', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    News::factory()->create(['title' => 'Live now', 'published_at' => now()->subHour()]);
    News::factory()->create(['title' => 'Upcoming', 'published_at' => now()->addDay()]);
    News::factory()->create(['title' => 'Draft', 'published_at' => null]);

    Livewire::actingAs($admin)
        ->test('pages::admin.news')
        ->assertSee('Live now')
        ->assertSee('Published')
        ->assertSee('Upcoming')
        ->assertSee('Scheduled')
        ->assertSee('Draft');
});

test('viewing an article increments its view count', function () {
    $news = News::factory()->create(['views_count' => 5]);

    $this->get(route('news.show', $news))->assertOk();

    expect($news->fresh()->views_count)->toBe(6);
});

test('article view counts render on the public article', function () {
    $news = News::factory()->create(['views_count' => 42]);

    $this->get(route('news.show', $news))
        ->assertOk()
        ->assertSee('42');
});
