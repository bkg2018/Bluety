---

# Assemblage

Cette notice décrit le montage des cartes de Bluety.

---
# Bus Daughter

Pièces :

* Circuit Bus Daughter
* Condensateur 10nF
* Header 40P anglre droit
* Header 2x12P angle droit

1. Souder le condensateur C9 (100 nF). Couper les pattes au dos.

2. Souder le header 2x12P P3. Attention à souder le petit côté coudé.

3. Souder le header 40P P2. Souder le petit côté. Ne pas trop chauffer sinon le plastique ramollit trop et les pattes pivotent. Si cela arrive, rechauffer la soudure et remettre la patte en position à l'aide d'une pince.

4. Nettoyer à l'alcool isopropanol.

---

# Blink'n Switch

Pièces :

* Circuit Blink'n Switch
* Façade avant
* 5 Supports 12mm avec vis et écrou
* 2 supports CI 16P (il y en a 4 au total)
* 2 supports CI 14P
* 8 supports CI 20P
* 8 LED 3mm bleues
* 16 LED 3mm vertes
* 16 LED 3mm jaunes
* 4 interrupteurs ON/ON ou ON/OFF
* 16 interrupteurs ON-OFF-ON
* 2 headers 2P pattes de 11mm
* 4 headers 8P pattes de 11mm
* 1 header 2x12P
* 10 condensateurs 100nF (104)
* 1 condensateur électro 100uF
* 3 réseaux résistance 10K (A103J)
* 5 réseaux résistance 470 (A471J) 

## Dos du circuit imprimé (1)

Placer le **dos** visible, (le trou du bouton marche arret en bas à gauche)

2. Souder les 10 condensateurs 100nF C1 à C4 et C6 à C11
4. Souder les 8 supports 20P et les 2 supports 14P, encoches vers le *haut* ou la *gauche* selon le cas
3. Souder les 3 réseaux de résistance **10K** RN1 RN4 RN7
   - Attention à *ne pas confondre avec les 470* : le composant est marqué A103J (103 signifie 10 x 10^3)
   - Attention au sens : aligner le point du composant avec le petit carré imprimé à gauche sur le circuit
4. Souder les 5 réseaux de résistance **470** RN2 RN3 RN5 RN6 RN8
   - le composant est marqué A471J (471 signifie 47 x 10^1)
   - Attention au sens : aligner le point du composant avec le petit carré imprimé à gauche sur le circuit

Ne pas souder le header P1 et le condensateur C5 manitenant, ils gêneraient la suite de l'assemblage.

Retourner la carte pour voir l'**avant**, trou du bouton power en bas à droite

## Avant du circuit imprimé

Visser les 5 supports 12mm sur la carte : support sur le dessus, boulon au dos. Ils vont servir à un assemblage provisoire plusieurs fois afin de bien aligner les composant de la façade, donc ne les démontez pas tout le long de cette étape.

L'espace de certains boulons est restreint une fois les composants en place, aussi il est préférable de maintenir le boulon et tourner le support plutôt que l'inverse. Si un composant nécessite à un moment de tourner un boulon, dévissez légèrement le support, tournez un peu le boulon et revissez le support.

> ***Important :***
>
>Tous les éléments soudés sur l'avant apparaissent en façade à travers une ouverture, aussi une grande partie de >l'assemblage consiste à souder *une seule patte*, puis  vérifier et corriger l'alignement avant de faire la >soudure définitive des pattes restantes. Ainsi il est possible de rechauffer la soudure pour bouger l'élément en >cas de problème d'alignement, ce qui sera pratiquement impossible une fois toutes les pattes soudées.

### Supports des dip-switch

Souder *une seule patte* de chacun des 2 supports CI 16P (SW10 et SW21), encoche vers la gauche. Ils serviront de base aux DIP-Switchs de sélection du numéro de port. 

>Retournez le circuit.
>
>Emboitez un second support 16P et un DIP-Switch sur chacun des deux supports.
>
>Fixez la façade avant avec deux ou trois vis en passant les dipswitch à travers leurs ouvertures.
>
>Si un switch n'est pas bien aligné, faites chauffer la soudure de la patte et réalignez l'ensemble pour qu'il soit bien aligné avec l'ouverture de la façade.

Retournez l'ensemble et terminez la soudure des supports.

Dévissez les vis de la façade, démontez la ainsi que les ensembles support+dip-switch emboités.

### Interrupteurs

Préparer les interrupteurs : vous devez avoir 16 interrupteurs à trois positions ON-OFF-ON et 4 interrupteurs à deux positions ON-OFF ou ON-ON. Séparez les tas pour ne pas vous tromper. Le kit présente normalement deux couleurs différentes mais cela peut dépendre du stock.

Positionner les 8 interrupteurs à **trois positions ON-OFF-ON** du port A, SW1 à SW8. Attention à placer l'encoche située sur le pas de vis vers le bas *pour chaque interrupteur*. Ils doivent être tous orientés de la même façon car ils ne sont généralement pas totalement symétriques.

Positionner les 8 interrupteurs à **trois positions ON-OFF-ON** SW17 à SW24. Même précaution pour l'encoche du pas de vis qui doit être en bas.

Positionner les 4 interrupteurs à **deux positions ON-OFF ou ON-ON** SW9, SW11, SW20, SW22, encoche du pas de vis vers le bas.

>Mettre tous les boutons en position basse.
>
>Vérifier l'alignement, glisser et fixer la façade à l'aide des vis.
>
>S'assurer que tout est toujours bien aligné et qu'aucun bouton ne s'est délogé, sinon le replacer et refaire la fixation de la façade.
>
>Vérifier que les interrupteurs situés les plus à droite sont bien à deux positions et non trois.

