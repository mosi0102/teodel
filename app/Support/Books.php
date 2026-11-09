<?php

namespace App\Support;

/**
 * داده‌ی نمونه‌ی کتاب‌ها (فقط برای تست فرانت). صفحه‌ی آموزش همان view مشترک music.lesson است با ۴ مرحله:
 *   ۱ intro (خلاصه + اطلاعات کامل کتاب) | ۲ read (خواندن فصل‌ها در سایت + فایل دانلودی) | ۳ review (مرور لغات/جمله‌های ذخیره‌شده + خلاصه‌ی فصل با AI) | ۴ author (بیوگرافی نویسنده)
 *
 * 🔌 قرارداد داده (بک‌اند همین شکل را از دیتابیس بدهد):
 *   id                شناسه (در دمو از ۳۰۰۱ تا با موزیک/پادکست/شدوئینگ قاطی نشود)
 *   title, author     عنوان و نویسنده — artist = author (برای سربرگ مشترک صفحه‌ی آموزش)
 *   cover             آدرس کاور (null = کاور رنگی ساختگی)
 *   level             a|b|c (تب‌های لیست) — cefr: «A2» و … (برچسب دقیق)
 *   type              novel | short_stories | non_fiction | graded
 *   genre, year, publisher, pages, isbn (اختیاری), language ('en')
 *   summary_fa, summary_en   خلاصه‌ی کتاب (مرحله‌ی ۱)
 *   author_bio_fa, author_bio_en, author_born, author_country, author_works[]   (مرحله‌ی ۴)
 *   chapters[]        { n, title, minutes, paragraphs[] (متن انگلیسی هر پاراگراف), summary_fa?, summary_en?, audio? }
 *                     ⚠️ فصل‌های قفل (کاربر رایگان روی کتاب غیررایگان: همه به‌جز فصل اول) متنشان اصلاً به view نمی‌رسد.
 *   words[]           لغات کلیدی کتاب (همان شکل Words: phrase, type, level, ipa{uk,us}, meaning, synonyms[], antonyms[], translate)
 *   file_url          فایل دانلودی (PDF/EPUB) — بک‌اند با کنترل دسترسی بدهد؛ null = دکمه‌ی دانلود نمایش داده نمی‌شود
 *   audio             صوت اختیاری کل کتاب (یا audio هر فصل)؛ null = پلیر نمایش داده نمی‌شود
 *   is_free           کتاب کاملاً رایگان (فقط ۳ کتاب)؛ بقیه برای کاربر رایگان فقط فصل اول (FREE_CHAPTERS)
 *   recommended       «پیشنهادی» ادمین
 *   completed_steps, current_step_fraction, progress (۰–۱۰۰)   پیشرفت کاربر (Progress)
 *   reading           { chapter, offset } آخرین جای خواندن (برای «ادامه از جایی که ماندم»)؛ در دمو localStorage
 *   updated_at        timestamp
 */
class Books
{
    public const STEPS = ['intro', 'read', 'review', 'author'];

    public const FIRST_ID = 3001;

    /** کاربر رایگان روی کتاب غیررایگان فقط این تعداد فصل اول را می‌خواند */
    public const FREE_CHAPTERS = 1;

    /** تعداد کلمه در دقیقه برای محاسبه‌ی زمان خواندن */
    public const WPM = 130;

    public static function all(): array
    {
        return array_map(fn ($b) => self::finish($b), self::demo());
    }

    public static function find(int $id): ?array
    {
        return collect(self::all())->firstWhere('id', $id);
    }

    /** دقیقه → «۱۲ دقیقه» / «۱ ساعت و ۵ دقیقه» */
    public static function fmtMinutes(int $min): string
    {
        $min = max(1, $min);
        if ($min < 60) {
            return __('books.min', ['n' => num($min)]);
        }

        return $min % 60
            ? __('books.hour_min', ['h' => num(intdiv($min, 60)), 'm' => num($min % 60)])
            : __('books.hour', ['h' => num(intdiv($min, 60))]);
    }

