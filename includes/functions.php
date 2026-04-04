<?php
// ヘルパー関数

// 領域マッピング
function getAreaLabel(string $area): string {
    $labels = [
        'couple' => '夫婦関係',
        'childcare' => '育児',
        'housework' => '家事分担',
        'money' => 'お金',
        'work_style' => '働き方',
        'housing' => '住居',
        'social' => '交友・遊び',
        'relatives' => '親族',
        'intimacy' => '性生活',
        'health' => '健康・生活習慣',
    ];
    return $labels[$area] ?? $area;
}

// ステータスマッピング
function getStatusLabel(string $status): string {
    $labels = [
        'unworded' => 'まだ言語化前',
        'needs_talk' => '話し合い必要',
        'talk_scheduled' => '話し合い予約済み',
        'awaiting_agreement' => '合意待ち',
        'in_progress' => '実行中',
        'on_hold' => '保留',
        'resolved' => '解消',
    ];
    return $labels[$status] ?? $status;
}

// ステータスの色クラス
function getStatusColor(string $status): string {
    $colors = [
        'unworded' => 'bg-stone-100 text-stone-600',
        'needs_talk' => 'bg-amber-100 text-amber-700',
        'talk_scheduled' => 'bg-blue-100 text-blue-700',
        'awaiting_agreement' => 'bg-purple-100 text-purple-700',
        'in_progress' => 'bg-emerald-100 text-emerald-700',
        'on_hold' => 'bg-stone-200 text-stone-500',
        'resolved' => 'bg-green-100 text-green-600',
    ];
    return $colors[$status] ?? 'bg-gray-100 text-gray-600';
}

// 緊急度バー色
function getUrgencyColor(int $level): string {
    $colors = [
        1 => 'bg-stone-300',
        2 => 'bg-amber-300',
        3 => 'bg-amber-400',
        4 => 'bg-orange-500',
        5 => 'bg-red-500',
    ];
    return $colors[$level] ?? 'bg-stone-300';
}

// パターンタイプの日本語マッピング
function getPatternTypeLabel(string $type): string {
    $labels = [
        'collapse_trigger' => '崩れやすい条件',
        'postpone_pattern' => '先送りしやすい論点',
        'conversation_blocker' => '会話が止まりやすい場面',
        'avoidance_trigger' => '逃避しやすいトリガー',
        'conflict_condition' => '衝突が起こりやすい状況',
        'success_condition' => 'うまくいきやすい条件',
        'kept_standard' => '守れた基準',
        'prevention_rule' => '再発防止ルール',
    ];
    return $labels[$type] ?? $type;
}

// パターンタイプのアイコン色
function getPatternTypeColor(string $type): string {
    $negative = ['collapse_trigger', 'postpone_pattern', 'conversation_blocker', 'avoidance_trigger', 'conflict_condition'];
    return in_array($type, $negative) ? 'text-red-400' : 'text-emerald-400';
}

// 気分の表示
function getMoodEmoji(int $mood): string {
    $moods = [1 => '😭', 2 => '😟', 3 => '😐', 4 => '😄', 5 => '🥰'];
    return $moods[$mood] ?? '😐';
}

function getMoodLabel(int $mood): string {
    $labels = [1 => 'かなりつらい', 2 => '少ししんどい', 3 => 'ふつう', 4 => 'まあまあ良い', 5 => '良い'];
    return $labels[$mood] ?? 'ふつう';
}

// 日付フォーマット
function formatDate(string $date): string {
    return date('n月j日', strtotime($date));
}

function formatDateFull(string $date): string {
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    $ts = strtotime($date);
    $w = $weekdays[date('w', $ts)];
    return date('Y年n月j日', $ts) . "({$w})";
}

// 今週の月曜日
function getThisMonday(): string {
    return date('Y-m-d', strtotime('monday this week'));
}

// 今週の日曜日
function getThisSunday(): string {
    return date('Y-m-d', strtotime('sunday this week'));
}

// HTMLエスケープ
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// 最終アクションからの経過日数を計算
function getDaysSince(string $date): int {
    return (int) ((time() - strtotime($date)) / 86400);
}

// 経過日数の表示テキスト
function getElapsedLabel(int $days): string {
    if ($days === 0) return '今日';
    if ($days <= 7) return $days . '日前';
    if ($days <= 30) return floor($days / 7) . '週間前';
    return floor($days / 30) . 'ヶ月前';
}

// アクションタイプのアイコン
function getActionIcon(string $type): string {
    $icons = [
        'created' => '📌',
        'talked' => '💬',
        'agreed' => '🤝',
        'action_done' => '✅',
        'status_changed' => '🔄',
        'note' => '📝',
        'postponed' => '⏸',
    ];
    return $icons[$type] ?? '·';
}

