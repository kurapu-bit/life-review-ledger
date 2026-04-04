<?php
$pageTitle = '今日の振り返り - Life Review Ledger';
$pageHeading = '今日の振り返り';
$currentPage = 'daily-input';

require_once __DIR__ . '/../includes/functions.php';

$today = date('Y年n月j日');
$weekday = ['日','月','火','水','木','金','土'][date('w')];

require __DIR__ . '/../includes/header.php';
?>

<div class="max-w-lg mx-auto">

    <div class="text-center mb-6">
        <p class="text-[15px] font-semibold text-slate-700"><?= $today ?>（<?= $weekday ?>）</p>
        <p class="text-[12px] text-slate-400 mt-1">今日の家庭運営を、2分で振り返ります。</p>
    </div>

    <div class="flex justify-center gap-1.5 mb-6" id="stepDots">
        <div class="step-indicator active"></div>
        <div class="step-indicator"></div>
        <div class="step-indicator"></div>
        <div class="step-indicator"></div>
    </div>

    <form id="dailyForm" onsubmit="return saveDaily()">

        <!-- Step 1: コンディション -->
        <div class="step active" data-step="1">
            <div class="card p-6 mb-4">
                <p class="text-[13px] font-semibold text-slate-700 mb-1">今日のコンディションは？</p>
                <p class="text-[11px] text-slate-400 mb-4">直感で選んでください</p>
                <div class="flex justify-center gap-3" id="moodSelector">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" onclick="selectMood(<?= $i ?>)" data-mood="<?= $i ?>" class="mood-opt"><?= getMoodEmoji($i) ?></button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="mood" id="moodInput" value="">
                <p class="text-center text-[12px] text-slate-400 mt-3 h-4" id="moodLabel"></p>
            </div>
            <div class="card p-6">
                <p class="text-[13px] font-semibold text-slate-700 mb-3">今日、家庭で何かあった？</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="signal-btn" data-signal="conflict" onclick="toggleSignal(this, 'on-conflict')"><span>😤</span> 衝突した</button>
                    <button type="button" class="signal-btn" data-signal="avoidance" onclick="toggleSignal(this, 'on-avoidance')"><span>😶</span> 逃避した</button>
                    <button type="button" class="signal-btn" data-signal="good" onclick="toggleSignal(this, 'on-good')"><span>🤝</span> 良い対話があった</button>
                </div>
                <input type="hidden" name="had_conflict" id="sigConflict" value="0">
                <input type="hidden" name="had_avoidance" id="sigAvoidance" value="0">
                <input type="hidden" name="had_good_talk" id="sigGood" value="0">
            </div>
            <button type="button" onclick="goStep(2)" class="w-full mt-4 py-3 bg-slate-900 text-white rounded-xl text-[13px] font-medium hover:bg-slate-800 transition-colors">次へ</button>
        </div>

        <!-- Step 2: 先送りチェック（LocalStorageから動的描画） -->
        <div class="step" data-step="2">
            <div class="card p-6">
                <p class="text-[13px] font-semibold text-slate-700 mb-1">今日、向き合えなかった論点は？</p>
                <p class="text-[11px] text-slate-400 mb-4">あてはまるものをタップ。なければそのまま「次へ」</p>
                <div class="space-y-2" id="issueChecks">
                    <!-- JSで描画 -->
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="button" onclick="goStep(1)" class="flex-1 py-3 border border-gray-200 text-slate-500 rounded-xl text-[13px] hover:bg-gray-50 transition-colors">戻る</button>
                <button type="button" onclick="goStep(3)" class="flex-1 py-3 bg-slate-900 text-white rounded-xl text-[13px] font-medium hover:bg-slate-800 transition-colors">次へ</button>
            </div>
        </div>

        <!-- Step 3: 明日の一手 -->
        <div class="step" data-step="3">
            <div class="card p-6 mb-4">
                <p class="text-[13px] font-semibold text-slate-700 mb-1">明日、家庭運営で1つだけやるとしたら？</p>
                <p class="text-[11px] text-slate-400 mb-3">大きなことでなくていい。小さな一歩を</p>
                <input type="text" name="tomorrow_action" id="tomorrowAction" class="field-input" placeholder="例：寝かしつけの話を切り出す">
            </div>
            <div class="card p-6">
                <p class="text-[13px] font-semibold text-slate-700 mb-1">補足があれば一言</p>
                <p class="text-[11px] text-slate-400 mb-3">状況の記録、自分への備忘。空欄でもOK</p>
                <textarea name="memo" id="memoInput" rows="2" class="field-input" placeholder="例：妻が疲れていたので切り出せなかった。週末に回す"></textarea>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="button" onclick="goStep(2)" class="flex-1 py-3 border border-gray-200 text-slate-500 rounded-xl text-[13px] hover:bg-gray-50 transition-colors">戻る</button>
                <button type="button" onclick="goStep(4)" class="flex-1 py-3 bg-slate-900 text-white rounded-xl text-[13px] font-medium hover:bg-slate-800 transition-colors">次へ</button>
            </div>
        </div>

        <!-- Step 4: 確認 & 保存 -->
        <div class="step" data-step="4">
            <div class="card p-8 text-center" id="step4Card">
                <div class="text-4xl mb-4">✓</div>
                <p class="text-[16px] font-semibold text-slate-800 mb-2">今日の振り返り、完了</p>
                <p class="text-[13px] text-slate-500 leading-relaxed mb-6">
                    この記録は週次レビューに反映され、<br>
                    あなたの崩れパターンの学習にも使われます。
                </p>
                <div id="summaryBox" class="text-left bg-gray-50 rounded-xl p-5 mb-6 space-y-2"></div>
                <div class="flex gap-3">
                    <a href="?page=home" class="flex-1 py-3 border border-gray-200 text-slate-500 rounded-xl text-[13px] hover:bg-gray-50 transition-colors text-center">ホームに戻る</a>
                    <button type="submit" class="flex-1 py-3 bg-slate-900 text-white rounded-xl text-[13px] font-medium hover:bg-slate-800 transition-colors">記録を保存</button>
                </div>
            </div>
            <!-- 保存完了後に差し替え -->
            <div id="savedBanner" class="saved-banner" style="display:none;">
                <div class="text-3xl mb-3">✅</div>
                <p class="text-[16px] font-semibold mb-2">保存しました</p>
                <p class="text-[13px] opacity-80 mb-5">ホーム・週次レビューに反映されています。</p>
                <a href="?page=home" class="inline-block px-6 py-2.5 bg-white/20 hover:bg-white/30 rounded-lg text-[13px] font-medium transition-colors">ホームで確認する →</a>
            </div>
        </div>

    </form>
