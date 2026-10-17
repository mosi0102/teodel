<?php

namespace App\Support;

/**
 * دیکشنری (صفحه‌ی /dictionary). سطح‌بندی ندارد؛ لغت‌ها الفبایی‌اند.
 *
 * 🔌 دو حالت برای بک‌اند:
 *   ۱) لیست کوچک تا چند هزار مورد: کنترلر کل لیست را به‌صورت $words می‌دهد (Dictionary::normalize) و جست‌وجو/فیلتر در مرورگر انجام می‌شود.
 *   ۲) لیست خیلی بزرگ: کنترلر $endpoint را می‌دهد؛ مرورگر هر بار همین را صدا می‌زند و فقط یک صفحه می‌گیرد:
 *        GET {endpoint}?q=&letter=a&type=noun|verb|adjective|adverb|other&saved=1&sort=az|za&page=1&per=60
 *        ← { "data": [ لغت… ], "total": 1234, "has_more": true }
 *      (در این حالت $letters = حروفی که لغت دارند، مثلاً ['a','b','c'] تا بقیه‌ی حروف غیرفعال نمایش داده شوند.)
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
            ];
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

        $out = [];
        foreach ($rows as $i => [$phrase, $type, $tr, $meaning, $ex, $exTr, $uk, $us, $syn, $ant]) {
            $out[] = [
                'id' => $i + 1, 'phrase' => $phrase, 'type' => $type, 'ipa' => ['uk' => $uk, 'us' => $us],
                'meaning' => $meaning, 'example' => $ex, 'example_tr' => $exTr, 'translate' => $tr, 'synonyms' => $syn, 'antonyms' => $ant,
            ];
        }

        return self::normalize($out);
    }
}
