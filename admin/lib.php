<?php
declare(strict_types=1);

session_start();

const FICHIER_DONNEES = __DIR__ . '/../donnees.php';
const DOSSIER_IMAGES  = __DIR__ . '/../img';

const ADMIN_PSEUDO = 'admin';
const ADMIN_HASH   = '$2y$10$AQiI.2FsdqEtRXSzuYePou0Q7Fcsy3Do9ypSOWfrDSJ1RHSOyk716';

function e(string $texte): string
{
    return htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
}

function estConnecte(): bool
{
    return !empty($_SESSION['admin']);
}

function protegerAdmin(): void
{
    if (!estConnecte()) {
        header('Location: login.php');
        exit;
    }
}

function jetonCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verifierCsrf(): void
{
    $recu    = (string) ($_POST['csrf'] ?? '');
    $attendu = (string) ($_SESSION['csrf'] ?? '');
    if ($attendu === '' || !hash_equals($attendu, $recu)) {
        http_response_code(403);
        exit('Jeton de sécurité invalide (CSRF).');
    }
}

function chargerProjets(): array
{
    $projets = [];
    require FICHIER_DONNEES;
    return $projets;
}

function sauverProjets(array $projets): void
{
    $export  = var_export($projets, true);
    $fonction = <<<'PHP'

function projetParId(int $id): ?array
{
    global $projets;
    foreach ($projets as $projet) {
        if ($projet['id'] === $id) {
            return $projet;
        }
    }
    return null;
}

PHP;
    file_put_contents(FICHIER_DONNEES, "<?php\n\$projets = " . $export . ";\n" . $fonction);
}

function prochainId(array $projets): int
{
    $ids = array_column($projets, 'id');
    return $ids === [] ? 1 : max($ids) + 1;
}

function slug(string $texte): string
{
    $texte = mb_strtolower(trim($texte), 'UTF-8');
    $texte = preg_replace('/[^a-z0-9]+/', '-', $texte) ?? '';
    return trim($texte, '-') ?: 'projet';
}

function imageUtilisee(string $chemin, array $projets, ?int $saufId = null): bool
{
    foreach ($projets as $projet) {
        if ($saufId !== null && (int) $projet['id'] === $saufId) {
            continue;
        }
        if ($projet['image'] === $chemin) {
            return true;
        }
    }
    return false;
}

function supprimerFichierImage(string $chemin, array $projets): void
{
    if (!str_starts_with($chemin, 'img/') || str_contains($chemin, '..')) {
        return;
    }
    if (imageUtilisee($chemin, $projets)) {
        return;
    }
    $fichier = __DIR__ . '/../' . $chemin;
    if (is_file($fichier)) {
        @unlink($fichier);
    }
}

function flash(string $type, string $texte): void
{
    $_SESSION['flash'] = ['type' => $type, 'texte' => $texte];
}

function afficherFlash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<p class="message message--' . e($f['type']) . '">' . e($f['texte']) . '</p>';
}