// デモデータ
function getDemoIssues(): array {
    return [
        [
            'id' => 1, 'title' => '育児分担のすれ違い', 'area' => 'childcare',
            'urgency' => 5, 'emotion_temp' => 4,
            'perception_gap' => 4, 'next_action' => '寝かしつけ担当について話す',
            'status' => 'needs_talk', 'notes' => '平日の寝かしつけがほぼ妻に偏っている',
            'last_action_date' => date('Y-m-d', strtotime('-14 days')),
            'last_action' => '論点として登録',
            'history' => [
                ['date' => date('Y-m-d', strtotime('-28 days')), 'type' => 'created', 'text' => '論点として登録'],
                ['date' => date('Y-m-d', strtotime('-21 days')), 'type' => 'note', 'text' => '寝かしつけが3週連続で妻に偏っていることに気づいた'],
                ['date' => date('Y-m-d', strtotime('-14 days')), 'type' => 'talked', 'text' => '妻に「寝かしつけ大変だよね」と声をかけた。ただ深い話にはならず'],
                ['date' => date('Y-m-d', strtotime('-14 days')), 'type' => 'status_changed', 'text' => 'ステータスを「話し合い必要」に変更'],
            ],
        ],
        [
            'id' => 2, 'title' => '住居の意思決定', 'area' => 'housing',
            'urgency' => 4, 'emotion_temp' => 3,
            'perception_gap' => 3, 'next_action' => '優先条件を2人で書き出す',
            'status' => 'unworded', 'notes' => '賃貸更新か購入か、お互いの本音が見えていない',
            'last_action_date' => date('Y-m-d', strtotime('-30 days')),
            'last_action' => '論点として登録',
            'history' => [
                ['date' => date('Y-m-d', strtotime('-30 days')), 'type' => 'created', 'text' => '論点として登録'],
            ],
        ],
        [
            'id' => 3, 'title' => '飲み会頻度のすり合わせ', 'area' => 'social',
            'urgency' => 3, 'emotion_temp' => 4,
            'perception_gap' => 4, 'next_action' => '月の上限回数を提案する',
            'status' => 'needs_talk', 'notes' => '自分は付き合いだと思っているが、妻は不満を感じている',
            'last_action_date' => date('Y-m-d', strtotime('-21 days')),
            'last_action' => '妻に軽く触れたが深まらず',
            'history' => [
                ['date' => date('Y-m-d', strtotime('-45 days')), 'type' => 'created', 'text' => '論点として登録'],
                ['date' => date('Y-m-d', strtotime('-35 days')), 'type' => 'note', 'text' => '妻が「また飲み？」と言った。不満が溜まっている気配'],
                ['date' => date('Y-m-d', strtotime('-21 days')), 'type' => 'talked', 'text' => '妻に軽く触れたが深まらず。月の回数を提案したい'],
                ['date' => date('Y-m-d', strtotime('-21 days')), 'type' => 'status_changed', 'text' => 'ステータスを「話し合い必要」に変更'],
            ],
        ],
        [
            'id' => 4, 'title' => '家計の見える化', 'area' => 'money',
            'urgency' => 3, 'emotion_temp' => 2,
            'perception_gap' => 2, 'next_action' => '共有家計簿アプリを調べる',
            'status' => 'on_hold', 'notes' => '何となく後回しになっているが、住居判断にも関わる',
            'last_action_date' => date('Y-m-d', strtotime('-60 days')),
            'last_action' => '論点として登録',
            'history' => [
                ['date' => date('Y-m-d', strtotime('-60 days')), 'type' => 'created', 'text' => '論点として登録'],
                ['date' => date('Y-m-d', strtotime('-55 days')), 'type' => 'postponed', 'text' => '住居の話が先と判断し、一旦保留に'],
            ],
        ],
    ];
}

