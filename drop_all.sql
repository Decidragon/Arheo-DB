-- Run this FIRST to completely wipe the database, then run tabele.sql

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
