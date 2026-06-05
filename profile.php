<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();
$user = currentUser();

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
    <div class="form-wrapper">
        <div class="form-card">
            <h1 class="form-title">Profiel</h1>
            <p class="form-subtitle">Pas je naam aan zodat vrienden je herkennen in de poule.</p>

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
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
