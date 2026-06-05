<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();
$user = currentUser();

$stats = [
    'pools'       => 0,
    'predictions' => 0,
];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pool_members WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $stats['pools'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM predictions WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $stats['predictions'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Stil: stats blijven 0 als tabel nog leeg is
$errors  = [];
$success = '';
$name    = $user['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_name') {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $errors[] = 'Naam is verplicht.';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Naam moet minimaal 2 tekens lang zijn.';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Naam mag maximaal 100 tekens lang zijn.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
        $stmt->execute([$name, $user['id']]);

        $_SESSION['user_name'] = $name;
        $success = 'Je naam is bijgewerkt.';
    }
}

$pageTitle = 'Profiel';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Account</div>
            <h1 class="page-title">Mijn profiel</h1>
            <p class="page-desc">Bekijk je accountgegevens en statistieken.</p>
        </div>
    </div>

    <section class="card" style="margin-bottom: 32px;">
        <div class="card-header">
            <div style="display: flex; align-items: center; gap: 20px;">
                <?php if ($success): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>

            <form method="POST" action="profile.php" novalidate>
                <input type="hidden" name="action" value="update_name">

                <div class="form-group">
                    <label class="form-label" for="name">Naam</label>
                    <input type="text" id="name" name="name" class="form-input"
                           value="<?= htmlspecialchars($name) ?>" required>
                    <p class="form-help">Minimaal 2 tekens</p>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">
                    Naam opslaan
                </button>
            </form>
                <div>
                    <h2 class="card-title"><?= htmlspecialchars($user['name']) ?></h2>
                    <p class="card-subtitle"><?= htmlspecialchars($user['email']) ?></p>
                </div>
            </div>
        </div>
        <dl style="display: grid; gap: 16px; margin: 0;">
            <div>
                <dt style="font-family: var(--font-mono); font-size: 11px; color: var(--text-mute); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px;">Naam</dt>
                <dd style="font-size: 16px; color: var(--chalk); margin: 0;"><?= htmlspecialchars($user['name']) ?></dd>
            </div>
            <div>
                <dt style="font-family: var(--font-mono); font-size: 11px; color: var(--text-mute); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px;">E-mailadres</dt>
                <dd style="font-size: 16px; color: var(--chalk); margin: 0;"><?= htmlspecialchars($user['email']) ?></dd>
            </div>
        </dl>
    </section>

    <div class="stat-row">
        <div class="stat stat-accent">
            <div class="stat-label">Poules</div>
            <div class="stat-value"><?= $stats['pools'] ?></div>
        </div>
        <div class="stat">
            <div class="stat-label">Voorspellingen</div>
            <div class="stat-value"><?= $stats['predictions'] ?></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
