<?php
$pageTitle = '未処理論点 - Life Review Ledger';
$pageHeading = '未処理論点';
$currentPage = 'issues';

require_once __DIR__ . '/../includes/functions.php';

$allAreas = ['couple','childcare','housework','money','work_style','housing','social','relatives','intimacy','health'];
$allStatuses = ['unworded','needs_talk','talk_scheduled','awaiting_agreement','in_progress','on_hold','resolved'];

require __DIR__ . '/../includes/header.php';
?>

<!-- フィルター & 追加ボタン -->
<div class="flex flex-wrap items-center gap-3 mb-5">
    <select id="filterArea" onchange="renderIssueList()" class="text-[13px] border border-gray-200 rounded-lg px-3 py-2 bg-white shadow-sm">
        <option value="all">すべての領域</option>
        <?php foreach ($allAreas as $area): ?>
        <option value="<?= $area ?>"><?= getAreaLabel($area) ?></option>
        <?php endforeach; ?>
    </select>
    <select id="filterStatus" onchange="renderIssueList()" class="text-[13px] border border-gray-200 rounded-lg px-3 py-2 bg-white shadow-sm">
        <option value="all">すべてのステータス</option>
        <?php foreach ($allStatuses as $status): ?>
        <option value="<?= $status ?>"><?= getStatusLabel($status) ?></option>
        <?php endforeach; ?>
    </select>
    <div class="flex items-center gap-1 ml-2" id="sortBtns"></div>
    <div class="flex-1"></div>
    <button onclick="openNewModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-900 text-white rounded-lg text-[13px] font-medium hover:bg-slate-800 transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        論点を追加
    </button>
</div>

<!-- 論点一覧（JSで描画） -->
<div id="filterBadge" class="mb-3"></div>
<div id="issueListContainer" class="space-y-3"></div>

