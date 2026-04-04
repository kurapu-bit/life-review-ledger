<?php
$pageTitle = 'ホーム - Life Review Ledger';
$pageHeading = 'ホーム';
$currentPage = 'home';

require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/header.php';
?>

<!-- 全体コンテナ（JSで描画） -->
<div id="homeContent">
    <p class="text-[13px] text-slate-400">読み込み中...</p>
</div>

<script>
<?php renderInitScript(); ?>

var areaLabels = <?= json_encode(array_combine(
    ['couple','childcare','housework','money','work_style','housing','social','relatives','intimacy','health'],
    array_map('getAreaLabel', ['couple','childcare','housework','money','work_style','housing','social','relatives','intimacy','health'])
), JSON_UNESCAPED_UNICODE) ?>;
var statusLabels = <?= json_encode(array_combine(
    ['unworded','needs_talk','talk_scheduled','awaiting_agreement','in_progress','on_hold','resolved'],
    array_map('getStatusLabel', ['unworded','needs_talk','talk_scheduled','awaiting_agreement','in_progress','on_hold','resolved'])
), JSON_UNESCAPED_UNICODE) ?>;
var statusColors = <?= json_encode(array_combine(
    ['unworded','needs_talk','talk_scheduled','awaiting_agreement','in_progress','on_hold','resolved'],
    array_map('getStatusColor', ['unworded','needs_talk','talk_scheduled','awaiting_agreement','in_progress','on_hold','resolved'])
), JSON_UNESCAPED_UNICODE) ?>;
var urgencyColors = {1:'bg-stone-300', 2:'bg-amber-300', 3:'bg-amber-400', 4:'bg-orange-500', 5:'bg-red-500'};
var moodLabels = {1:'かなりつらい', 2:'少ししんどい', 3:'ふつう', 4:'まあまあ良い', 5:'良い'};

