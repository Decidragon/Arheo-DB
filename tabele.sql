
DROP VIEW  IF EXISTS v_complexe_detaliu     CASCADE;
DROP VIEW  IF EXISTS v_surse_bibliografice  CASCADE;
DROP VIEW  IF EXISTS v_perioade_sumar       CASCADE;
DROP VIEW  IF EXISTS v_statistici_situri    CASCADE;
DROP VIEW  IF EXISTS v_artefacte_complet    CASCADE;
DROP TABLE IF EXISTS artefacte_surse        CASCADE;
DROP TABLE IF EXISTS artefacte              CASCADE;
DROP TABLE IF EXISTS complexe               CASCADE;
DROP TABLE IF EXISTS surse_bibliografice    CASCADE;
DROP TABLE IF EXISTS perioade_istorice      CASCADE;
DROP TABLE IF EXISTS locatii                CASCADE;
DROP TABLE IF EXISTS tari                   CASCADE;

CREATE TABLE tari (
    id_tara      SERIAL PRIMARY KEY,
    cod_tara     CHAR(1)      NOT NULL UNIQUE,
    nume_tara    VARCHAR(60)  NOT NULL UNIQUE
);

CREATE TABLE locatii (
    id_locatie   SERIAL PRIMARY KEY,
    localitate   VARCHAR(100) NOT NULL,
    punctul      VARCHAR(100),
    sat          VARCHAR(100),
    provincia    VARCHAR(50),
    id_tara      INT NOT NULL REFERENCES tari(id_tara) ON DELETE RESTRICT,
    UNIQUE (localitate, punctul)
);

CREATE TABLE perioade_istorice (
    id_perioada  SERIAL PRIMARY KEY,
    epoca        VARCHAR(20)  NOT NULL,
    cultura      VARCHAR(60)  NOT NULL,
    UNIQUE (epoca, cultura)
);

CREATE TABLE surse_bibliografice (
    id_sursa     SERIAL PRIMARY KEY,
    cod_sursa    VARCHAR(60)  NOT NULL UNIQUE
);

CREATE TABLE complexe (
    id_complex   SERIAL PRIMARY KEY,
    id_locatie   INT NOT NULL REFERENCES locatii(id_locatie) ON DELETE CASCADE,
    cod_complex  VARCHAR(20)  NOT NULL,
    UNIQUE (id_locatie, cod_complex)
);

CREATE TABLE artefacte (
    id_artefact          SERIAL PRIMARY KEY,
    id_original          INT UNIQUE,
    denumire             VARCHAR(100) NOT NULL,
    id_perioada          INT REFERENCES perioade_istorice(id_perioada) ON DELETE SET NULL,
    id_locatie           INT REFERENCES locatii(id_locatie)            ON DELETE SET NULL,
    id_complex           INT REFERENCES complexe(id_complex)           ON DELETE SET NULL,
    id_artefact_parinte  INT REFERENCES artefacte(id_artefact)         ON DELETE SET NULL,
    sectiunea            VARCHAR(50),
    caroul               VARCHAR(50),
    adincimea            VARCHAR(50),
    citare_completa      TEXT,
    nr_inventar          VARCHAR(50),
    originea             VARCHAR(100),
    datarea              VARCHAR(100),
    alte_coduri          VARCHAR(100),
    cod_zoia             VARCHAR(50),
    sistem_cro           VARCHAR(50),
    tip_forma            VARCHAR(50),
    var_forma            VARCHAR(50),
    tipologia            VARCHAR(100),
    alt_nume             VARCHAR(100),
    cantitate            INT,
    nr_buza              INT,
    tip_buza             VARCHAR(50),
    dim_buza             VARCHAR(50),
    nr_git               INT,
    dim_git              VARCHAR(50),
    nr_umar              INT,
    tip_umar             VARCHAR(50),
    dim_umar             VARCHAR(50),
    nr_burta             INT,
    tip_burta            VARCHAR(50),
    dim_burta            VARCHAR(50),
    nr_toarta            INT,
    tip_toarta           VARCHAR(50),
    nr_picior            INT,
    tip_picior           VARCHAR(50),
    dim_picior           VARCHAR(50),
    tehn_ornam           VARCHAR(100),
    tip_ornament         VARCHAR(100),
    var_ornam            VARCHAR(100),
    fond_de_pi           VARCHAR(100),
    tip_semne            VARCHAR(100),
    cul_exteri           VARCHAR(50),
    cul_interi           VARCHAR(50),
    cul_pictur           VARCHAR(50),
    amestec              VARCHAR(100),
    netezire             VARCHAR(100),
    ardere               VARCHAR(100),
    factura              VARCHAR(100),
    lungime              NUMERIC(8,2),
    latime               NUMERIC(8,2),
    inaltime             NUMERIC(8,2),
    nr_fragmente         INT CHECK (nr_fragmente > 0),
    stare                VARCHAR(20) NOT NULL DEFAULT 'nedeterminat'
                         CHECK (stare IN ('intreg','fragmentar','nedeterminat')),
    categorie            VARCHAR(50),
    domfunct             VARCHAR(100),
    sectfunct            VARCHAR(100),
    folosire             VARCHAR(100),
    detinator            VARCHAR(100),
    stratigraf           TEXT,
    nivel                VARCHAR(20),
    legaturi             TEXT,
    raspandire           TEXT,
    asocierea            TEXT,
    nr_proba_c           VARCHAR(50),
    observatii           TEXT,
    scos                 BOOLEAN DEFAULT FALSE,
    faza                 VARCHAR(20),
    etapa                VARCHAR(20),
    descriere            TEXT,
    legaturi_culturale   TEXT,
    dataset              VARCHAR(20)
);

