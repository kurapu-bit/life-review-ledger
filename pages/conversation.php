<?php
$pageTitle = '夫婦会話準備 - Life Review Ledger';
$pageHeading = '夫婦会話準備';
$currentPage = 'conversation';

require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-5">
    <p class="text-[12px] text-slate-400 leading-relaxed">話し合いの"場"に行く前に、事実・感情・本音・ゴールを整理して臨む。準備不足による失敗を防ぎます。</p>
</div>

<div id="talkNeededSection"></div>
<div id="prepListSection"></div>

<!-- 新規モーダル -->
<div id="newPrepModal" style="display:none; position:fixed; inset:0; z-index:50; align-items:center; justify-content:center; background:rgba(0,0,0,0.4); backdrop-filter:blur(4px); padding:16px;" onclick="if(event.target===this)hidePrepModal()">
    <div style="background:#fff; border-radius:18px; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
        <form class="p-7" onsubmit="return saveNewPrep()">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-[16px] font-semibold text-slate-800">会話準備を作成</h3>
                <button type="button" onclick="hidePrepModal()" class="p-1 text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="field-label text-slate-500">紐づける論点</label>
                    <select id="npIssueId" class="field-input"><option value="">（なし）</option></select>
                </div>
                <div>
                    <label class="field-label text-slate-500">話すテーマ</label>
                    <input type="text" id="npTopic" class="field-input" placeholder="例：育児分担の不満">
                </div>
                <div>
                    <label class="field-label text-slate-500">今回の会話の目的</label>
                    <textarea rows="2" id="npPurpose" class="field-input" placeholder="この会話で何を達成したいか"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><label class="field-label text-slate-500">事実</label><textarea rows="2" id="npFacts" class="field-input" placeholder="客観的な事実を書く"></textarea></div>
                    <div><label class="field-label text-slate-500">感情</label><textarea rows="2" id="npEmotions" class="field-input" placeholder="率直な気持ちを書く"></textarea></div>
                </div>
                <div>
                    <label class="field-label text-slate-500">本当に伝えたいこと</label>
                    <textarea rows="2" id="npMessage" class="field-input" placeholder="怒りや不満の裏にある、本当の願い"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><label class="field-label text-slate-500">言わない方がいい表現</label><textarea rows="2" id="npAvoid" class="field-input" placeholder="言い訳・攻撃になりやすい言葉"></textarea></div>
                    <div><label class="field-label text-slate-500">相手の地雷</label><textarea rows="2" id="npTriggers" class="field-input" placeholder="防御的になるポイント"></textarea></div>
                </div>
                <div><label class="field-label text-slate-500">決めたいこと</label><input type="text" id="npOutcome" class="field-input" placeholder="具体的なゴール"></div>
                <div><label class="field-label text-slate-500">会話予定日時</label><input type="datetime-local" id="npSchedule" class="field-input"></div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="hidePrepModal()" class="flex-1 py-3 border border-gray-200 rounded-xl text-[13px] text-slate-500 hover:bg-gray-50 transition-colors">キャンセル</button>
                <button type="submit" class="flex-1 py-3 bg-slate-900 text-white rounded-xl text-[13px] font-medium hover:bg-slate-800 transition-colors">作成する</button>
            </div>
        </form>
    </div>
</div>

<script>
<?php renderInitScript(); ?>

var PREP_KEY = LRL.getPrefix() + 'conversation_preps';
var areaLabels = <?= json_encode(array_combine(
    ['couple','childcare','housework','money','work_style','housing','social','relatives','intimacy','health'],
    array_map('getAreaLabel', ['couple','childcare','housework','money','work_style','housing','social','relatives','intimacy','health'])
), JSON_UNESCAPED_UNICODE) ?>;
var statusColors = <?= json_encode(array_combine(
    ['unworded','needs_talk','talk_scheduled','awaiting_agreement','in_progress','on_hold','resolved'],
    array_map('getStatusColor', ['unworded','needs_talk','talk_scheduled','awaiting_agreement','in_progress','on_hold','resolved'])
), JSON_UNESCAPED_UNICODE) ?>;

function getPreps() { try { return JSON.parse(localStorage.getItem(PREP_KEY)) || []; } catch(e) { return []; } }
function savePreps(preps) { localStorage.setItem(PREP_KEY, JSON.stringify(preps)); }

// デモデータはtest1（ID:1）のみ
if (LRL_USER_ID === 1 && !localStorage.getItem(LRL.getPrefix() + 'preps_initialized')) {
    var demoPreps = <?= json_encode(getDemoConversationPreps(), JSON_UNESCAPED_UNICODE) ?>;
    savePreps(demoPreps);
    localStorage.setItem(LRL.getPrefix() + 'preps_initialized', '1');
}

