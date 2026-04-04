<?php
$pageTitle = '崩れ方・反復パターン - Life Review Ledger';
$pageHeading = '崩れ方・反復パターン';
$currentPage = 'patterns';

require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-5">
    <p class="text-[12px] text-slate-400 leading-relaxed">日次の記録から、あなたの崩れ方とうまくいく条件を自動で抽出します。データが増えるほど精度が上がります。</p>
</div>

<div id="patternsContent"><p class="text-[13px] text-slate-400">分析中...</p></div>

<!-- 手動追加モーダル -->
<div id="newPatternModal" style="display:none; position:fixed; inset:0; z-index:50; align-items:center; justify-content:center; background:rgba(0,0,0,0.4); backdrop-filter:blur(4px); padding:16px;" onclick="if(event.target===this)hidePatternModal()">
    <div style="background:#fff; border-radius:18px; width:100%; max-width:480px; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
        <form class="p-7" onsubmit="return saveNewPattern()">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-[16px] font-semibold text-slate-800">パターンを手動で追加</h3>
                <button type="button" onclick="hidePatternModal()" class="p-1 text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="field-label">分類</label>
                    <select id="mpSide" class="field-input">
                        <option value="negative">崩れやすい条件</option>
                        <option value="positive">うまくいきやすい条件</option>
                    </select>
                </div>
                <div>
                    <label class="field-label">パターンの内容</label>
                    <textarea rows="3" id="mpText" class="field-input" placeholder="どういう状況で、何が起きるか（うまくいくか）"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="hidePatternModal()" class="flex-1 py-3 border border-gray-200 rounded-xl text-[13px] text-slate-500 hover:bg-gray-50 transition-colors">キャンセル</button>
                <button type="submit" class="flex-1 py-3 bg-slate-900 text-white rounded-xl text-[13px] font-medium hover:bg-slate-800 transition-colors">追加する</button>
            </div>
        </form>
    </div>
</div>

<script>
<?php renderInitScript(); ?>

var showAllNeg = false;
var showAllPos = false;
var TOP_N = 3;

function renderPatterns() {
    var analyzed = LRL.analyzePatterns();
    var manual = LRL.getManualPatterns();
    var inputs = LRL.getDailyInputs();

    var container = document.getElementById('patternsContent');
    var html = '';

    // データ量
    html += '<div class="card p-4 mb-5 flex items-center justify-between">';
    html += '<div class="flex items-center gap-3">';
    html += '<span class="text-[12px] text-slate-500">分析対象: <span class="font-semibold text-slate-700">' + inputs.length + '日分</span></span>';
    var confLevel = inputs.length >= 20 ? '高' : inputs.length >= 10 ? '中' : '低';
    var confColor = inputs.length >= 20 ? 'text-emerald-600 bg-emerald-50' : inputs.length >= 10 ? 'text-amber-600 bg-amber-50' : 'text-slate-500 bg-slate-100';
    html += '<span class="text-[11px] font-semibold px-2 py-0.5 rounded ' + confColor + '">精度: ' + confLevel + '</span>';
    html += '</div>';
    html += '<button type="button" onclick="showPatternModal()" class="text-[12px] text-brand-500 hover:text-brand-400 font-medium">+ 手動で追加</button>';
    html += '</div>';

    var manualNeg = manual.filter(function(p){ return p.side === 'negative'; });
    var manualPos = manual.filter(function(p){ return p.side === 'positive'; });

    // 2カラム
    html += '<div class="grid gap-5 lg:grid-cols-2">';

    // 崩れやすい条件
    html += renderSide(analyzed.negative, manualNeg, 'negative');
    // うまくいく条件
    html += renderSide(analyzed.positive, manualPos, 'positive');

    html += '</div>';

    // サマリー
    html += renderSummary(analyzed);

    container.innerHTML = html;
}