<!-- 編集モーダル -->
<div id="editModal" style="display:none; position:fixed; inset:0; z-index:50; align-items:center; justify-content:center; background:rgba(0,0,0,0.4); backdrop-filter:blur(4px); padding:16px;" onclick="if(event.target===this)closeEditModal()">
    <div style="background:#fff; border-radius:18px; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
        <form id="editForm" class="p-7" onsubmit="return saveEditIssue()">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-[16px] font-semibold text-slate-800">論点を編集</h3>
                <button type="button" onclick="closeEditModal()" class="p-1 text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            <!-- ステータス -->
            <div class="mb-6">
                <p class="field-label">ステータス</p>
                <div class="flex flex-wrap gap-2" id="editStatusBtns">
                    <?php foreach ($allStatuses as $s): ?>
                    <button type="button" data-status="<?= $s ?>" onclick="selectStatus('<?= $s ?>')" class="status-btn <?= getStatusColor($s) ?>"><?= getStatusLabel($s) ?></button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="status" id="editStatus">
            </div>

            <div class="mb-5">
                <label class="field-label">論点タイトル</label>
                <input type="text" id="editTitle" class="field-input">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="field-label">領域</label>
                    <select id="editArea" class="field-input">
                        <?php foreach ($allAreas as $area): ?>
                        <option value="<?= $area ?>"><?= getAreaLabel($area) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label">緊急度</label>
                    <div class="flex gap-2 mt-1" id="editUrgencyBtns">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" data-val="<?= $i ?>" onclick="selectUrgency(<?= $i ?>)" class="w-10 h-10 rounded-lg border-2 border-gray-200 text-[13px] font-bold text-slate-400 hover:border-brand-400 transition-all"><?= $i ?></button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="editUrgency">
                </div>
            </div>

            <!-- サジェストエリア -->
            <div id="suggestBar" style="display:none" class="mb-5 bg-blue-50 border border-blue-100 rounded-xl p-4">
                <p class="text-[11px] font-semibold text-blue-600 uppercase tracking-wider mb-2">データに基づく推定</p>
                <div id="suggestContent" class="space-y-1.5"></div>
                <button type="button" onclick="applySuggestions()" class="mt-3 text-[12px] font-medium text-blue-600 hover:text-blue-500 transition-colors">推定値を反映する →</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="field-label">感情温度</label>
                    <div class="flex gap-2 mt-1" id="editEmotionBtns">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" data-val="<?= $i ?>" onclick="selectEmotion(<?= $i ?>)" class="w-10 h-10 rounded-lg border-2 border-gray-200 text-[13px] font-bold text-slate-400 hover:border-rose-300 transition-all"><?= $i ?></button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="editEmotion">
                </div>
                <div>
                    <label class="field-label">認識差</label>
                    <div class="flex gap-2 mt-1" id="editGapBtns">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" data-val="<?= $i ?>" onclick="selectGap(<?= $i ?>)" class="w-10 h-10 rounded-lg border-2 border-gray-200 text-[13px] font-bold text-slate-400 hover:border-purple-300 transition-all"><?= $i ?></button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="editGap">
                </div>
            </div>

            <div class="mb-5">
                <label class="field-label">次のアクション</label>
                <input type="text" id="editNextAction" class="field-input" placeholder="次にやるべきことを1つ">
            </div>
            <div class="mb-6">
                <label class="field-label">メモ</label>
                <textarea id="editNotes" rows="3" class="field-input" placeholder="状況や気になっていることを自由に"></textarea>
            </div>

            <!-- アクション履歴 -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-3">
                    <p class="field-label" style="margin:0">アクション履歴</p>
                    <span class="text-[11px] text-slate-400" id="historyCount"></span>
                </div>
                <div id="historyTimeline" class="relative pl-5 space-y-0 mb-4 max-h-[240px] overflow-y-auto"></div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2">アクションを追加</p>
                    <div class="flex gap-2 mb-2 flex-wrap" id="actionTypeBtns">
                        <button type="button" onclick="selectActionType('talked')" data-atype="talked" class="atype-btn text-[11px] px-2.5 py-1 rounded-md border border-gray-200 text-slate-500 hover:bg-white transition-colors">💬 話した</button>
                        <button type="button" onclick="selectActionType('agreed')" data-atype="agreed" class="atype-btn text-[11px] px-2.5 py-1 rounded-md border border-gray-200 text-slate-500 hover:bg-white transition-colors">🤝 合意した</button>
                        <button type="button" onclick="selectActionType('action_done')" data-atype="action_done" class="atype-btn text-[11px] px-2.5 py-1 rounded-md border border-gray-200 text-slate-500 hover:bg-white transition-colors">✅ 実行した</button>
                        <button type="button" onclick="selectActionType('note')" data-atype="note" class="atype-btn text-[11px] px-2.5 py-1 rounded-md border border-gray-200 text-slate-500 hover:bg-white transition-colors">📝 メモ</button>
                        <button type="button" onclick="selectActionType('postponed')" data-atype="postponed" class="atype-btn text-[11px] px-2.5 py-1 rounded-md border border-gray-200 text-slate-500 hover:bg-white transition-colors">⏸ 保留にした</button>
                    </div>
                    <input type="hidden" id="newActionType" value="">
                    <div class="flex gap-2">
                        <input type="text" id="newActionText" class="field-input flex-1" placeholder="何をしたか、一言で" style="padding:8px 12px;font-size:12px;">
                        <button type="button" onclick="addAction()" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-[12px] font-medium hover:bg-slate-800 transition-colors flex-shrink-0">追加</button>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeEditModal()" class="flex-1 py-3 border border-gray-200 rounded-xl text-[13px] text-slate-500 hover:bg-gray-50 transition-colors">キャンセル</button>
                <button type="submit" class="flex-1 py-3 bg-slate-900 text-white rounded-xl text-[13px] font-medium hover:bg-slate-800 transition-colors shadow-sm">保存する</button>
            </div>
        </form>
    </div>
</div>

<!-- 新規追加モーダル -->
<div id="newModal" style="display:none; position:fixed; inset:0; z-index:50; align-items:center; justify-content:center; background:rgba(0,0,0,0.4); backdrop-filter:blur(4px); padding:16px;" onclick="if(event.target===this)closeNewModal()">
    <div style="background:#fff; border-radius:18px; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
        <form class="p-7" onsubmit="return saveNewIssue()">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-[16px] font-semibold text-slate-800">新しい論点を追加</h3>
                <button type="button" onclick="closeNewModal()" class="p-1 text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="mb-5">
                <label class="field-label">論点タイトル</label>
                <input type="text" id="newTitle" class="field-input" placeholder="例：育児の役割分担について">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="field-label">領域</label>
                    <select id="newArea" class="field-input">
                        <?php foreach ($allAreas as $area): ?>
                        <option value="<?= $area ?>"><?= getAreaLabel($area) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label">緊急度</label>
                    <select id="newUrgency" class="field-input">
                        <option value="1">1 - 低い</option><option value="2">2</option>
                        <option value="3" selected>3 - 中</option><option value="4">4</option>
                        <option value="5">5 - 高い</option>
                    </select>
                </div>
            </div>
            <!-- 感情温度・認識差（任意） -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="field-label">感情温度 <span class="text-slate-400 font-normal">（任意）</span></label>
                    <p class="text-[11px] text-slate-400 mb-2">この論点について、今どれくらい感情が動いているか</p>
                    <div class="flex gap-2" id="newEmotionBtns">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" data-val="<?= $i ?>" onclick="selectNewEmotion(<?= $i ?>)" class="w-9 h-9 rounded-lg border-2 border-gray-200 text-[12px] font-bold text-slate-400 hover:border-rose-300 transition-all"><?= $i ?></button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="newEmotion" value="0">
                </div>
                <div>
                    <label class="field-label">認識差 <span class="text-slate-400 font-normal">（任意）</span></label>
                    <p class="text-[11px] text-slate-400 mb-2">相手とどれくらい認識がズレていると感じるか</p>
                    <div class="flex gap-2" id="newGapBtns">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" data-val="<?= $i ?>" onclick="selectNewGap(<?= $i ?>)" class="w-9 h-9 rounded-lg border-2 border-gray-200 text-[12px] font-bold text-slate-400 hover:border-purple-300 transition-all"><?= $i ?></button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="newGap" value="0">
                </div>
            </div>
            <div class="mb-5">
                <label class="field-label">メモ</label>
                <textarea id="newNotes" rows="3" class="field-input" placeholder="今の状況や気になっていることを自由に"></textarea>
            </div>
            <div class="mb-6">
                <label class="field-label">次のアクション</label>
                <input type="text" id="newNextAction" class="field-input" placeholder="例：週末に30分話す時間を確保する">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeNewModal()" class="flex-1 py-3 border border-gray-200 rounded-xl text-[13px] text-slate-500 hover:bg-gray-50 transition-colors">キャンセル</button>
                <button type="submit" class="flex-1 py-3 bg-slate-900 text-white rounded-xl text-[13px] font-medium hover:bg-slate-800 transition-colors shadow-sm">追加する</button>
            </div>
        </form>
    </div>
