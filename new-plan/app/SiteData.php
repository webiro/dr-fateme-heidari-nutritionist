<?php
/**
 * SiteData — builds everything the public frontend needs from the database.
 *
 * Produces the same JSON shape the static site inlined into index.html
 * (site-config / site-slogans / site-media), so assets/app.js keeps working
 * unchanged apart from the small dynamic-content extensions.
 */

final class SiteData
{
    private const CACHE_TTL = 900; // page cache seconds (0 = disabled)

    /** The exact SEO article of the previous static site (content.txt). */
    private static function defaultArticleHtml(): string
    {
        $file = __DIR__ . '/article-default.html';
        return is_file($file) ? (string) file_get_contents($file) : '';
    }

    /** Default master prompt for the dashboard AI idea generator. */
    private static function defaultMasterPrompt(): string
    {
        return <<<'PROMPT'
تو دستیار تولید محتوای وب‌سایت «دکتر فاطمه حیدری، متخصص و کارشناس تغذیه در نوشهر» هستی.
۱۰ موضوع داغ، ترند و جذاب از دنیای تغذیه، رژیم‌درمانی، لاغری، عضله‌سازی و سبک زندگی سالم که این هفته برای مخاطب فارسی‌زبان ایرانی پرطرفدار هستند، پیشنهاد بده.
موضوعات باید ترکیبی باشند از: اخبار و ترندهای جدید تغذیه، سوالات پرتکرار مخاطبان، باورهای غلط رایج، و نکات کاربردی برای رزرو مشاوره.
برای هر مورد فقط و فقط JSON معتبر برگردان، بدون هیچ متن اضافه:

[
  {
    "title": "تیتر جذاب و کوتاه فارسی",
    "summary": "توضیح دو تا سه جمله‌ای برای شروع تولید محتوا",
    "format": "reel یا post یا article",
    "hashtag": "#هشتگ_پیشنهادی"
  }
]
PROMPT;
    }

    /** Diet platform master prompt (body only; the JSON tail is always appended by the app). */
    private static function defaultPlatformPrompt(): string
    {
        $file = __DIR__ . '/platform-prompt-default.txt';
        return is_file($file) ? rtrim((string) file_get_contents($file)) : '';
    }