function getDemoDailyInputs(): array {
    return [
        // 4週前（月〜日）
        ['input_date' => date('Y-m-d', strtotime('-27 days')), 'mood' => 3, 'had_conflict' => 0, 'had_avoidance' => 0, 'memo' => '平穏な月曜。仕事が忙しく帰宅遅め'],
        ['input_date' => date('Y-m-d', strtotime('-26 days')), 'mood' => 3, 'had_conflict' => 0, 'had_avoidance' => 1, 'memo' => '帰りが遅くて妻と話す時間がなかった。平日夜はいつもこう'],
        ['input_date' => date('Y-m-d', strtotime('-25 days')), 'mood' => 2, 'had_conflict' => 1, 'had_avoidance' => 0, 'memo' => '水曜夜、疲れているところに妻から家事の指摘。口論に'],
        ['input_date' => date('Y-m-d', strtotime('-24 days')), 'mood' => 2, 'had_conflict' => 0, 'had_avoidance' => 1, 'memo' => '昨日のことを謝りたいが切り出せず。飲み会で帰宅遅い'],
        ['input_date' => date('Y-m-d', strtotime('-23 days')), 'mood' => 2, 'had_conflict' => 0, 'had_avoidance' => 1, 'memo' => '金曜、仕事疲れ。お金の話を先送り。妻の顔色が気になる'],
        ['input_date' => date('Y-m-d', strtotime('-22 days')), 'mood' => 4, 'had_conflict' => 0, 'had_avoidance' => 0, 'memo' => '土曜午前、子どもと公園。妻と少し話せた。穏やか'],
        ['input_date' => date('Y-m-d', strtotime('-21 days')), 'mood' => 4, 'had_conflict' => 0, 'had_avoidance' => 0, 'memo' => '日曜、3人でゆっくり過ごせた。こういう時間が大事'],
        // 3週前
        ['input_date' => date('Y-m-d', strtotime('-20 days')), 'mood' => 3, 'had_conflict' => 0, 'had_avoidance' => 0, 'memo' => '週明け。先週末の余韻で比較的穏やか'],
        ['input_date' => date('Y-m-d', strtotime('-19 days')), 'mood' => 3, 'had_conflict' => 0, 'had_avoidance' => 1, 'memo' => '飲み会の頻度について話したかったが先送り。火曜夜は疲れる'],
        ['input_date' => date('Y-m-d', strtotime('-18 days')), 'mood' => 2, 'had_conflict' => 1, 'had_avoidance' => 0, 'memo' => '水曜夜、寝かしつけで揉めた。「いつも自分ばっかり」と妻'],
        ['input_date' => date('Y-m-d', strtotime('-17 days')), 'mood' => 1, 'had_conflict' => 0, 'had_avoidance' => 1, 'memo' => '仕事で失敗した日に家の論点を持ち出され、黙り込んだ'],
        ['input_date' => date('Y-m-d', strtotime('-16 days')), 'mood' => 2, 'had_conflict' => 0, 'had_avoidance' => 1, 'memo' => '金曜、飲み会後に罪悪感。妻と目を合わせられず寝た'],
        ['input_date' => date('Y-m-d', strtotime('-15 days')), 'mood' => 3, 'had_conflict' => 0, 'had_avoidance' => 0, 'memo' => '土曜午前に「最近ごめん」と切り出せた。少し楽になった'],
        ['input_date' => date('Y-m-d', strtotime('-14 days')), 'mood' => 4, 'had_conflict' => 0, 'had_avoidance' => 0, 'memo' => '日曜、1テーマに絞って育児分担を話せた。結論は出なかったが前進'],
        // 2週前
        ['input_date' => date('Y-m-d', strtotime('-13 days')), 'mood' => 3, 'had_conflict' => 0, 'had_avoidance' => 0, 'memo' => '週末の対話の余韻で良いスタート'],
        ['input_date' => date('Y-m-d', strtotime('-12 days')), 'mood' => 3, 'had_conflict' => 0, 'had_avoidance' => 1, 'memo' => '住居の話を先送り。「今じゃなくていい」と思ってしまう'],
        ['input_date' => date('Y-m-d', strtotime('-11 days')), 'mood' => 2, 'had_conflict' => 1, 'had_avoidance' => 0, 'memo' => '水曜夜、帰宅後に妻から抽象的に責められた感じ。疲労+指摘で爆発'],
        ['input_date' => date('Y-m-d', strtotime('-10 days')), 'mood' => 2, 'had_conflict' => 0, 'had_avoidance' => 1, 'memo' => '昨日の件、謝りたいが「また怒られる」と怖くて先送り'],
        ['input_date' => date('Y-m-d', strtotime('-9 days')), 'mood' => 3, 'had_conflict' => 0, 'had_avoidance' => 0, 'memo' => '金曜、少し落ち着いた。週末に話す時間を作りたい'],
        ['input_date' => date('Y-m-d', strtotime('-8 days')), 'mood' => 4, 'had_conflict' => 0, 'had_avoidance' => 0, 'memo' => '土曜午前、先に自分の非を整理してから話した。うまくいった'],
        ['input_date' => date('Y-m-d', strtotime('-7 days')), 'mood' => 4, 'had_conflict' => 0, 'had_avoidance' => 0, 'memo' => '日曜、良い雰囲気。結論を急がず話せた'],
        // 今週
        ['input_date' => date('Y-m-d', strtotime('-6 days')), 'mood' => 3, 'had_conflict' => 0, 'had_avoidance' => 1, 'memo' => '妻が不機嫌だったが、聞けなかった。平日夜は難しい'],
        ['input_date' => date('Y-m-d', strtotime('-5 days')), 'mood' => 2, 'had_conflict' => 1, 'had_avoidance' => 0, 'memo' => '火曜夜、寝かしつけで口論。疲労が溜まっていた'],
        ['input_date' => date('Y-m-d', strtotime('-4 days')), 'mood' => 2, 'had_conflict' => 0, 'had_avoidance' => 1, 'memo' => '昨日の件を謝りたかったが切り出せず'],
        ['input_date' => date('Y-m-d', strtotime('-3 days')), 'mood' => 3, 'had_conflict' => 0, 'had_avoidance' => 0, 'memo' => '少し落ち着いた。週末に話す時間を作りたい'],
        ['input_date' => date('Y-m-d', strtotime('-2 days')), 'mood' => 4, 'had_conflict' => 0, 'had_avoidance' => 0, 'memo' => '朝、子どもと3人で散歩できた'],
        ['input_date' => date('Y-m-d', strtotime('-1 day')), 'mood' => 3, 'had_conflict' => 0, 'had_avoidance' => 1, 'memo' => '飲み会の頻度について話そうとしたが先送り'],
    ];
}

