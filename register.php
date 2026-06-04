<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Als de gebruiker al is ingelogd, stuur door naar dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

if ($name === '') {
    $errors[] = 'Naam is verplicht.';
} elseif (strlen($name) < 2) {
    $errors[] = 'Naam moet minimaal 2 tekens lang zijn.';
}

if ($email === '') {
    $errors[] = 'E-mailadres is verplicht.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Ongeldig e-mailadres.';
}

if ($password === '') {
    $errors[] = 'Wachtwoord is verplicht.';
} elseif (strlen($password) < 6) {
    $errors[] = 'Wachtwoord moet minimaal 6 tekens lang zijn.';
}

if ($confirm === '') {
    $errors[] = 'Bevestig je wachtwoord.';
} elseif ($password !== $confirm) {
    $errors[] = 'Wachtwoorden komen niet overeen.';
}
    // =============================================================
    // TODO 1: VALIDATIE
    // =============================================================
    // Controleer of de velden correct zijn ingevuld en voeg eventuele
    // foutmeldingen toe aan de $errors array.
    //
    // Vereisten:
    //  - $name mag niet leeg zijn en moet minimaal 2 tekens lang zijn.
    //  - $email mag niet leeg zijn en moet een geldig e-mailadres zijn
    //    (tip: gebruik filter_var met FILTER_VALIDATE_EMAIL).
    //  - $password moet minimaal 6 tekens lang zijn.
    //  - $password en $confirm moeten gelijk zijn.
    //
    // Voorbeeld van een foutmelding toevoegen:
    //   $errors[] = 'Naam is verplicht.';
    // =============================================================


    // =============================================================
    // TODO 2: CONTROLEER OF E-MAIL AL BESTAAT
    // =============================================================
    // Alleen uitvoeren als er nog geen fouten zijn.
    // Gebruik een prepared statement op de `users` tabel.
    // Als het e-mailadres al bestaat, voeg dan een foutmelding toe
    // aan de $errors array.
    //
    // Voorbeeld prepared statement:
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()){
        $errors[] = "Dit e-mailadres is al geregistreerd.";
    }
        
    if (empty($errors)) {     
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([$name,$email,$hashed]);

        header('Location: login.php?registered=1');
        exit;
    }
    // =============================================================


    // =============================================================
    // TODO 3: GEBRUIKER OPSLAAN
    // =============================================================
    // Alleen uitvoeren als $errors nog leeg is.
    //
    //  a) Hash het wachtwoord met password_hash():
    //       $hashed = password_hash($password, PASSWORD_DEFAULT);
    //
    //  b) Voeg de gebruiker toe met een INSERT statement:
    //       INSERT INTO users (name, email, password) ???
    //
    //  c) Stuur de gebruiker door naar login.php met een succes-parameter:
    //       header('Location: ');
    //       exit;
    // =============================================================

}

$pageTitle = 'Registreren';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="form-wrapper">
        <div class="form-card">
            <h1 class="form-title">Word lid</h1>
            <p class="form-subtitle">Maak een account en start direct met voorspellen.</p>

            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate>
                <div class="form-group">
                    <label class="form-label" for="name">Volledige naam</label>
                    <input type="text" id="name" name="name" class="form-input"
                           value="<?= htmlspecialchars($name) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">E-mailadres</label>
                    <input type="email" id="email" name="email" class="form-input"
                           value="<?= htmlspecialchars($email) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Wachtwoord</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                    <p class="form-help">Minimaal 6 tekens</p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirm">Bevestig wachtwoord</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-input" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">
                    Account aanmaken
                </button>
            </form>

            <div class="form-footer">
                Al een account? <a href="login.php">Log hier in</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
