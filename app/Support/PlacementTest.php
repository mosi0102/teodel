<?php

namespace App\Support;

/**
 * آزمون تعیین سطح (/level-test): سه بخش «دایره واژگان»، «گرامر» و «درک شنیداری».
 * هر بخش یک آزمون چهارگزینه‌ای (پیش‌فرض ۱۵ سؤال) است که از هر سطح A1 تا C1 به‌طور مساوی سؤال دارد.
 *
 * 🔌 بک‌اند (بانک سؤال را بعداً در پنل ادمین می‌سازید):
 *   هر سؤال:
 *     id        شناسه
 *     section   vocab | grammar | listening
 *     level     a1 | a2 | b1 | b2 | c1        ← سطح سؤال (به کاربر در حین آزمون نشان داده نمی‌شود)
 *     title     عنوان نوع آزمون (مثلاً Vocabulary)
 *     fa        سؤال/دستور فارسی بالای سؤال (مثلاً «جای خالی را با گزینه‌ی مناسب کامل کنید.»)
 *     q         متن اصلی سؤال (انگلیسی)
 *     options   دقیقاً ۴ گزینه
 *     answer    اندیس گزینه‌ی درست (۰ تا ۳)
 *     audio     (فقط listening) آدرس فایل صوتی؛ اگر نبود در دمو با صدای مرورگر از transcript خوانده می‌شود
 *     transcript (listening) متن صدا؛ در مرور پایان آزمون نشان داده می‌شود
 *     explain   (اختیاری) توضیح کوتاه جواب
 *
 *   دو راه تحویل سؤال‌ها:
 *     ۱) $bank = همه‌ی بانک؛ مرورگر برای هر آزمون به‌طور تصادفی و با توزیع مساوی سطح‌ها انتخاب می‌کند (دمو).
 *     ۲) data-start-url: GET ?section=vocab ← { "questions": [ … ] } (بک‌اند خودش انتخاب می‌کند؛ پیشنهاد می‌شود جواب درست
 *        را بعد از پایان آزمون بدهد — اینجا فرانت جواب را از خود سؤال می‌خواند).
 *   نتیجه: رویداد placement:finish یا آدرس data-finish-url (README). محاسبه‌ی سطح در فرانت فقط نمایشی است.
 *
 * $results (آخرین نمره‌ی هر بخش؛ اگر هنوز شرکت نکرده، کلید نباشد):
 *   { vocab: { score: 80, level: 'b1', correct: 12, total: 15, at: 1760000000 }, … }
 */
class PlacementTest
{
    public const LEVELS = ['a1', 'a2', 'b1', 'b2', 'c1'];

    /** @return array<int, array<string, mixed>> */
    public static function sections(): array
    {
        return [
            ['key' => 'vocab'],
            ['key' => 'grammar'],
            ['key' => 'listening'],
        ];
    }

    public static function demoResults(): array
    {
        return [];
    }