</div>

<script>
// --- 初期化: デモデータをLocalStorageに投入 ---
<?php renderInitScript(); ?>

var currentStep = 1;
var postponedIds = [];
var areaLabels = <?= json_encode(array_combine(
    ['couple','childcare','housework','money','work_style','housing','social','relatives','intimacy','health'],
    array_map('getAreaLabel', ['couple','childcare','housework','money','work_style','housing','social','relatives','intimacy','health'])
), JSON_UNESCAPED_UNICODE) ?>;

// Step 2の論点リストをLocalStorageから描画
function renderIssueChecks() {
    var issues = LRL.getIssues().filter(function(i) { return i.status !== 'resolved'; });
    var container = document.getElementById('issueChecks');
    if (issues.length === 0) {
        container.innerHTML = '<p class="text-[12px] text-slate-400 italic">未処理の論点はありません</p>';
        return;
    }
    container.innerHTML = issues.map(function(issue) {
        var days = Math.floor((Date.now() - new Date(issue.last_action_date).getTime()) / 86400000);
        return '<div class="issue-check flex items-center gap-3" onclick="toggleIssue(this, ' + issue.id + ')">'
            + '<div class="w-5 h-5 rounded border-2 border-gray-300 flex items-center justify-center flex-shrink-0 check-box"></div>'
            + '<div class="flex-1 min-w-0">'
            + '<p class="text-[13px] font-medium text-slate-700">' + issue.title + '</p>'
            + '<p class="text-[11px] text-slate-400">' + (areaLabels[issue.area] || issue.area) + ' · 最終アクション ' + days + '日前</p>'
            + '</div></div>';
    }).join('');
}
renderIssueChecks();

