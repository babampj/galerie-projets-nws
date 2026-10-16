<?php
require __DIR__ . '/lib.php';

if (estConnecte()) {
    header('Location: index.php');
    exit;
}

$erreurs = [];
$pseudo  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $pseudo      = trim((string) ($_POST['pseudo'] ?? ''));
    $motDePasse  = (string) ($_POST['mot_de_passe'] ?? '');
    $tentatives  = (int) ($_SESSION['tentatives'] ?? 0);
    $depuis      = (int) ($_SESSION['tentative_depuis'] ?? 0);

    if ($tentatives >= 5 && (time() - $depuis) < 900) {
        $erreurs[] = 'Trop de tentatives échouées. Réessayez dans 15 minutes.';
    } elseif ($pseudo !== ADMIN_PSEUDO || !password_verify($motDePasse, ADMIN_HASH)) {
        if ($tentatives === 0 || (time() - $depuis) > 900) {
            $_SESSION['tentatives']      = 0;
            $_SESSION['tentative_depuis'] = time();
        }
        $_SESSION['tentatives'] = $tentatives + 1;
        $erreurs[] = 'Identifiants incorrects.';
    } else {
        session_regenerate_id(true);
        unset($_SESSION['tentatives'], $_SESSION['tentative_depuis']);
        $_SESSION['admin'] = $pseudo;
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — Administration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Fugaz+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<main class="connexion">
    <div class="connexion__boite">
        <p class="connexion__sur">Espace réservé</p>
        <h1>Administration</h1>
        <p class="connexion__intro">Gestion de la galerie des projets étudiants NWS.</p>

        <?php foreach ($erreurs as $err): ?>
            <p class="message message--erreur"><?= e($err) ?></p>
        <?php endforeach; ?>

        <form method="post" action="login.php" class="connexion__formulaire">
            <input type="hidden" name="csrf" value="<?= e(jetonCsrf()) ?>">

            <p class="champ">
                <label for="pseudo">Pseudo</label>
                <input type="text" id="pseudo" name="pseudo" value="<?= e($pseudo) ?>"
                       required autocomplete="username" placeholder="admin">
            </p>

            <p class="champ">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe"
                       required autocomplete="current-password" placeholder="••••••••">
            </p>

            <button class="btn btn--orange btn--large" type="submit">Se connecter</button>
        </form>

        <p class="connexion__aide">
            <a href="../index.php">← Retour à la galerie</a>
        </p>
    </div>
</main>
</body>
</html>