</div>

<script>
// --- 初期化 ---
<?php renderInitScript(); ?>

var areaLabels = <?= json_encode(array_combine($allAreas, array_map('getAreaLabel', $allAreas)), JSON_UNESCAPED_UNICODE) ?>;
var statusLabels = <?= json_encode(array_combine($allStatuses, array_map('getStatusLabel', $allStatuses)), JSON_UNESCAPED_UNICODE) ?>;
var statusColors = <?= json_encode(array_combine($allStatuses, array_map('getStatusColor', $allStatuses)), JSON_UNESCAPED_UNICODE) ?>;
var urgencyColors = {1:'bg-stone-300',2:'bg-amber-300',3:'bg-amber-400',4:'bg-orange-500',5:'bg-red-500'};
var actionIcons = {created:'📌',talked:'💬',agreed:'🤝',action_done:'✅',status_changed:'🔄',note:'📝',postponed:'⏸'};

// --- 一覧描画 ---
var activeSpecialFilter = '';
var currentSort = 'urgency';  // urgency | created | last_action
var currentSortDir = 'desc';  // asc | desc

function toggleSort(field) {
    if (currentSort === field) {
        currentSortDir = currentSortDir === 'desc' ? 'asc' : 'desc';
    } else {
        currentSort = field;
        currentSortDir = 'desc';
    }
    renderSortBtns();
    renderIssueList();
}

function renderSortBtns() {
    var sorts = [
        {key:'urgency', label:'緊急度'},
        {key:'created', label:'登録日'},
        {key:'last_action', label:'最終アクション'}
    ];
    var container = document.getElementById('sortBtns');
    container.innerHTML = sorts.map(function(s) {
        var isActive = currentSort === s.key;
        var arrow = isActive ? (currentSortDir === 'desc' ? ' ↓' : ' ↑') : '';
        var cls = isActive
            ? 'background:#0f172a; color:#fff;'
            : 'background:transparent; color:#94a3b8;';
        return '<button type="button" onclick="toggleSort(\'' + s.key + '\')" style="padding:6px 10px; border-radius:8px; font-size:11px; font-weight:500; cursor:pointer; transition:all 0.15s; border:none; ' + cls + '">' + s.label + arrow + '</button>';
    }).join('');
}

function sortIssues(issues) {
    var dir = currentSortDir === 'desc' ? -1 : 1;
    return issues.slice().sort(function(a, b) {
        if (currentSort === 'urgency') {
            return (b.urgency - a.urgency) * dir;
        } else if (currentSort === 'created') {
            var aCreated = (a.history || []).find(function(h){ return h.type === 'created'; });
            var bCreated = (b.history || []).find(function(h){ return h.type === 'created'; });
            var aDate = aCreated ? aCreated.date : a.last_action_date;
            var bDate = bCreated ? bCreated.date : b.last_action_date;
            return aDate.localeCompare(bDate) * dir;
        } else if (currentSort === 'last_action') {
            return (a.last_action_date || '').localeCompare(b.last_action_date || '') * dir;
        }
        return 0;
    });
}