    /** زمان خواندن یک متن (دقیقه) */
    public static function minutesFor(array $paragraphs): int
    {
        $words = str_word_count(implode(' ', $paragraphs));

        return max(1, (int) round($words / self::WPM));
    }

    /** آیا فصل n برای این کاربر قفل است؟ */
    public static function chapterLocked(array $book, int $n, bool $subscribed): bool
    {
        return ! $subscribed && ! $book['is_free'] && $n > self::FREE_CHAPTERS;
    }

    private static function finish(array $b): array
    {
        $b['artist'] = $b['author'];
        $b['chapters'] = array_values(array_map(function ($c, $i) {
            $c['n'] = $i + 1;
            $c['minutes'] = $c['minutes'] ?? self::minutesFor($c['paragraphs']);

            return $c;
        }, $b['chapters'], array_keys($b['chapters'])));
        $b['chapters_count'] = count($b['chapters']);
        $b['minutes'] = array_sum(array_column($b['chapters'], 'minutes'));
        $b['total_steps'] = count(self::STEPS);
        $b['free_steps'] = count(self::STEPS); // همه‌ی مرحله‌ها باز است؛ محدودیت کاربر رایگان روی فصل‌هاست
        $b['progress'] = Progress::percent($b['completed_steps'], $b['current_step_fraction'], count(self::STEPS));

        return $b;
    }

    /* ------------------------------------------------------------------ داده‌ی نمونه */

