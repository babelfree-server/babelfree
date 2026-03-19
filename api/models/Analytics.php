<?php
/**
 * Analytics Model — Aggregated insights for El Viaje del Jaguar
 * Admin-only. All queries are read-only aggregations.
 */
class Analytics {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /** Overview: total users, active users, completion rates */
    public function getOverview(): array {
        $total = $this->pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
        $active7d = $this->pdo->query("SELECT COUNT(*) FROM users WHERE last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $active30d = $this->pdo->query("SELECT COUNT(*) FROM users WHERE last_login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
        $premium = $this->pdo->query("SELECT COUNT(*) FROM users WHERE tier = 'premium' AND (premium_expires_at IS NULL OR premium_expires_at > NOW())")->fetchColumn();

        $completedAny = $this->pdo->query("SELECT COUNT(DISTINCT user_id) FROM destination_progress WHERE is_complete = 1")->fetchColumn();
        $completedAll = $this->pdo->query("
            SELECT COUNT(*) FROM (
                SELECT user_id, COUNT(DISTINCT destination_id) AS cnt
                FROM destination_progress WHERE is_complete = 1
                GROUP BY user_id HAVING cnt >= 58
            ) t
        ")->fetchColumn();

        $newToday = $this->pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $newThisWeek = $this->pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

        return [
            'totalUsers' => (int)$total,
            'activeUsers7d' => (int)$active7d,
            'activeUsers30d' => (int)$active30d,
            'premiumUsers' => (int)$premium,
            'usersCompletedAny' => (int)$completedAny,
            'usersCompletedAll' => (int)$completedAll,
            'newToday' => (int)$newToday,
            'newThisWeek' => (int)$newThisWeek,
        ];
    }

    /** Per-destination: starts, completions, avg encounters completed */
    public function getDestinationStats(): array {
        $stmt = $this->pdo->query("
            SELECT
                destination_id,
                COUNT(*) AS starts,
                SUM(is_complete) AS completions,
                ROUND(AVG(completed_count), 1) AS avg_completed,
                ROUND(AVG(total_encounters), 1) AS avg_total,
                ROUND(SUM(is_complete) / COUNT(*) * 100, 1) AS completion_rate
            FROM destination_progress
            GROUP BY destination_id
            ORDER BY destination_id
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Drop-off funnel: how many users reached each destination */
    public function getFunnel(): array {
        $stmt = $this->pdo->query("
            SELECT
                CAST(REPLACE(destination_id, 'dest', '') AS UNSIGNED) AS dest_num,
                COUNT(DISTINCT user_id) AS users_reached
            FROM destination_progress
            GROUP BY dest_num
            ORDER BY dest_num
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Escape room stats per destination */
    public function getEscapeRoomStats(): array {
        $stmt = $this->pdo->query("
            SELECT
                destination_id,
                COUNT(*) AS attempts,
                SUM(is_complete) AS completions,
                ROUND(SUM(is_complete) / COUNT(*) * 100, 1) AS completion_rate
            FROM escape_room_progress
            GROUP BY destination_id
            ORDER BY destination_id
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Riddle quest (busqueda) aggregate stats */
    public function getBusquedaStats(): array {
        $total = $this->pdo->query("SELECT COUNT(*) FROM busqueda_progress")->fetchColumn();
        $avgBridge = $this->pdo->query("SELECT ROUND(AVG(bridge_segments), 1) FROM busqueda_progress")->fetchColumn();
        $named = $this->pdo->query("SELECT COUNT(*) FROM busqueda_progress WHERE rana_name IS NOT NULL AND rana_name != ''")->fetchColumn();
        $completed = $this->pdo->query("SELECT COUNT(*) FROM busqueda_progress WHERE bridge_segments >= 58")->fetchColumn();

        // Distribution of bridge_segments
        $dist = $this->pdo->query("
            SELECT
                CASE
                    WHEN bridge_segments = 0 THEN '0'
                    WHEN bridge_segments BETWEEN 1 AND 10 THEN '1-10'
                    WHEN bridge_segments BETWEEN 11 AND 20 THEN '11-20'
                    WHEN bridge_segments BETWEEN 21 AND 30 THEN '21-30'
                    WHEN bridge_segments BETWEEN 31 AND 40 THEN '31-40'
                    WHEN bridge_segments BETWEEN 41 AND 50 THEN '41-50'
                    WHEN bridge_segments BETWEEN 51 AND 57 THEN '51-57'
                    ELSE '58 (complete)'
                END AS bracket,
                COUNT(*) AS users
            FROM busqueda_progress
            GROUP BY bracket
            ORDER BY MIN(bridge_segments)
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'totalPlayers' => (int)$total,
            'avgBridgeSegments' => (float)$avgBridge,
            'ranasNamed' => (int)$named,
            'questCompleted' => (int)$completed,
            'distribution' => $dist,
        ];
    }

    /** Feedback overview */
    public function getFeedbackStats(): array {
        $stmt = $this->pdo->query("
            SELECT
                feedback_type,
                status,
                COUNT(*) AS count
            FROM feedback
            GROUP BY feedback_type, status
            ORDER BY feedback_type, status
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = $this->pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
        $unresolved = $this->pdo->query("SELECT COUNT(*) FROM feedback WHERE status IN ('new', 'reviewed')")->fetchColumn();

        return [
            'total' => (int)$total,
            'unresolved' => (int)$unresolved,
            'byTypeAndStatus' => $rows,
        ];
    }

    /** User registration over time (last 30 days) */
    public function getRegistrationTrend(): array {
        $stmt = $this->pdo->query("
            SELECT DATE(created_at) AS date, COUNT(*) AS signups
            FROM users
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Active sessions over time (last 30 days) */
    public function getActivityTrend(): array {
        $stmt = $this->pdo->query("
            SELECT DATE(last_login_at) AS date, COUNT(*) AS active_users
            FROM users
            WHERE last_login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(last_login_at)
            ORDER BY date
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** CEFR distribution of users (based on furthest destination completed) */
    public function getCefrDistribution(): array {
        $stmt = $this->pdo->query("
            SELECT
                CASE
                    WHEN max_dest <= 12 THEN 'A1'
                    WHEN max_dest <= 18 THEN 'A2'
                    WHEN max_dest <= 28 THEN 'B1'
                    WHEN max_dest <= 38 THEN 'B2'
                    WHEN max_dest <= 48 THEN 'C1'
                    ELSE 'C2'
                END AS cefr_level,
                COUNT(*) AS users
            FROM (
                SELECT user_id, MAX(CAST(REPLACE(destination_id, 'dest', '') AS UNSIGNED)) AS max_dest
                FROM destination_progress WHERE is_complete = 1
                GROUP BY user_id
            ) t
            GROUP BY cefr_level
            ORDER BY FIELD(cefr_level, 'A1', 'A2', 'B1', 'B2', 'C1', 'C2')
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Top struggle destinations (lowest completion rate with >= 5 attempts) */
    public function getStrugglePoints(): array {
        $stmt = $this->pdo->query("
            SELECT
                destination_id,
                COUNT(*) AS starts,
                SUM(is_complete) AS completions,
                ROUND(SUM(is_complete) / COUNT(*) * 100, 1) AS completion_rate,
                ROUND(AVG(completed_count / GREATEST(total_encounters, 1)) * 100, 1) AS avg_progress_pct
            FROM destination_progress
            GROUP BY destination_id
            HAVING starts >= 5
            ORDER BY completion_rate ASC
            LIMIT 10
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Native language distribution of users */
    public function getNativeLanguageDistribution(): array {
        $stmt = $this->pdo->query("
            SELECT native_lang, COUNT(*) AS users
            FROM users
            WHERE native_lang IS NOT NULL AND native_lang != ''
            GROUP BY native_lang
            ORDER BY users DESC
            LIMIT 20
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
