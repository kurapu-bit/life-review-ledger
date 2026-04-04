-- Life Review Ledger - Database Schema
-- さくらサーバー MySQL 8.0 互換
-- ※さくらサーバーではCREATE DATABASEは不要（コンパネで作成済み）

-- ユーザー
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 未処理論点
CREATE TABLE IF NOT EXISTS issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    area VARCHAR(30) NOT NULL DEFAULT 'couple',
    urgency TINYINT NOT NULL DEFAULT 3,
    emotion_temp TINYINT NOT NULL DEFAULT 3,
    perception_gap TINYINT NOT NULL DEFAULT 3,
    next_action TEXT,
    status VARCHAR(30) NOT NULL DEFAULT 'unworded',
    notes TEXT,
    last_action_date DATE,
    last_action TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 論点のアクション履歴
CREATE TABLE IF NOT EXISTS issue_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    action_date DATE NOT NULL,
    action_type VARCHAR(30) NOT NULL,
    description TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 日次入力
CREATE TABLE IF NOT EXISTS daily_inputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    input_date DATE NOT NULL,
    mood TINYINT NOT NULL DEFAULT 3,
    had_conflict TINYINT(1) DEFAULT 0,
    had_avoidance TINYINT(1) DEFAULT 0,
    had_good_talk TINYINT(1) DEFAULT 0,
    postponed_issue_ids TEXT,
    tomorrow_action TEXT,
    memo TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, input_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 夫婦会話準備
CREATE TABLE IF NOT EXISTS conversation_preps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    issue_id INT,
    topic VARCHAR(255) NOT NULL,
    purpose TEXT,
    facts TEXT,
    emotions TEXT,
    my_true_message TEXT,
    avoid_phrases TEXT,
    partner_triggers TEXT,
    desired_outcome TEXT,
    scheduled_at DATETIME,
    status VARCHAR(30) DEFAULT 'preparing',
    result_type VARCHAR(30),
    result_agreement TEXT,
    result_learnings TEXT,
    result_next_approach TEXT,
    result_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 手動パターン
CREATE TABLE IF NOT EXISTS manual_patterns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    side VARCHAR(10) NOT NULL,
    description TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