    private static function demo(): array
    {
        $now = time();

        return [
            [
                'id' => 3001, 'title' => 'The Lighthouse Keeper', 'author' => 'Clara Wynn', 'cover' => null,
                'level' => 'a', 'cefr' => 'A2', 'type' => 'graded', 'genre' => 'Adventure', 'year' => 2021, 'publisher' => 'Teodel Readers',
                'pages' => 48, 'isbn' => '978-0-00-000001-1', 'language' => 'en',
                'is_free' => true, 'recommended' => true, 'file_url' => '/books/the-lighthouse-keeper.pdf', 'audio' => null,
                'completed_steps' => 1, 'current_step_fraction' => 0.3, 'updated_at' => $now - 86400 * 2,
                'summary_fa' => 'تام، پیرمردی که سال‌ها نگهبان یک فانوس دریایی کوچک بوده، یک شب طوفانی صدای کمک می‌شنود. داستانی ساده و گرم درباره‌ی شجاعت، تنهایی و دوستی که برای سطح A2 نوشته شده است.',
                'summary_en' => 'Tom has looked after a small lighthouse for thirty years. One stormy night he hears a call for help. A warm, simple story about courage, loneliness and friendship, written for A2 learners.',
                'author_born' => 1978, 'author_country' => 'Ireland', 'author_works' => ['The Lighthouse Keeper', 'Rain on the Hill', 'A Bus to Nowhere'],
                'author_bio_fa' => 'کلارا وین نویسنده‌ی ایرلندی کتاب‌های داستانی ساده برای زبان‌آموزان است. او سال‌ها معلم زبان انگلیسی بوده و داستان‌هایش را با جمله‌های کوتاه و لغات پرکاربرد می‌نویسد تا خواننده بدون دیکشنری لذت ببرد.',
                'author_bio_en' => 'Clara Wynn is an Irish writer of easy readers for English learners. She taught English for many years, and she writes with short sentences and common words, so readers can enjoy a story without a dictionary.',
                'chapters' => [
                    ['title' => 'The Light on the Rock', 'summary_fa' => 'با تام و زندگی آرامش در فانوس دریایی آشنا می‌شویم.', 'summary_en' => 'We meet Tom and his quiet life in the lighthouse.', 'paragraphs' => [
                        'Tom lived alone in a white lighthouse on a big black rock. Every evening, he climbed one hundred and twelve steps to the top. Then he turned on the light, and it shone far across the sea.',
                        'He was not lonely. He had a radio, a cat called Pepper and many old books. In the morning he cleaned the glass, and in the afternoon he fished from the rocks.',
                        'Ships passed the lighthouse every night. Tom never met the sailors, but he liked to think about them. "My light keeps them safe," he often said to Pepper.',
                    ]],
                    ['title' => 'The Storm', 'summary_fa' => 'طوفانی بزرگ می‌آید و تام صدای کمک می‌شنود.', 'summary_en' => 'A big storm comes and Tom hears a call for help.', 'paragraphs' => [
                        'One night in November, the wind started to scream. Huge waves hit the rock, and the whole lighthouse shook. Pepper hid under the bed.',
                        'Tom went up to check the light. Suddenly, the radio made a noise. "Help! Our boat is in trouble!" a young voice said. "We can\'t see the coast!"',
                        'Tom\'s hands were cold, but his voice was calm. "Look for my light," he said into the radio. "Keep it on your left and come slowly."',
                    ]],
                    ['title' => 'A New Friend', 'summary_fa' => 'قایق نجات پیدا می‌کند و تام یک دوست تازه پیدا می‌کند.', 'summary_en' => 'The boat is saved and Tom finds a new friend.', 'paragraphs' => [
                        'In the morning, the sea was quiet again. A small blue boat was on the beach near the rock. A girl and her father were sitting next to it, tired but happy.',
                        '"You saved our lives," the girl said. Her name was Mia. Tom smiled and invited them in for hot tea and bread.',
                        'After that day, Mia visited the lighthouse every Sunday. She learned to clean the glass and to fish from the rocks. And Tom was never alone again.',
                    ]],
                ],
                'words' => self::words(['shine', 'lonely', 'wave', 'shake', 'coast', 'calm']),
            ],
            [
                'id' => 3002, 'title' => 'City of Small Things', 'author' => 'Omar Haddad', 'cover' => null,
                'level' => 'a', 'cefr' => 'A2', 'type' => 'short_stories', 'genre' => 'Slice of life', 'year' => 2019, 'publisher' => 'Bright Page',
                'pages' => 64, 'isbn' => null, 'language' => 'en',
                'is_free' => true, 'recommended' => false, 'file_url' => null, 'audio' => null,
                'completed_steps' => 0, 'current_step_fraction' => 0, 'updated_at' => $now - 86400 * 9,
                'summary_fa' => 'مجموعه‌ای از داستان‌های کوتاه درباره‌ی آدم‌های معمولی یک شهر بزرگ: نانوایی که زود بیدار می‌شود، راننده‌ی اتوبوسی که همه را می‌شناسد و دختری که گربه‌ی گمشده‌اش را پیدا می‌کند.',
                'summary_en' => 'Short stories about ordinary people in a big city: a baker who wakes up early, a bus driver who knows everyone, and a girl who finds her lost cat.',
                'author_born' => 1985, 'author_country' => 'Jordan', 'author_works' => ['City of Small Things', 'The Night Market'],
                'author_bio_fa' => 'عمر حداد نویسنده و عکاس است. او از زندگی روزمره‌ی شهر الهام می‌گیرد و داستان‌هایش را برای نوجوانان و زبان‌آموزان می‌نویسد.',
                'author_bio_en' => 'Omar Haddad is a writer and photographer. He finds ideas in everyday city life and writes for teenagers and language learners.',
                'chapters' => [
                    ['title' => 'The Baker', 'summary_fa' => 'یک نانوا هر روز ساعت چهار صبح کارش را شروع می‌کند.', 'summary_en' => 'A baker starts work at four every morning.', 'paragraphs' => [
                        'Sami wakes up at four o\'clock every morning. The streets are dark and empty, but his small bakery is warm and bright.',
                        'He makes bread, cakes and cookies. At seven, the first customers arrive. They are always in a hurry, but they always smile when they smell the fresh bread.',
                    ]],
                    ['title' => 'Bus Number 9', 'summary_fa' => 'راننده‌ی اتوبوس همه‌ی مسافرانش را به اسم می‌شناسد.', 'summary_en' => 'The bus driver knows all his passengers by name.', 'paragraphs' => [
                        'Lina drives bus number 9. She knows every passenger by name: the old man with the newspaper, the student with the headphones and the nurse who works at night.',
                        'One day the old man doesn\'t get on the bus. Lina is worried. After work, she goes to his house with a bag of oranges.',
                    ]],
                    ['title' => 'Where Is Biscuit?', 'summary_fa' => 'دختری کوچک دنبال گربه‌ی گمشده‌اش می‌گردد.', 'summary_en' => 'A little girl looks for her lost cat.', 'paragraphs' => [
                        'Biscuit is a small orange cat. On Monday morning, the window is open and Biscuit is not there. Nora looks under the beds and behind the sofa.',
                        'She makes posters and puts them on every street corner. In the evening, the phone rings. It is Sami, the baker. "I think your cat likes my cakes," he laughs.',
                    ]],
                ],
                'words' => self::words(['customer', 'hurry', 'passenger', 'worried', 'poster', 'corner']),
            ],
            [
                'id' => 3003, 'title' => 'The Secret of Willow Farm', 'author' => 'Hannah Brooks', 'cover' => null,
                'level' => 'b', 'cefr' => 'B1', 'type' => 'novel', 'genre' => 'Mystery', 'year' => 2020, 'publisher' => 'Northwind Press',
                'pages' => 96, 'isbn' => '978-0-00-000003-5', 'language' => 'en',
                'is_free' => false, 'recommended' => true, 'file_url' => '/books/willow-farm.epub', 'audio' => '/songs/'.rawurlencode('Benny Rivers – Step by Step in Time.mp3'),
                'completed_steps' => 0, 'current_step_fraction' => 0, 'updated_at' => $now - 86400,
                'summary_fa' => 'وقتی جک برای تعطیلات تابستان به مزرعه‌ی پدربزرگش می‌رود، نور عجیبی را هر شب در انبار قدیمی می‌بیند. یک داستان معمایی هیجان‌انگیز برای سطح B1.',
                'summary_en' => 'When Jack spends the summer at his grandfather\'s farm, he notices a strange light in the old barn every night. An exciting mystery for B1 readers.',
                'author_born' => 1971, 'author_country' => 'United Kingdom', 'author_works' => ['The Secret of Willow Farm', 'Footsteps in the Snow', 'The Missing Map'],
                'author_bio_fa' => 'هانا بروکس نویسنده‌ی بریتانیایی داستان‌های معمایی برای نوجوانان است. کتاب‌هایش به بیش از ده زبان ترجمه شده و در مدرسه‌های زیادی برای تمرین خواندن استفاده می‌شود.',
                'author_bio_en' => 'Hannah Brooks is a British author of mystery stories for young readers. Her books have been translated into more than ten languages and are used in many schools for reading practice.',
                'chapters' => [
                    ['title' => 'Arrival', 'summary_fa' => 'جک به مزرعه می‌رسد و متوجه رفتار عجیب پدربزرگ می‌شود.', 'summary_en' => 'Jack arrives at the farm and notices his grandfather acting strangely.', 'paragraphs' => [
                        'The train stopped at a tiny station in the middle of green fields. Jack picked up his bag and stepped onto the platform. His grandfather was waiting next to an old red truck.',
                        '"You\'ve grown," Grandpa said, but his smile disappeared quickly. On the way to the farm he hardly spoke, and he kept looking at the road behind them.',
                        'Willow Farm was exactly as Jack remembered: the white house, the duck pond and, at the bottom of the hill, the huge wooden barn that nobody used any more.',
                    ]],
                    ['title' => 'Lights in the Barn', 'summary_fa' => 'جک شب‌ها نوری را در انبار می‌بیند.', 'summary_en' => 'Jack sees a light in the barn at night.', 'paragraphs' => [
                        'That night Jack couldn\'t sleep. Around midnight he went to the window and froze. A thin yellow light was moving inside the barn, as if someone were carrying a torch.',
                        'At breakfast he mentioned it, trying to sound casual. Grandpa put down his cup. "It\'s just the moon on the windows," he said. "Stay away from that barn. The floor isn\'t safe."',
                        'But the next night the light was there again, and this time Jack was sure he heard voices.',
                    ]],
                    ['title' => 'The Truth', 'summary_fa' => 'جک راز انبار را کشف می‌کند.', 'summary_en' => 'Jack discovers the secret of the barn.', 'paragraphs' => [
                        'On the third night Jack took his phone and crept down the hill. The barn door was open a little. Inside, three people were standing around a table covered in plans and photographs.',
                        'One of them was Grandpa. "Jack," he sighed, "I suppose you\'d better know. They want to build a road through the farm. We\'re trying to prove that this land is special."',
                        'On the table was a photograph of a rare bird, and next to it, a nest. Jack smiled. For the rest of the summer, he had a very important job.',
                    ]],
                ],
                'words' => self::words(['notice', 'hardly', 'casual', 'freeze', 'creep', 'prove', 'rare']),
            ],
            [
                'id' => 3004, 'title' => 'Small Habits, Big Changes', 'author' => 'Daniel Reyes', 'cover' => null,
                'level' => 'b', 'cefr' => 'B2', 'type' => 'non_fiction', 'genre' => 'Self-help', 'year' => 2022, 'publisher' => 'Clearway Books',
                'pages' => 120, 'isbn' => '978-0-00-000004-2', 'language' => 'en',
                'is_free' => false, 'recommended' => false, 'file_url' => '/books/small-habits.pdf', 'audio' => null,
                'completed_steps' => 0, 'current_step_fraction' => 0, 'updated_at' => $now - 86400 * 20,
                'summary_fa' => 'کتابی کاربردی درباره‌ی اینکه چطور عادت‌های کوچک روزانه، مثل ده دقیقه مطالعه، در طول زمان زندگی ما را عوض می‌کنند. با مثال‌های واقعی و جمله‌های روشن.',
                'summary_en' => 'A practical book about how small daily habits, like ten minutes of reading, change our lives over time. With real examples and clear language.',
                'author_born' => 1980, 'author_country' => 'Spain', 'author_works' => ['Small Habits, Big Changes', 'The Calm Hour'],
                'author_bio_fa' => 'دانیل ریس روان‌شناس و مربی است. او در کتاب‌هایش ایده‌های علمی را با زبانی ساده توضیح می‌دهد.',
                'author_bio_en' => 'Daniel Reyes is a psychologist and coach. In his books he explains scientific ideas in simple language.',
                'chapters' => [
                    ['title' => 'Why Small Is Powerful', 'summary_fa' => 'چرا قدم‌های کوچک از تصمیم‌های بزرگ مؤثرترند.', 'summary_en' => 'Why small steps work better than big decisions.', 'paragraphs' => [
                        'Most of us try to change our lives with big decisions. We promise to run every day or to learn a language in three months, and after a week we give up.',
                        'Small habits work differently. Because they are easy, we actually do them, and because we do them every day, the results add up. One percent better each day sounds like nothing, but over a year it makes an enormous difference.',
                    ]],
                    ['title' => 'Designing Your Environment', 'summary_fa' => 'محیط اطراف ما چطور رفتارمان را شکل می‌دهد.', 'summary_en' => 'How our surroundings shape our behaviour.', 'paragraphs' => [
                        'If you want to read more, put a book on your pillow. If you want to eat less sugar, don\'t keep biscuits on the kitchen table. Our environment makes some actions easy and others difficult.',
                        'Instead of relying on willpower, change the room. It sounds simple, yet it is one of the most effective strategies we have.',
                    ]],
                ],
                'words' => self::words(['habit', 'give up', 'add up', 'enormous', 'rely on', 'willpower']),
            ],
            [
                'id' => 3005, 'title' => 'Voices from the Ice', 'author' => 'Ingrid Solberg', 'cover' => null,
                'level' => 'c', 'cefr' => 'C1', 'type' => 'non_fiction', 'genre' => 'Travel', 'year' => 2018, 'publisher' => 'Polar Line',
                'pages' => 210, 'isbn' => null, 'language' => 'en',
                'is_free' => true, 'recommended' => true, 'file_url' => null, 'audio' => null,
                'completed_steps' => 0, 'current_step_fraction' => 0, 'updated_at' => $now - 86400 * 4,
                'summary_fa' => 'خاطرات یک پژوهشگر که یک زمستان کامل را در ایستگاهی در قطب جنوب گذرانده است؛ درباره‌ی سرما، سکوت، کار گروهی و تغییرات اقلیمی.',
                'summary_en' => 'A researcher\'s memoir of a full winter at an Antarctic station: cold, silence, teamwork and a changing climate.',
                'author_born' => 1975, 'author_country' => 'Norway', 'author_works' => ['Voices from the Ice'],
                'author_bio_fa' => 'اینگرید سولبرگ اقلیم‌شناس نروژی است که بیش از ده سال در مناطق قطبی پژوهش کرده است.',
                'author_bio_en' => 'Ingrid Solberg is a Norwegian climate scientist who has spent more than a decade doing research in polar regions.',
                'chapters' => [
                    ['title' => 'The Last Plane', 'summary_fa' => 'آخرین هواپیما می‌رود و زمستان طولانی شروع می‌شود.', 'summary_en' => 'The last plane leaves and the long winter begins.', 'paragraphs' => [
                        'When the last plane of the season disappeared over the horizon, an unfamiliar silence settled over the station. For the next eight months, the fourteen of us would be completely on our own.',
                        'Nothing in my training had prepared me for the psychological weight of that moment. It was not fear, exactly, but a sharpened awareness that every decision now carried consequences.',
                    ]],
                    ['title' => 'Measuring the Invisible', 'summary_fa' => 'کار روزانه‌ی اندازه‌گیری یخ و هوا.', 'summary_en' => 'The daily work of measuring ice and air.', 'paragraphs' => [
                        'Our days revolved around measurements: the thickness of the ice, the composition of the air, the speed of the wind. Individually, the numbers seemed trivial; collectively, they told a story that was impossible to ignore.',
                        'The ice, it turned out, was not the permanent, unchanging presence I had imagined, but something closer to a living archive, quietly recording the planet\'s history.',
                    ]],
                ],
                'words' => self::words(['horizon', 'settle', 'consequence', 'revolve around', 'trivial', 'permanent']),
            ],
            [
                'id' => 3006, 'title' => 'The Clockmaker\'s Daughter', 'author' => 'Leo Martens', 'cover' => null,
                'level' => 'c', 'cefr' => 'B2', 'type' => 'novel', 'genre' => 'Historical', 'year' => 2017, 'publisher' => 'Old Bridge',
                'pages' => 180, 'isbn' => '978-0-00-000006-6', 'language' => 'en',
                'is_free' => false, 'recommended' => false, 'file_url' => '/books/clockmaker.pdf', 'audio' => null,
                'completed_steps' => 0, 'current_step_fraction' => 0, 'updated_at' => $now - 86400 * 30,
                'summary_fa' => 'در شهری اروپایی در قرن نوزدهم، دختر یک ساعت‌ساز باید کارگاه پدرش را نجات دهد. داستانی درباره‌ی استقلال، هنر و خانواده.',
                'summary_en' => 'In a 19th-century European town, a clockmaker\'s daughter must save her father\'s workshop. A story of independence, craft and family.',
                'author_born' => 1966, 'author_country' => 'Belgium', 'author_works' => ['The Clockmaker\'s Daughter', 'Glass and Iron'],
                'author_bio_fa' => 'لئو مارتنس رمان‌نویس بلژیکی است که داستان‌های تاریخی می‌نویسد.',
                'author_bio_en' => 'Leo Martens is a Belgian novelist who writes historical fiction.',
                'chapters' => [
                    ['title' => 'Ticking', 'summary_fa' => 'الیزا در کارگاه پدرش بزرگ شده است.', 'summary_en' => 'Eliza has grown up in her father\'s workshop.', 'paragraphs' => [
                        'Eliza had grown up to the sound of ticking. Two hundred clocks hung on the walls of her father\'s workshop, and she could tell, by sound alone, which one was running slow.',
                        'Her father said she had better hands than he did. The town said a girl had no business in a workshop. Eliza had long ago decided which opinion mattered.',
                    ]],
                    ['title' => 'The Debt', 'summary_fa' => 'پدر بیمار می‌شود و بدهی کارگاه آشکار می‌شود.', 'summary_en' => 'Her father falls ill and the workshop\'s debt is revealed.', 'paragraphs' => [
                        'The winter her father fell ill, a man in a grey coat arrived with a letter. The workshop, it said, would belong to the bank by spring unless the debt was paid in full.',
                        'Eliza read the letter twice, folded it carefully and went back to her bench. There was, she calculated, exactly enough time to build one extraordinary clock.',
                    ]],
                ],
                'words' => self::words(['tick', 'opinion', 'debt', 'in full', 'extraordinary']),
            ],
        ];
    }