var openPrepIds = {};
var prepTab = 'active';

function setPrepTab(tab) { prepTab = tab; renderPrepList(); }
function renderAll() { renderTalkNeeded(); renderPrepList(); }

function renderTalkNeeded() {
    var issues = LRL.getIssues().filter(function(i) { return i.status === 'needs_talk' || i.status === 'talk_scheduled'; });
    var container = document.getElementById('talkNeededSection');
    if (issues.length === 0) { container.innerHTML = ''; return; }
    var html = '<div class="mb-6"><p class="section-label mb-3">話し合いが必要な論点</p><div class="grid gap-3 md:grid-cols-2">';
    issues.forEach(function(issue) {
        html += '<div class="card card-hover p-4 flex items-center justify-between cursor-pointer" onclick="showPrepModalForIssue('+issue.id+')">'
            + '<div><span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium '+(statusColors[issue.status]||'')+' mb-1">'+(areaLabels[issue.area]||'')+'</span>'
            + '<p class="text-[13px] font-medium text-slate-800">'+esc(issue.title)+'</p></div>'
            + '<span class="text-[12px] px-3 py-1.5 bg-brand-500 text-white rounded-lg font-medium">準備を作成</span></div>';
    });
    html += '</div></div>';
    container.innerHTML = html;
}