Retourner la carte, et souder *un seul point* de chacun des 20 interrupteurs avec une petite quantité de soudure.

> Vérifier l'alignement en retournant l'ensemble. Au besoin, chauffer le point de l'interrupteur mal aligné pour le replacer correctement. 

Quant tout est bien aligné, soudez les autres pattes et remettez la soudure nécessaire sur la première. Faites ceci pour les 20 interrupteurs.

Remettre les interrupteurs en position basse, dévisser et enlever la façade.

### LEDs

Préparez les tas de LED : 16 vertes pour les ports d'entrée, 16 jaunes pour les ports de sortie (vous pouvez inverser les couleurs selon votre goût), 8 bleues pour le port de contrôle.

>si vous préférez avoir des LED remplaçables, vous pouvez souder des supports femelles à deux broches à la place des LEDs, mais l'alignement des LEDs à la bonne longueur sera plus compliqué. Cette possibilité est laissée à votre appréciation et les supports ne sont pas fournis dans le kit.

Posez le circuit imprimé sur des supports de votre choix (de préférence non métalliques) afin qu'il soit au dessus du plan de travail et permette aux LEDs de glisser dans leurs emplacements. La hauteur n'a pas beaucoup d'importance, 5 mm suffisent.

Placer les 16 LEDs vertes (ou jaunes) des ports d'entrée LED1 à LED8 et LED17 à LED24. La patte la plus courte est l'anode et se place en haut sur le signe (-) du circuit imprimé.

Placer les 16 LEDs jaunes (ou vertes) des ports de sortie LED9 à LED16 et LED25 à LED32. La patte la plus courte est l'anode et se place en haut sur le signe (-) du circuit imprimé.

Placer les 8 LED bleues du port de contrôle LED33 à LED40. La patte la plus courte est l'anode et se place en haut sur le signe (-) du circuit imprimé.

Vérifier une dernière fois que les pattes les plus courtes sont bien situées sur le haut, ainsi que l'applat de la LED.

Fixer de nouveau la façade à l'aide des 4 vis.

Soulever l'ensemble : les LEDs glissent dans leur logement et se plaquent sur le circuit imprimé.

Retourner délicatement l'ensemble pour que les LEDs glissent en sens inverse et se placent dans leur ouverture sur la façade. Une grande partie d'entre elles va s'y positionner directement, pour les autres agissez avec une pince sur les pattes pour les placer dans leur ouverture en façade.

Posez la façade sur le plan de travail.

>Si une LED est mal positionnée, utilisez ses pattes et une pince pour la replacer correctement. Procédez délicatement pour ne pas tordre les pattes.
>
>Vérifiez bien que toutes les LEDs sont  placées dans leur logement sur la façade en la regardant par en dessous. Elles doivent toutes dépasser de la même hauteur.

Vérifiez une dernière fois que les pattes les plus courtes sont bien sur le symbole (-) du circuit. En cas d'erreur, vous devez retourner la carte, dévisser et ôter la façade, remettre la LED dans le bon sens, revisser la façade et reprendre le bon positionnement des LEDs dans les logements.

Une fois tout bien positionné et vérifié, soudez les pattes des LEDs.

Dévissez et ôtez la façade. Procédez délicatement pour ne pas plier les LEDs.

### Headers I/O et Power

Positionnez les 4 connecteurs 8P à longues pattes et les 2 connecteurs 2P à longues pattes sur l'avant dur circuit imprimé.

Fixer la façade, en prenant garde aux LEDs. Ne mettez pas la vis centrale, elle gênerait le positionnement des headers.

Poser la façade arrière sur la façade avant, perpendiculairement de manière à recouvrir les ouvertures des headers. Vous pouvez placer une feuille de papier entre les deux pour ne pas risquer d'abimer leur revêtement.

Retourner délicatement l'ensemble et le poser à l'envers sur le plan de travail.

A l'aide d'une pince, placez les connecteurs dans les ouvertures de la façade. C'est un peu délicat, ne forcez pas pour ne pas tordre les pattes. Si cela arrive, démontez la façade, sortez le connecteur, redressez les pattes et recommencez l'opération.

Lorsque tout est bien en place, soudez *une patte* de chacun des 6 connecteurs. 

Retournez l'ensemble pour vérifier l'alignement. 

Si tout est corectement placé, retournez et terminez la soudure des pattes restantes.

### Dos du circuit imprimé (2)


Dévisser la façade et retourner l'ensemble.

Souder le condensateur C5, attention à la polarité la patte la plus courte est sur le (-). Couper les pattes.

Souder le connecteur mâle 2x12P P1. 

### Finition

Nettoyez soigneusement la façade avec un chiffon doux ou microfibre. n'utilisez pas de produit détergent ou alcoolique. Les produits speciaux pour écran plat sont acceptables.

Revissez la façade.

Emboiter les dip-switch sur leurs supports 16P puis emboiter sur le circuit imprimé à travers l'ouverture en façade.


## Ecran LCD 4x20

Fixer les quatre supports à l'écran, support sur l'avant et boulon au dos.

Visser l'ensemble sur la façade à l'aide des vis.

# Façade avant

Visser le bouton d'alimentation.

Le cable et son branchement auront lieu une fois la façade et l'ordinateur fixés au boitier.

# Façade arrière

Connecteur HDMI

USB Clavier

Interrupteurs FLASH

Power connector

Bouton reset


# Branchements

BusDaughter => Blink'n Switch

Façade arrière

Bouton Power