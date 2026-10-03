<?php
require_once 'db.php';
$pdo = get_db();

$dataset  = $_GET['dataset'] ?? $_POST['dataset'] ?? '';
$msg      = '';
$error    = '';
$artefact = null;

$search_id = trim($_GET['id'] ?? $_POST['search_id'] ?? '');

if ($search_id !== '') {
    $stmt = $pdo->prepare("
        SELECT a.*, p.epoca, p.cultura, l.localitate, l.punctul
        FROM artefacte a
        LEFT JOIN perioade_istorice p ON p.id_perioada = a.id_perioada
        LEFT JOIN locatii l           ON l.id_locatie  = a.id_locatie
        WHERE a.id_original = ? OR a.id_artefact = ?
    ");
    $stmt->execute([$search_id, $search_id]);
    $artefact = $stmt->fetch();
    if (!$artefact) $error = "Niciun artefact găsit cu ID-ul \"{$search_id}\".";
}

// Confirmed delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $id = (int)$_POST['id_artefact'];
    $pdo->prepare("DELETE FROM artefacte_surse WHERE id_artefact = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM artefacte WHERE id_artefact = ?")->execute([$id]);
    $msg      = "Artefactul a fost șters cu succes.";
    $artefact = null;
    $search_id = '';
}

$back_url = $dataset ? "artefacte.php?dataset={$dataset}" : 'artefacte.php';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Șterge artefact — Arheo DB</title>
    <link rel="stylesheet" type="text/css" href="mystyle.css" />
</head>
<body>

<nav>
    <div class="container inner">
        <a class="nav-brand" href="index.php">⚱ Arheo DB</a>
        <ul class="nav-links">
            <li><a href="index.php">Acasă</a></li>
            <li><a href="artefacte.php">Bodrog</a></li>
            <li><a href="artefacte.php?dataset=lumeanou">Lumea Nouă</a></li>
            <li><a href="artefacte.php?dataset=lumeanou1">Lumea Nouă 1</a></li>
            <li><a href="surse.php">Surse</a></li>
        </ul>
    </div>
</nav>

<div class="page-header">
    <div class="container flex-between" style="flex-wrap:wrap;gap:12px">
        <div>
            <h1>Șterge artefact</h1>
            <p>Caută artefactul după ID și confirmă ștergerea</p>
        </div>
        <a href="<?= $back_url ?>" class="btn btn-secondary">← Înapoi la listă</a>
    </div>
</div>

<div class="container" style="max-width:620px">

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?>
            <a href="<?= $back_url ?>" style="margin-left:12px">← Înapoi la listă</a>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Search -->
    <div class="card">
        <form method="GET" action="" style="display:flex;gap:10px;align-items:flex-end">
            <?php if ($dataset): ?><input type="hidden" name="dataset" value="<?= htmlspecialchars($dataset) ?>"><?php endif; ?>
            <div class="form-group" style="margin:0;flex:1">
                <label>Număr artefact (ID)</label>
                <input type="text" name="id" value="<?= htmlspecialchars($search_id) ?>" placeholder="Ex: 42">
            </div>
            <button type="submit" class="btn btn-primary">Caută</button>
        </form>
    </div>

    <?php if ($artefact): ?>
    <!-- Confirmation card -->
    <div class="card" style="border-color:#fca5a5">
        <h2 style="color:#dc2626;margin-bottom:16px">⚠ Confirmare ștergere</h2>

        <table style="width:100%;margin-bottom:20px">
            <tr><td class="text-muted" style="width:140px;padding:6px 0">ID</td><td><?= $artefact['id_original'] ?? $artefact['id_artefact'] ?></td></tr>
            <tr><td class="text-muted" style="padding:6px 0">Denumire</td><td><strong><?= htmlspecialchars($artefact['denumire']) ?></strong></td></tr>
            <tr><td class="text-muted" style="padding:6px 0">Cultură</td><td><?= htmlspecialchars($artefact['cultura'] ?? '—') ?></td></tr>
            <tr><td class="text-muted" style="padding:6px 0">Localitate</td><td><?= htmlspecialchars($artefact['localitate'] ?? '—') ?></td></tr>
            <tr><td class="text-muted" style="padding:6px 0">Punct</td><td><?= htmlspecialchars($artefact['punctul'] ?? '—') ?></td></tr>
            <tr><td class="text-muted" style="padding:6px 0">Stare</td><td><?= htmlspecialchars($artefact['stare']) ?></td></tr>
        </table>

        <p style="color:#dc2626;font-size:13px;margin-bottom:16px">
            Această acțiune este <strong>permanentă</strong> și nu poate fi anulată.
        </p>

        <form method="POST" action="">
            <input type="hidden" name="confirm_delete" value="1">
            <input type="hidden" name="id_artefact" value="<?= $artefact['id_artefact'] ?>">
            <input type="hidden" name="dataset" value="<?= htmlspecialchars($dataset) ?>">
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-danger" style="background:#dc2626;color:#fff;border-color:#dc2626">
                    🗑 Șterge definitiv
                </button>
                <a href="<?= $back_url ?>" class="btn btn-secondary">Anulează</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

</div>

<footer><div class="container"><p>Arheo DB &mdash; Bază de date arheologică</p></div></footer>
</body>
</html>
