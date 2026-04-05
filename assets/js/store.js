/**
 * Life Review Ledger - LocalStorage データストア
 * 全画面共通のデータ保存/読み込み基盤
 */
var LRL = (function() {
    // URLパス + ユーザーIDからプレフィックスを生成（ユーザーごとにデータ分離）
    var pathPrefix = (function() {
        var path = window.location.pathname.split('/').filter(function(s){ return s; })[0] || 'default';
        var userId = (typeof LRL_USER_ID !== 'undefined') ? LRL_USER_ID : 0;
        return 'lrl_' + path + '_u' + userId + '_';
    })();

    var KEYS = {
        dailyInputs: pathPrefix + 'daily_inputs',
        issues: pathPrefix + 'issues',
        initialized: pathPrefix + 'initialized'
    };

    // --- 汎用 ---
    function getJSON(key, fallback) {
        try { return JSON.parse(localStorage.getItem(key)) || fallback; }
        catch(e) { return fallback; }
    }
    function setJSON(key, val) {
        localStorage.setItem(key, JSON.stringify(val));
    }

    // --- 日次入力 ---
    function getDailyInputs() {
        return getJSON(KEYS.dailyInputs, []);
    }
    function saveDailyInput(input) {
        // input: { date, mood, conflict, avoidance, good_talk, postponed_ids[], tomorrow_action, memo }
        var inputs = getDailyInputs();
        // 同じ日付があれば上書き
        var idx = inputs.findIndex(function(d) { return d.date === input.date; });
        if (idx > -1) { inputs[idx] = input; }
        else { inputs.push(input); }
        // 日付順にソート
        inputs.sort(function(a, b) { return a.date.localeCompare(b.date); });
        setJSON(KEYS.dailyInputs, inputs);
        return input;
    }
    function getDailyInputsForWeek(mondayStr, sundayStr) {
        return getDailyInputs().filter(function(d) {
            return d.date >= mondayStr && d.date <= sundayStr;
        });
    }
    function getTodayInput() {
        var today = todayStr();
        return getDailyInputs().find(function(d) { return d.date === today; }) || null;
    }

    // --- 論点（Issues） ---
    function getIssues() {
        return getJSON(KEYS.issues, []);
    }
    function saveIssue(issue) {
        var issues = getIssues();
        var idx = issues.findIndex(function(i) { return i.id === issue.id; });
        if (idx > -1) { issues[idx] = issue; }
        else {
            issue.id = issues.length > 0 ? Math.max.apply(null, issues.map(function(i){return i.id;})) + 1 : 1;
            issues.push(issue);
        }
        setJSON(KEYS.issues, issues);
        return issue;
    }
    function addHistoryToIssue(issueId, historyItem) {
        var issues = getIssues();
        var issue = issues.find(function(i) { return i.id === issueId; });
        if (!issue) return;
        if (!issue.history) issue.history = [];
        issue.history.push(historyItem);
        issue.last_action_date = historyItem.date;
        issue.last_action = historyItem.text;
        setJSON(KEYS.issues, issues);
    }

    // --- 先送り記録の自動反映 ---
    function logPostponedIssues(issueIds, dateStr) {
        if (!issueIds || issueIds.length === 0) return;
        var issues = getIssues();
        issueIds.forEach(function(id) {
            var issue = issues.find(function(i) { return i.id === id; });
            if (!issue) return;
            if (!issue.history) issue.history = [];
            // 同じ日に同じ先送り記録がなければ追加
            var alreadyLogged = issue.history.some(function(h) {
                return h.date === dateStr && h.type === 'postponed' && h.text.indexOf('日次振り返り') > -1;
            });
            if (!alreadyLogged) {
                issue.history.push({
                    date: dateStr,
                    type: 'postponed',
                    text: '日次振り返りで「今日向き合えなかった」と記録'
                });
            }
        });
        setJSON(KEYS.issues, issues);
    }

    // --- 集計 ---
    function getWeekStats(mondayStr, sundayStr) {
        var inputs = getDailyInputsForWeek(mondayStr, sundayStr);
        var moods = inputs.map(function(d){ return d.mood; }).filter(function(m){ return m > 0; });
        return {
            inputCount: inputs.length,
            avgMood: moods.length > 0 ? Math.round(moods.reduce(function(a,b){return a+b;},0) / moods.length * 10) / 10 : 0,
            conflictDays: inputs.filter(function(d){ return d.conflict == 1; }).length,
            avoidanceDays: inputs.filter(function(d){ return d.avoidance == 1; }).length,
            goodTalkDays: inputs.filter(function(d){ return d.good_talk == 1; }).length,
            inputs: inputs
        };
    }
    function getCollapseRisk(stats) {
        if (stats.avoidanceDays >= 3 || stats.conflictDays >= 2) return 'high';
        if (stats.avoidanceDays >= 2 || stats.conflictDays >= 1) return 'medium';
        return 'low';
    }

    // --- ユーティリティ（日本時間基準） ---
    function toJST(d) {
        // ブラウザのタイムゾーンに関わらず日本時間を取得
        return new Date(d.toLocaleString('en-US', { timeZone: 'Asia/Tokyo' }));
    }
    function fmtDateStr(d) {
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    }
    function todayStr() {
        return fmtDateStr(toJST(new Date()));
    }
    function getMondayStr() {
        var d = toJST(new Date());
        var day = d.getDay();
        var diff = d.getDate() - day + (day === 0 ? -6 : 1);
        d.setDate(diff);
        return fmtDateStr(d);
    }
    function getSundayStr() {
        var mon = new Date(getMondayStr());
        mon.setDate(mon.getDate() + 6);
        return fmtDateStr(mon);
    }

    // --- 初期デモデータ投入（test1ユーザーのみ） ---
    function initDemoData(phpIssues, phpDailyInputs) {
        var userId = (typeof LRL_USER_ID !== 'undefined') ? LRL_USER_ID : 0;
        // デモデータはtest1（ID:1）のみに投入
        if (userId !== 1) return;
        if (localStorage.getItem(KEYS.initialized)) return;
        setJSON(KEYS.issues, phpIssues);
        setJSON(KEYS.dailyInputs, phpDailyInputs);
        localStorage.setItem(KEYS.initialized, '1');
    }
    function resetData() {
        localStorage.removeItem(KEYS.dailyInputs);
        localStorage.removeItem(KEYS.issues);
        localStorage.removeItem(KEYS.initialized);
        localStorage.removeItem(pathPrefix + 'preps_initialized');
        localStorage.removeItem(pathPrefix + 'conversation_preps');
        localStorage.removeItem(pathPrefix + 'patterns');
    }

    // プレフィックスを外部に公開（会話準備・パターン画面で使う）
    function getPrefix() { return pathPrefix; }

    // --- パターン自動抽出 ---
    function analyzePatterns() {
        var inputs = getDailyInputs();
        var issues = getIssues();
        var patterns = { negative: [], positive: [] };

        if (inputs.length < 5) return patterns;

        var dayNames = ['日','月','火','水','木','金','土'];
        var usedDows = { negative: {}, positive: {} }; // 同じ曜日の重複防止

        // --- 崩れ条件 ---

        // 1. 衝突+逃避が多い最悪の曜日（1つだけ）
        var badDow = {};
        inputs.forEach(function(d) {
            if (d.conflict == 1 || d.avoidance == 1) {
                var dow = new Date(d.date).getDay();
                badDow[dow] = (badDow[dow] || 0) + 1;
            }
        });
        var worstDay = Object.keys(badDow).sort(function(a,b){ return badDow[b]-badDow[a]; })[0];
        if (worstDay !== undefined && badDow[worstDay] >= 3) {
            var memos = inputs.filter(function(d) {
                return new Date(d.date).getDay() == worstDay && (d.conflict == 1 || d.avoidance == 1) && d.memo;
            }).map(function(d){ return d.memo; });
            patterns.negative.push({
                type: 'collapse_trigger',
                text: dayNames[worstDay] + '曜日に崩れやすい（衝突・逃避が計' + badDow[worstDay] + '回）',
                evidence: memos.slice(0, 1),
                confidence: Math.min(badDow[worstDay] / 6, 1),
                count: badDow[worstDay]
            });
            usedDows.negative[worstDay] = true;
        }

        // 2. 衝突→翌日逃避のパターン
        var conflictThenAvoid = 0;
        for (var i = 1; i < inputs.length; i++) {
            if (inputs[i-1].conflict == 1 && inputs[i].avoidance == 1) conflictThenAvoid++;
        }
        if (conflictThenAvoid >= 2) {
            patterns.negative.push({
                type: 'avoidance_trigger',
                text: '衝突の翌日に逃避しやすい（' + conflictThenAvoid + '回）',
                evidence: ['衝突後に謝れず先送りするパターンが繰り返されています'],
                confidence: Math.min(conflictThenAvoid / 4, 1),
                count: conflictThenAvoid
            });
        }

        // 3. メモからキーワード抽出（最も多いもの1〜2個）
        var negKws = [
            { word: '疲', label: '疲労が溜まった日' },
            { word: '飲み会', label: '飲み会の前後' },
            { word: '帰宅遅', label: '帰宅が遅い日' },
            { word: '帰りが遅', label: '帰宅が遅い日' },
        ];
        var kwResults = [];
        negKws.forEach(function(kw) {
            var hits = inputs.filter(function(d) {
                return d.memo && d.memo.indexOf(kw.word) > -1 && (d.conflict == 1 || d.avoidance == 1 || d.mood <= 2);
            });
            if (hits.length >= 2) kwResults.push({ label: kw.label, count: hits.length, memos: hits.map(function(d){return d.memo;}) });
        });
        // 帰宅遅い系は統合
        var merged = {};
        kwResults.forEach(function(r) {
            if (merged[r.label]) { merged[r.label].count = Math.max(merged[r.label].count, r.count); }
            else { merged[r.label] = r; }
        });
        Object.values(merged).sort(function(a,b){return b.count-a.count;}).slice(0, 2).forEach(function(r) {
            patterns.negative.push({
                type: 'collapse_trigger',
                text: r.label + 'に崩れやすい（' + r.count + '回）',
                evidence: r.memos.slice(0, 1),
                confidence: Math.min(r.count / 5, 1),
                count: r.count
            });
        });

        // 4. 先送りが多い論点（上位1つ）
        var postponeCount = {};
        inputs.forEach(function(d) {
            if (d.postponed_ids && d.postponed_ids.length > 0) {
                d.postponed_ids.forEach(function(id) { postponeCount[id] = (postponeCount[id] || 0) + 1; });
            }
        });
        var topPostponed = Object.keys(postponeCount).sort(function(a,b){ return postponeCount[b]-postponeCount[a]; })[0];
        if (topPostponed && postponeCount[topPostponed] >= 2) {
            var pIssue = issues.find(function(i){ return i.id == topPostponed; });
            patterns.negative.push({
                type: 'postpone_pattern',
                text: '「' + (pIssue ? pIssue.title : '論点') + '」を繰り返し先送り（' + postponeCount[topPostponed] + '回）',
                evidence: ['この論点に向き合うタイミングを意識的に設ける必要があります'],
                confidence: Math.min(postponeCount[topPostponed] / 5, 1),
                count: postponeCount[topPostponed]
            });
        }

        // --- うまくいく条件 ---

        // 5. 気分が良い最高の曜日（1つだけ）
        var moodByDow = {};
        inputs.forEach(function(d) {
            var dow = new Date(d.date).getDay();
            if (!moodByDow[dow]) moodByDow[dow] = [];
            moodByDow[dow].push(d.mood);
        });
        var bestDay = null, bestAvg = 0;
        Object.keys(moodByDow).forEach(function(dow) {
            if (moodByDow[dow].length < 2) return;
            var avg = moodByDow[dow].reduce(function(a,b){return a+b;},0) / moodByDow[dow].length;
            if (avg > bestAvg) { bestAvg = avg; bestDay = dow; }
        });
        if (bestDay !== null && bestAvg >= 3.5) {
            var goodMemos = inputs.filter(function(d) {
                return new Date(d.date).getDay() == bestDay && d.mood >= 4 && d.memo;
            }).map(function(d){ return d.memo; });
            patterns.positive.push({
                type: 'success_condition',
                text: dayNames[bestDay] + '曜日が最もうまくいきやすい（気分平均 ' + bestAvg.toFixed(1) + '）',
                evidence: goodMemos.slice(0, 1),
                confidence: Math.min(bestAvg / 5, 1),
                count: moodByDow[bestDay].length
            });
        }

        // 6. メモからポジティブキーワード（閾値を2回以上に）
        var posKws = [
            { word: '散歩', label: '散歩・外出した日' },
            { word: '午前', label: '午前中に対話した時' },
            { word: '3人', label: '家族3人で過ごした時' },
            { word: 'ゆっくり', label: 'ゆっくり過ごせた日' },
            { word: '謝', label: '先に自分から謝った時' },
            { word: '1テーマ', label: '1テーマに絞った対話' },
            { word: '自分の非', label: '先に自分の非を整理した時' },
        ];
        var posResults = [];
        posKws.forEach(function(kw) {
            var hits = inputs.filter(function(d) {
                return d.memo && d.memo.indexOf(kw.word) > -1 && d.mood >= 3;
            });
            if (hits.length >= 2) posResults.push({ label: kw.label, count: hits.length, memos: hits.map(function(d){return d.memo;}) });
        });
        posResults.sort(function(a,b){return b.count-a.count;}).slice(0, 3).forEach(function(r) {
            patterns.positive.push({
                type: 'success_condition',
                text: r.label + 'にうまくいく（' + r.count + '回）',
                evidence: r.memos.slice(0, 1),
                confidence: Math.min(r.count / 4, 1),
                count: r.count
            });
        });

        // 7. 衝突→回復パターン
        var recoveries = [];
        for (var j = 0; j < inputs.length; j++) {
            if (inputs[j].conflict == 1) {
                for (var k = j+1; k < Math.min(j+5, inputs.length); k++) {
                    if (inputs[k].mood >= 3 && !inputs[k].conflict) { recoveries.push(k - j); break; }
                }
            }
        }
        if (recoveries.length >= 2) {
            var avgRec = Math.round(recoveries.reduce(function(a,b){return a+b;},0) / recoveries.length * 10) / 10;
            patterns.positive.push({
                type: 'prevention_rule',
                text: '衝突後、平均 ' + avgRec + ' 日で回復する',
                evidence: [Math.ceil(avgRec) + '日以内に切り出せれば関係は持ち直す傾向'],
                confidence: Math.min(recoveries.length / 3, 1),
                count: recoveries.length
            });
        }

        // confidence順にソート
        patterns.negative.sort(function(a,b){ return b.confidence - a.confidence; });
        patterns.positive.sort(function(a,b){ return b.confidence - a.confidence; });

        return patterns;
    }

    // --- 手動パターン ---
    function getManualPatterns() {
        return getJSON(pathPrefix + 'patterns', []);
    }
    function saveManualPattern(pattern) {
        var patterns = getManualPatterns();
        pattern.id = Date.now();
        patterns.push(pattern);
        setJSON(pathPrefix + 'patterns', patterns);
        return pattern;
    }
    function deleteManualPattern(id) {
        var patterns = getManualPatterns().filter(function(p){ return p.id !== id; });
        setJSON(pathPrefix + 'patterns', patterns);
    }

    return {
        getDailyInputs: getDailyInputs,
        saveDailyInput: saveDailyInput,
        getDailyInputsForWeek: getDailyInputsForWeek,
        getTodayInput: getTodayInput,
        getIssues: getIssues,
        saveIssue: saveIssue,
        addHistoryToIssue: addHistoryToIssue,
        logPostponedIssues: logPostponedIssues,
        getWeekStats: getWeekStats,
        getCollapseRisk: getCollapseRisk,
        todayStr: todayStr,
        getMondayStr: getMondayStr,
        getSundayStr: getSundayStr,
        analyzePatterns: analyzePatterns,
        getManualPatterns: getManualPatterns,
        saveManualPattern: saveManualPattern,
        deleteManualPattern: deleteManualPattern,
        getPrefix: getPrefix,
        initDemoData: initDemoData,
        resetData: resetData,

        // --- API同期 ---
        api: {
            // APIのベースパス（自動検出）
            basePath: (function() {
                var path = window.location.pathname.split('/').filter(function(s){ return s; })[0] || '';
                return '/' + path + '/api/index.php';
            })(),

            // API呼び出し
            call: function(action, body) {
                return fetch(LRL.api.basePath + '?action=' + action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: body ? JSON.stringify(body) : null
                }).then(function(r) { return r.json(); });
            },

            // DB利用可能か確認
            check: function() {
                return fetch(LRL.api.basePath + '?action=get_all')
                    .then(function(r) { return r.json(); })
                    .then(function(data) { return data.ok === true; })
                    .catch(function() { return false; });
            },

            // テーブル初期化
            initTables: function() {
                return LRL.api.call('init_tables');
            },

            // LocalStorage → DB に全データ移行
            migrate: function() {
                var prefix = getPrefix();
                var preps = [];
                try { preps = JSON.parse(localStorage.getItem(prefix + 'conversation_preps')) || []; } catch(e) {}
                var patterns = getManualPatterns();

                var payload = {
                    issues: getIssues(),
                    dailyInputs: getDailyInputs(),
                    preps: preps,
                    patterns: patterns
                };

                return LRL.api.call('migrate', payload).then(function(result) {
                    if (result.ok) {
                        console.log('DB移行完了:', result.message);
                    }
                    return result;
                });
            },

            // DBからデータを取得してLocalStorageに反映
            sync: function() {
                return LRL.api.call('get_all').then(function(data) {
                    if (!data.ok) return data;
                    // LocalStorageに反映（キャッシュとして）
                    setJSON(KEYS.issues, data.issues);
                    setJSON(KEYS.dailyInputs, data.dailyInputs);
                    var prefix = getPrefix();
                    localStorage.setItem(prefix + 'conversation_preps', JSON.stringify(data.preps));
                    // patternsは手動パターンのみ
                    localStorage.setItem(prefix + 'patterns', JSON.stringify(data.patterns));
                    console.log('DB同期完了');
                    return data;
                });
            },

            // 日次入力をDBにも保存
            saveDaily: function(input) {
                return LRL.api.call('save_daily', input);
            },

            // 論点をDBにも保存
            saveIssue: function(issue) {
                return LRL.api.call('save_issue', issue);
            },

            // 会話準備をDBにも保存
            savePrep: function(prep) {
                return LRL.api.call('save_prep', prep);
            },

            // パターンをDBにも保存
            savePattern: function(pattern) {
                return LRL.api.call('save_pattern', pattern);
            },

            deletePattern: function(id) {
                return LRL.api.call('delete_pattern', { id: id });
            },

            deletePrep: function(id) {
                return LRL.api.call('delete_prep', { id: id });
            }
        }
    };
})();

// グローバルXSSエスケープ関数（全ページで使用）
function esc(s) {
    if (!s) return '';
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
function escAttr(s) {
    if (!s) return '';
    return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/'/g,'&#39;');
}