    /** بانک نمونه (۴ سؤال برای هر سطح در هر بخش) — فقط برای تست فرانت */
    public static function demoBank(): array
    {
        $fa = [
            'vocab' => 'کلمه‌ی مناسب را برای جای خالی انتخاب کنید.',
            'grammar' => 'گزینه‌ی درست را برای تکمیل جمله انتخاب کنید.',
            'listening' => 'به صدا گوش دهید و به سؤال پاسخ دهید.',
        ];
        $title = ['vocab' => 'Vocabulary', 'grammar' => 'Grammar', 'listening' => 'Listening'];

        // [level, q, [options], answerIndex]   (listening: [level, transcript, q, [options], answerIndex])
        $vocab = [
            ['a1', 'I drink a glass of ____ every morning.', ['water', 'chair', 'shoe', 'window'], 0],
            ['a1', 'My mother’s sister is my ____.', ['uncle', 'aunt', 'grandfather', 'neighbour'], 1],
            ['a1', 'The opposite of “hot” is ____.', ['cold', 'warm', 'big', 'slow'], 0],
            ['a1', 'We go to bed at ____.', ['night', 'morning', 'summer', 'Monday'], 0],
            ['a2', 'I need to ____ a taxi to get to the airport.', ['book', 'build', 'borrow', 'believe'], 0],
            ['a2', 'She was so ____ after the long walk that she fell asleep immediately.', ['tired', 'thin', 'angry', 'empty'], 0],
            ['a2', 'Could you please ____ the window? It’s very hot in here.', ['open', 'wash', 'break', 'win'], 0],
            ['a2', 'The ____ of the film was very sad, and many people cried.', ['ending', 'price', 'ceiling', 'speed'], 0],
            ['b1', 'He apologised for being late and promised it would not ____ again.', ['happen', 'hurt', 'hold', 'hope'], 0],
            ['b1', 'The company decided to ____ a new product next year.', ['launch', 'lend', 'lack', 'lean'], 0],
            ['b1', 'I’m not sure about the plan; I need more time to ____ my options.', ['consider', 'concern', 'consume', 'conduct'], 0],
            ['b1', 'She has a very ____ personality; she makes friends everywhere.', ['outgoing', 'outdoor', 'outcome', 'outline'], 0],
            ['b2', 'The manager’s decision had a ____ effect on the whole team.', ['profound', 'profit', 'proof', 'prompt'], 0],
            ['b2', 'Scientists are trying to ____ the cause of the disease.', ['determine', 'deter', 'detain', 'deny'], 0],
            ['b2', 'He is a very ____ person; he never changes his mind, even when he is wrong.', ['stubborn', 'subtle', 'sincere', 'shallow'], 0],
            ['b2', 'The two companies decided to ____ their resources to finish the project faster.', ['pool', 'drain', 'spill', 'scatter'], 0],
            ['c1', 'Her ____ remarks about the project upset the whole team.', ['scathing', 'scanty', 'scarce', 'scenic'], 0],
            ['c1', 'The government’s response was ____; it came too late to make any difference.', ['belated', 'bewildered', 'bountiful', 'bashful'], 0],
            ['c1', 'His argument was so ____ that nobody could find a single flaw in it.', ['watertight', 'waterproof', 'waterlogged', 'watery'], 0],
            ['c1', 'The politician tried to ____ the question by changing the subject.', ['evade', 'evoke', 'evict', 'evolve'], 0],
        ];
        $grammar = [
            ['a1', 'She ____ a student.', ['is', 'are', 'am', 'be'], 0],
            ['a1', 'They ____ to school every day.', ['go', 'goes', 'going', 'gone'], 0],
            ['a1', 'There ____ two books on the table.', ['are', 'is', 'am', 'be'], 0],
            ['a1', 'I have ____ apple.', ['an', 'a', 'two', 'many'], 0],
            ['a2', 'Yesterday we ____ a very good film.', ['watched', 'watch', 'watching', 'watches'], 0],
            ['a2', 'He is ____ than his brother.', ['taller', 'tall', 'tallest', 'more tall'], 0],
            ['a2', 'I ____ my homework when you called me.', ['was doing', 'am doing', 'do', 'have done'], 0],
            ['a2', 'If it rains tomorrow, we ____ at home.', ['will stay', 'stayed', 'staying', 'would stayed'], 0],
            ['b1', 'I have lived in this city ____ 2015.', ['since', 'for', 'from', 'during'], 0],
            ['b1', 'She asked me where ____.', ['I lived', 'did I live', 'I do live', 'do I live'], 0],
            ['b1', 'The report ____ by the manager yesterday.', ['was written', 'wrote', 'has written', 'is writing'], 0],
            ['b1', 'You ____ wear a seat belt; it’s the law.', ['must', 'might', 'would', 'may'], 0],
            ['b2', 'If I ____ more time, I would learn another language.', ['had', 'have', 'will have', 'would have'], 0],
            ['b2', 'By the time we arrived, the film ____.', ['had already started', 'already started', 'has already started', 'was already starting'], 0],
            ['b2', 'She suggested ____ a different route.', ['taking', 'to take', 'take', 'took'], 0],
            ['b2', 'Not only ____ late, but he also forgot the documents.', ['did he arrive', 'he arrived', 'arrived he', 'he did arrive'], 0],
            ['c1', 'Had I known about the delay, I ____ a later train.', ['would have taken', 'would take', 'had taken', 'will take'], 0],
            ['c1', 'The more you practise, ____ you become.', ['the more confident', 'more confident', 'the confident more', 'most confident'], 0],
            ['c1', 'It is high time you ____ your own decisions.', ['made', 'make', 'will make', 'have made'], 0],
            ['c1', 'I’d rather you ____ smoke in here.', ['didn’t', 'don’t', 'won’t', 'hadn’t'], 0],
        ];
        $listening = [
            ['a1', 'Hello, my name is Sara. I am twelve years old.', 'How old is Sara?', ['Ten', 'Eleven', 'Twelve', 'Thirteen'], 2],
            ['a1', 'I have a cat. Its name is Tom. It is black.', 'What colour is the cat?', ['Black', 'White', 'Brown', 'Grey'], 0],
            ['a1', 'The shop opens at nine and closes at six.', 'When does the shop open?', ['At six', 'At nine', 'At three', 'At seven'], 1],
            ['a1', 'My favourite food is pizza, but I don’t like fish.', 'What does the speaker NOT like?', ['Pizza', 'Fish', 'Rice', 'Chicken'], 1],
            ['a2', 'Excuse me, how do I get to the train station? Go straight, then turn left at the bank. It’s next to the post office.', 'Where is the train station?', ['Next to the post office', 'Behind the school', 'Next to the cinema', 'Opposite the hotel'], 0],
            ['a2', 'I usually walk to work, but today it is raining, so I’m taking the bus.', 'Why is the speaker taking the bus?', ['It is raining', 'The bus is cheaper', 'The office is far', 'They are late'], 0],
            ['a2', 'Our flight leaves at half past seven, so we need to be at the airport by five thirty.', 'What time must they be at the airport?', ['7:30', '5:30', '6:30', '5:00'], 1],
            ['a2', 'I’d like a table for four, please. Certainly. Would you like to sit inside or outside? Outside, please.', 'Where does the customer want to sit?', ['Inside', 'Outside', 'Near the door', 'At the bar'], 1],
            ['b1', 'I was going to join the gym, but then I found out it costs fifty pounds a month, which is more than I expected. So I’m going to run in the park instead.', 'Why won’t the speaker join the gym?', ['It is too far', 'It is too expensive', 'It is too crowded', 'It is closed'], 1],
            ['b1', 'The meeting has been moved from Tuesday to Thursday because the manager is away on business until Wednesday.', 'Why was the meeting moved?', ['The manager is travelling', 'The room is busy', 'Nobody was free on Tuesday', 'The meeting is cancelled'], 0],
            ['b1', 'Although the hotel was cheap, the room was clean and the staff were very friendly. I would definitely stay there again.', 'What does the speaker think of the hotel?', ['It was dirty and unfriendly', 'It was cheap and pleasant', 'It was expensive but clean', 'It was far from the centre'], 1],
            ['b1', 'If you want to improve your pronunciation, try recording yourself and comparing it with a native speaker. It’s surprising how much you can learn.', 'What advice does the speaker give?', ['Record yourself', 'Move abroad', 'Read aloud only', 'Watch less TV'], 0],
            ['b2', 'The company’s profits fell by ten percent last quarter, mainly due to rising production costs, although sales remained steady.', 'What was the main reason for the fall in profits?', ['Falling sales', 'Higher production costs', 'Fewer customers', 'Poor advertising'], 1],
            ['b2', 'Rather than building a new road, the council has decided to improve public transport, hoping to reduce traffic and pollution at the same time.', 'What has the council decided to do?', ['Build a new road', 'Invest in public transport', 'Ban all cars', 'Increase taxes'], 1],
            ['b2', 'I’d have called earlier, but my phone died and I couldn’t find the charger anywhere. I hope you weren’t worried.', 'Why didn’t the speaker call earlier?', ['They forgot the number', 'Their phone had no battery', 'They were too busy', 'They were asleep'], 1],
            ['b2', 'Contrary to popular belief, the study found that people who sleep less than six hours are not necessarily less productive, provided they take short breaks during the day.', 'What did the study find?', ['Short sleepers are always less productive', 'Short sleepers can stay productive with breaks', 'Sleep has no effect on health', 'Breaks reduce productivity'], 1],
            ['c1', 'The author’s latest novel, though ostensibly a detective story, is really a meditation on memory and loss, which is why critics have struggled to categorise it.', 'Why have critics found it hard to categorise the novel?', ['It is too long', 'It is more than a detective story', 'It has too many characters', 'It was badly written'], 1],
            ['c1', 'While the initial results looked promising, the researchers cautioned that the sample was too small to draw any firm conclusions.', 'What did the researchers say about the results?', ['They are conclusive', 'They should be treated with caution', 'They are wrong', 'They were confirmed by others'], 1],
            ['c1', 'Far from being a setback, the delay gave the team an opportunity to refine the design, and the final product was considerably better as a result.', 'How does the speaker view the delay?', ['As a disaster', 'As beneficial', 'As irrelevant', 'As avoidable'], 1],
            ['c1', 'She gave up a lucrative career in law to open a small bookshop, a decision her family regarded as reckless but which she has never regretted.', 'How did her family react to her decision?', ['They supported her', 'They thought it was irresponsible', 'They were indifferent', 'They were envious'], 1],
        ];

        $out = [];
        foreach ($vocab as $i => [$lv, $q, $opts, $ans]) {
            $out[] = ['id' => 'v' . ($i + 1), 'section' => 'vocab', 'level' => $lv, 'title' => $title['vocab'], 'fa' => $fa['vocab'], 'q' => $q, 'options' => $opts, 'answer' => $ans];
        }
        foreach ($grammar as $i => [$lv, $q, $opts, $ans]) {
            $out[] = ['id' => 'g' . ($i + 1), 'section' => 'grammar', 'level' => $lv, 'title' => $title['grammar'], 'fa' => $fa['grammar'], 'q' => $q, 'options' => $opts, 'answer' => $ans];
        }
        foreach ($listening as $i => [$lv, $tr, $q, $opts, $ans]) {
            $out[] = ['id' => 'l' . ($i + 1), 'section' => 'listening', 'level' => $lv, 'title' => $title['listening'], 'fa' => $fa['listening'], 'q' => $q, 'options' => $opts, 'answer' => $ans, 'audio' => null, 'transcript' => $tr];
        }

        return $out;
    }
}