function renderSide(aiPatterns, manualPatterns, side) {
    var isNeg = side === 'negative';
    var dotColor = isNeg ? 'bg-red-400' : 'bg-emerald-400';
    var label = isNeg ? '崩れやすい条件' : 'うまくいきやすい条件';
    var showAll = isNeg ? showAllNeg : showAllPos;
    var rankBg = isNeg ? 'background:#fef2f2; color:#dc2626;' : 'background:#ecfdf5; color:#059669;';
    var rankBgSub = isNeg ? 'background:#fff5f5; color:#f87171;' : 'background:#f0fdf4; color:#6ee7b7;';

    var html = '<div>';
    html += '<div class="flex items-center gap-2 mb-3"><div class="w-2.5 h-2.5 rounded-full ' + dotColor + '"></div><p class="section-label" style="margin:0">' + label + '</p></div>';

    var allItems = [];
    aiPatterns.forEach(function(p) { allItems.push({ data: p, source: 'ai' }); });
    manualPatterns.forEach(function(p) { allItems.push({ data: p, source: 'manual' }); });

    if (allItems.length === 0) {
        html += '<div class="card p-6 text-center"><p class="text-[13px] text-slate-400">まだパターンが検出されていません。<br>日次記録を続けると自動で抽出されます。</p></div>';
        html += '</div>';
        return html;
    }

    var visible = showAll ? allItems : allItems.slice(0, TOP_N);
    var remaining = allItems.length - TOP_N;

    html += '<div class="card overflow-hidden"><div class="divide-y divide-gray-50">';

    visible.forEach(function(item, idx) {
        var p = item.data;
        var isTop3 = idx < 3;

        html += '<div class="rank-item">';
        // 順位
        html += '<div class="rank-num" style="' + (isTop3 ? rankBg : rankBgSub) + '">' + (idx + 1) + '</div>';
        // 内容
        html += '<div class="flex-1 min-w-0">';
        html += '<div class="flex items-center gap-2 mb-0.5">';
        html += '<p class="text-[13px] font-medium text-slate-700 leading-snug">' + esc(item.source === 'ai' ? p.text : p.text) + '</p>';
        html += '</div>';

        if (item.source === 'ai') {
            // AI: 検出回数 + 確信度ドット + 根拠1件
            html += '<div class="flex items-center gap-2 mt-1">';
            html += '<span class="auto-badge">自動抽出</span>';
            html += '<span class="text-[10px] text-slate-400">' + p.count + '回検出</span>';
            // 確信度ドット（3段階）
            var dots = Math.ceil(p.confidence * 3);
            html += '<span class="flex gap-0.5 ml-1">';
            for (var d = 0; d < 3; d++) {
                html += '<span class="confidence-dot" style="background:' + (d < dots ? (isNeg ? '#f87171' : '#6ee7b7') : '#e5e7eb') + ';"></span>';
            }
            html += '</span>';
            html += '</div>';
            // 根拠（1件だけ、コンパクトに）
            if (p.evidence && p.evidence.length > 0) {
                html += '<p class="evidence-text">「' + esc(p.evidence[0]) + '」</p>';
            }
        } else {
            html += '<div class="flex items-center gap-2 mt-1"><span class="manual-badge">✎ 手動</span>';
            html += '<button type="button" onclick="deletePattern(' + p.id + ')" class="text-[10px] text-red-400 hover:text-red-600">削除</button></div>';
        }

        html += '</div></div>';
    });

    html += '</div>';

    // 展開ボタン
    if (remaining > 0 && !showAll) {
        html += '<div class="px-4 py-2 border-t border-gray-50 text-center">';
        html += '<button type="button" onclick="toggleShowAll(\'' + side + '\')" class="expand-btn">+ 他 ' + remaining + ' 件を表示</button>';
        html += '</div>';
    } else if (showAll && allItems.length > TOP_N) {
        html += '<div class="px-4 py-2 border-t border-gray-50 text-center">';
        html += '<button type="button" onclick="toggleShowAll(\'' + side + '\')" class="expand-btn">上位 ' + TOP_N + ' 件のみ表示</button>';
        html += '</div>';
    }

    html += '</div></div>';
    return html;
}

function renderSummary(analyzed) {
    if (analyzed.negative.length === 0 && analyzed.positive.length === 0) return '';

    var html = '<div class="mt-6" style="background:linear-gradient(135deg,#0f172a,#334155);border-radius:14px;padding:24px;color:white;position:relative;overflow:hidden;">';
    html += '<div style="position:absolute;top:-50%;right:-20%;width:300px;height:300px;background:radial-gradient(circle,rgba(176,148,116,0.08) 0%,transparent 70%);pointer-events:none;"></div>';
    html += '<div class="relative">';
    html += '<div class="flex items-center gap-2 mb-4"><span class="auto-badge" style="background:rgba(255,255,255,0.1);color:#93c5fd;">データ分析</span><p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">あなたの傾向サマリー</p></div>';
    html += '<div class="grid gap-5 md:grid-cols-3">';

    if (analyzed.negative.length > 0) {
        html += '<div><p class="text-[11px] text-slate-500 mb-1">最も注意すべき崩れ条件</p>';
        html += '<p class="text-[13px] text-slate-200 leading-relaxed">' + esc(analyzed.negative[0].text) + '</p></div>';
    }

    var postpones = analyzed.negative.filter(function(p){ return p.type === 'postpone_pattern'; });
    html += '<div><p class="text-[11px] text-slate-500 mb-1">先送りしやすい論点</p>';
    if (postpones.length > 0) {
        html += '<p class="text-[13px] text-slate-200 leading-relaxed">' + postpones.slice(0,2).map(function(p){ return esc(p.text); }).join('<br>') + '</p>';
    } else {
        html += '<p class="text-[13px] text-slate-500">データ蓄積中</p>';
    }
    html += '</div>';

    if (analyzed.positive.length > 0) {
        html += '<div><p class="text-[11px] text-slate-500 mb-1">うまくいく条件</p>';
        html += '<p class="text-[13px] text-slate-200 leading-relaxed">' + analyzed.positive.slice(0,2).map(function(p){ return esc(p.text); }).join(' / ') + '</p></div>';
    }

    html += '</div></div></div>';
    return html;
}

function toggleShowAll(side) {
    if (side === 'negative') showAllNeg = !showAllNeg;
    else showAllPos = !showAllPos;
    renderPatterns();
}

function showPatternModal() { document.getElementById('newPatternModal').style.display = 'flex'; }
function hidePatternModal() { document.getElementById('newPatternModal').style.display = 'none'; }

function saveNewPattern() {
    var text = document.getElementById('mpText').value.trim();
    if (!text) { document.getElementById('mpText').focus(); return false; }
    LRL.saveManualPattern({ side: document.getElementById('mpSide').value, text: text });
    document.getElementById('mpText').value = '';
    hidePatternModal();
    renderPatterns();
    return false;
}

function deletePattern(id) {
    if (!confirm('このパターンを削除しますか？')) return;
    LRL.deleteManualPattern(id);
    renderPatterns();
}

// esc() はstore.jsでグローバル定義済み

document.addEventListener('keydown', function(e) { if (e.key === 'Escape') hidePatternModal(); });

renderPatterns();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
