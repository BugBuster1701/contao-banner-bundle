# Upgrade Hinweise

Diese Upgrade Hinweise sind für den Umstieg von Contao 3 nach Contao 4 bei Verwendung der Erweiterung "Banner", neu: "Contao-Banner-Bundle".

1. Rückwärts inkompatible Änderungen
2. Datenbank Änderungen
3. Upgrade von Contao 3.5.x mit "Banner" nach Contao 4 mit "Contao-Banner-Bundle"


## Rückwärts inkompatible Änderungen

Es wird kein Flash Banner mehr unterstützt und somit keine Dateien mit Endung *swf* oder *swc*.


## Datenbank Änderungen

In der Tabelle tl_banner_category gab es folgende Änderungen, die jedoch nur den Debug Modus betreffen:

### Neue Spalte

* banner_expert_debug_all

### Entferne Spalten

* banner_expert_debug_tag
* banner_expert_debug_helper
* banner_expert_debug_image
* banner_expert_debug_referrer


## Upgrade von Contao 3.5.x mit "Banner" nach Contao 4 mit "Contao-Banner-Bundle"

Da es unterschiedliche Wege gibt für ein Upgrade von Contao 3 nach Contao 4, sei *ein* Weg hier als Beispiel genommen, um die Daten von der Erweiterung Banner zu erhalten. Das gilt prinzipiell auch für alle anderen Erweiterungen.

* Contao 4 wird installiert und bekommt bei Eingabe im Install Tool eine komplette Kopie der Contao 3 Datenbank.
* Im Install Tool werden Tabellen und Felder angeboten zum löschen und neu anzulegen, dabei dürfen:
  * **keine** Tabellen gelöscht werden die mit *tl_banner* anfangen und 
  * **keine** Felder gelöscht werden in der Tabelle *tl_module* die mit *banner_* beginnen.

Nach Migration von Contao 3 nach 4 durch das Install Tool (für Contao selbst), kann dann mit der Installation von Contao-Banner-Bundle (bugbuster/contao-banner-bundle) begonnen werden.
Installationsanleitung siehe [INSTALLATION_DE.md](INSTALLATION_DE.md)

Bei Aufruf vom Install Tool wird dann angeboten, die Änderungen wie oben im Abschnitt [Datenbank Änderungen](datenbank-nderungen) durchzuführen.
Das kann bedenkenlos getan werden, da diese Felder wie erwähnt lediglich Einstellungen für den Debug Modus betreffen.