    /** لغات کلیدی نمونه (شکل Words) */
    private static function words(array $keys): array
    {
        $bank = [
            'shine' => ['verb', 'A2', '/ʃaɪn/', 'To give out or reflect bright light.', ['glow', 'gleam'], [], 'درخشیدن'],
            'lonely' => ['adjective', 'A2', '/ˈləʊnli/', 'Unhappy because you are alone.', ['alone', 'isolated'], [], 'تنها؛ دلتنگ'],
            'wave' => ['noun', 'A2', '/weɪv/', 'A line of raised water that moves across the sea.', ['swell'], [], 'موج'],
            'shake' => ['verb', 'A2', '/ʃeɪk/', 'To move quickly from side to side or up and down.', ['tremble'], [], 'لرزیدن'],
            'coast' => ['noun', 'A2', '/kəʊst/', 'The land next to the sea.', ['shore', 'seaside'], [], 'ساحل'],
            'calm' => ['adjective', 'A2', '/kɑːm/', 'Relaxed and not worried or excited.', ['peaceful', 'relaxed'], ['nervous'], 'آرام'],
            'customer' => ['noun', 'A2', '/ˈkʌstəmə/', 'A person who buys things from a shop.', ['client', 'buyer'], [], 'مشتری'],
            'hurry' => ['noun', 'A2', '/ˈhʌri/', 'The need to do something quickly.', ['rush'], [], 'عجله'],
            'passenger' => ['noun', 'A2', '/ˈpæsɪndʒə/', 'A person who travels in a vehicle but is not driving it.', ['traveller', 'rider'], ['driver'], 'مسافر'],
            'worried' => ['adjective', 'A2', '/ˈwʌrid/', 'Unhappy because you think something bad might happen.', ['anxious', 'concerned'], ['relaxed'], 'نگران'],
            'poster' => ['noun', 'A2', '/ˈpəʊstə/', 'A large printed notice put on a wall.', ['notice', 'sign'], [], 'پوستر؛ آگهی'],
            'corner' => ['noun', 'A2', '/ˈkɔːnə/', 'The place where two streets meet.', ['junction'], [], 'گوشه؛ سر کوچه'],
            'notice' => ['verb', 'B1', '/ˈnəʊtɪs/', 'To see or become aware of something.', ['spot', 'observe'], ['ignore'], 'متوجه شدن'],
            'hardly' => ['adverb', 'B1', '/ˈhɑːdli/', 'Almost not.', ['barely', 'scarcely'], [], 'به‌سختی؛ تقریباً نه'],
            'casual' => ['adjective', 'B1', '/ˈkæʒuəl/', 'Relaxed and not showing much interest.', ['relaxed', 'informal'], ['serious'], 'بی‌خیال؛ عادی'],
            'freeze' => ['verb', 'B1', '/friːz/', 'To suddenly stop moving because you are afraid or surprised.', ['stop still'], [], 'خشک شدن (از ترس)'],
            'creep' => ['verb', 'B1', '/kriːp/', 'To move slowly and quietly so nobody notices you.', ['sneak', 'tiptoe'], [], 'آهسته و یواشکی رفتن'],
            'prove' => ['verb', 'B1', '/pruːv/', 'To show that something is true.', ['demonstrate', 'confirm'], ['disprove'], 'ثابت کردن'],
            'rare' => ['adjective', 'B1', '/reə/', 'Not common; not happening or found often.', ['unusual', 'uncommon'], ['common'], 'کمیاب'],
            'habit' => ['noun', 'B1', '/ˈhæbɪt/', 'Something you do often and regularly.', ['routine', 'practice'], [], 'عادت'],
            'give up' => ['phrasal verb', 'B1', '/ɡɪv ʌp/', 'To stop trying to do something.', ['quit', 'stop'], ['continue'], 'دست کشیدن؛ تسلیم شدن'],
            'add up' => ['phrasal verb', 'B2', '/æd ʌp/', 'To increase gradually until there is a large amount.', ['accumulate'], [], 'روی هم جمع شدن'],
            'enormous' => ['adjective', 'B1', '/ɪˈnɔːməs/', 'Extremely large.', ['huge', 'massive'], ['tiny'], 'بسیار بزرگ'],
            'rely on' => ['phrasal verb', 'B2', '/rɪˈlaɪ ɒn/', 'To depend on someone or something.', ['depend on', 'count on'], [], 'تکیه کردن بر'],
            'willpower' => ['noun', 'B2', '/ˈwɪlpaʊə/', 'The ability to control yourself and do difficult things.', ['determination', 'self-control'], [], 'اراده'],
            'horizon' => ['noun', 'B2', '/həˈraɪzn/', 'The line where the earth or sea seems to meet the sky.', ['skyline'], [], 'افق'],
            'settle' => ['verb', 'B2', '/ˈsetl/', 'To come down and stay over a place.', ['descend', 'rest'], [], 'نشستن؛ فرا گرفتن'],
            'consequence' => ['noun', 'B2', '/ˈkɒnsɪkwəns/', 'A result of an action, often a bad one.', ['result', 'outcome'], ['cause'], 'پیامد'],
            'revolve around' => ['phrasal verb', 'C1', '/rɪˈvɒlv əˈraʊnd/', 'To have something as the main subject or purpose.', ['centre on'], [], 'حول چیزی چرخیدن'],
            'trivial' => ['adjective', 'C1', '/ˈtrɪviəl/', 'Not important or serious.', ['minor', 'insignificant'], ['significant'], 'پیش‌پاافتاده'],
            'permanent' => ['adjective', 'B2', '/ˈpɜːmənənt/', 'Lasting for a very long time or forever.', ['lasting', 'eternal'], ['temporary'], 'دائمی'],
            'tick' => ['verb', 'B1', '/tɪk/', 'Of a clock: to make a short repeated sound.', [], [], 'تیک‌تاک کردن'],
            'opinion' => ['noun', 'B1', '/əˈpɪnjən/', 'What you think about something.', ['view', 'belief'], [], 'نظر'],
            'debt' => ['noun', 'B2', '/det/', 'Money that you owe someone.', ['loan'], ['credit'], 'بدهی'],
            'in full' => ['phrase', 'B2', '/ɪn fʊl/', 'Completely; the whole amount.', ['completely', 'entirely'], ['partly'], 'به‌طور کامل'],
            'extraordinary' => ['adjective', 'B2', '/ɪkˈstrɔːdnri/', 'Very unusual or special.', ['remarkable', 'amazing'], ['ordinary'], 'خارق‌العاده'],
        ];

        return array_values(array_map(fn ($k) => [
            'phrase' => $k, 'type' => $bank[$k][0], 'level' => $bank[$k][1],
            'ipa' => ['uk' => $bank[$k][2], 'us' => $bank[$k][2]],
            'meaning' => $bank[$k][3], 'synonyms' => $bank[$k][4], 'antonyms' => $bank[$k][5], 'translate' => $bank[$k][6],
        ], array_filter($keys, fn ($k) => isset($bank[$k]))));
    }
}