function renderIssueList() {
    var issues = LRL.getIssues();
    var fArea = document.getElementById('filterArea').value;
    var fStatus = document.getElementById('filterStatus').value;

    // 特殊フィルター（ホームからのリンク）
    if (activeSpecialFilter === 'unresolved') {
        issues = issues.filter(function(i){ return i.status !== 'resolved'; });
    } else if (activeSpecialFilter === 'stale') {
        issues = issues.filter(function(i){ return i.status !== 'resolved' && daysSince(i.last_action_date) > 14; });
    } else {
        if (fArea !== 'all') issues = issues.filter(function(i){ return i.area === fArea; });
        if (fStatus !== 'all') issues = issues.filter(function(i){ return i.status === fStatus; });
    }

    // ソート適用
    issues = sortIssues(issues);

    var container = document.getElementById('issueListContainer');
    if (issues.length === 0) {
        container.innerHTML = '<div class="card p-12 text-center"><p class="text-[13px] text-slate-400">該当する論点はありません</p></div>';
        return;
    }

    container.innerHTML = issues.map(function(issue) {
        var days = daysSince(issue.last_action_date);
        var histCount = (issue.history || []).length;
        var createdEntry = (issue.history || []).find(function(h){ return h.type === 'created'; });
        var createdDate = createdEntry ? createdEntry.date : issue.last_action_date;

        var urgBars = ''; for (var u=1;u<=5;u++) urgBars += '<div class="bar-seg '+(u<=issue.urgency?urgencyColors[issue.urgency]:'bg-gray-200')+'"></div>';
        var emoBars = ''; for (var e=1;e<=5;e++) emoBars += '<div class="bar-seg '+(e<=issue.emotion_temp?'bg-rose-400':'bg-gray-200')+'"></div>';
        var gapBars = ''; for (var g=1;g<=5;g++) gapBars += '<div class="bar-seg '+(g<=issue.perception_gap?'bg-purple-400':'bg-gray-200')+'"></div>';

        var histDots = '';
        for (var h=0;h<Math.min(histCount,8);h++) histDots += '<div class="w-[6px] h-[6px] rounded-full bg-brand-400"></div>';
        histDots += '<div class="w-[6px] h-[6px] rounded-full border border-dashed border-slate-300"></div>';

        return '<div class="issue-card p-5" onclick="openEditById('+issue.id+')">'
            + '<div class="flex items-start gap-5">'
            + '<div class="flex-1 min-w-0">'
            + '<div class="flex items-center gap-2 mb-2">'
            + '<span class="status-btn '+(statusColors[issue.status]||'')+'">'+(statusLabels[issue.status]||'')+'</span>'
            + '<span class="text-[11px] text-slate-400 font-medium">'+(areaLabels[issue.area]||'')+'</span>'
            + '</div>'
            + '<h3 class="text-[15px] font-semibold text-slate-800 mb-1">'+esc(issue.title)+'</h3>'
            + (issue.notes ? '<p class="text-[13px] text-slate-500 leading-relaxed mb-2">'+esc(issue.notes)+'</p>' : '')
            + (issue.next_action ? '<div class="flex items-center gap-2 mt-2"><svg class="w-3.5 h-3.5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg><span class="text-[13px] text-brand-600 font-medium">'+esc(issue.next_action)+'</span></div>' : '')
            + '<div class="flex items-center gap-2 mt-3 flex-wrap">'
            + '<div class="flex gap-[3px]">'+histDots+'</div>'
            + '<span class="text-[11px] text-slate-400">'+histCount+'件</span>'
            + '<span class="text-slate-200">·</span>'
            + '<span class="text-[11px] text-slate-400">'+fmtDate(createdDate)+' 登録</span>'
            + '<span class="text-slate-200">→</span>'
            + '<span class="text-[11px] '+(days>14?'text-amber-500 font-medium':'text-slate-400')+'">最終 '+fmtDate(issue.last_action_date)+'</span>'
            + '</div>'
            + '</div>'
            + '<div class="flex-shrink-0 w-32 space-y-3 hidden sm:block">'
            + '<div><p class="text-[10px] text-slate-400 uppercase tracking-wider mb-1">緊急度</p><div class="flex gap-1">'+urgBars+'</div></div>'
            + '<div><p class="text-[10px] text-slate-400 uppercase tracking-wider mb-1">感情温度</p><div class="flex gap-1">'+emoBars+'</div></div>'
            + '<div><p class="text-[10px] text-slate-400 uppercase tracking-wider mb-1">認識差</p><div class="flex gap-1">'+gapBars+'</div></div>'
            + '</div>'
            + '<div class="flex-shrink-0 pt-1"><svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/></svg></div>'
            + '</div></div>';
    }).join('');
}

// --- モーダル ---
function showModal(id) { var m=document.getElementById(id); m.style.display='flex'; m.style.alignItems='center'; m.style.justifyContent='center'; }
function hideModal(id) { document.getElementById(id).style.display='none'; }

