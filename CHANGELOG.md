# Changelog

Tous les changements notables de ce bundle sont documentés dans ce fichier.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).

## [Unreleased]

### Added

- **Renvoi à la demande d'un PDF régénéré** (historique du magasin) : un bouton « Générer un nouveau PDF et l'envoyer » est proposé pour tout planning validé, avec confirmation, sans attendre que le bundle détecte un changement. Il génère le PDF à partir des shifts actuels et l'envoie comme version mise à jour, ce qui permet de renvoyer une modification d'horaire faite après la validation. Le bouton principal « Renvoyer une version mise à jour » reste affiché quand un changement est détecté. Même droit d'approbation qu'avant ; `tests/Views/HistoryResendButtonTest.php`.

## [1.0.0] - 2026-10-22

### Added

- Première version : fenêtre « Les shifts de demain sont-ils corrects ? » avant l'heure limite (aperçu, soumettre / modifier / plus tard), validation par un détenteur de `planning_check.approve` avec envoi du PDF au patron, refus avec motif, renvoi d'une version mise à jour, escalade à l'échéance (tâche `planning-check-escalate` ou repli à la première page), historique, réglages par magasin, notifications enrichies. Contrat de bundle `^1.27`.