function getHeroMessage(stats, unresolved, stale, talkNeeded) {
    var sub = '<span class="text-slate-400 text-[15px] font-normal">';
    var endSub = '</span>';
    var msg, badge, level;

    // --- 記録がない場合 ---
    if (stats.inputCount === 0) {
        return {
            message: 'まだ今週の記録がありません。' + '<br>' + sub + 'まずは今日の振り返りから始めましょう。' + endSub,
            badge: '<span class="px-3 py-1.5 rounded-full text-[11px] font-semibold bg-slate-500/15 text-slate-400 border border-slate-500/20">記録なし</span>'
        };
    }

    // --- 衝突が連日 ---
    if (stats.conflictDays >= 3) {
        return {
            message: '衝突が続いています。' + '<br>' + sub + '今は論点を増やすより、1つだけに絞って話す時間をつくりましょう。' + endSub,
            badge: '<span class="px-3 py-1.5 rounded-full text-[11px] font-semibold bg-red-500/15 text-red-400 border border-red-500/20">要注意</span>'
        };
    }

    // --- 逃避が続いている ---
    if (stats.avoidanceDays >= 3) {
        return {
            message: '話を避ける日が続いています。' + '<br>' + sub + '「今、何を避けているか」を1つだけ言語化してみましょう。' + endSub,
            badge: '<span class="px-3 py-1.5 rounded-full text-[11px] font-semibold bg-red-500/15 text-red-400 border border-red-500/20">要注意</span>'
        };
    }

    // --- 衝突+逃避の複合 ---
    if (stats.conflictDays >= 2 && stats.avoidanceDays >= 2) {
        return {
            message: '衝突と逃避が交互に起きています。' + '<br>' + sub + '関係の悪循環に入りかけています。週末に30分だけ、落ち着いて話す場を設けましょう。' + endSub,
            badge: '<span class="px-3 py-1.5 rounded-full text-[11px] font-semibold bg-red-500/15 text-red-400 border border-red-500/20">要注意</span>'
        };
    }

    // --- 停滞している論点が多い ---
    if (stale.length >= 3) {
        return {
            message: '14日以上動いていない論点が ' + stale.length + '件あります。' + '<br>' + sub + '全部でなくていい。最も気になる1つだけ、次のアクションを決めましょう。' + endSub,
            badge: '<span class="px-3 py-1.5 rounded-full text-[11px] font-semibold bg-amber-500/15 text-amber-400 border border-amber-500/20">やや注意</span>'
        };
    }

    // --- 衝突が1〜2回 ---
    if (stats.conflictDays >= 1) {
        return {
            message: '今週、衝突がありました。' + '<br>' + sub + '衝突の内容を整理して、会話準備をつくると次に活かせます。' + endSub,
            badge: '<span class="px-3 py-1.5 rounded-full text-[11px] font-semibold bg-amber-500/15 text-amber-400 border border-amber-500/20">やや注意</span>'
        };
    }

    // --- 逃避が1〜2回 ---
    if (stats.avoidanceDays >= 1) {
        return {
            message: '話を先送りした日がありました。' + '<br>' + sub + '先送りが習慣になる前に、週末の30分で1つ向き合いましょう。' + endSub,
            badge: '<span class="px-3 py-1.5 rounded-full text-[11px] font-semibold bg-amber-500/15 text-amber-400 border border-amber-500/20">やや注意</span>'
        };
    }

    // --- 話し合いが必要な論点がある ---
    if (talkNeeded.length > 0) {
        return {
            message: '穏やかな週です。話し合いの準備をするには良いタイミングです。' + '<br>' + sub + '「' + talkNeeded[0].title + '」の会話準備を作ってみましょう。' + endSub,
            badge: '<span class="px-3 py-1.5 rounded-full text-[11px] font-semibold bg-blue-500/15 text-blue-400 border border-blue-500/20">好機</span>'
        };
    }

    // --- 気分が良い週 ---
    if (stats.avgMood >= 4) {
        return {
            message: '今週は良い流れです。' + '<br>' + sub + 'この調子を来週も続けるために、何がうまくいったか振り返っておきましょう。' + endSub,
            badge: '<span class="px-3 py-1.5 rounded-full text-[11px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">好調</span>'
        };
    }

    // --- 未処理論点がゼロ ---
    if (unresolved.length === 0) {
        return {
            message: '未処理の論点はありません。' + '<br>' + sub + '今のうちに、気になっていることを論点として言語化しておくと安心です。' + endSub,
            badge: '<span class="px-3 py-1.5 rounded-full text-[11px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">安定</span>'
        };
    }

    // --- デフォルト（穏やか） ---
    return {
        message: '比較的穏やかな週です。' + '<br>' + sub + 'この余裕があるうちに、1つ整理しておきましょう。' + endSub,
        badge: '<span class="px-3 py-1.5 rounded-full text-[11px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">安定</span>'
    };
}

