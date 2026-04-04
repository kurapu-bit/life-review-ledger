<?php
$pageTitle = '今週のレビュー - Life Review Ledger';
$pageHeading = '今週のレビュー';
$currentPage = 'weekly';

require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/header.php';
?>

<div id="weeklyContent">
    <p class="text-[13px] text-slate-400">読み込み中...</p>
</div>

<script>
<?php renderInitScript(); ?>

var moodLabels = {1:'かなりつらい', 2:'少ししんどい', 3:'ふつう', 4:'まあまあ良い', 5:'良い'};
var moodEmojis = {1:'😭', 2:'😟', 3:'😐', 4:'😄', 5:'🥰'};
var areaLabels = <?= json_encode(array_combine(
    ['couple','childcare','housework','money','work_style','housing','social','relatives','intimacy','health'],
    array_map('getAreaLabel', ['couple','childcare','housework','money','work_style','housing','social','relatives','intimacy','health'])
), JSON_UNESCAPED_UNICODE) ?>;

var weekOffset = 0; // 0=今週, -1=先週, -2=2週前...

function getWeekRange(offset) {
    var d = new Date();
    d.setDate(d.getDate() + (offset * 7));
    var day = d.getDay();
    var diff = d.getDate() - day + (day === 0 ? -6 : 1);
    var mon = new Date(d);
    mon.setDate(diff);
    var sun = new Date(mon);
    sun.setDate(sun.getDate() + 6);
    var fmt = function(dt) { return dt.getFullYear()+'-'+String(dt.getMonth()+1).padStart(2,'0')+'-'+String(dt.getDate()).padStart(2,'0'); };
    return { monday: fmt(mon), sunday: fmt(sun) };
}

function changeWeek(dir) {
    weekOffset += dir;
    if (weekOffset > 0) weekOffset = 0;
    renderWeekly();
}

function jumpToDate(dateStr) {
    if (!dateStr) return;
    var target = new Date(dateStr);
    var today = new Date();
    var diffDays = Math.floor((today - target) / 86400000);
    var diffWeeks = Math.floor(diffDays / 7);
    weekOffset = -diffWeeks;
    if (weekOffset > 0) weekOffset = 0;
    renderWeekly();
}

function goThisWeek() {
    weekOffset = 0;
    renderWeekly();
}

