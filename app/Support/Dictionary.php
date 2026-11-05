<?php

namespace App\Support;

/**
 * دیکشنری (صفحه‌ی /dictionary). لغت‌ها الفبایی‌اند و با سطح (A1–C2) و دسته (GRE / IELTS / اصطلاحات / اسلنگ) فیلتر می‌شوند.
 *
 * 🔌 دو حالت برای بک‌اند:
 *   ۱) لیست کوچک تا چند هزار مورد: کنترلر کل لیست را به‌صورت $words می‌دهد (Dictionary::normalize) و جست‌وجو/فیلتر در مرورگر انجام می‌شود.
 *   ۲) لیست خیلی بزرگ: کنترلر $endpoint را می‌دهد؛ مرورگر هر بار همین را صدا می‌زند و فقط یک صفحه می‌گیرد:
 *        GET {endpoint}?q=&letter=a&type=noun|verb|adjective|adverb|other&saved=1&sort=az|za&page=1&per=60
 *        ← { "data": [ لغت… ], "total": 1234, "has_more": true }
 *      (در این حالت $letters = حروفی که لغت دارند، مثلاً ['a','b','c'] تا بقیه‌ی حروف غیرفعال نمایش داده شوند.)
 *
 * فیلترها: type (نقش دستوری)، level (a1…c2)، category (gre|ielts|idiom|slang). در حالت endpoint هر سه به‌صورت query فرستاده می‌شوند:
 *   GET {endpoint}?q=&letter=&type=&level=a1&category=gre&saved=1&sort=az&page=1&per=60
 * داده‌های تکمیلی هر لغت (level, tags, connotation, senses, collocations, family, morphemes, origin): app/Support/WordExtras.php
 *
 * هر لغت (همه‌ی کلیدها اختیاری‌اند جز id و phrase):
 *   id, phrase, type (noun|verb|adjective|adverb|conjunction|phrasal verb|idiom …), ipa{uk,us}, audio{uk,us},
 *   meaning (تعریف انگلیسی), example, example_tr, translate (فارسی), synonyms[], antonyms[],
 *   image (آدرس تصویر لغت، اختیاری؛ فقط در offcanvas جزئیات نشان داده می‌شود و در لیست نیست — بدون آن کاشی رنگی با حرف اول می‌آید)
 *
 * $savedKeys = کلیدهای (حروف کوچک) لغت‌هایی که کاربر قبلاً ذخیره کرده؛ بوکمارک همان لیست «لغات و عبارت‌های هایلایت‌شده» است.
 */
class Dictionary
{
    /** @return array<int, array<string, mixed>> */
    public static function normalize(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $phrase = trim((string) ($r['phrase'] ?? ''));
            if ($phrase === '' || ! isset($r['id'])) {
                continue;
            }
            $out[] = [
                'id' => $r['id'],
                'key' => mb_strtolower($phrase),
                'phrase' => $phrase,
                'type' => (string) ($r['type'] ?? ''),
                'ipa' => ['uk' => $r['ipa']['uk'] ?? '', 'us' => $r['ipa']['us'] ?? ''],
                'audio' => ['uk' => $r['audio']['uk'] ?? '', 'us' => $r['audio']['us'] ?? ''],
                'meaning' => (string) ($r['meaning'] ?? ''),
                'example' => (string) ($r['example'] ?? ''),
                'example_tr' => (string) ($r['example_tr'] ?? ''),
                'translate' => (string) ($r['translate'] ?? ''),
                'image' => $r['image'] ?? null,
                'synonyms' => array_values((array) ($r['synonyms'] ?? [])),
                'antonyms' => array_values((array) ($r['antonyms'] ?? [])),
            ] + WordExtras::normalize($r);
        }

