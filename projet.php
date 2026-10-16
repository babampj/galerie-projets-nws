<?php
require __DIR__ . '/donnees.php';

$id = (int) ($_GET['id'] ?? 0);
$projet = projetParId($id);

if (!$projet) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Projet introuvable — Galerie des projets</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fira+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Fugaz+One&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <header class="entete">
            <div class="conteneur entete__interieur">
                <a class="entete__logo" href="index.php">
                    <img src="logo-nws.png" alt="Normandie Web School" width="2496" height="960">
                </a>
                <nav class="entete__nav" aria-label="Navigation principale">
                    <a href="index.php">Galerie NWS</a>
                </nav>
            </div>
        </header>
        <main class="conteneur erreur-404">
            <p class="erreur-404__code">404</p>
            <h1>Ce projet n’existe pas</h1>
            <p class="erreur-404__texte">Le projet demandé est introuvable : le lien est peut-être incorrect.</p>
            <a class="btn btn--orange" href="index.php">Retour à la galerie</a>
        </main>
        <footer class="pied">
            <div class="conteneur pied__interieur">
                <p class="pied__marque"><strong>NWS</strong> — Normandie Web School</p>
                <ul class="pied__liens">
                    <li><a href="https://normandiewebschool.fr" rel="noopener">normandiewebschool.fr</a></li>
                    <li><a href="index.php">Galerie</a></li>
                </ul>
                <p class="pied__note">Version pédagogique — projets fictifs à titre de démonstration.</p>
            </div>
        </footer>
    </body>
    </html>
    <?php
    exit;
}

$fichierVues = __DIR__ . '/vues.json';
$vues = [];
if (is_file($fichierVues)) {
    $vues = json_decode((string) file_get_contents($fichierVues), true) ?: [];
}
$vues[$id] = (int) ($vues[$id] ?? 0) + 1;
file_put_contents($fichierVues, json_encode($vues, JSON_PRETTY_PRINT));
$consultations = $vues[$id];

$outils = $projet['outils'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($projet['titre']) ?> — Galerie des projets</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Fugaz+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="entete">
    <div class="conteneur entete__interieur">
        <a class="entete__logo" href="index.php">
            <img src="logo-nws.png" alt="Normandie Web School" width="2496" height="960">
        </a>
        <nav class="entete__nav" aria-label="Navigation principale">
            <a href="index.php">Galerie NWS</a>
        </nav>
    </div>
</header>

<main class="fiche">
    <div class="conteneur">

        <p class="fil-ariane">
            <a href="index.php">← Tous les projets</a>
        </p>

        <header class="fiche__entete">
            <figure class="fiche__visuel">
                <img src="<?= htmlspecialchars($projet['image']) ?>"
                     alt="<?= htmlspecialchars($projet['titre']) ?>" width="640" height="400">
            </figure>

            <div class="fiche__intro">
                <p class="fiche__groupe"><?= htmlspecialchars($projet['etudiant']) ?></p>
                <h1><?= htmlspecialchars($projet['titre']) ?></h1>

                <p class="fiche__pills">
                    <span class="pilule <?= $projet['specialite'] === 'Développement web' ? 'pilule--dev' : ($projet['specialite'] === 'Communication graphique / UX/UI' ? 'pilule--design' : 'pilule--marketing') ?>">
                        <?= htmlspecialchars($projet['specialite']) ?>
                    </span>
                    <span class="pilule pilule--neutre"><?= htmlspecialchars($projet['type']) ?></span>
                    <span class="pilule pilule--neutre"><?= htmlspecialchars($projet['promotion']) ?></span>
                    <span class="pilule pilule--annee"><?= (int) $projet['annee'] ?></span>
                    <?php if ($projet['enAvant']): ?>
                        <span class="pilule pilule--une">À la une</span>
                    <?php endif; ?>
                </p>

                <p class="fiche__stats">
                    <strong><?= $consultations ?></strong>
                    consultation<?= $consultations > 1 ? 's' : '' ?>
                </p>

                <?php if ($projet['lien']): ?>
                    <p>
                        <a class="btn btn--orange" href="<?= htmlspecialchars($projet['lien']['url']) ?>"
                           target="_blank" rel="noopener noreferrer">
                            <?= $projet['lien']['type'] === 'github'
                                ? 'Voir le code sur GitHub'
                                : 'Voir le portfolio' ?> ↗
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </header>

        <div class="fiche__corps">
            <section class="bloc">
                <h2>Description</h2>
                <p class="bloc__texte"><?= htmlspecialchars($projet['description']) ?></p>
            </section>

            <section class="bloc bloc--accent">
                <h2>Contexte &amp; objectifs</h2>
                <p class="bloc__texte"><?= htmlspecialchars($projet['contexte']) ?></p>
            </section>

            <section class="bloc">
                <h2>Outils &amp; technologies</h2>
                <ul class="tags">
                    <?php foreach ($outils as $outil): ?>
                        <li class="tag"><?= htmlspecialchars($outil) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="bloc">
                <h2>Étudiant participant</h2>
                <ul class="etudiants">
                    <li class="etudiant">
                        <span class="etudiant__initiales" aria-hidden="true">
                            <?= htmlspecialchars(strtoupper(mb_substr($projet['etudiant'], 0, 1))) ?>
                        </span>
                        <span class="etudiant__nom"><?= htmlspecialchars($projet['etudiant']) ?></span>
                    </li>
                </ul>
            </section>
        </div>

        <section class="bloc galerie">
            <h2>Visuels du projet</h2>
            <div class="galerie__grille">
                <button class="galerie__vignette" type="button"
                        data-full="<?= htmlspecialchars($projet['image']) ?>"
                        data-legende="<?= htmlspecialchars($projet['titre']) ?>"
                        aria-label="Agrandir l’image">
                    <img src="<?= htmlspecialchars($projet['image']) ?>"
                         alt="<?= htmlspecialchars($projet['titre']) ?>" width="640" height="400">
                </button>
            </div>
        </section>

        <p class="fiche__retour">
            <a class="btn btn--ligne" href="index.php">← Retour à la galerie</a>
        </p>

    </div>
</main>

<dialog class="visionneuse" id="visionneuse" aria-label="Aperçu de l’image">
    <img class="visionneuse__img" id="visionneuse-img" src="" alt="">
    <p class="visionneuse__legende" id="visionneuse-legende"></p>
    <button class="visionneuse__fermer" type="button" id="visionneuse-fermer" aria-label="Fermer l’aperçu">✕</button>
</dialog>

<footer class="pied">
    <div class="conteneur pied__interieur">
        <p class="pied__marque"><strong>NWS</strong> — Normandie Web School</p>
        <ul class="pied__liens">
            <li><a href="https://normandiewebschool.fr" rel="noopener">normandiewebschool.fr</a></li>
            <li><a href="index.php">Galerie</a></li>
        </ul>
        <p class="pied__note">Version pédagogique — projets fictifs à titre de démonstration.</p>
    </div>
</footer>
<script src="fiche.js" defer></script>
</body>
</html>
