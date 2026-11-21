<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Competition;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Demo content: two projects, one open competition and test accounts.
 *
 * The images are borrowed from the reference site for local testing only.
 * They are downloaded into storage/app/public/demo (git-ignored) and must be
 * replaced with your own photography before the site goes live.
 */
class DemoContentSeeder extends Seeder
{
    private const SOURCE = 'https://bekhuis-kleinjan.nl/wp-content/uploads/';

    /**
     * Local file name => path on the reference site.
     *
     * @var array<string, string>
     */
    private const IMAGES = [
        'hero-1.jpg' => '2024/11/BekhuisKleinJan-Bergen-op-Zoom-1-1.jpg',
        'hero-2.jpg' => '2025/08/DSC07247-HDR-scaled-1920x1280.jpg',
        'hero-3.webp' => '2026/06/Bekhuis-KleinJan-woning-ontwerp-1920x1256.webp',
        'spotlight-1.jpg' => '2024/11/BekhuisKleinJan-Bergen-op-Zoom-vooraanzicht.jpg',
        'spotlight-2.jpg' => '2024/11/BekhuisKleinJan-Bergen-op-Zoom-8.jpg',
        'spotlight-3.jpg' => '2024/01/BekhuisKleinJan-villa-bergenopzoom.jpg',
        'villa-front.webp' => '2026/06/moderne-rietgedekte-villa-voorgevel-bekhuis_kleinjan_.webp',
        'villa-side.webp' => '2026/06/villa-rieten-dak-lamellen-zijgevel-900x600.webp',
        'villa-entrance.webp' => '2026/06/entree-moderne-villa-rietkap-houten-voordeur-bekhuiskleinjan-900x1350.webp',
        'barn-house.jpg' => '2025/11/Moderne-schuurwoning.jpg',
        'barn-canopy.jpg' => '2025/11/Schuurwoning-overkapping-900x679.jpg',
        'barn-garden.webp' => '2026/06/Bekhuis-KleinJan-woning-ontwerp_-e1781528780110-900x480.webp',
        'villa-lake.jpg' => '2024/11/Luxe-villa-Meerstad-12-900x599.jpg',
    ];