        return $out;
    }

    /** حروفِ ابتدایی که لغت دارند (برای نوار الفبا) */
    public static function letters(array $words): array
    {
        $l = [];
        foreach ($words as $w) {
            $c = mb_strtolower(mb_substr($w['phrase'], 0, 1));
            if (preg_match('/^[a-z]$/', $c)) {
                $l[$c] = true;
            }
        }
        $l = array_keys($l);
        sort($l);

        return $l;
    }

    /** نمونه برای تست فرانت (فقط تا وصل‌شدن بک‌اند) */
    public static function demo(): array
    {
        // [phrase, type, translate, meaning, example, example_tr, ipa(uk), ipa(us), synonyms, antonyms]
        $rows = [
            ['abandon', 'verb', 'رها کردن؛ ترک کردن', 'To leave someone or something completely and not come back.', 'They had to abandon the car in the snow.', 'آن‌ها مجبور شدند ماشین را توی برف رها کنند.', '/əˈbændən/', '/əˈbændən/', ['leave', 'desert'], ['keep']],
            ['ability', 'noun', 'توانایی', 'The skill or power to do something.', 'She has the ability to learn languages quickly.', 'او توانایی یادگیری سریع زبان‌ها را دارد.', '/əˈbɪləti/', '/əˈbɪləti/', ['skill', 'talent'], ['inability']],
            ['accept', 'verb', 'پذیرفتن', 'To agree to take something that is offered.', 'He accepted the job without thinking twice.', 'او بدون تردید شغل را پذیرفت.', '/əkˈsept/', '/əkˈsept/', ['receive', 'agree to'], ['refuse', 'reject']],
            ['achieve', 'verb', 'به دست آوردن؛ موفق شدن', 'To succeed in doing something good after trying hard.', 'You can achieve anything if you keep practising.', 'اگر تمرین را ادامه بدهی، می‌توانی به هر چیزی برسی.', '/əˈtʃiːv/', '/əˈtʃiːv/', ['accomplish', 'reach'], ['fail']],
            ['adventure', 'noun', 'ماجراجویی', 'An exciting experience, often with some risk.', 'Their trip across the desert was a real adventure.', 'سفرشان در بیابان یک ماجراجویی واقعی بود.', '/ədˈventʃə/', '/ædˈventʃər/', ['expedition'], []],
            ['afraid', 'adjective', 'ترسیده', 'Feeling fear because something bad might happen.', 'I’m afraid of speaking in front of a big crowd.', 'من از صحبت‌کردن جلوی جمعیت زیاد می‌ترسم.', '/əˈfreɪd/', '/əˈfreɪd/', ['scared', 'frightened'], ['brave']],
            ['agree', 'verb', 'موافق بودن', 'To have the same opinion as someone else.', 'We both agree that the film was too long.', 'هر دوی ما موافقیم که فیلم خیلی طولانی بود.', '/əˈɡriː/', '/əˈɡriː/', ['concur'], ['disagree']],
            ['alone', 'adjective', 'تنها', 'Without any other person.', 'She likes to walk alone by the sea.', 'او دوست دارد تنها کنار دریا قدم بزند.', '/əˈləʊn/', '/əˈloʊn/', ['by oneself'], ['together']],
            ['amazing', 'adjective', 'شگفت‌انگیز', 'Very surprising and very good.', 'The view from the top was amazing.', 'منظره از بالا شگفت‌انگیز بود.', '/əˈmeɪzɪŋ/', '/əˈmeɪzɪŋ/', ['wonderful', 'incredible'], ['ordinary']],
            ['ancient', 'adjective', 'باستانی', 'Very old; from a time long ago.', 'We visited an ancient temple in the mountains.', 'ما از یک معبد باستانی در کوهستان دیدن کردیم.', '/ˈeɪnʃənt/', '/ˈeɪnʃənt/', ['old', 'antique'], ['modern']],
            ['arrive', 'verb', 'رسیدن', 'To reach a place at the end of a journey.', 'The train arrives at six o’clock.', 'قطار ساعت شش می‌رسد.', '/əˈraɪv/', '/əˈraɪv/', ['reach', 'get to'], ['leave', 'depart']],
            ['beautiful', 'adjective', 'زیبا', 'Very pleasing to look at or listen to.', 'What a beautiful voice she has!', 'چه صدای زیبایی دارد!', '/ˈbjuːtɪfl/', '/ˈbjuːtɪfl/', ['lovely', 'gorgeous'], ['ugly']],
            ['because', 'conjunction', 'زیرا؛ چون', 'For the reason that.', 'I stayed home because it was raining.', 'چون باران می‌آمد در خانه ماندم.', '/bɪˈkɒz/', '/bɪˈkɔːz/', ['since'], []],
            ['believe', 'verb', 'باور کردن', 'To feel sure that something is true.', 'I believe that practice makes perfect.', 'من باور دارم که تمرین انسان را کامل می‌کند.', '/bɪˈliːv/', '/bɪˈliːv/', ['trust', 'think'], ['doubt']],
            ['borrow', 'verb', 'قرض گرفتن', 'To take something from someone and give it back later.', 'Can I borrow your pen for a minute?', 'می‌توانم یک دقیقه خودکارت را قرض بگیرم؟', '/ˈbɒrəʊ/', '/ˈbɑːroʊ/', [], ['lend']],
            ['brave', 'adjective', 'شجاع', 'Ready to face danger or pain without showing fear.', 'The brave boy saved his little sister.', 'پسر شجاع خواهر کوچکش را نجات داد.', '/breɪv/', '/breɪv/', ['courageous', 'fearless'], ['cowardly']],
            ['breathe', 'verb', 'نفس کشیدن', 'To take air into your lungs and let it out again.', 'Breathe in slowly and then breathe out.', 'آهسته نفس بکش و بعد بیرون بده.', '/briːð/', '/briːð/', [], []],
            ['bridge', 'noun', 'پل', 'A structure built over a river or road so people can cross.', 'We walked across the old stone bridge.', 'از روی پل سنگیِ قدیمی گذشتیم.', '/brɪdʒ/', '/brɪdʒ/', [], []],
            ['careful', 'adjective', 'مراقب؛ دقیق', 'Thinking about what you do so you avoid mistakes or danger.', 'Be careful, the floor is wet.', 'مراقب باش، کف خیس است.', '/ˈkeəfl/', '/ˈkerfl/', ['cautious'], ['careless']],
            ['celebrate', 'verb', 'جشن گرفتن', 'To do something enjoyable because of a special day or event.', 'We celebrated her birthday with a small party.', 'تولدش را با یک مهمانی کوچک جشن گرفتیم.', '/ˈselɪbreɪt/', '/ˈseləbreɪt/', [], []],
            ['challenge', 'noun', 'چالش؛ مبارزه‌طلبی', 'Something new and difficult that tests your ability.', 'Learning a new language is a big challenge.', 'یادگیری یک زبان جدید چالش بزرگی است.', '/ˈtʃælɪndʒ/', '/ˈtʃælɪndʒ/', ['test'], []],
            ['choose', 'verb', 'انتخاب کردن', 'To decide which one you want from several.', 'You can choose any song you like.', 'می‌توانی هر آهنگی را که دوست داری انتخاب کنی.', '/tʃuːz/', '/tʃuːz/', ['pick', 'select'], []],
            ['climb', 'verb', 'بالا رفتن', 'To go up something such as a hill, tree or ladder.', 'They climbed the mountain in four hours.', 'آن‌ها در چهار ساعت از کوه بالا رفتند.', '/klaɪm/', '/klaɪm/', ['ascend'], ['descend']],
            ['comfortable', 'adjective', 'راحت', 'Giving a pleasant feeling of rest; not causing pain.', 'This sofa is really comfortable.', 'این مبل واقعاً راحت است.', '/ˈkʌmftəbl/', '/ˈkʌmftərbl/', ['cosy'], ['uncomfortable']],
            ['compare', 'verb', 'مقایسه کردن', 'To look at things to see how they are similar or different.', 'Compare the two answers and tell me which is better.', 'دو پاسخ را مقایسه کن و بگو کدام بهتر است.', '/kəmˈpeə/', '/kəmˈper/', [], []],
            ['courage', 'noun', 'شجاعت', 'The ability to do something frightening.', 'It took a lot of courage to speak up.', 'برای حرف‌زدن شجاعت زیادی لازم بود.', '/ˈkʌrɪdʒ/', '/ˈkɜːrɪdʒ/', ['bravery'], ['fear']],
            ['create', 'verb', 'ساختن؛ آفریدن', 'To make something new exist.', 'Artists create beautiful things from simple materials.', 'هنرمندان از مواد ساده چیزهای زیبا می‌سازند.', '/kriˈeɪt/', '/kriˈeɪt/', ['make', 'produce'], ['destroy']],
            ['dangerous', 'adjective', 'خطرناک', 'Likely to cause harm or injury.', 'It is dangerous to swim here.', 'شنا کردن اینجا خطرناک است.', '/ˈdeɪndʒərəs/', '/ˈdeɪndʒərəs/', ['risky'], ['safe']],
            ['decide', 'verb', 'تصمیم گرفتن', 'To choose after thinking about the possibilities.', 'I decided to learn English with music.', 'تصمیم گرفتم انگلیسی را با موزیک یاد بگیرم.', '/dɪˈsaɪd/', '/dɪˈsaɪd/', ['choose'], []],
            ['delicious', 'adjective', 'خوشمزه', 'Having a very pleasant taste or smell.', 'The soup smells delicious.', 'سوپ بوی خوشمزه‌ای دارد.', '/dɪˈlɪʃəs/', '/dɪˈlɪʃəs/', ['tasty'], ['awful']],
            ['describe', 'verb', 'توصیف کردن', 'To say what someone or something is like.', 'Can you describe the man you saw?', 'می‌توانی مردی را که دیدی توصیف کنی؟', '/dɪˈskraɪb/', '/dɪˈskraɪb/', [], []],
            ['develop', 'verb', 'توسعه دادن؛ رشد کردن', 'To grow or change into something bigger or better.', 'Children develop quickly in their first years.', 'کودکان در سال‌های اول سریع رشد می‌کنند.', '/dɪˈveləp/', '/dɪˈveləp/', ['grow'], []],
            ['difficult', 'adjective', 'سخت؛ دشوار', 'Not easy; needing effort or skill.', 'The first exam question was very difficult.', 'سؤال اول امتحان خیلی سخت بود.', '/ˈdɪfɪkəlt/', '/ˈdɪfɪkəlt/', ['hard'], ['easy']],
            ['discover', 'verb', 'کشف کردن', 'To find or learn something for the first time.', 'Scientists discovered a new kind of frog.', 'دانشمندان نوع تازه‌ای از قورباغه را کشف کردند.', '/dɪˈskʌvə/', '/dɪˈskʌvər/', ['find out'], []],
            ['dream', 'noun', 'رؤیا؛ آرزو', 'Pictures and events you see in your mind while you sleep; also a hope for the future.', 'My dream is to travel around the world.', 'آرزوی من سفر به دور دنیاست.', '/driːm/', '/driːm/', ['hope', 'ambition'], []],
            ['earn', 'verb', 'به دست آوردن (درآمد)', 'To receive money for work you do.', 'She earns a good salary as a nurse.', 'او به‌عنوان پرستار حقوق خوبی دریافت می‌کند.', '/ɜːn/', '/ɝːn/', ['make'], ['spend']],
            ['easy', 'adjective', 'آسان', 'Not difficult; needing little effort.', 'This song is easy to sing along to.', 'همراهی با این آهنگ آسان است.', '/ˈiːzi/', '/ˈiːzi/', ['simple'], ['difficult']],
            ['edge', 'noun', 'لبه', 'The part along the outside of something.', 'Don’t stand so close to the edge.', 'این‌قدر نزدیک لبه نایست.', '/edʒ/', '/edʒ/', ['rim', 'border'], ['centre']],
            ['enough', 'adverb', 'به اندازه‌ی کافی', 'As much as is needed.', 'Is the water warm enough for you?', 'آب برای تو به اندازه‌ی کافی گرم است؟', '/ɪˈnʌf/', '/ɪˈnʌf/', ['sufficiently'], []],
            ['escape', 'verb', 'فرار کردن', 'To get away from a place or danger.', 'The cat escaped through an open window.', 'گربه از پنجره‌ی باز فرار کرد.', '/ɪˈskeɪp/', '/ɪˈskeɪp/', ['flee'], []],
            ['expensive', 'adjective', 'گران', 'Costing a lot of money.', 'That jacket is too expensive for me.', 'آن کاپشن برای من خیلی گران است.', '/ɪkˈspensɪv/', '/ɪkˈspensɪv/', ['costly'], ['cheap']],
            ['familiar', 'adjective', 'آشنا', 'Well known to you; easy to recognise.', 'Her face looks familiar to me.', 'چهره‌اش به نظرم آشناست.', '/fəˈmɪliə/', '/fəˈmɪljər/', ['known'], ['strange']],
            ['forget', 'verb', 'فراموش کردن', 'To be unable to remember something.', 'Don’t forget to call me tonight.', 'امشب فراموش نکن به من زنگ بزنی.', '/fəˈɡet/', '/fərˈɡet/', [], ['remember']],
            ['forgive', 'verb', 'بخشیدن', 'To stop being angry with someone who has done something wrong.', 'I forgive you for being late.', 'دیر آمدنت را می‌بخشم.', '/fəˈɡɪv/', '/fərˈɡɪv/', ['pardon'], ['blame']],
            ['gentle', 'adjective', 'ملایم؛ مهربان', 'Soft and kind; not rough.', 'The nurse has a gentle voice.', 'پرستار صدای ملایمی دارد.', '/ˈdʒentl/', '/ˈdʒentl/', ['soft', 'tender'], ['rough']],
            ['grateful', 'adjective', 'سپاسگزار', 'Feeling thankful for something.', 'I’m grateful for all your help.', 'از تمام کمکت سپاسگزارم.', '/ˈɡreɪtfl/', '/ˈɡreɪtfl/', ['thankful'], ['ungrateful']],
            ['guess', 'verb', 'حدس زدن', 'To give an answer without being sure it is right.', 'Can you guess how old he is?', 'می‌توانی حدس بزنی چند سالش است؟', '/ɡes/', '/ɡes/', ['estimate'], []],
            ['habit', 'noun', 'عادت', 'Something you do often and almost without thinking.', 'Reading before bed is a good habit.', 'مطالعه پیش از خواب عادت خوبی است.', '/ˈhæbɪt/', '/ˈhæbɪt/', ['routine'], []],
            ['happen', 'verb', 'اتفاق افتادن', 'To take place, usually without being planned.', 'What happened to your arm?', 'دستت چه شده؟', '/ˈhæpən/', '/ˈhæpən/', ['occur'], []],
            ['hesitate', 'verb', 'تردید کردن', 'To pause before doing something because you are not sure.', 'Don’t hesitate to ask if you need help.', 'اگر کمک خواستی بی‌تردید بپرس.', '/ˈhezɪteɪt/', '/ˈhezɪteɪt/', ['pause'], []],
            ['honest', 'adjective', 'صادق؛ درستکار', 'Telling the truth; not cheating or lying.', 'To be honest, I didn’t like the ending.', 'راستش پایان فیلم را دوست نداشتم.', '/ˈɒnɪst/', '/ˈɑːnɪst/', ['truthful'], ['dishonest']],
            ['imagine', 'verb', 'تصور کردن', 'To form a picture of something in your mind.', 'Imagine you are on a quiet beach.', 'تصور کن روی ساحلی آرام هستی.', '/ɪˈmædʒɪn/', '/ɪˈmædʒɪn/', ['picture'], []],
            ['improve', 'verb', 'بهتر کردن؛ پیشرفت کردن', 'To become better, or to make something better.', 'Listening to songs can improve your English.', 'گوش‌دادن به آهنگ می‌تواند انگلیسی‌ات را بهتر کند.', '/ɪmˈpruːv/', '/ɪmˈpruːv/', ['get better'], ['worsen']],
            ['journey', 'noun', 'سفر؛ مسیر', 'The act of travelling from one place to another.', 'The journey took us nearly ten hours.', 'سفر نزدیک ده ساعت طول کشید.', '/ˈdʒɜːni/', '/ˈdʒɜːrni/', ['trip'], []],
            ['knowledge', 'noun', 'دانش', 'What you know through learning or experience.', 'He has a deep knowledge of history.', 'او دانش عمیقی از تاریخ دارد.', '/ˈnɒlɪdʒ/', '/ˈnɑːlɪdʒ/', ['understanding'], ['ignorance']],
            ['lonely', 'adjective', 'تنها؛ دلتنگ', 'Unhappy because you have no friends or company.', 'She felt lonely in the big new city.', 'او در شهر بزرگ و تازه احساس تنهایی می‌کرد.', '/ˈləʊnli/', '/ˈloʊnli/', ['isolated'], []],
            ['memory', 'noun', 'حافظه؛ خاطره', 'The ability to remember; something you remember.', 'That song brings back happy memories.', 'آن آهنگ خاطرات خوش را زنده می‌کند.', '/ˈmeməri/', '/ˈmeməri/', ['recollection'], []],
            ['notice', 'verb', 'متوجه شدن', 'To see or become aware of something.', 'Did you notice her new haircut?', 'متوجه مدل موی جدیدش شدی؟', '/ˈnəʊtɪs/', '/ˈnoʊtɪs/', ['spot'], ['overlook']],
            ['peaceful', 'adjective', 'آرام؛ صلح‌آمیز', 'Quiet and calm, without trouble.', 'The village is very peaceful at night.', 'روستا شب‌ها بسیار آرام است.', '/ˈpiːsfl/', '/ˈpiːsfl/', ['calm', 'tranquil'], ['noisy']],
            ['promise', 'noun', 'قول', 'A statement that you will definitely do something.', 'He kept his promise and came back.', 'او به قولش عمل کرد و برگشت.', '/ˈprɒmɪs/', '/ˈprɑːmɪs/', ['pledge'], []],
            ['quiet', 'adjective', 'آرام؛ ساکت', 'Making little or no noise.', 'Please be quiet in the library.', 'لطفاً در کتابخانه ساکت باشید.', '/ˈkwaɪət/', '/ˈkwaɪət/', ['silent'], ['loud']],
            ['remember', 'verb', 'به یاد داشتن', 'To keep something in your mind; to bring it back to mind.', 'I remember my first English lesson.', 'اولین درس انگلیسی‌ام را به یاد دارم.', '/rɪˈmembə/', '/rɪˈmembər/', ['recall'], ['forget']],
            ['rhythm', 'noun', 'ریتم؛ آهنگ', 'A regular pattern of sounds or movements.', 'Clap your hands to the rhythm of the song.', 'با ریتم آهنگ دست بزن.', '/ˈrɪðəm/', '/ˈrɪðəm/', ['beat'], []],
            ['silence', 'noun', 'سکوت', 'Complete quiet; no sound at all.', 'There was a long silence after his speech.', 'پس از سخنرانی‌اش سکوتی طولانی برقرار شد.', '/ˈsaɪləns/', '/ˈsaɪləns/', ['quiet'], ['noise']],
            ['strength', 'noun', 'قدرت؛ نیرو', 'The quality of being physically or mentally strong.', 'He found the strength to start again.', 'او نیروی آغاز دوباره را پیدا کرد.', '/streŋθ/', '/streŋkθ/', ['power'], ['weakness']],
            ['surprise', 'noun', 'سورپرایز؛ شگفتی', 'Something unexpected; the feeling it causes.', 'The party was a complete surprise.', 'مهمانی کاملاً غافلگیرکننده بود.', '/səˈpraɪz/', '/sərˈpraɪz/', ['shock'], []],
            ['whisper', 'verb', 'نجوا کردن', 'To speak very quietly using your breath, not your voice.', 'She whispered the answer in my ear.', 'او جواب را در گوشم نجوا کرد.', '/ˈwɪspə/', '/ˈwɪspər/', ['murmur'], ['shout']],
            ['wonder', 'verb', 'کنجکاو بودن؛ تعجب کردن', 'To want to know something; to think about it with curiosity.', 'I wonder what the weather will be like.', 'کنجکاوم هوا چطور خواهد بود.', '/ˈwʌndə/', '/ˈwʌndər/', ['ponder'], []],
            ['yesterday', 'adverb', 'دیروز', 'On the day before today.', 'I saw her yesterday at the market.', 'دیروز او را در بازار دیدم.', '/ˈjestədeɪ/', '/ˈjestərdeɪ/', [], ['tomorrow']],
            ['give up', 'phrasal verb', 'تسلیم شدن؛ ول کردن', 'To stop trying; to stop doing something.', 'Don’t give up, you are almost there.', 'ول نکن، تقریباً رسیده‌ای.', '/ɡɪv ˈʌp/', '/ɡɪv ˈʌp/', ['quit'], ['continue']],
            ['look forward to', 'phrasal verb', 'مشتاقانه منتظر بودن', 'To feel happy about something that is going to happen.', 'I’m looking forward to the weekend.', 'مشتاقانه منتظر آخر هفته‌ام.', '/lʊk ˈfɔːwəd tə/', '/lʊk ˈfɔːrwərd tə/', [], ['dread']],
            ['take off', 'phrasal verb', 'بلند شدن (هواپیما)؛ درآوردن', 'To leave the ground and begin to fly; to remove clothes.', 'The plane takes off in ten minutes.', 'هواپیما ده دقیقه‌ی دیگر بلند می‌شود.', '/teɪk ˈɒf/', '/teɪk ˈɔːf/', [], ['land']],
            ['a piece of cake', 'idiom', 'کار خیلی آسان', 'Something that is very easy to do.', 'The test was a piece of cake.', 'امتحان خیلی آسان بود.', '/ə piːs əv keɪk/', '/ə piːs əv keɪk/', ['child’s play'], []],
            ['break the ice', 'idiom', 'یخ جمع را شکستن', 'To do or say something to make people feel more relaxed.', 'He told a joke to break the ice.', 'او برای شکستن یخ جمع یک جوک تعریف کرد.', '/breɪk ði aɪs/', '/breɪk ði aɪs/', [], []],
            ['under the weather', 'idiom', 'کسل؛ بی‌حال', 'Slightly ill or not feeling well.', 'I’m a bit under the weather today.', 'امروز کمی کسل هستم.', '/ˌʌndə ðə ˈweðə/', '/ˌʌndər ðə ˈweðər/', ['unwell'], []],
            ['zone', 'noun', 'منطقه؛ ناحیه', 'An area that is different from others in some way.', 'This is a no-parking zone.', 'اینجا منطقه‌ی ممنوعِ پارک است.', '/zəʊn/', '/zoʊn/', ['area'], []],
        ];

        $lv = ['a1', 'a2', 'b1', 'b2', 'c1', 'c2'];
        $out = [];
        foreach ($rows as $i => [$phrase, $type, $tr, $meaning, $ex, $exTr, $uk, $us, $syn, $ant]) {
            // سطح و دسته‌ی نمونه فقط برای دیدن فیلترها در دمو (بک‌اند مقدار واقعی می‌دهد)
            $h = abs(crc32($phrase));
            $tags = [];
            if (in_array($type, ['idiom', 'phrasal verb'], true)) {
                $tags[] = 'idiom';
            }
            if ($h % 3 === 0) {
                $tags[] = 'ielts';
            }
            if ($h % 7 === 0) {
                $tags[] = 'gre';
            }
            $out[] = [
                'id' => $i + 1, 'phrase' => $phrase, 'type' => $type, 'ipa' => ['uk' => $uk, 'us' => $us],
                'meaning' => $meaning, 'example' => $ex, 'example_tr' => $exTr, 'translate' => $tr, 'synonyms' => $syn, 'antonyms' => $ant,
                'level' => $lv[$h % 6], 'tags' => $tags,
            ];
        }

        $extras = self::demoExtras();
        foreach ($out as &$o) {
            if (isset($extras[$o['phrase']])) {
                $o = $extras[$o['phrase']] + $o;
            }
        }
        unset($o);

        return self::normalize(array_merge($out, self::demoRich(count($out) + 1)));
    }

    /** داده‌ی تکمیلی نمونه برای چند لغتِ رایج تا در دمو تقریباً همه‌جا جزئیات دیده شود (بک‌اند مقدار واقعی می‌دهد) */
    private static function demoExtras(): array
    {
        return [
            'abandon' => ['connotation' => 'negative', 'collocations' => ['abandon a plan', 'abandon ship', 'abandoned building'], 'family' => [['word' => 'abandonment', 'pos' => 'noun'], ['word' => 'abandoned', 'pos' => 'adjective']], 'morphemes' => [['part' => 'a-', 'meaning' => 'به‌سوی / تحت اختیار'], ['part' => 'bandon', 'meaning' => 'اختیار؛ کنترل']], 'origin' => 'From Old French “abandoner”: to put under someone else’s control.'],
            'ability' => ['connotation' => 'positive', 'collocations' => ['the ability to', 'natural ability', 'lose the ability to'], 'family' => [['word' => 'able', 'pos' => 'adjective'], ['word' => 'unable', 'pos' => 'adjective'], ['word' => 'enable', 'pos' => 'verb'], ['word' => 'disability', 'pos' => 'noun']], 'morphemes' => [['part' => 'abil-', 'meaning' => 'توانستن (ریشه‌ی لاتین)'], ['part' => '-ity', 'meaning' => 'حالت؛ ویژگی (پسوند اسم‌ساز)']]],
            'achieve' => ['connotation' => 'positive', 'collocations' => ['achieve a goal', 'achieve success', 'achieve results'], 'family' => [['word' => 'achievement', 'pos' => 'noun'], ['word' => 'achiever', 'pos' => 'noun'], ['word' => 'achievable', 'pos' => 'adjective']], 'morphemes' => [['part' => '-ment', 'meaning' => 'نتیجه‌ی کار (در achievement)']], 'origin' => 'From Old French “achever”: to bring to a head, to finish.'],
            'agree' => ['connotation' => 'neutral', 'collocations' => ['agree with', 'agree to', 'agree on', 'totally agree'], 'family' => [['word' => 'agreement', 'pos' => 'noun'], ['word' => 'agreeable', 'pos' => 'adjective'], ['word' => 'disagree', 'pos' => 'verb']], 'morphemes' => [['part' => 'a-', 'meaning' => 'به‌سوی'], ['part' => '-gree', 'meaning' => 'خوشایند؛ رضایت'], ['part' => 'dis-', 'meaning' => 'نه؛ برعکس']]],
            'beautiful' => ['connotation' => 'positive', 'collocations' => ['beautiful view', 'beautiful smile', 'absolutely beautiful'], 'family' => [['word' => 'beauty', 'pos' => 'noun'], ['word' => 'beautify', 'pos' => 'verb'], ['word' => 'beautifully', 'pos' => 'adverb']], 'morphemes' => [['part' => 'beauty', 'meaning' => 'زیبایی'], ['part' => '-ful', 'meaning' => 'پر از؛ دارای']], 'origin' => 'From Old French “beaute” + the suffix “-ful”.'],
            'believe' => ['connotation' => 'positive', 'collocations' => ['believe in', 'believe it or not', 'firmly believe'], 'family' => [['word' => 'belief', 'pos' => 'noun'], ['word' => 'believable', 'pos' => 'adjective'], ['word' => 'unbelievable', 'pos' => 'adjective'], ['word' => 'disbelief', 'pos' => 'noun']], 'morphemes' => [['part' => 'be-', 'meaning' => 'کاملاً'], ['part' => '-lieve', 'meaning' => 'عزیز داشتن؛ باور کردن']]],
            'careful' => ['connotation' => 'neutral', 'collocations' => ['be careful with', 'careful planning', 'careful not to'], 'family' => [['word' => 'care', 'pos' => 'noun'], ['word' => 'carefully', 'pos' => 'adverb'], ['word' => 'careless', 'pos' => 'adjective']], 'morphemes' => [['part' => 'care', 'meaning' => 'مراقبت؛ دقت'], ['part' => '-ful', 'meaning' => 'پر از؛ دارای']]],
            'create' => ['connotation' => 'positive', 'collocations' => ['create a problem', 'create jobs', 'create an account'], 'family' => [['word' => 'creation', 'pos' => 'noun'], ['word' => 'creative', 'pos' => 'adjective'], ['word' => 'creator', 'pos' => 'noun'], ['word' => 'creativity', 'pos' => 'noun']], 'morphemes' => [['part' => 'cre-', 'meaning' => 'به وجود آوردن (ریشه‌ی لاتین)']]],
            'dangerous' => ['connotation' => 'negative', 'collocations' => ['dangerous situation', 'highly dangerous', 'dangerous driving'], 'family' => [['word' => 'danger', 'pos' => 'noun'], ['word' => 'endanger', 'pos' => 'verb'], ['word' => 'dangerously', 'pos' => 'adverb']], 'morphemes' => [['part' => 'danger', 'meaning' => 'خطر'], ['part' => '-ous', 'meaning' => 'دارای؛ پر از']]],
            'decide' => ['connotation' => 'neutral', 'collocations' => ['decide to', 'decide on', 'decide against'], 'family' => [['word' => 'decision', 'pos' => 'noun'], ['word' => 'decisive', 'pos' => 'adjective'], ['word' => 'undecided', 'pos' => 'adjective']], 'morphemes' => [['part' => 'de-', 'meaning' => 'پایین؛ جدا'], ['part' => '-cide', 'meaning' => 'بریدن؛ قطع کردن']], 'origin' => 'From Latin “decidere”: to cut off, to settle.'],
            'develop' => ['connotation' => 'positive', 'collocations' => ['develop skills', 'develop a habit', 'developing country'], 'family' => [['word' => 'development', 'pos' => 'noun'], ['word' => 'developer', 'pos' => 'noun'], ['word' => 'developed', 'pos' => 'adjective'], ['word' => 'underdeveloped', 'pos' => 'adjective']], 'morphemes' => [['part' => 'de-', 'meaning' => 'معکوس'], ['part' => '-velop', 'meaning' => 'پیچیدن']]],
            'difficult' => ['connotation' => 'neutral', 'collocations' => ['difficult question', 'find it difficult to', 'difficult time'], 'family' => [['word' => 'difficulty', 'pos' => 'noun'], ['word' => 'difficulties', 'pos' => 'noun']], 'morphemes' => [['part' => 'dif-', 'meaning' => 'نه؛ برعکس'], ['part' => '-ficult', 'meaning' => 'آسان؛ شدنی']]],
            'discover' => ['connotation' => 'positive', 'collocations' => ['discover a cure', 'discover the truth', 'newly discovered'], 'family' => [['word' => 'discovery', 'pos' => 'noun'], ['word' => 'discoverer', 'pos' => 'noun'], ['word' => 'undiscovered', 'pos' => 'adjective']], 'morphemes' => [['part' => 'dis-', 'meaning' => 'برداشتن؛ برعکس'], ['part' => 'cover', 'meaning' => 'پوشاندن']], 'origin' => 'Literally “to uncover”: dis- (undo) + cover.'],
            'forgive' => ['connotation' => 'positive', 'collocations' => ['forgive and forget', 'forgive someone for', 'hard to forgive'], 'family' => [['word' => 'forgiveness', 'pos' => 'noun'], ['word' => 'forgiving', 'pos' => 'adjective'], ['word' => 'unforgivable', 'pos' => 'adjective']], 'morphemes' => [['part' => 'for-', 'meaning' => 'کاملاً؛ از بین‌بردن'], ['part' => 'give', 'meaning' => 'دادن']]],
            'imagine' => ['connotation' => 'positive', 'collocations' => ['imagine that', 'can’t imagine', 'just imagine'], 'family' => [['word' => 'imagination', 'pos' => 'noun'], ['word' => 'imaginary', 'pos' => 'adjective'], ['word' => 'imaginative', 'pos' => 'adjective'], ['word' => 'image', 'pos' => 'noun']], 'morphemes' => [['part' => 'imag-', 'meaning' => 'تصویر'], ['part' => '-ine', 'meaning' => 'فعل‌ساز']]],
            'improve' => ['connotation' => 'positive', 'collocations' => ['improve your English', 'improve quality', 'greatly improve'], 'family' => [['word' => 'improvement', 'pos' => 'noun'], ['word' => 'improved', 'pos' => 'adjective']], 'morphemes' => [['part' => 'im-', 'meaning' => 'در'], ['part' => '-prove', 'meaning' => 'سود؛ بهره']]],
            'knowledge' => ['connotation' => 'positive', 'collocations' => ['basic knowledge', 'general knowledge', 'common knowledge', 'knowledge of'], 'family' => [['word' => 'know', 'pos' => 'verb'], ['word' => 'knowledgeable', 'pos' => 'adjective'], ['word' => 'known', 'pos' => 'adjective']], 'morphemes' => [['part' => 'know', 'meaning' => 'دانستن'], ['part' => '-ledge', 'meaning' => 'حالت؛ عمل (پسوند قدیمی)']]],
            'peaceful' => ['connotation' => 'positive', 'collocations' => ['peaceful place', 'peaceful protest', 'peaceful life'], 'family' => [['word' => 'peace', 'pos' => 'noun'], ['word' => 'peacefully', 'pos' => 'adverb'], ['word' => 'peacemaker', 'pos' => 'noun']], 'morphemes' => [['part' => 'peace', 'meaning' => 'آرامش؛ صلح'], ['part' => '-ful', 'meaning' => 'پر از؛ دارای']]],
            'promise' => ['connotation' => 'positive', 'collocations' => ['keep a promise', 'break a promise', 'promise to', 'empty promise'], 'family' => [['word' => 'promising', 'pos' => 'adjective'], ['word' => 'promised', 'pos' => 'adjective'], ['word' => 'promissory', 'pos' => 'adjective']], 'morphemes' => [['part' => 'pro-', 'meaning' => 'پیش؛ جلو'], ['part' => '-mise', 'meaning' => 'فرستادن']], 'origin' => 'From Latin “promittere”: to send forth, to assure.'],
            'remember' => ['connotation' => 'neutral', 'collocations' => ['remember to', 'remember doing', 'as far as I remember'], 'family' => [['word' => 'remembrance', 'pos' => 'noun'], ['word' => 'remembered', 'pos' => 'adjective']], 'morphemes' => [['part' => 're-', 'meaning' => 'دوباره'], ['part' => 'member', 'meaning' => 'یاد؛ خاطره (ریشه‌ی لاتین)']]],
            'silence' => ['connotation' => 'neutral', 'collocations' => ['in silence', 'awkward silence', 'break the silence', 'deafening silence'], 'family' => [['word' => 'silent', 'pos' => 'adjective'], ['word' => 'silently', 'pos' => 'adverb'], ['word' => 'silencer', 'pos' => 'noun']], 'morphemes' => [['part' => 'silen-', 'meaning' => 'آرام بودن']]],
            'strength' => ['connotation' => 'positive', 'collocations' => ['physical strength', 'strength and weakness', 'gain strength'], 'family' => [['word' => 'strong', 'pos' => 'adjective'], ['word' => 'strengthen', 'pos' => 'verb'], ['word' => 'strongly', 'pos' => 'adverb']], 'morphemes' => [['part' => 'strong', 'meaning' => 'قوی'], ['part' => '-th', 'meaning' => 'حالت؛ ویژگی (اسم‌ساز)']]],
            'surprise' => ['connotation' => 'neutral', 'collocations' => ['a pleasant surprise', 'take by surprise', 'surprise party'], 'family' => [['word' => 'surprised', 'pos' => 'adjective'], ['word' => 'surprising', 'pos' => 'adjective'], ['word' => 'surprisingly', 'pos' => 'adverb']], 'morphemes' => [['part' => 'sur-', 'meaning' => 'روی؛ فراتر'], ['part' => '-prise', 'meaning' => 'گرفتن']]],
        ];
    }

    /** چند لغت با داده‌های تکمیلی کامل (برای دیدن جزئیات در دمو) */
    private static function demoRich(int $startId): array
    {
        return [
            [
                'id' => $startId, 'phrase' => 'speechless', 'type' => 'adjective', 'level' => 'b2', 'tags' => ['ielts'], 'connotation' => 'neutral',
                'ipa' => ['uk' => '/ˈspiːtʃləs/', 'us' => '/ˈspiːtʃləs/'],
                'meaning' => 'Unable to speak because of strong emotion such as surprise, anger or joy.',
                'example' => 'The news left her speechless.', 'example_tr' => 'این خبر او را بی‌زبان کرد.', 'translate' => 'ناگفتنی؛ بی‌زبان؛ مات‌ومبهوت',
                'synonyms' => ['dumbfounded', 'stunned'], 'antonyms' => ['talkative'],
                'collocations' => ['left speechless', 'absolutely speechless', 'speechless with rage'],
                'family' => [['word' => 'speech', 'pos' => 'noun'], ['word' => 'speak', 'pos' => 'verb'], ['word' => 'speaker', 'pos' => 'noun'], ['word' => 'speechlessly', 'pos' => 'adverb']],
                'morphemes' => [['part' => 'speech', 'meaning' => 'گفتار؛ توانایی حرف زدن'], ['part' => '-less', 'meaning' => 'بدون؛ فاقد (پسوند)']],
                'origin' => 'Formed in Old English from “spǣc” (speech) + “-lēas” (without).',
            ],
            [
                'id' => $startId + 1, 'phrase' => 'write', 'type' => 'verb', 'level' => 'a1', 'tags' => ['ielts'], 'connotation' => 'neutral',
                'ipa' => ['uk' => '/raɪt/', 'us' => '/raɪt/'],
                'meaning' => 'To make letters, words or numbers on a surface, usually with a pen or on a keyboard.',
                'example' => 'Please write your name at the top of the page.', 'example_tr' => 'لطفاً نامت را بالای صفحه بنویس.', 'translate' => 'نوشتن',
                'synonyms' => ['compose', 'note down'], 'antonyms' => [],
                'collocations' => ['write a letter', 'write an essay', 'write back', 'write down'],
                'family' => [['word' => 'writer', 'pos' => 'noun'], ['word' => 'writing', 'pos' => 'noun'], ['word' => 'written', 'pos' => 'adjective'], ['word' => 'rewrite', 'pos' => 'verb']],
                'morphemes' => [['part' => 'writ-', 'meaning' => 'نوشتن (ریشه‌ی ژرمنی)'], ['part' => '-er', 'meaning' => 'انجام‌دهنده‌ی کار (در writer)']],
            ],
            [
                'id' => $startId + 2, 'phrase' => 'light', 'type' => 'noun', 'level' => 'a2', 'tags' => [], 'connotation' => 'positive',
                'ipa' => ['uk' => '/laɪt/', 'us' => '/laɪt/'],
                'meaning' => 'The natural energy from the sun or a lamp that lets you see things.',
                'example' => 'Turn on the light, it’s getting dark.', 'example_tr' => 'چراغ را روشن کن، دارد تاریک می‌شود.', 'translate' => 'نور؛ روشنایی',
                'synonyms' => ['brightness'], 'antonyms' => ['darkness'],
                'senses' => [
                    ['pos' => 'adjective', 'meaning' => 'Not heavy; easy to lift.', 'example' => 'This bag is very light.', 'example_tr' => 'این کیف خیلی سبک است.', 'translate' => 'سبک'],
                    ['pos' => 'verb', 'meaning' => 'To make something start burning.', 'example' => 'She lit a candle.', 'example_tr' => 'او یک شمع روشن کرد.', 'translate' => 'روشن کردن'],
                ],
                'collocations' => ['natural light', 'turn on the light', 'light bulb', 'light rain'],
                'family' => [['word' => 'lighten', 'pos' => 'verb'], ['word' => 'lighting', 'pos' => 'noun'], ['word' => 'lightly', 'pos' => 'adverb']],
            ],
            [
                'id' => $startId + 3, 'phrase' => 'cunning', 'type' => 'adjective', 'level' => 'c1', 'tags' => ['gre', 'ielts'], 'connotation' => 'negative',
                'ipa' => ['uk' => '/ˈkʌnɪŋ/', 'us' => '/ˈkʌnɪŋ/'],
                'meaning' => 'Clever in a dishonest or deceptive way, to get what you want.',
                'example' => 'The cunning fox tricked the farmer.', 'example_tr' => 'روباه حیله‌گر کشاورز را فریب داد.', 'translate' => 'حیله‌گر؛ مکار',
                'synonyms' => ['sly', 'crafty'], 'antonyms' => ['naive', 'honest'],
                'senses' => [['pos' => 'noun', 'meaning' => 'Skill in deceiving others to get an advantage.', 'example' => 'He won by pure cunning.', 'example_tr' => 'او فقط با حیله برنده شد.', 'translate' => 'حیله؛ مکر']],
                'collocations' => ['a cunning plan', 'cunning trick'],
                'family' => [['word' => 'cunningly', 'pos' => 'adverb']],
                'origin' => 'From Old English “cunnan” (to know); the sense of “deceptive” came later.',
            ],
            [
                'id' => $startId + 4, 'phrase' => 'chill', 'type' => 'verb', 'level' => 'b1', 'tags' => ['slang'], 'connotation' => 'positive',
                'ipa' => ['uk' => '/tʃɪl/', 'us' => '/tʃɪl/'],
                'meaning' => '(Slang) To relax and spend time without doing anything stressful.',
                'example' => 'Let’s just chill at home tonight.', 'example_tr' => 'بیا امشب فقط توی خونه ریلکس کنیم.', 'translate' => 'ریلکس کردن؛ وقت گذراندن',
                'synonyms' => ['relax', 'hang out'], 'antonyms' => ['stress'],
                'senses' => [
                    ['pos' => 'noun', 'meaning' => 'A feeling of coldness.', 'example' => 'There was a chill in the air.', 'example_tr' => 'سرمایی در هوا بود.', 'translate' => 'سرما؛ خنکی'],
                    ['pos' => 'adjective', 'meaning' => '(Slang) Calm and easy-going.', 'example' => 'He’s a really chill guy.', 'example_tr' => 'او آدم خیلی آروم و راحتیه.', 'translate' => 'آروم؛ راحت'],
                ],
                'collocations' => ['chill out', 'chill pill', 'chill with friends'],
                'family' => [['word' => 'chilly', 'pos' => 'adjective'], ['word' => 'chilled', 'pos' => 'adjective']],
            ],
            [
                'id' => $startId + 5, 'phrase' => 'no cap', 'type' => 'slang', 'level' => 'b2', 'tags' => ['slang'], 'connotation' => 'neutral',
                'ipa' => ['uk' => '/nəʊ kæp/', 'us' => '/noʊ kæp/'],
                'meaning' => '(Slang) Not lying; being completely honest.',
                'example' => 'That was the best pizza I’ve ever had, no cap.', 'example_tr' => 'بهترین پیتزایی بود که تا حالا خوردم، شوخی نیست.', 'translate' => 'دروغ نمی‌گم؛ جدی',
                'synonyms' => ['for real', 'seriously'], 'antonyms' => ['cap'],
            ],
        ];
    }
}