function getDemoPatterns(): array {
    return [
        ['id' => 1, 'type' => 'collapse_trigger', 'description' => '疲労が高い平日夜に、妻から抽象的に責められたと感じた時', 'frequency' => 8],
        ['id' => 2, 'type' => 'collapse_trigger', 'description' => '仕事で失敗した週に、家庭の論点を持ち出された時', 'frequency' => 4],
        ['id' => 3, 'type' => 'avoidance_trigger', 'description' => '飲み会の後、罪悪感があるのに向き合わず寝てしまう', 'frequency' => 6],
        ['id' => 4, 'type' => 'postpone_pattern', 'description' => 'お金の話を「今じゃなくていい」と先送りする', 'frequency' => 5],
        ['id' => 5, 'type' => 'conversation_blocker', 'description' => '妻が感情的になると、自分が黙り込んでしまう', 'frequency' => 7],
        ['id' => 6, 'type' => 'success_condition', 'description' => '休日午前、子どもが寝ている間に落ち着いて話す', 'frequency' => 3],
        ['id' => 7, 'type' => 'success_condition', 'description' => '先に自分の非を整理してから話を切り出す', 'frequency' => 4],
        ['id' => 8, 'type' => 'success_condition', 'description' => '1テーマに絞って、結論を急がない', 'frequency' => 3],
        ['id' => 9, 'type' => 'prevention_rule', 'description' => '疲労が高い日は論点の話し合いを避ける。翌朝に回す', 'frequency' => 2],
        ['id' => 10, 'type' => 'kept_standard', 'description' => '週に1回は「最近どう？」と聞く時間を作る', 'frequency' => 3],
    ];
}

// デモデータをLocalStorage用JSONに変換
function getDemoIssuesJSON(): string {
    return json_encode(getDemoIssues(), JSON_UNESCAPED_UNICODE);
}

function getDemoDailyInputsJSON(): string {
    $inputs = getDemoDailyInputs();
    $converted = [];
    foreach ($inputs as $d) {
        $converted[] = [
            'date' => $d['input_date'],
            'mood' => $d['mood'],
            'conflict' => $d['had_conflict'],
            'avoidance' => $d['had_avoidance'],
            'good_talk' => 0,
            'postponed_ids' => [],
            'tomorrow_action' => '',
            'memo' => $d['memo'],
        ];
    }
    return json_encode($converted, JSON_UNESCAPED_UNICODE);
}

// 初期化JS文を出力（<script>タグの中で呼ぶこと）
function renderInitScript(): void {
    echo 'LRL.initDemoData(' . getDemoIssuesJSON() . ', ' . getDemoDailyInputsJSON() . ');';
}

function getDemoConversationPreps(): array {
    return [
        [
            'id' => 1, 'issue_id' => 1,
            'purpose' => '寝かしつけ担当の偏りを解消し、持続可能な分担を再設計する',
            'topic' => '育児分担のすれ違い',
            'facts' => '平日の寝かしつけを3週間ほぼ妻が担当している。自分の帰宅は平均20:30。',
            'emotions' => '申し訳なさがある。でも「また責められる」という防御感もある。',
            'avoid_phrases' => '「仕事が忙しいから仕方ない」「俺だってやりたい」',
            'partner_triggers' => '「やりたいけどできない」系の言い訳。抽象的な約束。',
            'my_true_message' => 'もっと育児に関わりたい。でも現実の制約もある。一緒に解決策を考えたい。',
            'desired_outcome' => '週2回は自分が寝かしつけを担当する具体スケジュールを決める',
            'status' => 'preparing',
            'scheduled_at' => date('Y-m-d 10:00:00', strtotime('next saturday')),
        ],
    ];
}