    public function run(): void
    {
        $images = $this->downloadImages();

        $admin = User::where('role', Role::Admin->value)->firstOrFail();

        $jury = $this->demoUser('jury@example.com', 'Demo Jury Member', fn (User $u) => $u->role = Role::Jury);
        $this->demoUser('editor@example.com', 'Demo Editor', fn (User $u) => $u->can_edit = true);
        $this->demoUser('participant@example.com', 'Demo Participant');

        $villa = $admin->projects()->firstOrNew(['slug' => 'villa-under-reed']);
        $villa->fill([
            'title' => 'Villa under Reed',
            'summary' => 'A contemporary family villa that pairs a sculpted thatched roof with crisp white volumes and generous glazing.',
            'body' => "The brief asked for a home that feels rooted in the landscape without imitating the past. The answer is a single, sweeping thatched roof that folds over two white masonry volumes.\n\n## Light and landscape\n\nFloor-to-ceiling glazing opens the living spaces to the garden, while timber louvres on the side elevation filter the afternoon sun.\n\n## Materials\n\nReed, lime-washed brick, oak and steel — a restrained palette that ages gracefully.",
            'location' => 'Overijssel, NL',
            'year' => 2025,
            'category' => 'Residential',
            'title_fa' => 'ویلا زیر سقف نی',
            'summary_fa' => 'ویلایی خانوادگی و معاصر که بامی تندیس‌گونه از نی را با حجم‌های سفید و خالص و جداره‌های شیشه‌ای گسترده همراه می‌کند.',
            'body_fa' => "خواستهٔ کارفرما خانه‌ای بود که در منظر ریشه داشته باشد، بی‌آنکه از گذشته تقلید کند. پاسخ، بامی یکپارچه و روان از نی است که بر دو حجم آجری سفید فرود آمده است.\n\n## نور و منظر\n\nجداره‌های شیشه‌ای از کف تا سقف، فضاهای نشیمن را به باغ می‌گشایند و لوورهای چوبی نمای جانبی، آفتاب بعدازظهر را پالایش می‌کنند.\n\n## مصالح\n\nنی، آجر با دوغاب آهکی، چوب بلوط و فولاد؛ پالتی سنجیده که با گذر زمان زیباتر می‌شود.",
            'location_fa' => 'اوفرایسل، هلند',
            'category_fa' => 'مسکونی',
            'cover_image' => $images['villa-front.webp'],
            'is_published' => true,
            'show_on_home' => true,
        ])->save();
        $this->gallery($villa, [$images['villa-side.webp'], $images['villa-entrance.webp'], $images['villa-lake.jpg']]);

        $barn = $admin->projects()->firstOrNew(['slug' => 'the-barn-house']);
        $barn->fill([
            'title' => 'The Barn House',
            'summary' => 'A modern reinterpretation of the regional barn: one calm gable, dark timber cladding and a deep sheltered terrace.',
            'body' => "Inspired by the agricultural buildings that dot the countryside, the Barn House reduces the program to a single, archetypal form.\n\n## A sheltered outdoor room\n\nA deep canopy extends the living space outdoors, creating a terrace that is usable in every season.\n\n## Quiet detailing\n\nConcealed gutters and flush window frames keep the silhouette pure.",
            'location' => 'Twente, NL',
            'year' => 2024,
            'category' => 'Residential',
            'title_fa' => 'خانهٔ انبار',
            'summary_fa' => 'بازخوانی معاصر انبارهای روستایی منطقه: یک شیروانی آرام، نمای چوبی تیره و ایوانی عمیق و سرپوشیده.',
            'body_fa' => "این خانه با الهام از بناهای کشاورزی که در روستاها پراکنده‌اند، برنامهٔ فضایی را در فرمی واحد و کهن‌الگویی خلاصه می‌کند.\n\n## اتاقی سرپوشیده در فضای باز\n\nسایبانی عمیق، فضای نشیمن را به بیرون امتداد می‌دهد و تراسی می‌سازد که در همهٔ فصل‌ها قابل استفاده است.\n\n## جزئیات آرام\n\nناودان‌های پنهان و قاب‌های پنجرهٔ هم‌سطح، نیم‌رخ بنا را خالص نگه می‌دارند.",
            'location_fa' => 'توِنته، هلند',
            'category_fa' => 'مسکونی',
            'cover_image' => $images['barn-house.jpg'],
            'is_published' => true,
            'show_on_home' => true,
        ])->save();
        $this->gallery($barn, [$images['barn-canopy.jpg'], $images['barn-garden.webp']]);

        $competition = Competition::firstOrNew(['slug' => 'waterfront-living-2027']);
        $competition->user_id = $admin->id;
        $competition->fill([
            'title' => 'Waterfront Living 2027',
            'summary' => 'An open ideas competition for a contemporary residence on the water’s edge — sustainable, site-specific and timeless.',
            'description' => "Charehco invites architects, designers and students to imagine a private residence on a lakeside plot of 2,400 m².\n\n## The challenge\n\nDesign a family home of around 350 m² that responds to the water, the light and the changing seasons, using low-carbon materials.\n\n## Evaluation\n\nThe jury evaluates entries on architectural quality, relation to the landscape, sustainability and feasibility. Judging is anonymous.\n\n## Prizes\n\nFirst prize €10,000, second prize €5,000, and three honourable mentions.",
            'location' => 'Lakeside plot, NL',
            'prize' => '€10,000 first prize',
            'title_fa' => 'زندگی در کنار آب ۲۰۲۷',
            'summary_fa' => 'مسابقه‌ای ایده‌محور و آزاد برای طراحی اقامتگاهی معاصر در لبهٔ آب؛ پایدار، متناسب با بستر و ماندگار.',
            'description_fa' => "چاره‌کو از معماران، طراحان و دانشجویان دعوت می‌کند تا یک اقامتگاه خصوصی را در زمینی به مساحت ۲٬۴۰۰ متر مربع در کنار دریاچه تصور کنند.\n\n## چالش\n\nخانه‌ای خانوادگی با زیربنای حدود ۳۵۰ متر مربع طراحی کنید که به آب، نور و تغییر فصل‌ها پاسخ دهد و از مصالح کم‌کربن بهره بگیرد.\n\n## داوری\n\nهیئت داوران آثار را بر پایهٔ کیفیت معماری، ارتباط با منظر، پایداری و امکان‌پذیری ارزیابی می‌کند. داوری به‌صورت ناشناس انجام می‌شود.\n\n## جوایز\n\nجایزهٔ اول ۱۰٬۰۰۰ یورو، جایزهٔ دوم ۵٬۰۰۰ یورو و سه تقدیرنامه.",
            'location_fa' => 'زمینی در کنار دریاچه، هلند',
            'prize_fa' => 'جایزهٔ اول ۱۰٬۰۰۰ یورو',
            'cover_image' => $images['spotlight-1.jpg'],
            'starts_at' => now()->subWeeks(2)->startOfDay(),
            'deadline_at' => now()->addMonths(2)->setTime(23, 59),
            'results_at' => now()->addMonths(3)->startOfDay(),
            'anonymous_judging' => true,
            'is_published' => true,
            'show_on_home' => true,
        ])->save();

        $requirements = [
            ['label' => 'Presentation boards', 'label_fa' => 'شیت‌های ارائه', 'description' => 'Two A1 boards in landscape orientation, combined into a single PDF.', 'description_fa' => 'دو شیت A1 افقی که در یک فایل PDF ترکیب شده باشند.', 'is_required' => true, 'accepted_types' => 'pdf', 'max_size_mb' => 30],
            ['label' => 'Design report', 'label_fa' => 'گزارش طراحی', 'description' => 'Up to 10 pages explaining the concept, materials and sustainability strategy.', 'description_fa' => 'حداکثر ۱۰ صفحه دربارهٔ ایده، مصالح و راهبرد پایداری.', 'is_required' => true, 'accepted_types' => 'pdf', 'max_size_mb' => 20],
            ['label' => 'Key image', 'label_fa' => 'تصویر شاخص', 'description' => 'One representative render or photograph for publication.', 'description_fa' => 'یک رندر یا عکس شاخص برای انتشار.', 'is_required' => true, 'accepted_types' => 'jpg,jpeg,png', 'max_size_mb' => 10],
            ['label' => 'Supplementary material', 'label_fa' => 'مدارک تکمیلی', 'description' => 'Optional: models, drawings or animations.', 'description_fa' => 'اختیاری: ماکت، نقشه یا انیمیشن.', 'is_required' => false, 'accepted_types' => 'zip,pdf,mp4', 'max_size_mb' => 50],
        ];

        foreach ($requirements as $sort => $data) {
            $competition->requirements()->updateOrCreate(['label' => $data['label']], [...$data, 'sort' => $sort + 1]);
        }

        $competition->jury()->syncWithoutDetaching([$jury->id]);
    }

