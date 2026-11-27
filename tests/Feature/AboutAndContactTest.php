<?php

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\NewContactMessage;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function contactForm(array $overrides = []): array
{
    return [
        'name' => 'Sara Ahmadi',
        'email' => 'sara@example.com',
        'topic' => 'competitions',
        'message' => 'Can students take part in the Waterfront competition?',
        ...$overrides,
    ];
}

test('the about page renders in Farsi by default and in English on request', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('دربارهٔ چاره‌آرک');

    $this->withSession(['locale' => 'en'])->get(route('about'))
        ->assertOk()
        ->assertSee('About Chareharch')
        ->assertSee('How a competition works');
});

test('about and contact are linked from the site navigation', function () {
    $this->get(route('home'))
        ->assertSee(route('about'), false)
        ->assertSee(route('contact'), false);
});

test('the contact page shows only the contact details that are configured', function () {
    config(['chareharch.contact.email' => 'studio@chareharch.test', 'chareharch.contact.phone' => null]);

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('studio@chareharch.test')
        ->assertDontSee('tel:', false);
});

test('the contact form is translated in Farsi', function () {
    $this->withSession(['locale' => 'fa'])->get(route('contact'))
        ->assertSee('برای ما پیام بفرستید')
        ->assertSee('پرسش عمومی');
});

test('a visitor can send a message and the admin is notified', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();

    $this->withSession(['locale' => 'en'])
        ->post(route('contact.store'), contactForm())
        ->assertRedirect(route('contact').'#contact-form')
        ->assertSessionHas('contact_sent');

    $message = ContactMessage::sole();
    expect($message->email)->toBe('sara@example.com')
        ->and($message->topic)->toBe('competitions')
        ->and($message->isRead())->toBeFalse();

    Notification::assertSentTo($admin, NewContactMessage::class);

    $this->get(route('contact'))->assertSee('Your message has been sent');
});

test('the contact form validates its fields', function () {
    $this->post(route('contact.store'), contactForm(['email' => 'not-an-email', 'message' => 'short']))
        ->assertSessionHasErrors(['email', 'message']);

    expect(ContactMessage::count())->toBe(0);
});

test('messages filled in by bots are silently dropped', function () {
    Notification::fake();

    $this->post(route('contact.store'), contactForm(['website' => 'https://spam.example']))
        ->assertSessionHas('contact_sent');

    expect(ContactMessage::count())->toBe(0);
    Notification::assertNothingSent();
});

test('the contact form is rate limited', function () {
    Notification::fake();

    foreach (range(1, 5) as $i) {
        $this->post(route('contact.store'), contactForm())->assertRedirect();
    }

    $this->post(route('contact.store'), contactForm())->assertTooManyRequests();
});

test('only the admin can read messages', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.messages'))->assertOk();
    $this->actingAs(User::factory()->editor()->create())->get(route('admin.messages'))->assertForbidden();
    $this->actingAs(User::factory()->jury()->create())->get(route('admin.messages'))->assertForbidden();
});

test('opening a message marks it as read, and it can be deleted', function () {
    $admin = User::factory()->admin()->create();
    $message = ContactMessage::create(contactForm());

    Livewire::actingAs($admin)->test('pages::admin.messages')
        ->assertSee('Sara Ahmadi')
        ->call('open', $message->id);

    expect($message->fresh()->isRead())->toBeTrue();

    Livewire::actingAs($admin)->test('pages::admin.messages')->call('delete', $message->id);

    expect(ContactMessage::count())->toBe(0);
});
