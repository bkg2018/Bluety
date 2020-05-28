# Assemblage

## Bus Daughter

Pièces :

* Circuit Bus Daughter
* Condensateur 10nF
* Header 40P anglre droit
* Header 2x12P angle droit

1. Souder le condensateur C9 (100 nF). Couper les pattes au dos.

2. Souder le header 2x12P P3. Attention à souder le petit côté coudé.

3. Souder le header 40P P2. Souder le petit côté. Ne pas trop chauffer sinon le plastique ramollit trop et les pattes pivotent. Si cela arrive, rechauffer la soudure et remettre la patte en position à l'aide d'une pince.

4. Nettoyer à l'alcool isopropanol.

## Blink'n Switch

Pièces nécessaires :

* Circuit Blink'n Switch
* 4 Supports 12mm avec vis et écrou (il y en a 5 au total)
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

Placer le **dos** visible, (le trou du bouton marche arret en bas à gauche)

2. Souder les 10 condensateurs 100nF C1 à C4 et C6 à C11
4. Souder les 8 supports 20P et les 2 supports 14P, encoches vers le *haut* ou la *gauche* selon le cas
3. Souder les 3 réseaux de résistance **10K** RN1 RN4 RN7
   - Attention à *ne pas confondre avec les 470* : le composant est marqué A103J (103 signifie 10 x 10^3)
   - Attention au sens : aligner le point du composant avec le petit carré imprimé à gauche sur le circuit
4. Souder les 5 réseaux de résistance **470** RN2 RN3 RN5 RN6 RN8
   - le composant est marqué A471J (471 signifie 47 x 10^1)
   - Attention au sens : aligner le point du composant avec le petit carré imprimé à gauche sur le circuit

Il reste à souder le header P1 et le condensateur C5 mais ceci sera fait en dernier car ils gêneraient la suite de l'assemblage.

Retourner la carte pour voir l'**avant**, trou du bouton power en bas à droite

Visser 4 supports sur la carte : support sur le dessus, boulon au dos. Ils vont servir à un assemblage provisoire plusieurs fois afin de bien aligner les composant de la façade.


== souder les headers I/O et power à longues pattes ici ? 


Préparer les interrupteurs : vous devez avoir 16 interrupteurs à trois positions ON-OFF-ON et 4 interrupteurs à deux positions ON-OFF ou ON-ON. Séparez les tas pour ne pas vous tromper. Le kit présente normalement deux couleurs différentes mais cela peut dépendre du stock.

Positionner les 8 interrupteurs à **trois positions ON-OFF-ON** du port A, SW1 à SW8. Attention à placer l'encoche située sur le pas de vis vers le bas pour chaque interrupteur sinonles boutons ne seront pas bien alignés.

Positionner les 8 interrupteurs à **trois positions ON-OFF-ON** SW17 à SW24. Même précaution pour l'encoche du pas de vis qui doit être en bas.

Positionner les 4 interrupteurs à **deux positions ON-OFF ou ON-ON** SW9, SW11, SW20, SW22, encoche du pas de vis vers le bas.

Mettre les boutons des 20 interrupteurs en position basse.

Vérifier l'alignement, glisser et fixer la façade à l'aide des vis et des 4 supports.

S'assurer que tout est toujours bien aligné et qu'aucun bouton ne s'est délogé, sinon le replacer et reprendre la fixation de la façade.

Vérifier que les interrupteurs situés les plus à droite sont bien à deux positions.

Retourner la carte, et souder un point de chacun des 20 interrupteurs. Vérifier l'alignement en retournant la carte. Si tout est correct, souder le reste des points, sinon chauffer le point de l'interrupteur mal aligné pour le replacer correctement. Evitez de déssouder complètement pour enlever l'interrupteur, vous risquez d'abimer le métal du trou du circuit imprimé.

Les interrrupteurs entièrement soudés, remettre les interrupteurs en position basse, dévisser les 3 vis des supports et enlever la façade. Laisser les supports ils vont servir encore.

Préparez les tas de LED : 16 vertes pour les ports d'entrée, 16 jaunes pour les ports de sortie (vous pouvez inverser les couleurs selon votre goût), 8 bleues pour le port de contrôle.

=> si vous préférez avoir des LED remplaçables, vous pouvez souder des supports femelles à deux broches à la place des LEDs, mais l'alignement des LEDs à la bonne longueur sera plus compliqué. Cette possibilité est laissée à votre appréciation et les supports ne sont pas fournis dans le kit.

Placer les 16 LEDs vertes (ou jaunes) des ports d'entrée LED1 à LED8 et LED17 à LED24. La patte la plus courte est l'anode et se place en haut sur le signe (-) du circuit imprimé.

Placer les 16 LEDs jaunes (ou vertes) des ports de sortie LED9 à LED16 et LED25 à LED32. La patte la plus courte est l'anode et se place en haut sur le signe (-) du circuit imprimé.

Placer les 8 LED bleues du port de contrôle LED33 à LED40. La patte la plus courte est l'anode et se place en haut sur le signe (-) du circuit imprimé.

Vérifiez une dernière fois que les pattes les plus courtes sont bien situées sur le haut, ainsi que l'applat de la LED.

Fixer de nouveau la façade à l'aide des 4 vis.

Soulever l'ensemble : les LEDs glissent dans leur logement et se plaquent sur le circuit imprimé.

Ce moment est délicat et vous devrez peut-être vous y prendre à plusieurs fois mais normalement cela ne présente pas de grande difficulté : retourner l'ensemble pour que les LEDs glissent en sens inverse et se placent dans leur ouverture sur la façade.

Si une LED est mal positionnée, utilisez leurs pattes pour les replacer correctement.

Vérifiez bien que toutes les LEDs sont bien placées dans leur logement sur la façade en la regardant par en dessous.

Posez l'ensemble sur le plan de travail, les pattes des LEDs visibles.

Vérifiez une dernière fois que les pattes les plus courtes sont bien sur le symbole (-) du circuit. En cas d'erreur, vous devez retourner la carte, dévisser et ôter la façade, remettre la LED dans le bon sens, revisser la façade et reprendre le bon positionnement des LEDs dans les logements.

Une fois tout bien positionné et vérifié, soudez les pattes des LEDs.

Dévissez et ôtez la façade. Procédez délicatement pour ne pas plier les LEDs.

Souder les


4. Souder les deux supports CI 16P (SW10 et SW21), encoche vers la gauche
3. 

Retourner de nouveau la carte

souder le condensateur C5
souder le header 2x12P P1

Reste pour assemblage final :

5 supports 12mm pour montage sur façade
2 supports CI 16P à emboiter sur les supports de SW10 et SW11
2 Dip Switchs 8P à emboiter sur les supports SW10 et SW11
**