var currentHistory = [];
var currentIssueId = null;
var selectedActionType = '';
var editingIdx = -1;

var suggestedEmotion = 0;
var suggestedGap = 0;

function openEditById(id) {
    var issue = LRL.getIssues().find(function(i){ return i.id === id; });
    if (!issue) return;
    currentIssueId = id;
    showModal('editModal');
    document.getElementById('editTitle').value = issue.title;
    document.getElementById('editArea').value = issue.area;
    document.getElementById('editNextAction').value = issue.next_action || '';
    document.getElementById('editNotes').value = issue.notes || '';
    selectStatus(issue.status);
    selectUrgency(issue.urgency);
    selectEmotion(issue.emotion_temp);
    selectGap(issue.perception_gap);
    currentHistory = (issue.history || []).slice();
    editingIdx = -1;
    renderTimeline();
    resetActionInput();
    renderSuggestions(issue);
}

// --- 感情温度・認識差の自動推定 ---
function renderSuggestions(issue) {
    var inputs = LRL.getDailyInputs();
    var suggestions = [];
    suggestedEmotion = 0;
    suggestedGap = 0;

    // 1. この論点で先送りが多い → 感情温度が高い可能性
    var postponeCount = 0;
    inputs.forEach(function(d) {
        if (d.postponed_ids && d.postponed_ids.indexOf(issue.id) > -1) postponeCount++;
    });
    if (postponeCount >= 3) {
        suggestedEmotion = Math.max(suggestedEmotion, 4);
        suggestions.push({ icon: '😶', text: 'この論点を ' + postponeCount + '回先送り → 感情温度が高い可能性', field: 'emotion', value: 4 });
    } else if (postponeCount >= 1) {
        suggestedEmotion = Math.max(suggestedEmotion, 3);
        suggestions.push({ icon: '😶', text: 'この論点を ' + postponeCount + '回先送り → 向き合いにくさがある', field: 'emotion', value: 3 });
    }

    // 2. 履歴に衝突・会話結果があれば判定
    var history = issue.history || [];
    var conflictInHistory = history.filter(function(h) { return h.text && (h.text.indexOf('口論') > -1 || h.text.indexOf('衝突') > -1 || h.text.indexOf('揉め') > -1); });
    if (conflictInHistory.length >= 2) {
        suggestedEmotion = Math.max(suggestedEmotion, 5);
        suggestions.push({ icon: '😤', text: '履歴に衝突が ' + conflictInHistory.length + '回 → 感情温度が非常に高い', field: 'emotion', value: 5 });
    }

    // 3. 会話準備の結果から認識差を推定
    var preps = [];
    try { preps = JSON.parse(localStorage.getItem(LRL.getPrefix() + 'conversation_preps')) || []; } catch(e) {}
    var relatedPreps = preps.filter(function(p) { return p.issue_id == issue.id && p.result; });

    var continuedCount = relatedPreps.filter(function(p) { return p.result && p.result.type === 'continued'; }).length;
    var partialCount = relatedPreps.filter(function(p) { return p.result && p.result.type === 'partial'; }).length;

    if (continuedCount >= 2) {
        suggestedGap = Math.max(suggestedGap, 5);
        suggestions.push({ icon: '🔄', text: '会話が ' + continuedCount + '回継続中 → 認識差が大きい可能性', field: 'gap', value: 5 });
    } else if (continuedCount >= 1 || partialCount >= 1) {
        suggestedGap = Math.max(suggestedGap, 4);
        suggestions.push({ icon: '⚡', text: '一部合意・継続が発生 → 認識差がまだある', field: 'gap', value: 4 });
    }

    // 4. 日次入力のメモにこの論点のキーワードが出てくる頻度
    var titleWords = issue.title.replace(/[のをはがにでと]/g, ' ').split(/\s+/).filter(function(w) { return w.length >= 2; });
    var mentionCount = 0;
    var mentionBadMood = 0;
    inputs.forEach(function(d) {
        if (!d.memo) return;
        var mentioned = titleWords.some(function(w) { return d.memo.indexOf(w) > -1; });
        if (mentioned) {
            mentionCount++;
            if (d.mood <= 2 || d.conflict == 1) mentionBadMood++;
        }
    });
    if (mentionBadMood >= 2) {
        suggestedEmotion = Math.max(suggestedEmotion, 4);
        suggestions.push({ icon: '📝', text: 'メモに ' + mentionCount + '回言及（うち低気分/衝突時 ' + mentionBadMood + '回）→ 感情的な重さがある', field: 'emotion', value: 4 });
    }

    // 5. 登録からの経過日数が長い + statusが進んでいない
    var createdEntry = history.find(function(h) { return h.type === 'created'; });
    var ageDays = createdEntry ? daysSince(createdEntry.date) : 0;
    if (ageDays > 30 && (issue.status === 'unworded' || issue.status === 'needs_talk')) {
        suggestedGap = Math.max(suggestedGap, 3);
        suggestions.push({ icon: '⏳', text: ageDays + '日間ステータスが進んでいない → お互い触れにくい論点の可能性', field: 'gap', value: 3 });
    }

    // 表示
    var bar = document.getElementById('suggestBar');
    var content = document.getElementById('suggestContent');

    if (suggestions.length === 0) {
        bar.style.display = 'none';
        return;
    }

    bar.style.display = 'block';
    content.innerHTML = suggestions.map(function(s) {
        var fieldLabel = s.field === 'emotion' ? '感情温度' : '認識差';
        var color = s.field === 'emotion' ? 'text-rose-500' : 'text-purple-500';
        return '<div class="flex items-start gap-2">'
            + '<span class="text-[13px]">' + s.icon + '</span>'
            + '<p class="text-[12px] text-slate-600 leading-relaxed">' + s.text
            + ' <span class="' + color + ' font-semibold">→ ' + fieldLabel + ': ' + s.value + '</span></p>'
            + '</div>';
    }).join('');
}

