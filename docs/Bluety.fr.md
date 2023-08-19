<A id="top">

[<img src="https://flagcdn.com/256x192/fr.png" width="25" height="19">](Bluety.fr.md)
[<img src="https://flagcdn.com/256x192/gb.png" width="25" height="19">](Bluety.md)

# Bluety - Panneaux avec contrôles E/S pour ordinateurs Z-80<A id="a1"></A>

Bluety est un ensemble de circuits imprimés pour un boîtier destiné à recevoir un ordinateur rétro 
basé sur le Z-80 de la famille RC2014, en particulier le kit SC126 de Stephen S. Cousins et le RC2014 Pro
de Spencer Owens.

<img src="Pictures/finished.jpg" width="25%" alt="Bluety terminé" />

## Sommaire<A id="toc"></A>

- [Bluety - Panneaux avec contrôles E/S pour ordinateurs Z-80](<#a1>)
- [Présentation](<01-Introduction.fr.md#a2>)
  - [Contenu](<01-Introduction.fr.md#a3>)
  - [Extension de bus](<01-Introduction.fr.md#a4>)
  - [Interrupteur marche/arrêt SC126](<01-Introduction.fr.md#a5>)
- [Liste des composants](<02-Components.fr.md#a6>)
  - [Boitier](<02-Components.fr.md#a7>)
  - [Cartes et circuits imprimés](<02-Components.fr.md#a8>)
  - [Composants](<02-Components.fr.md#a9>)
  - [Connecteurs et supports](<02-Components.fr.md#a10>)
  - [Interrupteurs](<02-Components.fr.md#a11>)
  - [Boutons](<02-Components.fr.md#a12>)
  - [Visserie](<02-Components.fr.md#a13>)
  - [Câbles](<02-Components.fr.md#a14>)
  - [Conclusion](<02-Components.fr.md#a15>)
- [Préparatifs](<03-Preparing.fr.md#a16>)
  - [Boitier](<03-Preparing.fr.md#a17>)
  - [Inventaire des outils](<03-Preparing.fr.md#a18>)
  - [Conseils](<03-Preparing.fr.md#a19>)
- [Carte Bus Daughter](<04-Assembling Bus Daughter.fr.md#a20>)
- [Carte Blink'n Switch](<05-Assembling Blink'n Switch.fr.md#a21>)
  - [Arrière (partie 1) : supports et composants](<05-Assembling Blink'n Switch.fr.md#a22>)
  - [Avant : supports des dip-switch](<05-Assembling Blink'n Switch.fr.md#a23>)
  - [Avant : connecteurs E/S et Alimentation](<05-Assembling Blink'n Switch.fr.md#a24>)
  - [Avant : interrupteurs](<05-Assembling Blink'n Switch.fr.md#a25>)
  - [Avant : LEDs](<05-Assembling Blink'n Switch.fr.md#a26>)
  - [Arrière : composants (partie 2)](<05-Assembling Blink'n Switch.fr.md#a27>)
  - [Finition](<05-Assembling Blink'n Switch.fr.md#a28>)
- [Façade avant](<06-Assembling Front Panel.fr.md#a29>)
  - [Ecran LCD 4x20](<06-Assembling Front Panel.fr.md#a30>)
  - [Bouton d'alimentation](<06-Assembling Front Panel.fr.md#a31>)
- [Façade arrière](<07-Assembling Back Panel.fr.md#a32>)
  - [Connecteur HDMI](<07-Assembling Back Panel.fr.md#a33>)
  - [Interrupteurs ROM Select et Protect](<07-Assembling Back Panel.fr.md#a34>)
- [Installation et branchements](<08-Installing and Wiring.fr.md#a35>)
  - [Installation SC126 / RC2014](<08-Installing and Wiring.fr.md#a36>)
  - [Branchement BusDaughter Blink'n Switch](<08-Installing and Wiring.fr.md#a37>)
  - [Façade avant](<08-Installing and Wiring.fr.md#a38>)
  - [Façade arrière](<08-Installing and Wiring.fr.md#a39>)
    - [HDMI (RC2014, SC126)](<08-Installing and Wiring.fr.md#a40>)
    - [Interrupteurs ROM (SC126 seulement)](<08-Installing and Wiring.fr.md#a41>)
    - [RESET](<08-Installing and Wiring.fr.md#a42>)
    - [SC126](<08-Installing and Wiring.fr.md#a43>)
    - [RC2014](<08-Installing and Wiring.fr.md#a44>)
  - [Bouton alimentation](<08-Installing and Wiring.fr.md#a45>)
    - [SC126](<08-Installing and Wiring.fr.md#a46>)
    - [RC2014](<08-Installing and Wiring.fr.md#a47>)
- [Utilisation](<09-Using.fr.md#a48>)
  - [Port de contrôle (13/0D)](<09-Using.fr.md#a49>)
  - [Ports d'entrée sortie A et B](<09-Using.fr.md#a50>)
    - [Lire sur le port A ou B](<09-Using.fr.md#a51>)
    - [Ecrire sur le port A ou B](<09-Using.fr.md#a52>)
  - [Ecran LCD : Interface I2C (SC126)](<09-Using.fr.md#a53>)
  - [Ecran LCD : Interface parallèle (SC126,RC2014 Pro)](<09-Using.fr.md#a54>)

[^](#top)
[<img src="https://flagcdn.com/256x192/fr.png" width="25" height="19">](Bluety.fr.md)
[<img src="https://flagcdn.com/256x192/gb.png" width="25" height="19">](Bluety.md)