function renderHome() {
    var issues = LRL.getIssues();
    var monday = LRL.getMondayStr();
    var sunday = LRL.getSundayStr();
    var stats = LRL.getWeekStats(monday, sunday);
    var risk = LRL.getCollapseRisk(stats);

    var unresolved = issues.filter(function(i){ return i.status !== 'resolved'; });
    var stale = issues.filter(function(i){ return daysSince(i.last_action_date) > 14 && i.status !== 'resolved'; });
    var topIssues = unresolved.slice().sort(function(a,b){ return b.urgency - a.urgency; }).slice(0, 3);
    var talkNeeded = issues.filter(function(i){ return i.status === 'needs_talk' || i.status === 'talk_scheduled'; });

    var html = '';

    // ヒーロー：状況に応じたメッセージ
    var heroResult = getHeroMessage(stats, unresolved, stale, talkNeeded);
    var riskMsg = heroResult.message;
    var riskBadge = heroResult.badge;

    // 気分推移バー
    var moodBars = '';
    stats.inputs.forEach(function(d) {
        var c = d.mood >= 4 ? '#6ee7b7' : (d.mood >= 3 ? '#fbbf24' : '#f87171');
        moodBars += '<div class="flex-1 rounded-sm min-h-[3px]" style="height:' + Math.max(d.mood*18,12) + '%;background:' + c + ';opacity:0.85;"></div>';
    });

    html += '<div class="hero p-6 mb-5"><div class="relative">';
    html += '<div class="flex items-start justify-between mb-6"><div>';
    html += '<p class="text-[12px] text-slate-500 mb-2">' + formatDateJP(new Date()) + ' — 今週の状況</p>';
    html += '<h3 class="text-[20px] font-semibold text-white leading-snug">' + riskMsg + '</h3>';
    html += '</div>' + riskBadge + '</div>';
    html += '<div class="grid grid-cols-2 md:grid-cols-4 gap-3">';
    html += '<a href="?page=issues&filter=unresolved" class="hero-stat hover:bg-white/10 transition-colors block"><p class="text-[11px] text-slate-500 mb-1">未処理論点</p><p class="text-[24px] font-bold text-white tracking-tight">' + unresolved.length + '</p></a>';
    html += '<a href="?page=issues&filter=stale" class="hero-stat hover:bg-white/10 transition-colors block"><p class="text-[11px] text-slate-500 mb-1">14日以上停滞</p><p class="text-[24px] font-bold text-amber-400 tracking-tight">' + stale.length + '</p></a>';
    html += '<a href="?page=weekly" class="hero-stat hover:bg-white/10 transition-colors block"><p class="text-[11px] text-slate-500 mb-1">今週の気分</p><p class="text-[24px] font-bold text-white tracking-tight">' + (stats.avgMood || '-') + '<span class="text-[13px] font-normal text-slate-600">/5</span></p></a>';
    html += '<div class="hero-stat"><p class="text-[11px] text-slate-500 mb-2">気分の推移</p><div class="flex items-end gap-[4px] h-7">' + (moodBars || '<span class="text-[11px] text-slate-600">記録なし</span>') + '</div></div>';
    html += '</div></div></div>';

    // 2カラム
    html += '<div class="grid gap-4 lg:grid-cols-3">';

    // 左 (2/3)
    html += '<div class="lg:col-span-2 space-y-4">';

    // 最優先
    html += '<div class="card p-5"><div class="flex items-center justify-between mb-3"><p class="section-label">今週向き合うこと</p><a href="?page=issues" class="text-[12px] text-brand-500 hover:text-brand-400 font-medium transition-colors">すべて見る →</a></div>';
    if (topIssues.length === 0) {
        html += '<p class="text-[13px] text-slate-400">論点はまだ登録されていません</p>';
    } else {
        html += '<div class="space-y-1">';
        topIssues.forEach(function(issue, idx) {
            var days = daysSince(issue.last_action_date);
            var urgBars = '';
            for (var u = 1; u <= 5; u++) urgBars += '<div class="w-[3px] h-3 rounded-full ' + (u <= issue.urgency ? urgencyColors[issue.urgency] : 'bg-gray-200') + '"></div>';
            html += '<a href="?page=issues" class="priority-item flex items-center gap-4 px-4 py-3.5 block">';
            html += '<span class="text-[22px] font-bold text-slate-200/60 w-6 text-center tabular-nums">' + (idx+1) + '</span>';
            html += '<div class="flex-1 min-w-0"><div class="flex items-center gap-2 mb-0.5">';
            html += '<p class="text-[14px] font-medium text-slate-800">' + esc(issue.title) + '</p>';
            html += '<span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium ' + (statusColors[issue.status]||'') + '">' + (areaLabels[issue.area]||'') + '</span>';
            html += '</div><p class="text-[12px] text-slate-400">' + esc(issue.next_action||'') + '</p></div>';
            html += '<div class="text-right flex-shrink-0 hidden sm:block"><div class="flex gap-[3px] justify-end mb-1">' + urgBars + '</div>';
            html += '<span class="text-[11px] ' + (days > 14 ? 'text-amber-500 font-medium' : 'text-slate-400') + '">' + elapsedLabel(days) + ' · ' + esc((issue.last_action||'').substring(0,8)) + '</span>';
            html += '</div></a>';
        });
        html += '</div>';
    }
    html += '</div>';

    // 次の一手
    html += '<div class="card p-5"><p class="section-label mb-3">次の一手</p><div class="flex flex-wrap gap-2.5">';
    if (topIssues.length > 0) {
        html += '<a href="?page=conversation" class="action-chip bg-gradient-to-b from-blue-50 to-blue-50/60 text-blue-700 border border-blue-100"><svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>' + topIssues[0].title + 'の会話準備</a>';
    }
    html += '<a href="?page=daily-input" class="action-chip bg-gradient-to-b from-emerald-50 to-emerald-50/60 text-emerald-700 border border-emerald-100"><svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>今日の振り返りをつける</a>';
    html += '<a href="?page=patterns" class="action-chip bg-gradient-to-b from-purple-50 to-purple-50/60 text-purple-700 border border-purple-100"><svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6m6 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0h6"/></svg>崩れパターンを確認</a>';
    html += '</div></div>';

    html += '</div>'; // 左カラム終了

    // 右 (1/3)
    html += '<div class="space-y-4">';

    // 会話
    html += '<div class="card p-5"><p class="section-label mb-3">今週の会話</p>';
    if (talkNeeded.length === 0) {
        html += '<p class="text-[13px] text-slate-400">予定なし</p>';
    } else {
        html += '<div class="space-y-3.5">';
        talkNeeded.forEach(function(issue) {
            html += '<div><p class="text-[13px] font-medium text-slate-700 leading-snug">' + esc(issue.title) + '</p>';
            html += '<div class="flex items-center gap-1.5 mt-1"><span class="w-1.5 h-1.5 rounded-full ' + (issue.status==='needs_talk'?'bg-amber-400':'bg-blue-400') + '"></span>';
            html += '<span class="text-[11px] text-slate-400">' + (statusLabels[issue.status]||'') + '</span>';
            html += '<span class="text-slate-200 mx-0.5">·</span><a href="?page=conversation" class="text-[11px] text-brand-500 hover:text-brand-400 font-medium transition-colors">準備する →</a></div></div>';
        });
        html += '</div>';
    }
    html += '</div>';

    // メモ
    html += '<div class="card p-5"><div class="flex items-center justify-between mb-3"><p class="section-label">今週のメモ</p><a href="?page=weekly" class="text-[12px] text-brand-500 hover:text-brand-400 font-medium transition-colors">レビュー →</a></div>';
    var memos = stats.inputs.filter(function(d){ return d.memo; }).slice(-3);
    if (memos.length === 0) {
        html += '<p class="text-[13px] text-slate-400">まだ記録がありません</p>';
    } else {
        html += '<div class="space-y-2">';
        memos.forEach(function(d) {
            var c = d.mood >= 4 ? '#6ee7b7' : (d.mood >= 3 ? '#fbbf24' : '#f87171');
            html += '<div class="memo-item"><p class="text-[12px] text-slate-600 leading-relaxed">' + esc(d.memo) + '</p>';
            html += '<div class="flex items-center gap-1.5 mt-1.5"><div class="w-1.5 h-1.5 rounded-full" style="background:' + c + ';"></div>';
            html += '<span class="text-[10px] text-slate-400">' + formatDateJPShort(d.date) + ' — ' + (moodLabels[d.mood]||'') + '</span></div></div>';
        });
        html += '</div>';
    }
    html += '</div>';

    html += '</div>'; // 右カラム終了
    html += '</div>'; // grid終了

    document.getElementById('homeContent').innerHTML = html;
}

function daysSince(dateStr) {
    return Math.floor((Date.now() - new Date(dateStr).getTime()) / 86400000);
}
function elapsedLabel(days) {
    if (days === 0) return '今日';
    if (days <= 7) return days + '日前';
    if (days <= 30) return Math.floor(days/7) + '週間前';
    return Math.floor(days/30) + 'ヶ月前';
}
function formatDateJP(d) {
    return (d.getMonth()+1) + '月' + d.getDate() + '日';
}
function formatDateJPShort(dateStr) {
    var d = new Date(dateStr);
    return (d.getMonth()+1) + '月' + d.getDate() + '日';
}

renderHome();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