function applySuggestions() {
    if (suggestedEmotion > 0) selectEmotion(suggestedEmotion);
    if (suggestedGap > 0) selectGap(suggestedGap);
    document.getElementById('suggestBar').style.display = 'none';
}
function closeEditModal() { hideModal('editModal'); }
function openNewModal() { showModal('newModal'); }
function closeNewModal() { hideModal('newModal'); }

// --- 編集保存 ---
function saveEditIssue() {
    var issue = LRL.getIssues().find(function(i){ return i.id === currentIssueId; });
    if (!issue) return false;

    var oldStatus = issue.status;
    issue.title = document.getElementById('editTitle').value;
    issue.area = document.getElementById('editArea').value;
    issue.status = document.getElementById('editStatus').value;
    issue.urgency = parseInt(document.getElementById('editUrgency').value) || 3;
    issue.emotion_temp = parseInt(document.getElementById('editEmotion').value) || 3;
    issue.perception_gap = parseInt(document.getElementById('editGap').value) || 3;
    issue.next_action = document.getElementById('editNextAction').value;
    issue.notes = document.getElementById('editNotes').value;
    issue.history = currentHistory;

    // ステータス変更時はアクション履歴に自動記録
    if (oldStatus !== issue.status) {
        issue.history.push({
            date: LRL.todayStr(),
            type: 'status_changed',
            text: 'ステータスを「' + (statusLabels[issue.status]||'') + '」に変更'
        });
    }

    // last_action更新
    if (issue.history.length > 0) {
        var latest = issue.history.slice().sort(function(a,b){ return b.date.localeCompare(a.date); })[0];
        issue.last_action_date = latest.date;
        issue.last_action = latest.text;
    }

    LRL.saveIssue(issue);
    closeEditModal();
    renderIssueList();
    return false;
}

// --- 新規追加保存 ---
// --- 新規追加: 感情温度・認識差ボタン ---
function selectNewNum(containerId, hiddenId, val, activeColor) {
    var current = parseInt(document.getElementById(hiddenId).value) || 0;
    // 同じ値を再クリックで解除
    var newVal = (current === val) ? 0 : val;
    document.getElementById(hiddenId).value = newVal;
    document.querySelectorAll('#'+containerId+' button').forEach(function(btn) {
        var v = parseInt(btn.dataset.val);
        if (newVal > 0 && v <= newVal) { btn.classList.add(activeColor,'text-white'); btn.classList.remove('border-gray-200','text-slate-400'); }
        else { btn.classList.remove(activeColor,'text-white'); btn.classList.add('border-gray-200','text-slate-400'); }
    });
}
function selectNewEmotion(v) { selectNewNum('newEmotionBtns','newEmotion',v,'bg-rose-400'); }
function selectNewGap(v) { selectNewNum('newGapBtns','newGap',v,'bg-purple-400'); }

function resetNewForm() {
    document.getElementById('newTitle').value = '';
    document.getElementById('newNotes').value = '';
    document.getElementById('newNextAction').value = '';
    document.getElementById('newUrgency').value = '3';
    document.getElementById('newEmotion').value = '0';
    document.getElementById('newGap').value = '0';
    document.querySelectorAll('#newEmotionBtns button, #newGapBtns button').forEach(function(btn) {
        btn.classList.remove('bg-rose-400','bg-purple-400','text-white');
        btn.classList.add('border-gray-200','text-slate-400');
    });
}

