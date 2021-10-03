# II) Contenu du kit<A id="a6"></A>

Vérifiez le contenu du kit, et contactez moi en cas d'erreur. Vous pouvez aussi intervenir dans le groupe de
discussion https://groups.google.com/forum/#!forum/rc2014-z80.

Avant toute chose, une précaution importante.

<TABLE><TR><TD><img src="Pictures/attention.png" width="300px" /></TD><TD><B>Ne touchez pas les circuits intégrés et
l'écran LCD sans être équipé d'un bracelet antistatique relié à une masse</B>. De préférence, laissez les à l'abri dans
leur étui anti-statique jusqu'au moment de la fixation de la façade avant sur le boîtier pour éliminer le risque de les
endommager avec de l'électricité statique. Dans tous les cas, ne les placez pas sur le circuit avant d'avoir procédé
aux vérifications électriques.</TD></TR></TABLE>

<TABLE><TR><TD><img src="Pictures/thisway.png" alt="Vérification" width="150px" /></TD><TD> Dans le processus
d'assemblage, les étapes où vous devrez procéder à une vérification ou à un assemblage particulier seront
indiquées par ce panneau.<B>N'ignorez pas ces recommandations</B>, elles garantissent le bon résultat de l'assemblage
lors des étapes les moins évidentes.</TD></TR></TABLE>

## II-1) Cartes et circuits imprimés<A id="a7"></A>

<TABLE><TR><TD><img src="Pictures/attention.png" width="180px" /></TD><TD>Veillez à protéger les deux façades
de toute rayure, brûlure, ou projection de flux lors des soudures. Ce sont les faces visibles donc conservez
les à l'abri jusqu'à la fin de l'assemblage. Les façades ne nécessitent aucune soudure.</TD></TR></TABLE>

Les façades peuvent être légèrement incurvées mais ceci disparaîtra après fixation sur le boîtier.

NombreCarteFaçade avant"Façade avant"Façade arrière"Façade arrière" style="zoom: 33%;" />   |

## II-2) Composants<A id="a8"></A>

| Nombre | Composants                                            |                                                                          |
| ------ | ----------------------------------------------------- | -----------------------------------------------------------------------: |
| 40     | 8 LED 3mm bleues<br />16 LED 3mm vertes<br />16 LED 3mm jaunes<br /><br />Note : vous pouvez choisir la couleur des LEDs lors de votre commande. | <img src="Pictures/026-LEDs.jpg" alt="LEDs" style="zoom: 33%;" /> |
| 11     | Condensateurs céramique 100nF (marqués 104)           | <img src="Pictures/013-Capa100nF.jpg" alt="100 nF (ou 0.1 uF)" style="zoom: 33%;" /> |
| 1      | Condensateur électrolytique 100uF                     | <img src="Pictures/034-capa100uF.jpg" alt="100 uF" style="zoom: 33%;" /> |
| 3      | Réseaux résistance 10 KOhms (marqués 103)             | <img src="Pictures/042A.jpg" alt="10 Kohm" style="zoom: 33%;" />         |
| 5      | Réseaux résistance 470 Ohms (marqués 471)             | <img src="Pictures/043A.jpg" alt="470 Ohm" style="zoom: 33%;" />         |
| 10     | Circuits intégrés :<br />- 3x 74HCT273N<br />- 2x 74HCT245N<br />- 3x 74HCT688<br />- 2x 74HCT32 | <img src="Pictures/037-ics.jpg" alt="Circuits intégrés" style="zoom: 33%;" /> |
| 1      | Ecran LCD 4x20 rétroéclairé bleu avec adaptateur I2C  | <img src="Pictures/038-LCD.jpg" alt="Ecran LCD" style="zoom: 33%;" />    |

## II-3) Connecteurs et supports<A id="a9"></A>

| Nombre | Connecteurs et supports de circuits intégrés |                                                                                            |
| ------ | -------------------------------------------- | -----------------------------------------------------------------------------------------: |
| 1      | Connecteur 40P angle droit                   | <img src="Pictures/014-header40P.jpg" alt="Connecteur 40P" style="zoom: 33%;" />           |
| 1      | Connecteur 2x12P mâle angle droit            | <img src="Pictures/015-header2x12P.jpg" alt="Connecteur 2x12P" style="zoom: 33%;" />       |
| 4      | Supports CI 16P                              | <img src="Pictures/023-support16.jpg" alt="Supports CI 16 positions" style="zoom: 33%;" /> |
| 2      | Supports CI 14P                              | <img src="Pictures/024-support14.jpg" alt="Supports CI 14 positions" style="zoom: 33%;" /> |
| 8      | Supports CI 20P                              | <img src="Pictures/025-support20.jpg" alt="Supports CI 20 positions" style="zoom: 33%;" /> |
| 2      | Connecteurs femelle 2P pattes de 11mm        | <img src="Pictures/030-h2P.jpg" alt="Connecteurs 2P" style="zoom: 33%;" />                 |
| 4      | Connecteurs femelle 8P pattes de 11mm        | <img src="Pictures/031-h8P.jpg" alt="Connecteurs 8P" style="zoom: 33%;" />                 |
| 1      | Connecteur mâle 2x12P droit                  | <img src="Pictures/032-h2x12P.jpg" alt="Connecteur 2x12P" style="zoom: 33%;" />            |
| 1      | Connecteur d'alimentation                    | <img src="Pictures/039-power.jpg" alt="Connecteur alimentation" style="zoom: 33%;" />      |
| 1      | Connecteur HDMI                              | <img src="Pictures/040-hdmi.jpg" alt="Connecteur HDMI" style="zoom: 33%;" />               |

## II-4) Interrupteurs<A id="a10"></A>

