<?php
require __DIR__ . '/lib.php';

protegerAdmin();

$projets = chargerProjets();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $id = (int) ($_POST['id'] ?? 0);
    $index = null;
    foreach ($projets as $i => $projet) {
        if ((int) $projet['id'] === $id) {
            $index = $i;
            break;
        }
    }

    if ($index === null) {
        flash('erreur', 'Projet introuvable.');
    } else {
        $supprime = $projets[$index];
        unset($projets[$index]);
        $projets = array_values($projets);
        sauverProjets($projets);
        supprimerFichierImage($supprime['image'], $projets);
        flash('succes', 'Projet « ' . $supprime['titre'] . ' » supprimé.');
    }

    header('Location: index.php');
    exit;
}

$nbProjets    = count($projets);
$nbUne        = count(array_filter($projets, fn ($p) => $p['enAvant']));
$promotions   = array_unique(array_column($projets, 'promotion'));
$specialites  = array_unique(array_column($projets, 'specialite'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Projets — Administration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Fugaz+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<nav class="admin-nav" aria-label="Navigation administration">
    <div class="conteneur admin-nav__interieur">
        <span class="admin-nav__titre">Admin NWS</span>
        <a class="admin-nav__lien admin-nav__lien--actif" href="index.php">Projets</a>
        <a class="admin-nav__lien" href="formulaire.php">Ajouter</a>
        <a class="admin-nav__lien" href="../index.php">Voir le site</a>
        <span class="admin-nav__espace"></span>
        <span class="admin-nav__user">Connecté : <?= e((string) $_SESSION['admin']) ?></span>
        <a class="admin-nav__lien" href="logout.php">Déconnexion</a>
    </div>
</nav>

<main class="admin conteneur">
    <div class="admin__entete">
        <h1>Les projets</h1>
        <div class="admin__actions">
            <a class="btn btn--orange" href="formulaire.php">+ Ajouter un projet</a>
        </div>
    </div>

    <?= afficherFlash() ?>

    <ul class="stats">
        <li class="stat">
            <span class="stat__valeur"><?= $nbProjets ?></span>
            <span class="stat__libelle">Projets</span>
        </li>
        <li class="stat">
            <span class="stat__valeur"><?= $nbUne ?></span>
            <span class="stat__libelle">À la une</span>
        </li>
        <li class="stat">
            <span class="stat__valeur"><?= count($promotions) ?></span>
            <span class="stat__libelle">Promotions</span>
        </li>
        <li class="stat">
            <span class="stat__valeur"><?= count($specialites) ?></span>
            <span class="stat__libelle">Spécialités</span>
        </li>
    </ul>

    <div class="tableau-wrapper">
        <table class="tableau">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Visuel</th>
                    <th>Projet</th>
                    <th>Étudiant</th>
                    <th>Promotion</th>
                    <th>Année</th>
                    <th>À la une</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($projets === []): ?>
                    <tr>
                        <td colspan="8">Aucun projet pour le moment.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($projets as $projet): ?>
                    <tr>
                        <td><?= (int) $projet['id'] ?></td>
                        <td>
                            <img src="../<?= e($projet['image']) ?>" alt="" width="84" height="52"
                                 style="border-radius:8px;object-fit:cover;display:block">
                        </td>
                        <td>
                            <a class="tableau__titre" href="../projet.php?id=<?= (int) $projet['id'] ?>">
                                <?= e($projet['titre']) ?>
                            </a>
                        </td>
                        <td><?= e($projet['etudiant']) ?></td>
                        <td><?= e($projet['promotion']) ?></td>
                        <td><?= (int) $projet['annee'] ?></td>
                        <td><?= $projet['enAvant'] ? 'Oui' : '—' ?></td>
                        <td>
                            <div class="tableau__actions">
                                <a class="btn-mini btn-mini--modifier"
                                   href="formulaire.php?mode=modifier&id=<?= (int) $projet['id'] ?>">Modifier</a>
                                <form class="formulaire-en-ligne" method="post" action="index.php"
                                      onsubmit="return confirm('Supprimer ce projet ?');">
                                    <input type="hidden" name="csrf" value="<?= e(jetonCsrf()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $projet['id'] ?>">
                                    <button class="btn-mini btn-mini--supprimer" type="submit">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