    /** Defaults mirror the current static site exactly. */
    public static function defaults(): array
    {
        return [
            'identity' => [
                'nameFa' => 'فاطمه حیدری',
                'nameEn' => 'Fateme Heidari',
                'shortNameFa' => 'فاطمه حیدری',
                'title' => 'متخصص و کارشناس تغذیه در نوشهر',
                'medicalCode' => 'ت-۱۱۴۴۲',
                'city' => 'نوشهر',
                'province' => 'مازندران',
                'country' => 'ایران',
                'intro' => 'با طراحی برنامه‌های غذایی متناسب با شرایط، هدف و سبک زندگی شما، در مسیر رسیدن به تغذیه سالم و وزن پایدار همراه شما هستم.',
                'footerText' => 'متخصص و کارشناس تغذیه در نوشهر. همراهی آرام برای تغذیه آگاهانه‌تر.',
            ],
            'links' => [
                'website' => 'https://drfatemeheidari.ir',
                'instagram' => 'https://instagram.com/diet_tisa',
                'telegram' => 'https://t.me/diet_tisa',
                'tisaAssistant' => 'https://t.me/ai_diet_tisa_bot',
                'phone' => '+989306968649',
                'whatsapp' => 'https://wa.me/989306968649',
                'medicalCouncil' => 'https://membersearch.irimc.org/member/profile?id=e5608720-3b33-42c7-a2e5-f45aec17a7ba',
                'bale' => '',
                'rubika' => '',
                'eitaa' => '',
            ],
            'seo' => [
                'title' => 'دکتر فاطمه حیدری ‐ متخصص و کارشناس تغذیه در نوشهر',
                'description' => 'فاطمه حیدری، متخصص و کارشناس تغذیه در نوشهر، برای طراحی برنامه غذایی متناسب با شرایط شما در کنار شماست. Fateme Heidari در مسیر تغذیه سالم و وزن پایدار همراه شماست.',
                'robots' => 'index,follow',
                'ogTitle' => 'دکتر فاطمه حیدری ‐ متخصص و کارشناس تغذیه در نوشهر',
                'ogDescription' => 'دکتر فاطمه حیدری ‐ کارشناس و متخصص تغذیه در شهر نوشهر ‐ دکتر تغذیه فاطمه حیدری نائیج',
                'ogImage' => 'assets/images/og-image.jpg',
                'ogSiteName' => 'دکتر فاطمه حیدری | تغذیه و رژیم‌درمانی',
                'ogImageAlt' => 'دکتر فاطمه حیدری، متخصص تغذیه در نوشهر',
                'twitterCard' => 'summary_large_image',
                'ratingValue' => '4.9',
                'ratingCount' => '271',
            ],
            'headings' => [
                'heroKicker' => 'Nutrition Studio · Nowshahr',
                'intro' => 'تغذیه فقط یک رژیم غذایی نیست',
                'introParagraph' => 'برنامه غذایی وقتی معنا پیدا می‌کند که با هدف شما، ریتم زندگی، وضعیت بدن، عادت‌های روزمره و ملاحظات سلامتی‌تان هماهنگ باشد. من کنار شما می‌مانم تا مسیر تغذیه، آرام، واقع‌بینانه و قابل ادامه باشد.',
                'introPills' => 'هدف شخصی|سبک زندگی|شرایط بدن|عادت‌ها|ملاحظات سلامت',
                'services' => 'خدمات تغذیه و رژیم غذایی دکتر فاطمه حیدری',
                'servicesSub' => 'مسیرهای متفاوت، یک نگاه دقیق و شخصی.',
                'faq' => 'سوالات متداول درباره رژیم و تغذیه با دکتر فاطمه حیدری',
                'videos' => 'آخرین ویدیوهای اینستاگرام دکتر فاطمه حیدری',
                'posts' => 'پست‌های اینستاگرام دکتر فاطمه حیدری',
                'contact' => 'راه‌های ارتباطی با دکتر فاطمه حیدری',
                'appoint' => 'رزرو وقت مشاوره تغذیه با دکتر فاطمه حیدری',
                'formNote' => 'فرم را با آرامش پر کنید. پس از ثبت، برای هماهنگی زمان با شما تماس گرفته می‌شود.',
                'videosEmpty' => 'ویدیویی در حال حاضر بارگذاری نشده است.',
                'postsEmpty' => 'پستی در حال حاضر بارگذاری نشده است.',
                'appointmentCta' => 'دریافت نوبت مشاوره',
            ],
            'location' => [
                'enabled' => true,
                'title' => 'آدرس مطب',
                'address' => 'نوشهر، استان مازندران',
                'lat' => 36.6508,
                'lng' => 51.4962,
            ],
            'sections' => [
                'intro' => true,
                'services' => true,
                'faq' => true,
                'videos' => true,
                'posts' => true,
                'contact' => true,
                'appointment' => true,
                'article' => true,
                'partners' => true,
                'location' => true,
            ],
            'colors' => [
                'light' => [
                    'bg' => '#faf9f6',
                    'surface' => 'rgba(255, 255, 255, 0.58)',
                    'blue' => '#b0c4de',
                    'pink' => '#f5b7c1',
                    'navy' => '#1b2433',
                    'text' => '#1b2433',
                    'muted' => '#5c6b80',
                    'line' => 'rgba(27, 36, 51, 0.08)',
                ],
                'dark' => [
                    'bg' => '#0e141d',
                    'surface' => 'rgba(22, 30, 44, 0.62)',
                    'blue' => '#b0c4de',
                    'pink' => '#f5b7c1',
                    'navy' => '#eef3f8',
                    'text' => '#f6f3ee',
                    'muted' => '#b7c2d0',
                    'line' => 'rgba(250, 249, 246, 0.08)',
                ],
            ],
            'article' => [
                'html' => self::defaultArticleHtml(),
            ],
            /** ترتیب نمایش سکشن‌ها در صفحه اصلی (هیرو همیشه اول است). */
            'sectionOrder' => [
                'intro', 'services', 'faq', 'videos', 'posts',
                'contact', 'appoint', 'article', 'partners', 'location',
            ],
            /** تنظیمات هوش مصنوعی (پاستا کلاد). */
            'ai' => [
                'provider' => 'paasta',
                'endpoint' => 'https://console.paasta.cloud/api/ai/v1/chat/completions',
                'modelsEndpoint' => 'https://console.paasta.cloud/api/ai/v1/models',
                'apiKey' => '',
                'model' => 'deepseek/deepseek-v4-flash',
                'masterPrompt' => self::defaultMasterPrompt(),
            ],
            /** پلتفرم تغذیه‌ساز (تنظیمات اولیه). */
            'platform' => [
                'clinician' => 'فاطمه حیدری',
                'lic' => '۱۱۴۴۲',
                'brand' => '',
                'addr' => '',
                'phone' => '09306968649',
                'instagram' => 'diet_tisa',
                'aiModel' => 'deepseek/deepseek-v4-flash',
                'apiKey' => '', // خالی = استفاده از کلید بخش هوش مصنوعی
                'masterPrompt' => self::defaultPlatformPrompt(),
                'meals' => [
                    'fasting' => true,
                    'breakfast' => true,
                    'morning' => true,
                    'lunch' => true,
                    'afternoon' => true,
                    'dinner' => true,
                    'latenight' => true,
                    'supplements' => true,
                ],
                'customTemplates' => [],
            ],
            'slogans' => [
                'Small steps. Stronger habits.',
                'Eat well. Live well.',
                'Better habits, better days.',
                'Consistency creates change.',
                'Take care of your future self.',
                'Nourish the body. Quiet the noise.',
                'Gentle discipline lasts longer.',
                'Food is information. Choose kindly.',
                'Balance is a practice, not a prize.',
                'Steady plates. Steady days.',
                'Health is built in ordinary hours.',
                'Listen to hunger. Honor energy.',
                'A calmer plate, a clearer mind.',
                'Wellness begins at the table.',
                'Progress prefers patience.',
                'Feed strength, not pressure.',
                'Your next meal can be a kind one.',
            ],
            'media' => [
                'videosPath' => 'uploads/reels/',
                'postsPath' => 'uploads/posts/',
                'maxVideos' => 50,
                'maxPosts' => 50,
                'maxPostSlides' => 30,
            ],
        ];
    }

