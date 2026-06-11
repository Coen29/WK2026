<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

requireAdmin();

$errors  = [];
$success = '';

try {
    $stmt = $pdo->query("SELECT * FROM matches ORDER BY match_date ASC");
    $matches = $stmt->fetchAll();
} catch (PDOException $e) {
    $matches = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matchId = (int)($_POST['match_id'] ?? 0);
    $home    = $_POST['home_score'] ?? '';
    $away    = $_POST['away_score'] ?? '';

    if ($matchId <= 0) {
        $errors[] = 'Ongeldige wedstrijd.';
    } elseif ($home === '' || $away === '') {
        $errors[] = 'Vul beide scores in.';
    } elseif (!ctype_digit((string)$home) || !ctype_digit((string)$away)) {
        $errors[] = 'Scores moeten gehele getallen zijn.';
    } elseif ((int)$home > 99 || (int)$away > 99) {
        $errors[] = 'Scores mogen niet hoger zijn dan 99.';
    } else {
        $homeScore = (int)$home;
        $awayScore = (int)$away;

        try {
            $stmt = $pdo->prepare("UPDATE matches SET home_score = ?, away_score = ? WHERE id = ?");
            $stmt->execute([$homeScore, $awayScore, $matchId]);

            $predStmt = $pdo->prepare(
                "SELECT user_id, predicted_home, predicted_away FROM predictions WHERE match_id = ?"
            );
            $predStmt->execute([$matchId]);

            $updatePoints = $pdo->prepare(
                "UPDATE predictions SET points = ? WHERE match_id = ? AND user_id = ?"
            );

            foreach ($predStmt->fetchAll() as $prediction) {
                $points = calculatePoints(
                    $homeScore,
                    $awayScore,
                    (int)$prediction['predicted_home'],
                    (int)$prediction['predicted_away']
                );
                $updatePoints->execute([$points, $matchId, $prediction['user_id']]);
            }

            $success = 'Uitslag opgeslagen en punten bijgewerkt.';

            $stmt = $pdo->query("SELECT * FROM matches ORDER BY match_date ASC");
            $matches = $stmt->fetchAll();
        } catch (PDOException $e) {
            $errors[] = 'Opslaan mislukt. Probeer het opnieuw.';
        }
    }
}

$pageTitle = 'Uitslagen beheren';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Beheer</div>
            <h1 class="page-title">Officiële uitslagen</h1>
            <p class="page-desc">Vul per wedstrijd de definitieve score in. Na opslaan worden automatisch punten berekend voor alle voorspellingen.</p>
        </div>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (empty($matches)): ?>
        <div class="empty">
            <div class="empty-icon">📅</div>
            <h2 class="empty-title">Nog geen wedstrijden</h2>
            <p class="empty-text">Er staan nog geen wedstrijden in de database.</p>
        </div>
    <?php else: ?>
        <div class="match-list">
            <?php foreach ($matches as $match):
                $mid = (int)$match['id'];
                $isPlayed = $match['home_score'] !== null && $match['away_score'] !== null;
                $date = new DateTime($match['match_date']);
            ?>
                <div class="match">
                    <div class="match-meta">
                        <span class="match-stage"><?= htmlspecialchars($match['stage']) ?></span>
                        <div class="match-meta-right">
                            <?php if ($isPlayed): ?>
                                <span class="match-played-badge">Gespeeld</span>
                            <?php endif; ?>
                            <span><?= $date->format('d M Y · H:i') ?></span>
                        </div>
                    </div>

                    <form method="POST" action="admin_scores.php" class="match-row">
                        <input type="hidden" name="match_id" value="<?= $mid ?>">

                        <div class="team team-home">
                            <span class="team-name"><?= htmlspecialchars($match['home_team']) ?></span>
                            <span class="team-flag"><?= strtoupper(substr($match['home_team'], 0, 2)) ?></span>
                        </div>

                        <div class="score-input-group">
                            <input type="number"
                                   name="home_score"
                                   class="score-input"
                                   min="0" max="99"
                                   value="<?= $isPlayed ? (int)$match['home_score'] : '' ?>"
                                   placeholder="-"
                                   required>
                            <span class="score-sep">:</span>
                            <input type="number"
                                   name="away_score"
                                   class="score-input"
                                   min="0" max="99"
                                   value="<?= $isPlayed ? (int)$match['away_score'] : '' ?>"
                                   placeholder="-"
                                   required>
                        </div>

                        <div class="team">
                            <span class="team-flag"><?= strtoupper(substr($match['away_team'], 0, 2)) ?></span>
                            <span class="team-name"><?= htmlspecialchars($match['away_team']) ?></span>
                        </div>

                        <div class="admin-score-actions">
                            <button type="submit" class="btn btn-primary btn-sm">
                                💾 Uitslag opslaan
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
