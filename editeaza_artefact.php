<?php
require_once 'db.php';
$pdo = get_db();
$dataset  = $_GET['dataset'] ?? $_POST['dataset'] ?? '';
$msg      = '';
$error    = '';
$artefact = null;
$search_id = trim($_GET['id'] ?? $_POST['search_id'] ?? '');

if ($search_id !== '') {
    $artefact = $pdo->prepare("
        SELECT a.*, p.epoca, p.cultura, l.localitate, l.punctul, c.cod_complex
        FROM artefacte a
        LEFT JOIN perioade_istorice p ON p.id_perioada = a.id_perioada
        LEFT JOIN locatii l           ON l.id_locatie  = a.id_locatie
        LEFT JOIN complexe c          ON c.id_complex  = a.id_complex
        WHERE a.id_original = ? OR a.id_artefact = ?
    ");
    $artefact->execute([$search_id, $search_id]);
    $artefact = $artefact->fetch();
    if (!$artefact) $error = "Niciun artefact găsit cu ID-ul \"{$search_id}\".";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $id       = (int)$_POST['id_artefact'];
    $denumire = trim($_POST['denumire']);
    $stare    = $_POST['stare'];
    $faza     = trim($_POST['faza'])  ?: null;
    $nivel    = trim($_POST['nivel']) ?: null;
    $nr_frag  = (int)$_POST['nr_fragmente'] ?: null;
    $citare   = trim($_POST['citare_completa']) ?: null;
    $obs      = $_POST['observatii'] ?? null;
    $cultura  = trim($_POST['cultura']);
    $epoca    = trim($_POST['epoca']);
    $per = $pdo->prepare("SELECT id_perioada FROM perioade_istorice WHERE cultura = ? AND epoca = ?");
    $per->execute([$cultura, $epoca]);
    $id_perioada = $per->fetchColumn() ?: null;
    $localitate = trim($_POST['localitate']);
    $punctul    = trim($_POST['punctul']) ?: null;
    $loc = $pdo->prepare("SELECT id_locatie FROM locatii WHERE localitate = ? AND (punctul = ? OR (punctul IS NULL AND ? IS NULL))");
    $loc->execute([$localitate, $punctul, $punctul]);
    $id_locatie = $loc->fetchColumn() ?: null;
    $pdo->prepare("
        UPDATE artefacte SET
            denumire        = ?,
            stare           = ?,
            faza            = ?,
            nivel           = ?,
            nr_fragmente    = ?,
            citare_completa = ?,
            observatii      = ?,
            id_perioada     = ?,
            id_locatie      = ?
        WHERE id_artefact = ?
    ")->execute([$denumire, $stare, $faza, $nivel, $nr_frag, $citare, $obs,
                 $id_perioada, $id_locatie, $id]);

    $msg = "Artefactul #{$id} a fost actualizat cu succes.";
    // Reload updated record
    $artefact = $pdo->prepare("
        SELECT a.*, p.epoca, p.cultura, l.localitate, l.punctul, c.cod_complex
        FROM artefacte a
        LEFT JOIN perioade_istorice p ON p.id_perioada = a.id_perioada
        LEFT JOIN locatii l           ON l.id_locatie  = a.id_locatie
        LEFT JOIN complexe c          ON c.id_complex  = a.id_complex
        WHERE a.id_artefact = ?
    ");
    $artefact->execute([$id]);
    $artefact = $artefact->fetch();
}

$culturi  = $pdo->query("SELECT DISTINCT cultura FROM perioade_istorice ORDER BY cultura")->fetchAll(PDO::FETCH_COLUMN);
$epoci    = $pdo->query("SELECT DISTINCT epoca FROM perioade_istorice ORDER BY epoca")->fetchAll(PDO::FETCH_COLUMN);
$locatii  = $pdo->query("SELECT DISTINCT localitate FROM locatii ORDER BY localitate")->fetchAll(PDO::FETCH_COLUMN);
$back_url = $dataset ? "artefacte.php?dataset={$dataset}" : 'artefacte.php';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editează artefact — Arheo DB</title>
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
            <h1>Editează artefact</h1>
            <p>Caută după numărul artefactului și modifică câmpurile dorite</p>
        </div>
        <a href="<?= $back_url ?>" class="btn btn-secondary">← Înapoi la listă</a>
    </div>
</div>

<div class="container" style="max-width:760px">

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
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
    <!-- Edit form -->
    <form method="POST" action="">
        <input type="hidden" name="save" value="1">
        <input type="hidden" name="id_artefact" value="<?= $artefact['id_artefact'] ?>">
        <input type="hidden" name="dataset" value="<?= htmlspecialchars($dataset) ?>">

        <div class="card">
            <h2 style="margin-bottom:20px">Artefact #<?= $artefact['id_original'] ?? $artefact['id_artefact'] ?></h2>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

                <div class="form-group">
                    <label>Denumire</label>
                    <input type="text" name="denumire" value="<?= htmlspecialchars($artefact['denumire']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Stare</label>
                    <select name="stare">
                        <?php foreach (['intreg','fragmentar','nedeterminat'] as $s): ?>
                            <option value="<?= $s ?>" <?= $artefact['stare'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Cultură</label>
                    <select name="cultura">
                        <?php foreach ($culturi as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $artefact['cultura'] === $c ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Epocă</label>
                    <select name="epoca">
                        <?php foreach ($epoci as $e): ?>
                            <option value="<?= htmlspecialchars($e) ?>" <?= $artefact['epoca'] === $e ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Localitate</label>
                    <select name="localitate">
                        <?php foreach ($locatii as $l): ?>
                            <option value="<?= htmlspecialchars($l) ?>" <?= $artefact['localitate'] === $l ? 'selected' : '' ?>>
                                <?= htmlspecialchars($l) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Punct</label>
                    <input type="text" name="punctul" value="<?= htmlspecialchars($artefact['punctul'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Fază</label>
                    <input type="text" name="faza" value="<?= htmlspecialchars($artefact['faza'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Nivel</label>
                    <input type="text" name="nivel" value="<?= htmlspecialchars($artefact['nivel'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Nr. fragmente</label>
                    <input type="number" name="nr_fragmente" value="<?= $artefact['nr_fragmente'] ?? '' ?>" min="1">
                </div>

                <div class="form-group">
                    <label>Sursă / Citare</label>
                    <input type="text" name="citare_completa" value="<?= htmlspecialchars($artefact['citare_completa'] ?? '') ?>">
                </div>

                <div class="form-group" style="grid-column:span 2">
                    <label>Observații</label>
                    <textarea name="observatii" rows="3"><?= htmlspecialchars($artefact['observatii'] ?? '') ?></textarea>
                </div>

            </div>

            <div style="display:flex;gap:10px;margin-top:8px">
                <button type="submit" class="btn btn-primary">💾 Salvează modificările</button>
                <a href="<?= $back_url ?>" class="btn btn-secondary">Anulează</a>
            </div>
        </div>
    </form>
    <?php endif; ?>

</div>

<footer><div class="container"><p>Arheo DB &mdash; Bază de date arheologică</p></div></footer>
</body>
</html>