function renderWeekly() {
    var range = getWeekRange(weekOffset);
    var monday = range.monday;
    var sunday = range.sunday;
    var stats = LRL.getWeekStats(monday, sunday);
    var issues = LRL.getIssues();
    var inputs = stats.inputs;
    var isThisWeek = weekOffset === 0;

    var weekLabel = isThisWeek ? '今週' : weekOffset === -1 ? '先週' : Math.abs(weekOffset) + '週前';

    var html = '';

    // ヘッダー帯（週切り替え）
    html += '<div class="flex items-center justify-between mb-5">';
    html += '<div class="flex items-center gap-2">';
    // ← ボタン
    html += '<button type="button" onclick="changeWeek(-1)" class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-slate-400 hover:bg-gray-50 hover:text-slate-600 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>';
    // 中央: 週ラベル（クリックで日付ピッカー）
    html += '<div class="text-center relative">';
    html += '<label class="cursor-pointer">';
    html += '<p class="text-[13px] font-medium text-slate-700">' + weekLabel + '</p>';
    html += '<p class="text-[11px] text-slate-400">' + fmtDate(monday) + ' 〜 ' + fmtDate(sunday) + ' <span class="text-brand-500">▾</span></p>';
    html += '<input type="date" onchange="jumpToDate(this.value)" value="' + monday + '" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;">';
    html += '</label>';
    html += '</div>';
    // → ボタン
    html += '<button type="button" onclick="changeWeek(1)" class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center transition-colors ' + (isThisWeek ? 'text-slate-200 cursor-not-allowed' : 'text-slate-400 hover:bg-gray-50 hover:text-slate-600') + '"' + (isThisWeek ? ' disabled' : '') + '><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>';
    // 今週に戻るボタン（今週以外の時だけ表示）
    if (!isThisWeek) {
        html += '<button type="button" onclick="goThisWeek()" class="ml-2 text-[11px] px-3 py-1.5 rounded-lg bg-slate-900 text-white font-medium hover:bg-slate-800 transition-colors">今週に戻る</button>';
    }
    html += '</div>';
    html += '<span class="text-[11px] px-3 py-1 rounded-full bg-brand-100 text-brand-600 font-semibold">入力 ' + stats.inputCount + '/7日</span>';
    html += '</div>';

    // サマリー帯
    var moodBars = '';
    inputs.forEach(function(d) {
        var c = d.mood >= 4 ? '#6ee7b7' : (d.mood >= 3 ? '#fbbf24' : '#f87171');
        moodBars += '<div class="w-3 rounded-sm" style="height:' + Math.max(d.mood*18,10) + '%;background:' + c + ';opacity:0.85;"></div>';
    });

    html += '<div class="card p-5 mb-5"><div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">';
    html += '<div><p class="text-[11px] text-slate-400 mb-0.5">平均気分</p><p class="text-[20px] font-bold text-slate-800">' + (stats.avgMood || '-') + '<span class="text-[12px] font-normal text-slate-400">/5</span></p></div>';
    html += '<div><p class="text-[11px] text-slate-400 mb-0.5">衝突</p><p class="text-[20px] font-bold text-red-500">' + stats.conflictDays + '<span class="text-[12px] font-normal text-slate-400">日</span></p></div>';
    html += '<div><p class="text-[11px] text-slate-400 mb-0.5">逃避</p><p class="text-[20px] font-bold text-amber-500">' + stats.avoidanceDays + '<span class="text-[12px] font-normal text-slate-400">日</span></p></div>';
    html += '<div><p class="text-[11px] text-slate-400 mb-1">気分推移</p><div class="flex items-end gap-[4px] h-6 justify-center">' + (moodBars || '<span class="text-[11px] text-slate-300">-</span>') + '</div></div>';
    html += '</div></div>';

    // メモがある入力を抽出
    var withMemo = inputs.filter(function(d) { return d.memo; });
    // シグナル別に分類
    var conflicts = inputs.filter(function(d) { return d.conflict == 1; });
    var avoidances = inputs.filter(function(d) { return d.avoidance == 1; });
    var goodTalks = inputs.filter(function(d) { return d.good_talk == 1; });

    // 先送りされた論点を集計
    var postponedMap = {};
    inputs.forEach(function(d) {
        if (d.postponed_ids && d.postponed_ids.length > 0) {
            d.postponed_ids.forEach(function(id) {
                if (!postponedMap[id]) postponedMap[id] = { count: 0, dates: [] };
                postponedMap[id].count++;
                postponedMap[id].dates.push(d.date);
            });
        }
    });

    // 2カラム
    html += '<div class="grid gap-4 lg:grid-cols-2">';

    // === 左カラム ===
    html += '<div class="space-y-4">';

    // 出来事
    html += '<div class="card p-5"><p class="section-label mb-3">今週の出来事</p>';
    if (withMemo.length === 0) {
        html += '<p class="text-[13px] text-slate-400">まだ記録がありません</p>';
    } else {
        html += '<div class="space-y-2.5">';
        withMemo.forEach(function(d) {
            var c = d.mood >= 4 ? '#6ee7b7' : (d.mood >= 3 ? '#fbbf24' : '#f87171');
            html += '<div class="flex gap-3"><span class="text-[11px] text-slate-400 flex-shrink-0 w-12 pt-0.5">' + fmtDate(d.date) + '</span>';
            html += '<div class="flex items-start gap-2"><div class="w-1.5 h-1.5 rounded-full mt-1.5 flex-shrink-0" style="background:' + c + ';"></div>';
            html += '<p class="text-[13px] text-slate-700 leading-relaxed">' + esc(d.memo) + '</p></div></div>';
        });
        html += '</div>';
    }
    html += '</div>';

    // 先送り
    html += '<div class="card p-5"><p class="section-label mb-3">先送りした論点</p>';
    var postponedIds = Object.keys(postponedMap);
    if (postponedIds.length === 0) {
        html += '<p class="text-[13px] text-slate-400">今週の先送りはありません</p>';
    } else {
        html += '<div class="space-y-2">';
        postponedIds.forEach(function(id) {
            var issue = issues.find(function(i) { return i.id == id; });
            var info = postponedMap[id];
            html += '<div class="flex items-start gap-2.5"><span class="text-[13px]">😶</span>';
            html += '<div><p class="text-[13px] text-slate-600 leading-relaxed">' + (issue ? esc(issue.title) : '論点ID:' + id) + '</p>';
            html += '<p class="text-[11px] text-slate-400">今週 ' + info.count + '回先送り</p></div></div>';
        });
        html += '</div>';
    }
    html += '</div>';

    // 逃避シグナル
    html += '<div class="card p-5"><p class="section-label mb-3">逃避があった日</p>';
    if (avoidances.length === 0) {
        html += '<p class="text-[13px] text-slate-400">今週は逃避なし</p>';
    } else {
        html += '<div class="space-y-2">';
        avoidances.forEach(function(d) {
            html += '<div class="flex items-start gap-2.5"><span class="text-[13px]">🫣</span>';
            html += '<p class="text-[13px] text-slate-600 leading-relaxed">' + fmtDate(d.date) + ' — ' + (d.memo ? esc(d.memo) : '（メモなし）') + '</p></div>';
        });
        html += '</div>';
    }
    html += '</div>';

    html += '</div>'; // 左カラム終了

    // === 右カラム ===
    html += '<div class="space-y-4">';

    // 良い対話
    html += '<div class="card p-5"><p class="section-label mb-3">良い対話があった日</p>';
    if (goodTalks.length === 0) {
        html += '<p class="text-[13px] text-slate-400">今週の記録なし</p>';
    } else {
        html += '<div class="space-y-2">';
        goodTalks.forEach(function(d) {
            html += '<div class="flex items-start gap-2.5"><span class="text-[13px]">🤝</span>';
            html += '<p class="text-[13px] text-slate-600 leading-relaxed">' + fmtDate(d.date) + ' — ' + (d.memo ? esc(d.memo) : '（メモなし）') + '</p></div>';
        });
        html += '</div>';
    }
    html += '</div>';

    // 衝突
    html += '<div class="card p-5"><p class="section-label mb-3">衝突があった日</p>';
    if (conflicts.length === 0) {
        html += '<p class="text-[13px] text-slate-400">今週は衝突なし</p>';
    } else {
        html += '<div class="space-y-2">';
        conflicts.forEach(function(d) {
            html += '<div class="flex items-start gap-2.5"><span class="text-[13px]">😤</span>';
            html += '<p class="text-[13px] text-slate-600 leading-relaxed">' + fmtDate(d.date) + ' — ' + (d.memo ? esc(d.memo) : '（メモなし）') + '</p></div>';
        });
        html += '</div>';
    }
    html += '</div>';

    // 明日の一手（今週設定したもの）
    var actions = inputs.filter(function(d) { return d.tomorrow_action; });
    html += '<div class="card p-5"><p class="section-label mb-3">今週立てた「明日の一手」</p>';
    if (actions.length === 0) {
        html += '<p class="text-[13px] text-slate-400">今週はまだ設定されていません</p>';
    } else {
        html += '<div class="space-y-2">';
        actions.forEach(function(d) {
            html += '<div class="flex items-start gap-2.5"><span class="text-[13px]">→</span>';
            html += '<div><p class="text-[13px] text-slate-700 leading-relaxed">' + esc(d.tomorrow_action) + '</p>';
            html += '<p class="text-[11px] text-slate-400">' + fmtDate(d.date) + 'に設定</p></div></div>';
        });
        html += '</div>';
    }
    html += '</div>';

    // 来週の基準（最優先の未処理論点から自動生成）
    var topIssues = issues.filter(function(i) { return i.status !== 'resolved'; })
        .sort(function(a,b) { return b.urgency - a.urgency; }).slice(0, 3);

    html += '<div style="background:linear-gradient(135deg,#0f172a,#334155);border-radius:14px;color:white;padding:20px;">';
    html += '<p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-3">来週向き合うべきこと</p>';
    if (topIssues.length === 0) {
        html += '<p class="text-[13px] text-slate-400">論点を登録してください</p>';
    } else {
        html += '<div class="space-y-2.5">';
        topIssues.forEach(function(issue, idx) {
            html += '<div class="flex items-start gap-2.5">';
            html += '<span class="w-5 h-5 rounded-md bg-white/10 flex items-center justify-center flex-shrink-0 text-brand-300 text-[11px] font-bold mt-px">' + (idx+1) + '</span>';
            html += '<div><p class="text-[13px] text-slate-200 leading-relaxed">' + esc(issue.title) + '</p>';
            html += '<p class="text-[11px] text-slate-500">' + (issue.next_action ? esc(issue.next_action) : '') + '</p></div></div>';
        });
        html += '</div>';
    }
    html += '</div>';

    html += '</div>'; // 右カラム終了
    html += '</div>'; // grid終了

    document.getElementById('weeklyContent').innerHTML = html;
}

function fmtDate(dateStr) {
    var d = new Date(dateStr);
    return (d.getMonth()+1) + '月' + d.getDate() + '日';
}
function esc(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

renderWeekly();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