    /** Read a settings group (JSON) merged over defaults. */
    public static function group(string $name): array
    {
        $defaults = self::defaults();
        $row = null;
        try {
            $row = Database::one('SELECT v FROM fh_settings WHERE k = ? LIMIT 1', [$name]);
        } catch (Throwable $e) {
            $row = null;
        }
        $stored = $row ? json_decode((string) $row['v'], true) : null;
        if (!is_array($stored)) {
            return $defaults[$name] ?? [];
        }
        return array_replace_recursive($defaults[$name] ?? [], $stored);
    }

    /** Write a settings group (full replace of the stored overlay). */
    public static function putGroup(string $name, array $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Database::q(
            'INSERT INTO fh_settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)',
            [$name, $json]
        );
        self::clearCache();
    }

    /** Display order of landing sections, always complete and valid. */
    public static function sectionOrder(): array
    {
        $defaults = ['intro', 'services', 'faq', 'videos', 'posts', 'contact', 'appoint', 'article', 'partners', 'location'];
        $order = array_values(array_filter(self::group('sectionOrder'), static fn($k) => in_array($k, $defaults, true)));
        foreach ($defaults as $key) {
            if (!in_array($key, $order, true)) {
                $order[] = $key;
            }
        }
        return $order;
    }

    /** Full frontend config (site-config JSON equivalent). */
    public static function config(): array
    {
        $identity = self::group('identity');
        $links = self::group('links');
        $headings = self::group('headings');
        $location = self::group('location');
        $colors = self::group('colors');
        $sections = self::group('sections');
        $media = self::group('media');
        $seo = self::group('seo');

        $services = Database::all(
            'SELECT title, text, icon FROM fh_services WHERE is_active = 1 ORDER BY sort ASC, id ASC'
        );
        $faq = Database::all(
            'SELECT question, answer FROM fh_faq WHERE is_active = 1 ORDER BY sort ASC, id ASC'
        );
        $menu = array_map(
            static fn(array $row): array => [
                'label' => $row['label'],
                'href' => $row['href'],
                'desktop' => (bool) $row['show_desktop'],
                'mobile' => (bool) $row['show_mobile'],
            ],
            Database::all('SELECT label, href, show_desktop, show_mobile FROM fh_menu WHERE is_active = 1 ORDER BY sort ASC, id ASC')
        );

        return [
            'doctor' => $identity,
            'links' => $links,
            'domain' => Env::get('SITE_DOMAIN', 'drfatemeheidari.ir'),
            'workerUrl' => '',
            'appointmentEndpoint' => Env::get('APPOINTMENT_ENDPOINT', 'api/appointment.php'),
            'location' => $location,
            'colors' => $colors,
            'media' => $media,
            'sections' => $sections,
            'headings' => $headings,
            'seo' => $seo,
            'menu' => $menu,
            'services' => $services,
            'faq' => $faq,
        ];
    }

