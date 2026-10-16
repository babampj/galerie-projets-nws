<?php
require __DIR__ . '/lib.php';

protegerAdmin();

$mode   = ($_GET['mode'] ?? 'ajout') === 'modifier' ? 'modifier' : 'ajout';
$id     = (int) ($_GET['id'] ?? 0);
$projets = chargerProjets();
$projet  = null;

if ($mode === 'modifier') {
    foreach ($projets as $candidat) {
        if ((int) $candidat['id'] === $id) {
            $projet = $candidat;
            break;
        }
    }
    if ($projet === null) {
        flash('erreur', 'Projet introuvable.');
        header('Location: index.php');
        exit;
    }
}

$specialites = [
    'Communication graphique / UX/UI',
    'Communication digitale',
    'Développement web',
];

$valeurs = [
    'titre'       => $projet['titre'] ?? '',
    'etudiant'    => $projet['etudiant'] ?? '',
    'annee'       => $projet['annee'] ?? (int) date('Y'),
    'promotion'   => $projet['promotion'] ?? '',
    'specialite'  => $projet['specialite'] ?? $specialites[0],
    'type'        => $projet['type'] ?? '',
    'image'       => $projet['image'] ?? '',
    'enAvant'     => $projet['enAvant'] ?? false,
    'description' => $projet['description'] ?? '',
    'contexte'    => $projet['contexte'] ?? '',
    'outilsTexte' => $projet ? implode(', ', $projet['outils']) : '',
    'lienType'    => $projet['lien']['type'] ?? '',
    'lienUrl'     => $projet['lien']['url'] ?? '',
];

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $valeurs['titre']       = trim((string) ($_POST['titre'] ?? ''));
    $valeurs['etudiant']    = trim((string) ($_POST['etudiant'] ?? ''));
    $valeurs['annee']       = trim((string) ($_POST['annee'] ?? ''));
    $valeurs['promotion']   = trim((string) ($_POST['promotion'] ?? ''));
    $valeurs['specialite']  = (string) ($_POST['specialite'] ?? '');
    $valeurs['type']        = trim((string) ($_POST['type'] ?? ''));
    $valeurs['enAvant']     = !empty($_POST['en_avant']);
    $valeurs['description'] = trim((string) ($_POST['description'] ?? ''));
    $valeurs['contexte']    = trim((string) ($_POST['contexte'] ?? ''));
    $valeurs['outilsTexte'] = trim((string) ($_POST['outils'] ?? ''));
    $valeurs['lienType']    = (string) ($_POST['lien_type'] ?? '');
    $valeurs['lienUrl']     = trim((string) ($_POST['lien_url'] ?? ''));

    if ($valeurs['titre'] === '') {
        $erreurs['titre'] = 'Le titre est obligatoire.';
    }
    if ($valeurs['etudiant'] === '') {
        $erreurs['etudiant'] = 'L’étudiant est obligatoire.';
    }
    $annee = filter_var($valeurs['annee'], FILTER_VALIDATE_INT);
    if ($annee === false || $annee < 2000 || $annee > 2100) {
        $erreurs['annee'] = 'L’année doit être un nombre entre 2000 et 2100.';
    }
    if ($valeurs['promotion'] === '') {
        $erreurs['promotion'] = 'La promotion est obligatoire.';
    }
    if (!in_array($valeurs['specialite'], $specialites, true)) {
        $erreurs['specialite'] = 'Spécialité invalide.';
    }
    if ($valeurs['type'] === '') {
        $erreurs['type'] = 'Le type de livrable est obligatoire.';
    }
    if ($valeurs['description'] === '') {
        $erreurs['description'] = 'La description est obligatoire.';
    }
    if ($valeurs['contexte'] === '') {
        $erreurs['contexte'] = 'Le contexte est obligatoire.';
    }
    if (!in_array($valeurs['lienType'], ['', 'portfolio', 'github'], true)) {
        $erreurs['lien'] = 'Type de lien invalide.';
    } elseif ($valeurs['lienType'] !== ''
              && filter_var($valeurs['lienUrl'], FILTER_VALIDATE_URL) === false) {
        $erreurs['lien'] = 'L’URL du lien doit être valide (https://…).';
    }

    $imageFinale   = $valeurs['image'];
    $ancienneImage = null;

    if ($erreurs === []) {
        $fichierTeleverse = $_FILES['image'] ?? null;

        if (is_array($fichierTeleverse) && $fichierTeleverse['error'] !== UPLOAD_ERR_NO_FILE) {
            $ext  = strtolower(pathinfo((string) $fichierTeleverse['name'], PATHINFO_EXTENSION));
            $okEx = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);

            if ($fichierTeleverse['error'] !== UPLOAD_ERR_OK) {
                $erreurs['image'] = 'Erreur lors du téléversement du fichier.';
            } elseif (!$okEx) {
                $erreurs['image'] = 'Format non autorisé (jpg, jpeg, png, webp ou gif).';
            } elseif ((int) $fichierTeleverse['size'] > 5242880) {
                $erreurs['image'] = 'Image trop lourde (5 Mo maximum).';
            } else {
                $nom = slug($valeurs['titre'] ?: 'projet') . '-' . uniqid() . '.' . $ext;
                if (move_uploaded_file((string) $fichierTeleverse['tmp_name'], DOSSIER_IMAGES . '/' . $nom)) {
                    $ancienneImage = $valeurs['image'] ?: null;
                    $imageFinale   = 'img/' . $nom;
                } else {
                    $erreurs['image'] = 'Impossible d’enregistrer l’image.';
                }
            }
        }

        if ($imageFinale === '' && $valeurs['titre'] !== '') {
            $imageFinale = 'img/' . slug($valeurs['titre']) . '.png';
        }
    }

    if ($erreurs === []) {
        $outils = array_values(array_filter(array_map('trim', explode(',', $valeurs['outilsTexte']))));

        $enregistre = [
            'id'          => $mode === 'modifier' ? (int) $projet['id'] : prochainId($projets),
            'titre'       => $valeurs['titre'],
            'etudiant'    => $valeurs['etudiant'],
            'annee'       => (int) $annee,
            'promotion'   => $valeurs['promotion'],
            'specialite'  => $valeurs['specialite'],
            'type'        => $valeurs['type'],
            'image'       => $imageFinale,
            'enAvant'     => (bool) $valeurs['enAvant'],
            'description' => $valeurs['description'],
            'contexte'    => $valeurs['contexte'],
            'outils'      => $outils,
            'lien'        => $valeurs['lienType'] === ''
                ? null
                : ['type' => $valeurs['lienType'], 'url' => $valeurs['lienUrl']],
        ];

        if ($mode === 'modifier') {
            foreach ($projets as $i => $candidat) {
                if ((int) $candidat['id'] === (int) $projet['id']) {
                    $projets[$i] = $enregistre;
                    break;
                }
            }
        } else {
            $projets[] = $enregistre;
        }

        sauverProjets($projets);

        if ($ancienneImage !== null && $ancienneImage !== $imageFinale) {
            supprimerFichierImage($ancienneImage, $projets);
        }

        flash('succes', $mode === 'modifier' ? 'Projet modifié.' : 'Projet ajouté.');
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
    <title><?= $mode === 'modifier' ? 'Modifier un projet' : 'Ajouter un projet' ?> — Administration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Fugaz+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<nav class="admin-nav" aria-label="Navigation administration">
    <div class="conteneur admin-nav__interieur">
        <span class="admin-nav__titre">Admin NWS</span>
        <a class="admin-nav__lien" href="index.php">Projets</a>
        <a class="admin-nav__lien admin-nav__lien--actif" href="formulaire.php">Ajouter</a>
        <a class="admin-nav__lien" href="../index.php">Voir le site</a>
        <span class="admin-nav__espace"></span>
        <span class="admin-nav__user">Connecté : <?= e((string) $_SESSION['admin']) ?></span>
        <a class="admin-nav__lien" href="logout.php">Déconnexion</a>
    </div>
</nav>

<main class="admin conteneur">
    <div class="admin__entete">
        <h1><?= $mode === 'modifier' ? 'Modifier le projet' : 'Ajouter un projet' ?></h1>
        <div class="admin__actions">
            <a class="btn btn--ligne" href="index.php">← Retour à la liste</a>
        </div>
    </div>

    <?php if ($erreurs !== []): ?>
        <p class="message message--erreur">
            Le formulaire comporte des erreurs :
            <ul>
                <?php foreach ($erreurs as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </p>
    <?php endif; ?>

    <form class="formulaire-admin" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(jetonCsrf()) ?>">

        <div class="formulaire-admin__colonnes">
            <div>
                <fieldset class="bloc-fieldset">
                    <legend>Identité du projet</legend>

                    <p class="champ">
                        <label for="titre">Titre</label>
                        <input type="text" id="titre" name="titre" required
                               value="<?= e((string) $valeurs['titre']) ?>">
                        <?php if (isset($erreurs['titre'])): ?>
                            <p class="champ__erreur"><?= e($erreurs['titre']) ?></p>
                        <?php endif; ?>
                    </p>

                    <p class="champ">
                        <label for="etudiant">Étudiant</label>
                        <input type="text" id="etudiant" name="etudiant" required
                               value="<?= e((string) $valeurs['etudiant']) ?>">
                        <?php if (isset($erreurs['etudiant'])): ?>
                            <p class="champ__erreur"><?= e($erreurs['etudiant']) ?></p>
                        <?php endif; ?>
                    </p>

                    <div class="formulaire-admin__double">
                        <p class="champ">
                            <label for="promotion">Promotion</label>
                            <input type="text" id="promotion" name="promotion" required
                                   list="liste-promotions"
                                   value="<?= e((string) $valeurs['promotion']) ?>"
                                   placeholder="Année 1">
                            <datalist id="liste-promotions">
                                <option value="Année 1"></option>
                                <option value="Année 2"></option>
                                <option value="Année 3"></option>
                                <option value="Année 4"></option>
                                <option value="Année 5"></option>
                            </datalist>
                            <?php if (isset($erreurs['promotion'])): ?>
                                <p class="champ__erreur"><?= e($erreurs['promotion']) ?></p>
                            <?php endif; ?>
                        </p>

                        <p class="champ">
                            <label for="annee">Année de réalisation</label>
                            <input type="number" id="annee" name="annee" required
                                   min="2000" max="2100"
                                   value="<?= e((string) $valeurs['annee']) ?>">
                            <?php if (isset($erreurs['annee'])): ?>
                                <p class="champ__erreur"><?= e($erreurs['annee']) ?></p>
                            <?php endif; ?>
                        </p>
                    </div>

                    <p class="champ">
                        <label for="specialite">Spécialité</label>
                        <select id="specialite" name="specialite">
                            <?php foreach ($specialites as $s): ?>
                                <option value="<?= e($s) ?>"<?= $s === $valeurs['specialite'] ? ' selected' : '' ?>>
                                    <?= e($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($erreurs['specialite'])): ?>
                            <p class="champ__erreur"><?= e($erreurs['specialite']) ?></p>
                        <?php endif; ?>
                    </p>

                    <p class="champ">
                        <label for="type">Type de livrable</label>
                        <input type="text" id="type" name="type" required
                               value="<?= e((string) $valeurs['type']) ?>"
                               placeholder="Identité visuelle, Site web…">
                        <?php if (isset($erreurs['type'])): ?>
                            <p class="champ__erreur"><?= e($erreurs['type']) ?></p>
                        <?php endif; ?>
                    </p>

                    <p class="champ champ--case">
                        <label for="en_avant">
                            <input type="checkbox" id="en_avant" name="en_avant" value="1"
                                <?= $valeurs['enAvant'] ? 'checked' : '' ?>>
                            Mettre à la une
                        </label>
                    </p>
                </fieldset>
            </div>

            <div>
                <fieldset class="bloc-fieldset">
                    <legend>Contenu</legend>

                    <p class="champ">
                        <label for="description">Description <span class="champ__aide">(carte de la galerie)</span></label>
                        <textarea id="description" name="description" required><?= e((string) $valeurs['description']) ?></textarea>
                        <?php if (isset($erreurs['description'])): ?>
                            <p class="champ__erreur"><?= e($erreurs['description']) ?></p>
                        <?php endif; ?>
                    </p>

                    <p class="champ">
                        <label for="contexte">Contexte &amp; objectifs <span class="champ__aide">(page détail)</span></label>
                        <textarea id="contexte" name="contexte" required><?= e((string) $valeurs['contexte']) ?></textarea>
                        <?php if (isset($erreurs['contexte'])): ?>
                            <p class="champ__erreur"><?= e($erreurs['contexte']) ?></p>
                        <?php endif; ?>
                    </p>

                    <p class="champ">
                        <label for="outils">Outils &amp; technologies <span class="champ__aide">(séparés par des virgules)</span></label>
                        <input type="text" id="outils" name="outils"
                               value="<?= e((string) $valeurs['outilsTexte']) ?>"
                               placeholder="HTML, CSS, Figma">
                    </p>
                </fieldset>

                <fieldset class="bloc-fieldset">
                    <legend>Image de couverture</legend>

                    <?php if ($valeurs['image'] !== ''): ?>
                        <p>
                            <img src="../<?= e($valeurs['image']) ?>" alt="Image actuelle"
                                 width="220" height="138"
                                 style="border-radius:12px;object-fit:cover;display:block">
                            <span class="champ__aide">Image actuelle</span>
                        </p>
                    <?php endif; ?>

                    <p class="champ">
                        <label for="image">Nouvelle image <span class="champ__aide">(jpg, png, webp ou gif — 5 Mo max, laisser vide pour conserver)</span></label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <?php if (isset($erreurs['image'])): ?>
                            <p class="champ__erreur"><?= e($erreurs['image']) ?></p>
                        <?php endif; ?>
                    </p>
                </fieldset>

                <fieldset class="bloc-fieldset">
                    <legend>Lien externe (optionnel)</legend>

                    <div class="formulaire-admin__double">
                        <p class="champ">
                            <label for="lien_type">Type</label>
                            <select id="lien_type" name="lien_type">
                                <option value=""<?= $valeurs['lienType'] === '' ? ' selected' : '' ?>>Aucun lien</option>
                                <option value="portfolio"<?= $valeurs['lienType'] === 'portfolio' ? ' selected' : '' ?>>Voir le portfolio</option>
                                <option value="github"<?= $valeurs['lienType'] === 'github' ? ' selected' : '' ?>>Voir le code sur GitHub</option>
                            </select>
                        </p>

                        <p class="champ">
                            <label for="lien_url">URL</label>
                            <input type="url" id="lien_url" name="lien_url"
                                   value="<?= e((string) $valeurs['lienUrl']) ?>"
                                   placeholder="https://…">
                        </p>
                    </div>
                    <?php if (isset($erreurs['lien'])): ?>
                        <p class="champ__erreur"><?= e($erreurs['lien']) ?></p>
                    <?php endif; ?>
                </fieldset>
            </div>
        </div>

        <div class="formulaire-admin__actions">
            <button class="btn btn--orange" type="submit">
                <?= $mode === 'modifier' ? 'Enregistrer les modifications' : 'Ajouter le projet' ?>
            </button>
            <a class="btn btn--ligne" href="index.php">Annuler</a>
        </div>
    </form>
</main>
</body>
</html>
