<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Als de gebruiker al is ingelogd, stuur door naar dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$email  = '';
$notice = '';

// Succesbericht na registratie
if (isset($_GET['registered'])) {
    $notice = 'Je account is aangemaakt. Log nu in met je gegevens.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password']   ?? '';

    // =============================================================
    // TODO 1: VALIDATIE
    // =============================================================
    // Controleer dat zowel $email als $password niet leeg zijn.
    // Voeg bij ontbrekende velden een foutmelding toe aan $errors.
    //
    // Bijvoorbeeld:
    if ($email === '') {
        $errors[] = 'E-mailadres is verplicht.';
    }
    if ($password === '') {
        $errors[] = 'Wachtwoord is verplicht.';
    }
    // =============================================================


    // =============================================================
    // TODO 2: GEBRUIKER OPHALEN & WACHTWOORD VERIFIËREN
    // =============================================================
    // Alleen uitvoeren als $errors nog leeg is.
    //
    //  a) Haal de gebruiker op uit de database op basis van e-mail:
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    //  b) Controleer of de gebruiker bestaat EN of het wachtwoord klopt
    //     met password_verify():
    if ($user && password_verify($password, $user['password'])) {
        // login gelukt
    } else {
        $errors[] = 'Ongeldige inloggegevens.';
    }
    //
    //  TIP: geef GEEN aparte foutmelding voor "gebruiker bestaat niet"
    //  versus "wachtwoord klopt niet" - dat is onveilig.
    // =============================================================


    // =============================================================
    // TODO 3: SESSIE STARTEN
    // =============================================================
    // Als de login is geslaagd, sla dan het volgende op in de sessie:
    if (empty($errors)) {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        //
        // Stuur vervolgens door naar het dashboard:
        header('Location: index.php');
        exit;
    }
    // =============================================================

}

$pageTitle = 'Inloggen';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="form-wrapper">
        <div class="form-card">
            <h1 class="form-title">Inloggen</h1>
            <p class="form-subtitle">Welkom terug in het stadion.</p>

            <?php if ($notice): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($notice) ?></div>
            <?php endif; ?>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>

            <form method="POST" action="login.php" novalidate>
                <div class="form-group">
                    <label class="form-label" for="email">E-mailadres</label>
                    <input type="email" id="email" name="email" class="form-input"
                        value="<?= htmlspecialchars($email) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Wachtwoord</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">
                    Inloggen
                </button>
            </form>

            <div class="form-footer">
                Nog geen account? <a href="register.php">Registreer hier</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>