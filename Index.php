<?php
require_once 'db.php';
$pdo = get_db();

$nr_artefacte = $pdo->query('SELECT COUNT(*) FROM artefacte')->fetchColumn();
$nr_locatii   = $pdo->query('SELECT COUNT(*) FROM locatii')->fetchColumn();
$nr_perioade  = $pdo->query('SELECT COUNT(*) FROM perioade_istorice')->fetchColumn();
$nr_surse     = $pdo->query('SELECT COUNT(*) FROM surse_bibliografice')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arheo DB — Patrimoniu Arheologic</title>
    <link rel="stylesheet" type="text/css" href="mystyle.css" />
</head>
<body>

<nav>
    <div class="container inner">
        <a class="nav-brand" href="index.php">⚱ Arheo DB</a>
        <ul class="nav-links">
            <li><a href="index.php" class="active">Acasă</a></li>
            <li><a href="artefacte.php">Bodrog</a></li>
            <li><a href="artefacte.php?dataset=lumeanou">Lumea Nouă</a></li>
            <li><a href="artefacte.php?dataset=lumeanou1">Lumea Nouă 1</a></li>
            <li><a href="surse.php">Surse</a></li>
        </ul>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h1>Patrimoniu Arheologic</h1>
        <p>Bază de date pentru artefacte eneolitice</p>
    </div>
</div>

<div class="container">
    <div class="flex gap-2 mb-2" style="flex-wrap:wrap;margin-bottom:28px">
        <?php
        $stats = [
            ['label'=>'Artefacte totale',     'val'=>$nr_artefacte, 'icon'=>'🏺'],
            ['label'=>'Situri / locații',     'val'=>$nr_locatii,   'icon'=>'📍'],
            ['label'=>'Perioade / culturi',   'val'=>$nr_perioade,  'icon'=>'⏳'],
            ['label'=>'Surse bibliografice',  'val'=>$nr_surse,     'icon'=>'📚'],
        ];
        foreach ($stats as $s): ?>
        <div class="stat-card" style="flex:1;min-width:180px">
            <div class="stat-label"><?= $s['icon'] ?> <?= $s['label'] ?></div>
            <div class="stat-value"><?= $s['val'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <h2 style="margin-bottom:14px;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.07em;font-weight:600">Liste de artefacte</h2>

    <div class="culture-tabs mb-2" style="margin-bottom:28px">
        <a href="artefacte.php" class="culture-tab">
            <span class="culture-tab-icon">🏺</span>
            <div>
                <div class="culture-tab-label">Bodrog</div>
                <div style="font-size:12px;color:var(--muted);margin-top:2px">Cultura eneolitică Bodrog</div>
            </div>
        </a>
        <a href="artefacte.php?dataset=lumeanou" class="culture-tab">
            <span class="culture-tab-icon">🏺</span>
            <div>
                <div class="culture-tab-label">Lumea Nouă</div>
                <div style="font-size:12px;color:var(--muted);margin-top:2px">gr. Lumea Nouă / Iclod / Suplac</div>
            </div>
        </a>
        <a href="artefacte.php?dataset=lumeanou1" class="culture-tab">
            <span class="culture-tab-icon">🏺</span>
            <div>
                <div class="culture-tab-label">Lumea Nouă 1</div>
                <div style="font-size:12px;color:var(--muted);margin-top:2px">Piscolt / Tisa / gr. Suplac</div>
            </div>
        </a>
    </div>

    <div style="display:flex;gap:10px">
        <a href="adauga_artefact.php" class="btn btn-primary">+ Adaugă artefact</a>
        <a href="surse.php" class="btn btn-secondary">Surse bibliografice</a>
    </div>

</div>

<footer>
    <div class="container">
        <p>Arheo DB &mdash; Bază de date arheologică</p>
    </div>
</footer>

</body>
</html>
