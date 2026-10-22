# Changelog

Tous les changements notables de ce bundle sont documentés dans ce fichier.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).

## [Unreleased]

### Changed

- Page de réglages refaite sur le modèle des réglages Owner : grille à deux colonnes, interrupteurs pour l'activation et les shifts ouverts, horaires côte à côte, jours de la semaine en pastilles, PDF d'essai dans sa propre carte.

## [1.0.0] - 2026-10-22

### Added

- Première version : fenêtre « Les shifts de demain sont-ils corrects ? » avant l'heure limite (aperçu, soumettre / modifier / plus tard), validation par un détenteur de `planning_check.approve` avec envoi du PDF au patron, refus avec motif, renvoi d'une version mise à jour, escalade à l'échéance (tâche `planning-check-escalate` ou repli à la première page), historique, réglages par magasin, notifications enrichies. Contrat de bundle `^1.27`.