    /**
     * @return array<string, string> local name => public-disk path (or remote URL on failure)
     */
    private function downloadImages(): array
    {
        $disk = Storage::disk('public');
        $paths = [];

        foreach (self::IMAGES as $name => $source) {
            $path = 'demo/'.$name;
            $url = self::SOURCE.$source;

            if (! $disk->exists($path)) {
                try {
                    $response = Http::timeout(30)->withUserAgent('Mozilla/5.0 (demo seeder)')->get($url);
                    $response->throw();
                    $disk->put($path, $response->body());
                } catch (Throwable $e) {
                    $this->command?->warn("Could not download {$url}; using the remote URL instead.");
                    $paths[$name] = $url;

                    continue;
                }
            }

            $paths[$name] = $path;
        }

        return $paths;
    }

    /**
     * Demo accounts use the factory password ("password"). Local use only.
     */
    private function demoUser(string $email, string $name, ?callable $configure = null): User
    {
        $user = User::where('email', $email)->first()
            ?? User::factory()->create(['email' => $email, 'name' => $name]);

        if ($configure) {
            $configure($user);
            $user->save();
        }

        return $user;
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function gallery(Project $project, array $paths): void
    {
        if ($project->images()->exists()) {
            return;
        }

        foreach ($paths as $i => $path) {
            $project->images()->create(['path' => $path, 'sort' => $i]);
        }
    }
}