CREATE TABLE artefacte_surse (
    id_artefact  INT NOT NULL REFERENCES artefacte(id_artefact)        ON DELETE CASCADE,
    id_sursa     INT NOT NULL REFERENCES surse_bibliografice(id_sursa) ON DELETE CASCADE,
    PRIMARY KEY (id_artefact, id_sursa)
);

CREATE OR REPLACE VIEW v_artefacte_complet AS
SELECT
    a.id_artefact, a.id_original, a.denumire,
    p.epoca, p.cultura,
    l.localitate, l.punctul, l.provincia,
    t.nume_tara, c.cod_complex,
    a.faza, a.etapa, a.nivel,
    a.sectiunea, a.caroul, a.adincimea,
    a.nr_fragmente, a.stare,
    a.tip_forma, a.var_forma,
    a.nr_buza, a.tip_buza, a.dim_buza,
    a.nr_git, a.dim_git,
    a.nr_umar, a.tip_umar, a.dim_umar,
    a.nr_burta, a.tip_burta, a.dim_burta,
    a.nr_toarta, a.tip_toarta,
    a.nr_picior, a.tip_picior, a.dim_picior,
    a.tehn_ornam, a.tip_ornament, a.var_ornam, a.fond_de_pi,
    a.cul_exteri, a.cul_interi, a.cul_pictur,
    a.amestec, a.netezire, a.ardere, a.factura,
    a.lungime, a.latime, a.inaltime,
    a.categorie, a.domfunct, a.sectfunct,
    a.stratigraf, a.legaturi, a.raspandire,
    a.citare_completa, a.nr_inventar, a.observatii,
    a.dataset
FROM artefacte a
LEFT JOIN perioade_istorice p ON p.id_perioada = a.id_perioada
LEFT JOIN locatii l           ON l.id_locatie  = a.id_locatie
LEFT JOIN tari t              ON t.id_tara     = l.id_tara
LEFT JOIN complexe c          ON c.id_complex  = a.id_complex;

CREATE OR REPLACE VIEW v_statistici_situri AS
SELECT
    l.localitate, l.punctul, l.provincia,
    COUNT(a.id_artefact)                                        AS total_artefacte,
    COUNT(a.id_artefact) FILTER (WHERE a.stare = 'fragmentar')  AS fragmentare,
    COUNT(a.id_artefact) FILTER (WHERE a.stare = 'intreg')      AS intregi,
    COUNT(DISTINCT a.id_perioada)                               AS nr_perioade,
    COUNT(DISTINCT a.id_complex)                                AS nr_complexe
FROM locatii l
LEFT JOIN artefacte a ON a.id_locatie = l.id_locatie
GROUP BY l.id_locatie, l.localitate, l.punctul, l.provincia;

CREATE OR REPLACE VIEW v_perioade_sumar AS
SELECT
    p.epoca, p.cultura,
    COUNT(a.id_artefact)          AS nr_artefacte,
    COUNT(DISTINCT a.id_locatie)  AS nr_situri,
    ROUND(AVG(a.nr_fragmente), 2) AS medie_fragmente
FROM perioade_istorice p
LEFT JOIN artefacte a ON a.id_perioada = p.id_perioada
GROUP BY p.id_perioada, p.epoca, p.cultura;

CREATE OR REPLACE VIEW v_surse_bibliografice AS
SELECT
    s.cod_sursa,
    COUNT(asu.id_artefact) AS nr_artefacte_citate
FROM surse_bibliografice s
LEFT JOIN artefacte_surse asu ON asu.id_sursa = s.id_sursa
GROUP BY s.id_sursa, s.cod_sursa;

CREATE OR REPLACE VIEW v_complexe_detaliu AS
SELECT
    l.localitate, l.punctul, c.cod_complex,
    COUNT(a.id_artefact)                  AS nr_artefacte,
    string_agg(DISTINCT a.denumire, ', ') AS tipuri_artefacte
FROM complexe c
JOIN locatii l        ON l.id_locatie = c.id_locatie
LEFT JOIN artefacte a ON a.id_complex = c.id_complex
GROUP BY c.id_complex, l.localitate, l.punctul, c.cod_complex;