function saveNewIssue() {
    var title = document.getElementById('newTitle').value.trim();
    if (!title) { document.getElementById('newTitle').focus(); return false; }

    var today = LRL.todayStr();
    var emotionInput = parseInt(document.getElementById('newEmotion').value) || 0;
    var gapInput = parseInt(document.getElementById('newGap').value) || 0;

    var newIssue = {
        id: 0,
        title: title,
        area: document.getElementById('newArea').value,
        urgency: parseInt(document.getElementById('newUrgency').value) || 3,
        emotion_temp: emotionInput || 3,  // 未入力ならデフォルト3
        perception_gap: gapInput || 3,
        next_action: document.getElementById('newNextAction').value,
        status: 'unworded',
        notes: document.getElementById('newNotes').value,
        last_action_date: today,
        last_action: '論点として登録',
        history: [{ date: today, type: 'created', text: '論点として登録' }]
    };

    LRL.saveIssue(newIssue);
    closeNewModal();
    resetNewForm();
    renderIssueList();
    return false;
}

// --- ステータス・数値選択 ---
function selectStatus(val) {
    document.getElementById('editStatus').value = val;
    document.querySelectorAll('#editStatusBtns .status-btn').forEach(function(btn) {
        btn.classList.toggle('selected', btn.dataset.status === val);
    });
}
function selectNum(cid, hid, val, color) {
    document.getElementById(hid).value = val;
    document.querySelectorAll('#'+cid+' button').forEach(function(btn) {
        var v = parseInt(btn.dataset.val);
        if (v <= val) { btn.classList.add(color,'text-white'); btn.classList.remove('border-gray-200','text-slate-400'); }
        else { btn.classList.remove(color,'text-white'); btn.classList.add('border-gray-200','text-slate-400'); }
    });
}
function selectUrgency(v) { selectNum('editUrgencyBtns','editUrgency',v,'bg-amber-400'); }
function selectEmotion(v) { selectNum('editEmotionBtns','editEmotion',v,'bg-rose-400'); }
function selectGap(v) { selectNum('editGapBtns','editGap',v,'bg-purple-400'); }

// --- タイムライン ---
function renderTimeline() {
    var container = document.getElementById('historyTimeline');
    var indexed = currentHistory.map(function(item,i){ return {item:item,idx:i}; });
    indexed.sort(function(a,b){ return b.item.date.localeCompare(a.item.date); });
    document.getElementById('historyCount').textContent = currentHistory.length+'件';

    if (indexed.length === 0) {
        container.innerHTML = '<p class="text-[12px] text-slate-400 italic pl-1">まだアクション履歴がありません</p>';
        return;
    }

    var html = '<div style="position:absolute;left:7px;top:8px;bottom:8px;width:2px;background:#e5e7eb;border-radius:1px;"></div>';
    indexed.forEach(function(entry, dispIdx) {
        var item = entry.item, origIdx = entry.idx;
        var icon = actionIcons[item.type] || '·';
        var isFirst = dispIdx === 0;

        if (editingIdx === origIdx) {
            var typeOpts = '';
            var tl = {created:'📌 登録',talked:'💬 話した',agreed:'🤝 合意',action_done:'✅ 実行',status_changed:'🔄 変更',note:'📝 メモ',postponed:'⏸ 保留'};
            for (var t in tl) typeOpts += '<option value="'+t+'"'+(t===item.type?' selected':'')+'>'+tl[t]+'</option>';
            html += '<div class="relative flex items-start gap-3 pb-4">'
                + '<div class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] flex-shrink-0 z-10 bg-brand-200 ring-2 ring-brand-400">'+icon+'</div>'
                + '<div class="flex-1 min-w-0 space-y-2">'
                + '<input type="text" id="editHistText" value="'+escAttr(item.text)+'" class="field-input" style="padding:6px 10px;font-size:12px;">'
                + '<div class="flex items-center gap-2"><input type="date" id="editHistDate" value="'+item.date+'" class="field-input" style="padding:5px 8px;font-size:11px;width:auto;">'
                + '<select id="editHistType" class="field-input" style="padding:5px 8px;font-size:11px;width:auto;">'+typeOpts+'</select></div>'
                + '<div class="flex items-center gap-2">'
                + '<button type="button" onclick="saveHistoryEdit('+origIdx+')" class="text-[11px] px-3 py-1 bg-slate-900 text-white rounded-md font-medium hover:bg-slate-800">保存</button>'
                + '<button type="button" onclick="cancelHistoryEdit()" class="text-[11px] px-3 py-1 border border-gray-200 text-slate-500 rounded-md hover:bg-gray-50">キャンセル</button>'
                + '<button type="button" onclick="deleteHistoryItem('+origIdx+')" class="text-[11px] px-2 py-1 text-red-400 hover:text-red-600 ml-auto">削除</button>'
                + '</div></div></div>';
        } else {
            html += '<div class="relative flex items-start gap-3 pb-4 group cursor-pointer" onclick="startEditHistory('+origIdx+')">'
                + '<div class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] flex-shrink-0 z-10 '+(isFirst?'bg-brand-100 ring-2 ring-brand-300':'bg-gray-100')+'">'+icon+'</div>'
                + '<div class="pt-px min-w-0 flex-1"><p class="text-[13px] text-slate-700 leading-relaxed">'+esc(item.text)+'</p>'
                + '<p class="text-[11px] text-slate-400 mt-0.5">'+fmtDate(item.date)+'</p></div>'
                + '<span class="text-[10px] text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0 pt-1">編集</span></div>';
        }
    });
    container.innerHTML = html;
}