    /** Media manifest (site-media JSON equivalent). */
    public static function media(): array
    {
        $videos = [];
        foreach (Database::all('SELECT source_type, source FROM fh_reels WHERE is_active = 1 ORDER BY sort ASC, id ASC') as $row) {
            $videos[] = ['type' => $row['source_type'], 'src' => $row['source']];
        }

        $posts = [];
        $rows = Database::all(
            'SELECT p.id, s.src FROM fh_posts p
             JOIN fh_post_slides s ON s.post_id = p.id
             WHERE p.is_active = 1
             ORDER BY p.sort ASC, p.id ASC, s.sort ASC, s.id ASC'
        );
        foreach ($rows as $row) {
            $posts[$row['id']] ??= ['folder' => (int) $row['id'], 'slides' => []];
            $posts[$row['id']]['slides'][] = $row['src'];
        }

        return ['videos' => $videos, 'posts' => array_values($posts)];
    }

    public static function slogans(): array
    {
        return ['slogans' => array_values(self::group('slogans'))];
    }

    public static function articleHtml(): string
    {
        return (string) (self::group('article')['html'] ?? '');
    }

    /** Bump a version used for cache busting of page cache files. */
    private static function cacheDir(): string
    {
        $dir = dirname(__DIR__) . '/storage/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Tiny HTML page cache for the public landing page.
     * Invalidated automatically whenever any admin action calls clearCache().
     */
    public static function pageCache(string $key): ?string
    {
        if (self::CACHE_TTL <= 0) {
            return null;
        }
        $file = self::cacheDir() . '/' . md5($key) . '.html';
        if (!is_file($file) || time() - (int) filemtime($file) > self::CACHE_TTL) {
            return null;
        }
        $html = file_get_contents($file);
        return $html === false ? null : $html;
    }

    public static function savePageCache(string $key, string $html): void
    {
        if (self::CACHE_TTL <= 0) {
            return;
        }
        @file_put_contents(self::cacheDir() . '/' . md5($key) . '.html', $html);
    }

    /** Invalidate page cache + cache bust marker. */
    public static function clearCache(): void
    {
        foreach (glob(self::cacheDir() . '/*.html') ?: [] as $file) {
            @unlink($file);
        }
        @file_put_contents(self::cacheDir() . '/content.stamp', (string) time());
    }

    /** Version string for asset cache busting, changes on every content save. */
    public static function version(): string
    {
        $stamp = self::cacheDir() . '/content.stamp';
        return is_file($stamp) ? (string) filemtime($stamp) : '1';
    }
}