function goStep(n) {
    document.querySelector('.step.active').classList.remove('active');
    document.querySelector('[data-step="' + n + '"]').classList.add('active');
    currentStep = n;
    updateDots();
    if (n === 4) buildSummary();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function updateDots() {
    document.querySelectorAll('.step-indicator').forEach(function(dot, i) {
        dot.classList.remove('active', 'done');
        if (i + 1 === currentStep) dot.classList.add('active');
        else if (i + 1 < currentStep) dot.classList.add('done');
    });
}
function selectMood(level) {
    document.getElementById('moodInput').value = level;
    var labels = {1:'かなりつらい', 2:'少ししんどい', 3:'ふつう', 4:'まあまあ良い', 5:'良い'};
    document.getElementById('moodLabel').textContent = labels[level];
    document.querySelectorAll('.mood-opt').forEach(function(btn) {
        btn.classList.toggle('selected', parseInt(btn.dataset.mood) === level);
    });
}
function toggleSignal(btn, cls) {
    btn.classList.toggle(cls);
    var isOn = btn.classList.contains(cls) ? '1' : '0';
    var sig = btn.dataset.signal;
    if (sig === 'conflict') document.getElementById('sigConflict').value = isOn;
    if (sig === 'avoidance') document.getElementById('sigAvoidance').value = isOn;
    if (sig === 'good') document.getElementById('sigGood').value = isOn;
}
function toggleIssue(el, id) {
    el.classList.toggle('checked');
    var idx = postponedIds.indexOf(id);
    if (idx > -1) {
        postponedIds.splice(idx, 1);
        el.querySelector('.check-box').innerHTML = '';
    } else {
        postponedIds.push(id);
        el.querySelector('.check-box').innerHTML = '<svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
    }
}

function buildSummary() {
    var lines = [];
    var mood = document.getElementById('moodInput').value;
    var moods = {1:'😭 かなりつらい', 2:'😟 少ししんどい', 3:'😐 ふつう', 4:'😄 まあまあ良い', 5:'🥰 良い'};
    if (mood) lines.push('<span class="text-[12px] text-slate-500">気分：</span><span class="text-[13px] text-slate-700">' + (moods[mood] || '') + '</span>');

    var signals = [];
    if (document.getElementById('sigConflict').value === '1') signals.push('😤 衝突');
    if (document.getElementById('sigAvoidance').value === '1') signals.push('😶 逃避');
    if (document.getElementById('sigGood').value === '1') signals.push('🤝 良い対話');
    if (signals.length) lines.push('<span class="text-[12px] text-slate-500">シグナル：</span><span class="text-[13px] text-slate-700">' + signals.join('、') + '</span>');

    if (postponedIds.length > 0) lines.push('<span class="text-[12px] text-slate-500">先送り：</span><span class="text-[13px] text-amber-600 font-medium">' + postponedIds.length + '件</span>');

    var action = document.getElementById('tomorrowAction').value;
    if (action) lines.push('<span class="text-[12px] text-slate-500">明日の一手：</span><span class="text-[13px] text-slate-700">' + action + '</span>');

    var memo = document.getElementById('memoInput').value;
    if (memo) lines.push('<span class="text-[12px] text-slate-500">メモ：</span><span class="text-[13px] text-slate-700">' + memo + '</span>');

    document.getElementById('summaryBox').innerHTML = lines.map(function(l){ return '<div>' + l + '</div>'; }).join('');
}

// --- 保存 ---
function saveDaily() {
    var today = LRL.todayStr();

    // 1. 日次入力を保存
    LRL.saveDailyInput({
        date: today,
        mood: parseInt(document.getElementById('moodInput').value) || 3,
        conflict: parseInt(document.getElementById('sigConflict').value),
        avoidance: parseInt(document.getElementById('sigAvoidance').value),
        good_talk: parseInt(document.getElementById('sigGood').value),
        postponed_ids: postponedIds.slice(),
        tomorrow_action: document.getElementById('tomorrowAction').value,
        memo: document.getElementById('memoInput').value
    });

    // 2. 先送り論点にアクション履歴を自動追加
    LRL.logPostponedIssues(postponedIds, today);

    // 3. 完了UIに切り替え
    document.getElementById('step4Card').style.display = 'none';
    document.getElementById('savedBanner').style.display = 'block';

    return false; // form送信を防ぐ
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
