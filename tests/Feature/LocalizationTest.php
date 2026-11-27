<?php

use App\Models\Competition;
use App\Models\Project;
use App\Models\User;
use App\Support\LocalDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

test('the site is Farsi and right-to-left by default', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('lang="fa" dir="rtl"', false)
        ->assertSee('پروژه‌های منتخب')
        ->assertDontSee('Selected projects');
});

test('choosing EN switches the whole site to English and left-to-right', function () {
    $this->get(route('locale.switch', 'en'))->assertRedirect();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('lang="en" dir="ltr"', false)
        ->assertSee('Selected projects')
        ->assertDontSee('پروژه‌های منتخب');
});

test('the language choice is remembered in a cookie', function () {
    $response = $this->get(route('locale.switch', 'en'));
    $response->assertCookie('locale', 'en');

    // A later visit, with only the cookie to go on.
    $this->flushSession();
    $this->withCookie('locale', 'en')->get(route('competitions.index'))->assertSee('dir="ltr"', false);
});

test('unsupported languages are rejected', function () {
    $this->get('/language/de')->assertNotFound();
});

test('the signed-in area and login page follow the chosen language', function () {
    $this->withSession(['locale' => 'fa'])->get(route('login'))
        ->assertSee('dir="rtl"', false)
        ->assertSee('ورود به حساب کاربری شما');

    $this->actingAs(User::factory()->admin()->create())
        ->withSession(['locale' => 'fa'])
        ->get(route('dashboard'))
        ->assertSee('dir="rtl"', false)
        ->assertSee('مدیریت کاربران');
});

test('Farsi pages show Farsi content and fall back to English when it is missing', function () {
    $translated = Project::factory()->create(['title' => 'Lake House', 'title_fa' => 'خانهٔ دریاچه', 'show_on_home' => true]);
    $untranslated = Project::factory()->create(['title' => 'Stone Barn', 'title_fa' => null, 'show_on_home' => true]);

    $this->withSession(['locale' => 'fa'])->get(route('projects.index'))
        ->assertSee('خانهٔ دریاچه')
        ->assertDontSee('Lake House')
        ->assertSee('Stone Barn');

    $this->withSession(['locale' => 'en'])->get(route('projects.show', $translated))
        ->assertSee('Lake House')
        ->assertDontSee('خانهٔ دریاچه');
});

test('competition requirements are shown in Farsi', function () {
    $competition = Competition::factory()->create(['title_fa' => 'مسابقهٔ آزمایشی']);
    $competition->requirements()->create(['label' => 'Boards', 'label_fa' => 'شیت‌ها', 'accepted_types' => 'pdf']);

    $this->withSession(['locale' => 'fa'])->get(route('competitions.show', $competition))
        ->assertSee('مسابقهٔ آزمایشی')
        ->assertSee('شیت‌ها');
});

test('dates use the Persian calendar in Farsi', function () {
    $date = CarbonImmutable::parse('2027-01-21 23:59');

    app()->setLocale('fa');
    expect(LocalDate::format($date))->toBe('۱ بهمن ۱۴۰۵');

    app()->setLocale('en');
    expect(LocalDate::format($date))->toBe('21 January 2027');
});

test('every interface string has a Farsi translation', function () {
    $fa = json_decode(File::get(lang_path('fa.json')), true);
    // __('…'), trans_choice('…'), @lang('…') and Livewire #[Title('…')] page titles.
    $pattern = '/(?:__|trans_choice|@lang|#\[Title)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s';
    $missing = [];

    foreach ([resource_path('views'), app_path()] as $dir) {
        foreach (File::allFiles($dir) as $file) {
            preg_match_all($pattern, $file->getContents(), $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $key = str_replace('\\'.$match[1], $match[1], $match[2]);

                if (! preg_match('/^[a-z_-]+\.[a-z0-9_.-]+$/', $key) && ! array_key_exists($key, $fa)) {
                    $missing[] = $key.'  ('.$file->getRelativePathname().')';
                }
            }
        }
    }

    expect(array_unique($missing))->toBe([]);
});
