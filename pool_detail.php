<?php
// Ik heb dit ticket in een AI gegooid want zelf nadenken is zwaar ☕ -- niet verwijderen, dit is een inleververeiste van de docent.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();
$user = currentUser();

$pool_id = (int)($_GET['id'] ?? 0);
if ($pool_id <= 0) {
    header('Location: pools.php');
    exit;
}

// Haal de poule op + check of gebruiker lid is
$pool = null;
$members = [];
$data = [];
$matchList = [];

try {
    // Poule + check of user lid is
    $stmt = $pdo->prepare("
        SELECT p.*, u.name AS creator_name
        FROM pools p
        INNER JOIN users u ON u.id = p.created_by
        INNER JOIN pool_members pm ON pm.pool_id = p.id AND pm.user_id = ?
        WHERE p.id = ?
    ");
    $stmt->execute([$user['id'], $pool_id]);
    $pool = $stmt->fetch();

    if (!$pool) {
        header('Location: pools.php');
        exit;
    }

    // Leden ophalen
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, pm.joined_at
        FROM pool_members pm
        INNER JOIN users u ON u.id = pm.user_id
        WHERE pm.pool_id = ?
        ORDER BY pm.joined_at ASC
    ");
    $stmt->execute([$pool_id]);
    $members = $stmt->fetchAll();

    // Voorspellingen van alle leden per wedstrijd ($data[match_id][user_id])
    $data = [];
    $poolMatches = [];
    // Voorspellingen van alle leden per wedstrijd
    $stmt = $pdo->prepare("
        SELECT
            m.id AS match_id,
            m.home_team,
            m.away_team,
            m.match_date,
            m.stage,
            u.id AS user_id,
            u.name AS user_name,
            pr.predicted_home,
            pr.predicted_away
        FROM matches m
        INNER JOIN pool_members pm ON pm.pool_id = ?
        INNER JOIN users u ON u.id = pm.user_id
        LEFT JOIN predictions pr ON pr.match_id = m.id AND pr.user_id = u.id
        ORDER BY m.match_date ASC, pm.joined_at ASC
    ");
    $stmt->execute([$pool_id]);
    foreach ($stmt->fetchAll() as $row) {
        $matchId = (int)$row['match_id'];
        $userId = (int)$row['user_id'];

        if (!isset($poolMatches[$matchId])) {
            $poolMatches[$matchId] = [
                'id' => $matchId,
                'home_team' => $row['home_team'],
                'away_team' => $row['away_team'],
                'match_date' => $row['match_date'],
                'stage' => $row['stage'],
            ];
        }

        $data[$matchId][$userId] = [
            'name' => $row['user_name'],
            'predicted_home' => $row['predicted_home'],
            'predicted_away' => $row['predicted_away'],
        ];
    }
} catch (PDOException $e) {
    die('Fout bij ophalen van poule: ' . htmlspecialchars($e->getMessage()));
}

$pageTitle = $pool['name'];
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div style="margin-bottom: 24px;">
        <a href="pools.php" class="nav-link" style="padding-left: 0;">← Terug naar poules</a>
    </div>

    <div class="pool-hero">
        <div class="feature-number">POULE</div>
        <h1 class="pool-hero-title"><?= htmlspecialchars($pool['name']) ?></h1>
        <?php if ($pool['description']): ?>
            <p style="color: var(--text-dim); font-size: 16px; max-width: 640px;">
                <?= nl2br(htmlspecialchars($pool['description'])) ?>
            </p>
        <?php endif; ?>
        <div class="pool-hero-code">
            <span class="pool-hero-code-label">Toegangscode:</span>
            <strong><?= htmlspecialchars($pool['access_code']) ?></strong>
        </div>
    </div>

    <div class="dash-grid">
        <!-- Deelnemers -->
        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Deelnemers</h2>
                    <p class="card-subtitle"><?= count($members) ?> LEDEN</p>
                </div>
            </div>

            <div class="member-list">
                <?php foreach ($members as $member): ?>
                    <div class="member">
                        <div class="member-avatar">
                            <?= strtoupper(substr(htmlspecialchars($member['name']), 0, 1)) ?>
                        </div>
                        <div class="member-info">
                            <div class="member-name"><?= htmlspecialchars($member['name']) ?></div>
                            <div class="member-email"><?= htmlspecialchars($member['email']) ?></div>
                        </div>
                        <?php if ((int)$member['id'] === (int)$pool['created_by']): ?>
                            <span class="member-badge">Beheerder</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Sidebar -->
        <aside class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Acties</h2>
                    <p class="card-subtitle">BEHEER</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="predictions.php" class="btn btn-primary btn-block">⚽ Voorspellen</a>
                <div style="padding: 16px; background: var(--bg-deep); border: 1px dashed var(--border-hi); border-radius: var(--radius-sm);">
                    <div style="font-family: var(--font-mono); font-size: 11px; color: var(--text-mute); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px;">
                        Deel deze code met vrienden
                    </div>
                    <div style="font-family: var(--font-display); font-size: 24px; color: var(--field); letter-spacing: 0.1em;">
                        <?= htmlspecialchars($pool['access_code']) ?>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <section class="card" style="margin-top: 24px;">
        <div class="card-header">
            <div>
                <h2 class="card-title">Voorspellingen van leden</h2>
                <p class="card-subtitle">PER WEDSTRIJD</p>
            </div>
        </div>

        <?php if (empty($poolMatches)): ?>
            <div class="empty">
                <div class="empty-icon">📅</div>
                <h2 class="empty-title">Nog geen wedstrijden</h2>
                <p class="empty-text">Zodra er wedstrijden zijn, zie je hier de voorspellingen van alle leden.</p>
            </div>
        <?php else: ?>
            <div class="match-list">
                <?php foreach ($poolMatches as $match):
                    $matchId = (int)$match['id'];
        <?php if (empty($matchList)): ?>
            <div class="empty" style="padding: 40px 24px;">
                <div class="empty-icon">📅</div>
                <h3 class="empty-title">Nog geen wedstrijden</h3>
                <p class="empty-text">Zodra wedstrijden zijn ingepland, zie je hier de voorspellingen van alle leden.</p>
            </div>
        <?php else: ?>
            <div class="match-list">
                <?php foreach ($matchList as $matchId => $match):
                    $date = new DateTime($match['match_date']);
                ?>
                    <div class="match">
                        <div class="match-meta">
                            <span class="match-stage"><?= htmlspecialchars($match['stage']) ?></span>
                            <span><?= $date->format('d M Y · H:i') ?></span>
                        </div>

                        <div class="match-row" style="margin-bottom: 20px;">
                            <div class="team team-home">
                                <span class="team-name"><?= htmlspecialchars($match['home_team']) ?></span>
                            </div>
                            <span class="score-sep">vs</span>
                            <div class="team">
                                <span class="team-name"><?= htmlspecialchars($match['away_team']) ?></span>
                            </div>
                        </div>

                        <div class="member-list">
                            <?php foreach ($members as $member):
                                $userId = (int)$member['id'];
                                $pred = $data[$matchId][$userId] ?? null;
                                $hasPrediction = $pred !== null
                                    && $pred['predicted_home'] !== null
                                    && $pred['predicted_away'] !== null;
                            ?>
                                <div class="member">
                                    <div class="member-info">
                                        <div class="member-name"><?= htmlspecialchars($member['name']) ?></div>
                                    </div>
                                    <div style="font-family: var(--font-display); font-size: 18px; color: var(--field);">
                                        <?php if ($hasPrediction): ?>
                                            <?= (int)$pred['predicted_home'] ?> – <?= (int)$pred['predicted_away'] ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
