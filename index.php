<?php
require __DIR__ . '/donnees.php';

$recherche         = $_GET['q'] ?? '';
$specialiteChoisie = $_GET['specialite'] ?? '';
$promotionChoisie  = $_GET['promotion'] ?? '';
$triChoisi         = $_GET['tri'] ?? 'date';

$specialites = array_unique(array_column($projets, 'specialite'));
sort($specialites);

$promotions = array_unique(array_column($projets, 'promotion'));
sort($promotions);

$trisDisponibles = [
    'date'  => 'Plus récents',
    'alpha' => 'Titre A → Z',
    'annee' => 'Année (ancien d’abord)',
];
if (!isset($trisDisponibles[$triChoisi])) {
    $triChoisi = 'date';
}

$resultats = [];

foreach ($projets as $projet) {
    $texteComplet = $projet['titre'] . ' ' . $projet['description']
                  . ' ' . $projet['etudiant'] . ' ' . $projet['type'];

    $trouve = ($recherche === '' || stripos($texteComplet, $recherche) !== false);

    $bonneSpecialite = ($specialiteChoisie === ''
                        || $projet['specialite'] === $specialiteChoisie);

    $bonnePromotion = ($promotionChoisie === ''
                       || $projet['promotion'] === $promotionChoisie);

    if ($trouve && $bonneSpecialite && $bonnePromotion) {
        $resultats[] = $projet;
    }
}

usort($resultats, function ($a, $b) use ($triChoisi) {
    if ($triChoisi === 'alpha') {
        return strcmp($a['titre'], $b['titre']);
    }
    if ($triChoisi === 'annee') {
        return $a['annee'] <=> $b['annee'];
    }
    return $b['annee'] <=> $a['annee'];
});

function couleurSpecialite(string $s): string
{
    if ($s === 'Développement web')               return 'pilule--dev';
    if ($s === 'Communication graphique / UX/UI') return 'pilule--design';
    if ($s === 'Communication digitale')          return 'pilule--marketing';
    return 'pilule--neutre';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>La galerie des projets — Normandie Web School</title>
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

<section class="hero">
    <div class="conteneur hero__interieur">
        <p class="hero__sur">Normandie Web School</p>
        <h1>La galerie des <span class="hero__accent">projets étudiants</span></h1>
        <p class="hero__intro">
            Identités visuelles, chartes éditoriales, sites web, applications, stratégies de communication…
            Retrouvez rapidement un projet grâce à la recherche et aux filtres.
        </p>
    </div>
</section>

<div class="conteneur">

    <form class="barre" method="get" action="index.php">
        <div class="barre__recherche">
            <label class="sr-only" for="q">Rechercher un projet</label>
            <input type="search" id="q" name="q" value="<?= htmlspecialchars($recherche) ?>"
                   autocomplete="off"
                   placeholder="Charte éditoriale, identité visuelle, site vitrine…">
            <button class="btn btn--orange" type="submit">Rechercher</button>
        </div>

        <div class="barre__filtres">
            <p class="champ">
                <label for="specialite">Spécialité</label>
                <select id="specialite" name="specialite">
                    <option value="">Toutes les spécialités</option>
                    <?php foreach ($specialites as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"
                            <?= $s === $specialiteChoisie ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p class="champ">
                <label for="promotion">Promotion</label>
                <select id="promotion" name="promotion">
                    <option value="">Toutes les promotions</option>
                    <?php foreach ($promotions as $promo): ?>
                        <option value="<?= htmlspecialchars($promo) ?>"
                            <?= $promo === $promotionChoisie ? 'selected' : '' ?>>
                            <?= htmlspecialchars($promo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p class="champ">
                <label for="tri">Trier par</label>
                <select id="tri" name="tri">
                    <?php foreach ($trisDisponibles as $cle => $libelle): ?>
                        <option value="<?= $cle ?>"<?= $cle === $triChoisi ? 'selected' : '' ?>>
                            <?= $libelle ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <div class="champ champ--actions">
                <a class="btn btn--ligne" href="index.php">Réinitialiser</a>
            </div>
        </div>
    </form>

    <div class="resultats">
        <p class="resultats__compteur">
            <strong><?= count($resultats) ?></strong>
            <?= count($resultats) > 1 ? 'projets' : 'projet' ?>
            <?php if ($recherche !== ''): ?>
                pour «&nbsp;<?= htmlspecialchars($recherche) ?>&nbsp;»
            <?php endif; ?>
        </p>
    </div>

    <section class="grille">
        <?php foreach ($resultats as $projet): ?>
            <article class="carte<?= $projet['enAvant'] ? ' carte--une' : '' ?>">
                <a class="carte__lien" href="projet.php?id=<?= (int) $projet['id'] ?>">
                    <div class="carte__visuel">
                        <img src="<?= htmlspecialchars($projet['image']) ?>"
                             alt="<?= htmlspecialchars($projet['titre']) ?>"
                             loading="lazy" width="640" height="400">
                        <span class="carte__type"><?= htmlspecialchars($projet['type']) ?></span>
                        <?php if ($projet['enAvant']): ?>
                            <span class="carte__badge">À la une</span>
                        <?php endif; ?>
                    </div>
                    <div class="carte__corps">
                        <p class="carte__groupe"><?= htmlspecialchars($projet['etudiant']) ?></p>
                        <h3 class="carte__titre"><?= htmlspecialchars($projet['titre']) ?></h3>
                        <p class="carte__desc"><?= htmlspecialchars($projet['description']) ?></p>
                        <p class="carte__meta">
                            <span class="pilule <?= couleurSpecialite($projet['specialite']) ?>">
                                <?= htmlspecialchars($projet['specialite']) ?>
                            </span>
                            <span class="carte__annee"><?= (int) $projet['annee'] ?></span>
                        </p>
                    </div>
                </a>
            </article>
        <?php endforeach; ?>
    </section>

    <?php if (count($resultats) === 0): ?>
        <div class="vide">
            <p class="vide__titre">Aucun projet ne correspond à votre recherche.</p>
            <p class="vide__texte">Essayez d’autres mots-clés ou réinitialisez les filtres.</p>
            <a class="btn btn--orange" href="index.php">Réinitialiser la recherche</a>
        </div>
    <?php endif; ?>

</div>

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