function startEditHistory(idx) { editingIdx = idx; renderTimeline(); }
function cancelHistoryEdit() { editingIdx = -1; renderTimeline(); }
function saveHistoryEdit(idx) {
    var t = document.getElementById('editHistText').value.trim();
    if (!t) return;
    currentHistory[idx].text = t;
    currentHistory[idx].date = document.getElementById('editHistDate').value;
    currentHistory[idx].type = document.getElementById('editHistType').value;
    editingIdx = -1;
    renderTimeline();
}
function deleteHistoryItem(idx) {
    if (!confirm('このアクションを削除しますか？')) return;
    currentHistory.splice(idx,1);
    editingIdx = -1;
    renderTimeline();
}

// --- アクションタイプ選択 ---
function selectActionType(type) {
    selectedActionType = type;
    document.querySelectorAll('.atype-btn').forEach(function(btn) {
        if (btn.dataset.atype===type) { btn.classList.add('bg-white','border-brand-400','text-slate-800','shadow-sm'); btn.classList.remove('border-gray-200','text-slate-500'); }
        else { btn.classList.remove('bg-white','border-brand-400','text-slate-800','shadow-sm'); btn.classList.add('border-gray-200','text-slate-500'); }
    });
}
function resetActionInput() {
    selectedActionType = '';
    document.getElementById('newActionText').value = '';
    document.querySelectorAll('.atype-btn').forEach(function(btn) {
        btn.classList.remove('bg-white','border-brand-400','text-slate-800','shadow-sm');
        btn.classList.add('border-gray-200','text-slate-500');
    });
}
function addAction() {
    var text = document.getElementById('newActionText').value.trim();
    if (!text) { document.getElementById('newActionText').focus(); return; }
    if (!selectedActionType) { alert('アクションの種類を選んでください'); return; }
    currentHistory.push({ date: LRL.todayStr(), type: selectedActionType, text: text });
    renderTimeline();
    resetActionInput();
    document.getElementById('historyTimeline').scrollTop = 0;
}

// --- ユーティリティ ---
function daysSince(d) { return Math.floor((Date.now()-new Date(d).getTime())/86400000); }
function elapsedLabel(d) { if(d===0)return'今日';if(d<=7)return d+'日前';if(d<=30)return Math.floor(d/7)+'週間前';return Math.floor(d/30)+'ヶ月前'; }
function fmtDate(s) { var d=new Date(s);return(d.getMonth()+1)+'月'+d.getDate()+'日'; }
// esc() / escAttr() はstore.jsでグローバル定義済み

// --- ESC ---
document.addEventListener('keydown', function(e) { if(e.key==='Escape'){hideModal('editModal');hideModal('newModal');} });

// --- URLパラメータからフィルター取得 ---
var urlParams = new URLSearchParams(window.location.search);
var filterParam = urlParams.get('filter');
if (filterParam === 'unresolved' || filterParam === 'stale') {
    activeSpecialFilter = filterParam;
}

function clearSpecialFilter() {
    activeSpecialFilter = '';
    // URLからfilterパラメータを除去
    var url = new URL(window.location);
    url.searchParams.delete('filter');
    window.history.replaceState({}, '', url);
    renderIssueList();
    renderFilterBadge();
}

function renderFilterBadge() {
    var badge = document.getElementById('filterBadge');
    if (!badge) return;
    if (activeSpecialFilter === 'unresolved') {
        badge.innerHTML = '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg text-[12px] font-medium">未処理論点のみ表示中 <button onclick="clearSpecialFilter()" class="text-slate-400 hover:text-slate-600 ml-1">✕</button></span>';
    } else if (activeSpecialFilter === 'stale') {
        badge.innerHTML = '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 text-amber-700 rounded-lg text-[12px] font-medium">14日以上停滞のみ表示中 <button onclick="clearSpecialFilter()" class="text-amber-400 hover:text-amber-600 ml-1">✕</button></span>';
    } else {
        badge.innerHTML = '';
    }
}

// --- 初期描画 ---
renderSortBtns();
renderIssueList();
renderFilterBadge();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