Bluety est livré avec 4 interrupteurs à deux positions qui contrôlent l'allumage des 4 rangées de LEDs ainsi que 16 interrupteurs
à trois position qui contrôlent les bits des ports d'entrée. Pour éviter la confusion le kit les livre dans des couleurs
différentes mais il faudra vérifier lors de la soudure que le type de l'interrupteur à souder est bien celui attendu.
Par ailleurs ces interrupteurs ne sont pas rigoureusement symétriques et vous devrez donc les orienter tous de la même
façon pour obtenir un bon alignement : ceci sera rappelé dans les instructions de l'assemblage.

| Nombres | Interrupteurs                        |                                                                                            |
| ------- | -----------------------------------  | -----------------------------------------------------------------------------------------: |
| 4       | Interrupteurs ON/ON ou ON/OFF        | <img src="Pictures/027-ONON.jpg" alt="ON/ON" style="zoom: 33%;" />                         |
| 16      | Interrupteurs ON-OFF-ON              | <img src="Pictures/028-ONOFFFON.jpg" alt="ON/OFF/ON" style="zoom: 33%;" />                 |
| 2       | Interrupteurs dipswitch 8 positions  | <img src="Pictures/029-dipswitch.jpg" alt="Dipswitch" style="zoom: 33%;" />                |
| 1       | Micro interrupteur avec câble 2 fils | <img src="Pictures/040-switchselect.jpg" alt="Interrupteur 2 fils" style="zoom: 33%;" />   |
| 2       | Micro interrupteur avec câble 3 fils | <img src="Pictures/040-switchprotect.jpg" alt="Interrupteurs 3 fils" style="zoom: 33%;" /> |

NOTE : La couleur des interrupteurs et des câbles peut varier.

## II-5) Boutons<A id="a11"></A>

| Nombre | Boutons                      |                                                                              |
| ------ | ---------------------------- | ---------------------------------------------------------------------------: |
| 1      | Bouton Reset                 | <img src="Pictures/040-resetbtn.jpg" alt="Reset" style="zoom: 33%;" />       |
| 1      | Bouton marche/arrêt avec LED | <img src="Pictures/040-pwrbtn.jpg" alt="Bouton ON/OFF" style="zoom: 33%;" /> |

## II-6) Visserie<A id="a12"></A>

| Nombre | Visserie et Supports                              |                                                                                  |
| ------ | ------------------------------------------------- | -------------------------------------------------------------------------------: |
| 6      | Boulons M2x8 (interrupteurs façade arrière)       | <img src="Pictures/040-M2x8.jpg" alt="M2x8" style="zoom: 33%;" />                |
| 2      | Boulons M3x8 (connecteur HDMI)                    | <img src="Pictures/040-M3x8.jpg" alt="M3x8" style="zoom: 33%;" />                |
| 5      | Supports 12 mm avec vis et écrou (façade avant)    | <img src="Pictures/022A-support12.jpg" alt="Supports 12 smm" style="zoom: 33%;" /> |
| 4      | Supports 10 mm avec vis et écrou (écran LCD)       | <img src="Pictures/022B-support10.jpg" alt="Supports 10 mm" style="zoom: 33%;" /> |
| 6      | Supports 15 mm avec vis et écrou (fond de boîtier) | <img src="Pictures/022C-support15.jpg" alt="Supports 15 mm" style="zoom:33%;" />                      |

NOTE : la couleur et la matière des supports 15mm peut varier.

## II-7) Câbles<A id="a13"></A>

Bluety est livré avec des câbles sur mesure dont la longueur convient pour le boîtier de référence d'une profondeur de 190 mm
et pour les trois configurations suivantes :

- SC126 seul
- SC126 avec SC113 Tiny
- RC2014 Pro

Pour un boîtier plus profond il faudra utiliser des rallonges de type Dupont ou sertir des câbles à la bonne longueur.

| Nombre | Câbles                                       |                                                                                    |
| ------ | -------------------------------------------- | ---------------------------------------------------------------------------------: |
| 1      | Câble HDMI/microHDMI                         |           <img src="Pictures/040-hdmicable.jpg" style="zoom: 33%;" />              |
| 1      | Câble bouton marche/arrêt avec 3 connecteurs | <img src="Pictures/040-power.jpg" alt="Cable bouton ON/OFF" style="zoom: 33%;" />  |
| 1      | Câble alimentation principale rouge          | <img src="Pictures/040-mainvcc.jpg" alt="Cable alimentation" style="zoom: 33%;" /> |
| 1      | Câble alimentation principale noir           | <img src="Pictures/040-maingnd.jpg" alt="Cable alimentation" style="zoom: 33%;" /> |
| 1      | Câble reset 2 fils                           | <img src="Pictures/040-reset.jpg" alt="Cable reset" style="zoom: 33%;" />          |
| 1      | Câble I2C 4 fils                             | <img src="Pictures/040-I2C.jpg" alt="Cable I2C" style="zoom: 33%;" />              |
| 2      | Câbles Dupont 12 fils                        | <img src="Pictures/040-dupont.jpg" alt="Cables connexion" style="zoom: 33%;" />    |

## II-8) Conclusion<A id="a14"></A>

Assurez-vous d'avoir tous les composants, cables, boutons avant l'assemblage. Contactez-moi en cas de problème et j'enverrai
gratuitement les pièces manquantes

Vérifiez que vous avez commandé le bon boitier et que les panneaux de façade avant et arrière de Bluety
prennent place correctement. Ajustez les contours en plastique du boitier avec du papier de verre si nécessaire.

Conservez les circuits intégrés à l'abri dans leur sachet anti-statique jusqu'au dernières étapes de l'assemblage. Portez
un bracelet anti-statique relié à une masse pour les manipuler