function renderPrepList() {
    var preps = getPreps();
    var issues = LRL.getIssues();
    var container = document.getElementById('prepListSection');

    var activePreps = [];
    var donePreps = [];
    preps.forEach(function(prep, idx) {
        var entry = { prep: prep, idx: idx };
        if (prep.status && prep.status.indexOf('completed') === 0) donePreps.push(entry);
        else activePreps.push(entry);
    });

    if (preps.length === 0) {
        container.innerHTML = '<p class="section-label mb-3">会話準備</p><div class="card p-8 text-center"><p class="text-[13px] text-slate-400">まだ会話準備はありません</p></div>';
        return;
    }

    var html = '';

    // タブ
    html += '<div class="flex items-center gap-1 mb-4">';
    html += '<button type="button" onclick="setPrepTab(\'active\')" class="px-4 py-2 rounded-lg text-[13px] font-medium transition-colors '
        + (prepTab === 'active' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-gray-100') + '">進行中 <span class="ml-1 text-[11px] opacity-70">' + activePreps.length + '</span></button>';
    html += '<button type="button" onclick="setPrepTab(\'done\')" class="px-4 py-2 rounded-lg text-[13px] font-medium transition-colors '
        + (prepTab === 'done' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-gray-100') + '">完了済み <span class="ml-1 text-[11px] opacity-70">' + donePreps.length + '</span></button>';
    html += '</div>';

    var displayList = prepTab === 'done' ? donePreps : activePreps;

    if (displayList.length === 0) {
        html += '<div class="card p-8 text-center"><p class="text-[13px] text-slate-400">' + (prepTab === 'done' ? '完了した会話準備はまだありません' : '進行中の会話準備はありません') + '</p></div>';
        container.innerHTML = html;
        return;
    }

    displayList.forEach(function(entry) {
        var prep = entry.prep;
        var idx = entry.idx;
        var isOpen = openPrepIds[idx];
        var linkedIssue = prep.issue_id ? issues.find(function(i){ return i.id == prep.issue_id; }) : null;

        var statusLabel = {preparing:'準備中', completed_agreed:'合意済み', completed_partial:'一部合意', completed_continued:'次回に継続'}[prep.status] || '準備中';
        var statusBg = prep.status === 'completed_agreed' ? 'bg-emerald-100 text-emerald-700'
            : prep.status === 'completed_partial' ? 'bg-amber-100 text-amber-700'
            : prep.status === 'completed_continued' ? 'bg-blue-100 text-blue-700'
            : 'bg-slate-100 text-slate-600';

        html += '<div class="prep-card mb-4">';

        // ヘッダー
        html += '<div class="prep-header flex items-center justify-between" onclick="togglePrep('+idx+')">';
        html += '<div class="flex-1 min-w-0">';
        html += '<div class="flex items-center gap-2 mb-1">';
        html += '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold '+statusBg+'">'+statusLabel+'</span>';
        if (linkedIssue) html += '<span class="text-[10px] text-slate-400 bg-gray-100 px-1.5 py-0.5 rounded">📌 '+esc(linkedIssue.title)+'</span>';
        if (prep.scheduled_at) html += '<span class="text-[11px] text-slate-400">予定: '+fmtDateTime(prep.scheduled_at)+'</span>';
        html += '</div>';
        html += '<p class="text-[14px] font-semibold text-slate-800">'+esc(prep.topic)+'</p>';
        html += '<p class="text-[12px] text-slate-400 mt-0.5">'+esc(prep.purpose||'').substring(0,60)+(prep.purpose && prep.purpose.length>60?'...':'')+'</p>';
        html += '</div>';
        html += '<svg class="w-4 h-4 text-slate-300 chevron flex-shrink-0 '+(isOpen?'open':'')+'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/></svg>';
        html += '</div>';

        // ボディ
        html += '<div class="prep-body '+(isOpen?'open':'')+'">';
        html += '<div class="p-5 grid gap-4 md:grid-cols-2">';
        html += '<div class="prep-section bg-brand-50"><p class="field-label text-brand-600">会話の目的</p><p class="text-[13px] text-slate-700 leading-relaxed">'+esc(prep.purpose)+'</p></div>';
        html += '<div class="prep-section bg-brand-50"><p class="field-label text-brand-600">決めたいこと</p><p class="text-[13px] text-slate-700 leading-relaxed">'+esc(prep.desired_outcome)+'</p></div>';
        if (prep.facts) html += '<div class="prep-section bg-gray-50"><p class="field-label text-slate-500"><span class="w-1.5 h-1.5 rounded-full bg-blue-400 inline-block mr-1"></span>事実</p><p class="text-[13px] text-slate-600 leading-relaxed">'+esc(prep.facts)+'</p></div>';
        if (prep.emotions) html += '<div class="prep-section bg-gray-50"><p class="field-label text-slate-500"><span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block mr-1"></span>感情</p><p class="text-[13px] text-slate-600 leading-relaxed">'+esc(prep.emotions)+'</p></div>';
        if (prep.my_true_message) {
            html += '<div class="md:col-span-2 prep-section" style="background:linear-gradient(135deg,#0f172a,#334155);border-radius:10px;">';
            html += '<p class="field-label text-brand-300">自分が本当に伝えたいこと</p><p class="text-[14px] text-slate-200 leading-relaxed">'+esc(prep.my_true_message)+'</p></div>';
        }
        if (prep.avoid_phrases) html += '<div class="prep-section bg-red-50 border border-red-100"><p class="field-label text-red-500">⊘ 言わない方がいい表現</p><p class="text-[13px] text-red-700 leading-relaxed">'+esc(prep.avoid_phrases)+'</p></div>';
        if (prep.partner_triggers) html += '<div class="prep-section bg-amber-50 border border-amber-100"><p class="field-label text-amber-600">⚠ 相手の地雷</p><p class="text-[13px] text-amber-700 leading-relaxed">'+esc(prep.partner_triggers)+'</p></div>';
        html += '</div>';

        // === 会話後の記録 ===
        html += '<div class="px-5 py-5 border-t border-gray-100">';

        if (prep.result) {
            // 記録済み
            var rIcon = prep.result.type === 'agreed' ? '🤝' : prep.result.type === 'partial' ? '⚡' : '🔄';
            var rLabel = prep.result.type === 'agreed' ? '合意できた' : prep.result.type === 'partial' ? '一部のみ合意' : '次回に継続';
            var rColor = prep.result.type === 'agreed' ? 'bg-emerald-50 border-emerald-200' : prep.result.type === 'partial' ? 'bg-amber-50 border-amber-200' : 'bg-blue-50 border-blue-200';

            html += '<p class="field-label text-slate-500 mb-3">会話の結果</p>';
            html += '<div class="'+rColor+' border rounded-xl p-4 mb-3">';
            html += '<p class="text-[13px] font-medium text-slate-700 mb-1">'+rIcon+' '+rLabel+'</p>';
            if (prep.result.agreement) html += '<div class="mt-2"><p class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold mb-1">合意内容</p><p class="text-[13px] text-slate-600 leading-relaxed">'+esc(prep.result.agreement)+'</p></div>';
            if (prep.result.learnings) html += '<div class="mt-2"><p class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold mb-1">わかったこと</p><p class="text-[13px] text-slate-600 leading-relaxed">'+esc(prep.result.learnings)+'</p></div>';
            if (prep.result.next_approach) html += '<div class="mt-2"><p class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold mb-1">次回のアプローチ</p><p class="text-[13px] text-slate-600 leading-relaxed">'+esc(prep.result.next_approach)+'</p></div>';
            html += '</div>';
            if (linkedIssue) html += '<p class="text-[11px] text-slate-400">📌 「'+esc(linkedIssue.title)+'」のアクション履歴に自動反映済み</p>';
            html += '<button type="button" onclick="resetResult('+idx+')" class="text-[11px] text-brand-500 hover:text-brand-400 font-medium mt-2">記録を修正する</button>';
        } else {
            // 未記録 → 結果入力UI
            html += '<p class="field-label text-slate-500 mb-3">会話後の記録</p>';
            html += '<p class="text-[12px] text-slate-400 mb-3">会話はどうでしたか？</p>';

            html += '<div class="flex flex-wrap gap-2 mb-4">';
            html += '<button type="button" onclick="selectResult('+idx+',\'agreed\')" class="result-btn result-agreed" data-rtype="agreed">🤝 合意できた</button>';
            html += '<button type="button" onclick="selectResult('+idx+',\'partial\')" class="result-btn result-partial" data-rtype="partial">⚡ 一部のみ合意</button>';
            html += '<button type="button" onclick="selectResult('+idx+',\'continued\')" class="result-btn result-continued" data-rtype="continued">🔄 次回に継続</button>';
            html += '</div>';

            html += '<div id="resultForm_'+idx+'" style="display:none;" class="space-y-3">';
            html += '<div id="resultAgreedField_'+idx+'"><label class="field-label text-slate-500">合意した内容</label><textarea id="resultAgreement_'+idx+'" class="field-input" rows="2" placeholder="具体的に何を決めたか"></textarea></div>';
            html += '<div><label class="field-label text-slate-500">わかったこと・気づき</label><textarea id="resultLearnings_'+idx+'" class="field-input" rows="2" placeholder="相手の本音、自分の気づきなど"></textarea></div>';
            html += '<div id="resultNextField_'+idx+'"><label class="field-label text-slate-500">次回のアプローチ</label><textarea id="resultNext_'+idx+'" class="field-input" rows="2" placeholder="次に話すとき、何を変えるか"></textarea></div>';
            html += '<button type="button" onclick="saveResult('+idx+')" class="px-5 py-2.5 bg-slate-900 text-white rounded-lg text-[12px] font-medium hover:bg-slate-800 transition-colors">結果を保存して論点に反映</button>';
            html += '</div>';
        }
        html += '</div>';

        // フッター
        html += '<div class="px-5 py-3 border-t border-gray-100 flex items-center justify-end">';
        html += '<button type="button" onclick="deletePrep('+idx+')" class="text-[11px] text-red-400 hover:text-red-600 transition-colors">削除</button>';
        html += '</div>';

        html += '</div></div>';
    });

    container.innerHTML = html;
}

function togglePrep(idx) { openPrepIds[idx] = !openPrepIds[idx]; renderPrepList(); }

// --- 結果タイプ選択 ---
var selectedResults = {};

function selectResult(idx, type) {
    selectedResults[idx] = type;
    // UIのハイライト
    var card = document.getElementById('resultForm_'+idx);
    if (card) card.style.display = 'block';

    var parent = card.parentElement;
    parent.querySelectorAll('.result-btn').forEach(function(btn) {
        btn.classList.remove('selected');
        if (btn.dataset.rtype === type) btn.classList.add('selected');
    });

    // 合意内容フィールドの表示切替
    var agreedField = document.getElementById('resultAgreedField_'+idx);
    agreedField.style.display = (type === 'agreed' || type === 'partial') ? 'block' : 'none';

    // 次回アプローチフィールドの表示切替
    var nextField = document.getElementById('resultNextField_'+idx);
    nextField.style.display = (type === 'partial' || type === 'continued') ? 'block' : 'none';
}

// --- 結果を保存 & 論点に反映 ---
function saveResult(idx) {
    var type = selectedResults[idx];
    if (!type) return;

    var preps = getPreps();
    var prep = preps[idx];

    var agreement = (document.getElementById('resultAgreement_'+idx) || {}).value || '';
    var learnings = (document.getElementById('resultLearnings_'+idx) || {}).value || '';
    var nextApproach = (document.getElementById('resultNext_'+idx) || {}).value || '';

    // 結果を保存
    prep.result = {
        type: type,
        date: LRL.todayStr(),
        agreement: agreement,
        learnings: learnings,
        next_approach: nextApproach
    };
    prep.status = 'completed_' + type;
    savePreps(preps);

    // 紐づく論点にアクション履歴を追加
    if (prep.issue_id) {
        var issues = LRL.getIssues();
        var issue = issues.find(function(i){ return i.id == prep.issue_id; });
        if (issue) {
            if (!issue.history) issue.history = [];
            var today = LRL.todayStr();

            // 会話結果を記録
            var resultText = type === 'agreed' ? '会話で合意: ' + (agreement || prep.topic)
                : type === 'partial' ? '一部合意: ' + (agreement || prep.topic)
                : '会話は継続に: ' + (learnings || prep.topic);
            issue.history.push({ date: today, type: type === 'agreed' ? 'agreed' : 'talked', text: resultText });

            // 学びがあれば記録
            if (learnings) {
                issue.history.push({ date: today, type: 'note', text: '気づき: ' + learnings });
            }

            // ステータス更新
            if (type === 'agreed') {
                issue.status = 'in_progress';
                issue.history.push({ date: today, type: 'status_changed', text: 'ステータスを「実行中」に変更' });
            } else if (type === 'partial') {
                issue.status = 'awaiting_agreement';
                issue.history.push({ date: today, type: 'status_changed', text: 'ステータスを「合意待ち」に変更' });
            }
            // continued の場合はステータスそのまま

            // last_action更新
            var latest = issue.history.slice().sort(function(a,b){ return b.date.localeCompare(a.date); })[0];
            issue.last_action_date = latest.date;
            issue.last_action = latest.text;

            LRL.saveIssue(issue);
        }
    }

    openPrepIds[idx] = true;
    renderAll();
}

function resetResult(idx) {
    var preps = getPreps();
    preps[idx].result = null;
    preps[idx].status = 'preparing';
    savePreps(preps);
    openPrepIds[idx] = true;
    renderPrepList();
}

function deletePrep(idx) {
    if (!confirm('この会話準備を削除しますか？')) return;
    var preps = getPreps();
    preps.splice(idx, 1);
    savePreps(preps);
    delete openPrepIds[idx];
    renderPrepList();
}

// --- 新規作成 ---
function showPrepModal() {
    populateIssueSelect();
    document.getElementById('npIssueId').value = '';
    var m = document.getElementById('newPrepModal'); m.style.display = 'flex';
}
function showPrepModalForIssue(issueId) {
    populateIssueSelect();
    document.getElementById('npIssueId').value = issueId;
    // テーマを自動入力
    var issue = LRL.getIssues().find(function(i){ return i.id === issueId; });
    if (issue) document.getElementById('npTopic').value = issue.title;
    var m = document.getElementById('newPrepModal'); m.style.display = 'flex';
}
function hidePrepModal() { document.getElementById('newPrepModal').style.display = 'none'; }

function populateIssueSelect() {
    var select = document.getElementById('npIssueId');
    var issues = LRL.getIssues().filter(function(i){ return i.status !== 'resolved'; });
    select.innerHTML = '<option value="">（紐づけなし）</option>';
    issues.forEach(function(issue) {
        select.innerHTML += '<option value="'+issue.id+'">'+esc(issue.title)+'</option>';
    });
}

function saveNewPrep() {
    var topic = document.getElementById('npTopic').value.trim();
    if (!topic) { document.getElementById('npTopic').focus(); return false; }

    var issueId = document.getElementById('npIssueId').value;

    var preps = getPreps();
    preps.push({
        id: Date.now(),
        issue_id: issueId ? parseInt(issueId) : null,
        topic: topic,
        purpose: document.getElementById('npPurpose').value,
        facts: document.getElementById('npFacts').value,
        emotions: document.getElementById('npEmotions').value,
        my_true_message: document.getElementById('npMessage').value,
        avoid_phrases: document.getElementById('npAvoid').value,
        partner_triggers: document.getElementById('npTriggers').value,
        desired_outcome: document.getElementById('npOutcome').value,
        scheduled_at: document.getElementById('npSchedule').value,
        result: null,
        status: 'preparing'
    });
    savePreps(preps);

    ['npTopic','npPurpose','npFacts','npEmotions','npMessage','npAvoid','npTriggers','npOutcome','npSchedule'].forEach(function(id) {
        document.getElementById(id).value = '';
    });
    document.getElementById('npIssueId').value = '';

    hidePrepModal();
    renderAll();
    return false;
}

// esc() はstore.jsでグローバル定義済み
function fmtDateTime(s) { if(!s)return''; var d=new Date(s); return(d.getMonth()+1)+'月'+d.getDate()+'日 '+String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0'); }

document.addEventListener('keydown', function(e) { if (e.key === 'Escape') hidePrepModal(); });

renderAll();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
