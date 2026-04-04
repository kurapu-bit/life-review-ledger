<?php
/**
 * API エンドポイント
 * フロントエンドからのデータ読み書きを処理
 */
date_default_timezone_set('Asia/Tokyo');

// セキュリティヘッダー
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// 認証チェック（ログインしていないユーザーはAPI利用不可）
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => '認証が必要です']);
    exit;
}

$db = getDB();
if (!$db) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'サービス一時利用不可']);
    exit;
}

// リクエスト解析
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// アクション名のバリデーション
if (!preg_match('/^[a-z_]+$/', $action)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '不正なリクエスト']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

// 認証済みユーザーIDを使用
$userId = currentUserId();

try {
    switch ($action) {

        // === テーブル初期化 ===
        case 'init_tables':
            $sql = file_get_contents(__DIR__ . '/../sql/schema.sql');
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                if ($stmt) $db->exec($stmt);
            }
            // デモユーザーがなければ作成
            $exists = $db->query("SELECT id FROM users WHERE id = 1")->fetch();
            if (!$exists) {
                $db->exec("INSERT INTO users (id, name, email, password_hash) VALUES (1, 'デモユーザー', 'demo@example.com', '-')");
            }
            respond(['ok' => true, 'message' => 'テーブル初期化完了']);
            break;

        // === 全データ取得（ページ読み込み時） ===
        case 'get_all':
            $issues = $db->prepare("SELECT * FROM issues WHERE user_id = ? ORDER BY urgency DESC");
            $issues->execute([$userId]);
            $issueRows = $issues->fetchAll();

            // 各論点に履歴を付与
            foreach ($issueRows as &$row) {
                $hist = $db->prepare("SELECT action_date as `date`, action_type as `type`, description as `text` FROM issue_history WHERE issue_id = ? ORDER BY action_date ASC, id ASC");
                $hist->execute([$row['id']]);
                $row['history'] = $hist->fetchAll();
                // 型変換
                $row['id'] = (int)$row['id'];
                $row['urgency'] = (int)$row['urgency'];
                $row['emotion_temp'] = (int)$row['emotion_temp'];
                $row['perception_gap'] = (int)$row['perception_gap'];
            }
            unset($row);

            $daily = $db->prepare("SELECT input_date as `date`, mood, had_conflict as conflict, had_avoidance as avoidance, had_good_talk as good_talk, postponed_issue_ids, tomorrow_action, memo FROM daily_inputs WHERE user_id = ? ORDER BY input_date ASC");
            $daily->execute([$userId]);
            $dailyRows = $daily->fetchAll();
            foreach ($dailyRows as &$d) {
                $d['mood'] = (int)$d['mood'];
                $d['conflict'] = (int)$d['conflict'];
                $d['avoidance'] = (int)$d['avoidance'];
                $d['good_talk'] = (int)$d['good_talk'];
                $d['postponed_ids'] = $d['postponed_issue_ids'] ? json_decode($d['postponed_issue_ids'], true) : [];
                unset($d['postponed_issue_ids']);
            }
            unset($d);

            $preps = $db->prepare("SELECT * FROM conversation_preps WHERE user_id = ? ORDER BY created_at DESC");
            $preps->execute([$userId]);
            $prepRows = $preps->fetchAll();
            foreach ($prepRows as &$p) {
                $p['id'] = (int)$p['id'];
                $p['issue_id'] = $p['issue_id'] ? (int)$p['issue_id'] : null;
                $p['result'] = $p['result_type'] ? [
                    'type' => $p['result_type'],
                    'agreement' => $p['result_agreement'],
                    'learnings' => $p['result_learnings'],
                    'next_approach' => $p['result_next_approach'],
                    'date' => $p['result_date']
                ] : null;
            }
            unset($p);

            $patterns = $db->prepare("SELECT id, side, description as text FROM manual_patterns WHERE user_id = ? ORDER BY created_at ASC");
            $patterns->execute([$userId]);
            $patternRows = $patterns->fetchAll();
            foreach ($patternRows as &$pt) { $pt['id'] = (int)$pt['id']; }
            unset($pt);

            respond([
                'ok' => true,
                'issues' => $issueRows,
                'dailyInputs' => $dailyRows,
                'preps' => $prepRows,
                'patterns' => $patternRows
            ]);
            break;

        // === LocalStorageから一括移行 ===
        case 'migrate':
            $db->beginTransaction();

            // 既存データをクリア（プリペアドステートメント使用）
            $db->prepare("DELETE FROM issue_history WHERE issue_id IN (SELECT id FROM issues WHERE user_id = ?)")->execute([$userId]);
            $db->prepare("DELETE FROM issues WHERE user_id = ?")->execute([$userId]);
            $db->prepare("DELETE FROM daily_inputs WHERE user_id = ?")->execute([$userId]);
            $db->prepare("DELETE FROM conversation_preps WHERE user_id = ?")->execute([$userId]);
            $db->prepare("DELETE FROM manual_patterns WHERE user_id = ?")->execute([$userId]);

            // Issues
            $idMap = []; // LocalStorage ID → DB ID
            if (!empty($body['issues'])) {
                $stmt = $db->prepare("INSERT INTO issues (user_id, title, area, urgency, emotion_temp, perception_gap, next_action, status, notes, last_action_date, last_action) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $histStmt = $db->prepare("INSERT INTO issue_history (issue_id, action_date, action_type, description) VALUES (?, ?, ?, ?)");

                foreach ($body['issues'] as $issue) {
                    $stmt->execute([
                        $userId, $issue['title'], $issue['area'] ?? 'couple',
                        $issue['urgency'] ?? 3, $issue['emotion_temp'] ?? 3, $issue['perception_gap'] ?? 3,
                        $issue['next_action'] ?? '', $issue['status'] ?? 'unworded',
                        $issue['notes'] ?? '', $issue['last_action_date'] ?? date('Y-m-d'),
                        $issue['last_action'] ?? ''
                    ]);
                    $newId = (int)$db->lastInsertId();
                    $idMap[$issue['id']] = $newId;

                    if (!empty($issue['history'])) {
                        foreach ($issue['history'] as $h) {
                            $histStmt->execute([$newId, $h['date'], $h['type'], $h['text']]);
                        }
                    }
                }
            }

            // Daily inputs
            if (!empty($body['dailyInputs'])) {
                $stmt = $db->prepare("INSERT INTO daily_inputs (user_id, input_date, mood, had_conflict, had_avoidance, had_good_talk, postponed_issue_ids, tomorrow_action, memo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE mood=VALUES(mood), had_conflict=VALUES(had_conflict), had_avoidance=VALUES(had_avoidance), had_good_talk=VALUES(had_good_talk), postponed_issue_ids=VALUES(postponed_issue_ids), tomorrow_action=VALUES(tomorrow_action), memo=VALUES(memo)");
                foreach ($body['dailyInputs'] as $d) {
                    // postponed_idsをDB用のIDにマッピング
                    $mappedIds = [];
                    if (!empty($d['postponed_ids'])) {
                        foreach ($d['postponed_ids'] as $oldId) {
                            if (isset($idMap[$oldId])) $mappedIds[] = $idMap[$oldId];
                        }
                    }
                    $stmt->execute([
                        $userId, $d['date'], $d['mood'] ?? 3,
                        $d['conflict'] ?? 0, $d['avoidance'] ?? 0, $d['good_talk'] ?? 0,
                        json_encode($mappedIds), $d['tomorrow_action'] ?? '', $d['memo'] ?? ''
                    ]);
                }
            }

            // Conversation preps
            if (!empty($body['preps'])) {
                $stmt = $db->prepare("INSERT INTO conversation_preps (user_id, issue_id, topic, purpose, facts, emotions, my_true_message, avoid_phrases, partner_triggers, desired_outcome, scheduled_at, status, result_type, result_agreement, result_learnings, result_next_approach, result_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($body['preps'] as $p) {
                    $mappedIssueId = isset($p['issue_id']) && isset($idMap[$p['issue_id']]) ? $idMap[$p['issue_id']] : null;
                    $result = $p['result'] ?? null;
                    $stmt->execute([
                        $userId, $mappedIssueId, $p['topic'] ?? '',
                        $p['purpose'] ?? '', $p['facts'] ?? '', $p['emotions'] ?? '',
                        $p['my_true_message'] ?? '', $p['avoid_phrases'] ?? '',
                        $p['partner_triggers'] ?? '', $p['desired_outcome'] ?? '',
                        $p['scheduled_at'] ? date('Y-m-d H:i:s', strtotime($p['scheduled_at'])) : null,
                        $p['status'] ?? 'preparing',
                        $result ? ($result['type'] ?? null) : null,
                        $result ? ($result['agreement'] ?? '') : null,
                        $result ? ($result['learnings'] ?? '') : null,
                        $result ? ($result['next_approach'] ?? '') : null,
                        $result ? ($result['date'] ?? null) : null
                    ]);
                }
            }

            // Manual patterns
            if (!empty($body['patterns'])) {
                $stmt = $db->prepare("INSERT INTO manual_patterns (user_id, side, description) VALUES (?, ?, ?)");
                foreach ($body['patterns'] as $p) {
                    $stmt->execute([$userId, $p['side'] ?? 'negative', $p['text'] ?? '']);
                }
            }

            $db->commit();
            respond(['ok' => true, 'message' => '移行完了', 'idMap' => $idMap]);
            break;

        // === 日次入力保存 ===
        case 'save_daily':
            $d = $body;
            $stmt = $db->prepare("INSERT INTO daily_inputs (user_id, input_date, mood, had_conflict, had_avoidance, had_good_talk, postponed_issue_ids, tomorrow_action, memo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE mood=VALUES(mood), had_conflict=VALUES(had_conflict), had_avoidance=VALUES(had_avoidance), had_good_talk=VALUES(had_good_talk), postponed_issue_ids=VALUES(postponed_issue_ids), tomorrow_action=VALUES(tomorrow_action), memo=VALUES(memo)");
            $stmt->execute([
                $userId, $d['date'], $d['mood'] ?? 3,
                $d['conflict'] ?? 0, $d['avoidance'] ?? 0, $d['good_talk'] ?? 0,
                json_encode($d['postponed_ids'] ?? []), $d['tomorrow_action'] ?? '', $d['memo'] ?? ''
            ]);
            respond(['ok' => true]);
            break;

        // === 論点保存（新規/更新） ===
        case 'save_issue':
            $i = $body;
            if (!empty($i['id'])) {
                $stmt = $db->prepare("UPDATE issues SET title=?, area=?, urgency=?, emotion_temp=?, perception_gap=?, next_action=?, status=?, notes=?, last_action_date=?, last_action=? WHERE id=? AND user_id=?");
                $stmt->execute([$i['title'], $i['area'], $i['urgency'], $i['emotion_temp'], $i['perception_gap'], $i['next_action'] ?? '', $i['status'], $i['notes'] ?? '', $i['last_action_date'] ?? date('Y-m-d'), $i['last_action'] ?? '', $i['id'], $userId]);
                $issueId = (int)$i['id'];
            } else {
                $stmt = $db->prepare("INSERT INTO issues (user_id, title, area, urgency, emotion_temp, perception_gap, next_action, status, notes, last_action_date, last_action) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $i['title'], $i['area'] ?? 'couple', $i['urgency'] ?? 3, $i['emotion_temp'] ?? 3, $i['perception_gap'] ?? 3, $i['next_action'] ?? '', $i['status'] ?? 'unworded', $i['notes'] ?? '', date('Y-m-d'), '論点として登録']);
                $issueId = (int)$db->lastInsertId();
            }

            // 履歴を同期（全削除→再挿入）
            if (isset($i['history'])) {
                $db->prepare("DELETE FROM issue_history WHERE issue_id = ?")->execute([$issueId]);
                $histStmt = $db->prepare("INSERT INTO issue_history (issue_id, action_date, action_type, description) VALUES (?, ?, ?, ?)");
                foreach ($i['history'] as $h) {
                    $histStmt->execute([$issueId, $h['date'], $h['type'], $h['text']]);
                }
            }
            respond(['ok' => true, 'id' => $issueId]);
            break;

        // === 会話準備保存 ===
        case 'save_prep':
            $p = $body;
            $result = $p['result'] ?? null;
            if (!empty($p['db_id'])) {
                $stmt = $db->prepare("UPDATE conversation_preps SET issue_id=?, topic=?, purpose=?, facts=?, emotions=?, my_true_message=?, avoid_phrases=?, partner_triggers=?, desired_outcome=?, scheduled_at=?, status=?, result_type=?, result_agreement=?, result_learnings=?, result_next_approach=?, result_date=? WHERE id=? AND user_id=?");
                $stmt->execute([
                    $p['issue_id'] ?: null, $p['topic'], $p['purpose'] ?? '', $p['facts'] ?? '', $p['emotions'] ?? '',
                    $p['my_true_message'] ?? '', $p['avoid_phrases'] ?? '', $p['partner_triggers'] ?? '',
                    $p['desired_outcome'] ?? '', $p['scheduled_at'] ?: null, $p['status'] ?? 'preparing',
                    $result ? ($result['type'] ?? null) : null, $result ? ($result['agreement'] ?? '') : null,
                    $result ? ($result['learnings'] ?? '') : null, $result ? ($result['next_approach'] ?? '') : null,
                    $result ? ($result['date'] ?? null) : null, $p['db_id'], $userId
                ]);
                respond(['ok' => true, 'id' => (int)$p['db_id']]);
            } else {
                $stmt = $db->prepare("INSERT INTO conversation_preps (user_id, issue_id, topic, purpose, facts, emotions, my_true_message, avoid_phrases, partner_triggers, desired_outcome, scheduled_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $userId, $p['issue_id'] ?: null, $p['topic'], $p['purpose'] ?? '', $p['facts'] ?? '', $p['emotions'] ?? '',
                    $p['my_true_message'] ?? '', $p['avoid_phrases'] ?? '', $p['partner_triggers'] ?? '',
                    $p['desired_outcome'] ?? '', $p['scheduled_at'] ?: null, 'preparing'
                ]);
                respond(['ok' => true, 'id' => (int)$db->lastInsertId()]);
            }
            break;

        // === 手動パターン保存/削除 ===
        case 'save_pattern':
            $stmt = $db->prepare("INSERT INTO manual_patterns (user_id, side, description) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $body['side'], $body['text']]);
            respond(['ok' => true, 'id' => (int)$db->lastInsertId()]);
            break;

        case 'delete_pattern':
            $db->prepare("DELETE FROM manual_patterns WHERE id = ? AND user_id = ?")->execute([$body['id'], $userId]);
            respond(['ok' => true]);
            break;

        case 'delete_prep':
            $db->prepare("DELETE FROM conversation_preps WHERE id = ? AND user_id = ?")->execute([$body['id'], $userId]);
            respond(['ok' => true]);
            break;

        default:
            respond(['ok' => false, 'error' => '不明なアクション'], 400);
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    // エラー詳細はサーバーログに記録、クライアントには汎用メッセージ
    error_log('LRL API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    respond(['ok' => false, 'error' => '処理中にエラーが発生しました'], 500);
}

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
