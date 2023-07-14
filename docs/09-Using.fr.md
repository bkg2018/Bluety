# Utilisation<A id="a48"></A>

Les ports d'entrée-sortie sont contrôlés par des instructions IN et OUT du Z-80, ainsi que certaines instructions propres au Z180.
Le BASIC a également des instructions IN et OUT pour lire ou écrire un octet sur un port. L'écran LCD se contrôle via l'interface
I2C à l'aide d'une bibliothèque à intégrer dans les programmes en assembleur. Le contrôle I2C à partir du BASIC nécessite de placer cette
bibliothèque en mémoire pour l'appeler avec USR() car le contrôle précis des timings ne sera pas possible avec les instructions IN et OUT
du BASIC.

## Port de contrôle (13/0D)<A id="a49"></A>

Le port de contrôle est câblé sur le numéro de port 13 (0D en hexadécimal) : ceci est fixé par design dans les logiciels de
contrôle SCM et RomWBW disponibles pour l'ordinateur SC126.

Une fois le démarrage terminé, le port de contrôle
est disponible pour les programmes.

Les 8 LED de Bluety 
sous l'écran LCD indiquent le dernier octet écrit sur ce port de contrôle.

- Le moniteur Small Computer Monitor affiche l'état de ses tests à l'allumage sur ce port, la séquence est très rapide.
- Le boot de RomWBW affiche l'avancement de son démarrage sur ce port, il y a davantage de vérifications et il calibre son horloge interne, le processus dure une à deux secondes.
- Tout programme peut allumer ou éteindre ces 8 LEDs en envoyant une donnée 8-bits sur le port 13. La LED la plus à gauche représente
le bit de poids fort : écrire 80h allumera uniquement cette LED la plus à gauche.

<TABLE><TR><TD><img src="Pictures/thisway.png" alt="Conseil" width="75px" /></TD><TD>Remarque : le port 13 retient la dernière donnée
écrite, donc il faut explicitement écrire un 0 pour éteindre les LEDs, notamment avec RomWBW qui laisse les 8 LEDs allumées à la fin
de ses vérifications.
</TD></TR></TABLE>

## Ports d'entrée sortie A et B<A id="a50"></A>

Les 2 ports possèdent chacun 8 LEDs pour le port en entrée et 8 LEDs pour le port en sortie qui indiquent le dernier octet lu ou écrit.
Dans les deux cas, le bit de poids fort (bit 7) est représenté par la LED la plus à gauche.

Le numéro de chacun des deux ports est sélectionnable par les huit interrupteurs de leur dipswitch, qui représentent les valeurs de 0 à 255.
Là aussi, le bit de poids fort (bit 7) est situé à gauche. Pour sélectionner le port 80h, placez l'interrupteur le plus à gauche en HAUT, et
tous les autres en BAS.

### Lire sur le port A ou B<A id="a51"></A>

Chacun des ports en entrée dispose d'un connecteur 8 fils, de 8 LEDs et de 8 interrupteurs.

| Etape | Description                                                  |                                                        |
| ----- | :----------------------------------------------------------- | -----------------------------------------------------: |
| 1     | Choisissez le numéro de port avec le DIPswitch, le bit de poids fort du numéro est représenté par l'interrupteur le plus à gauche. Par exemple, pour utiliser le port 7, on place les trois interrupteurs les plus à droite en position haute et les cinq autres en position basse. | <img src="Pictures/97-portselect.jpg" /> |
| 2     | Placez les interrupteurs en position médiane et connectez vos fils sur le port d'entrée. |     <img src="Pictures/096-input.jpg" width="300px" /> |
| 3     | Les interrupteurs peuvent être placés en position haute pour forcer un bit à 1 ou en position basse pour le forcer à 0. |          <img src="Pictures/TODO.png" width="300px" /> |
| 4     | L'interrupteur supérieur à droite des LEDs les active ou désactive. Chaque LED affiche l'état de l'entrée qui lui correspond. En position basse, l'interrupteur éteint les LEDs quel que soit l'état du port, ce qui évite que le port d'entrée ou de sortie dépense du courant pour l'allumage des LEDs. |          <img src="Pictures/TODO.png" width="300px" /> |
| 5     | Pour lire l'état du port , utilisez :<br />● l'instruction `IN` du Z-80<br />● l'instruction `IN0` du Z-180<br />● la fonction `INP()` du BASIC<br />● la commande `IN` de SCM<br />La donnée reçue aura les bits à 1 sur les entrées qui seront à +5V, et les LEDs de ces bits seront allumées (si l'interrupteur on/off est sur *on*). Les bits des entrées reliées à GND ou recevant moins de 5V seront à 0 et la LED sera éteinte. |                                                        |

Attention: les lignes d'entrée alimentent directement des circuits intégrés et les LEDs, aussi vous ne devez pas tirer plus de quelques milliampères
et pas beaucoup plus de +5V sous peine de brûler des composants et de rendre définitivement inutile le poirt d'entrée.

En général, un niveau d'entrée inférieur à +4.7V sera considéré comme
un bit à 0.

### Ecrire sur le port A ou B<A id="a52"></A>

| Etape | Description                                                  |                                                        |
| ----- | ------------------------------------------------------------ | -----------------------------------------------------: |
| 1     | Choisissez le numéro de port avec le DIPswitch, le bit de poids fort du numéro est représenté par l'interrupteur le plus à gauche. Par exemple, pour utiliser le port 7, on place les trois interrupteurs les plus à droite en position haute et les cinq autres en position basse. | <img src="Pictures/97-portselect.jpg" /> |
| 2     | Connectez vos fils sur le port de sortie si besoin.          |            <img src="Pictures/TODO.png" width="300" /> |
| 3     | L'interrupteur inférieur à droite des LEDs active ou désactive les LEDs. Lorsqu'elles sont désactivées, le connecteur de sortie reçoit tout le courant disponible, sinon les LEDs allumées prennent une part de la charge. |            <img src="Pictures/TODO.png" width="300" /> |
| 4     | Pour écrire une donnée 8 bits sur le port, utilisez :<br />● l'instruction `OUT` du Z-80<br />● l'instruction `OUT0` du Z-180<br />● la commande `OUT` du BASIC<br />● la commande `OUT` de SCM<br />5V sont placés sur les sorties des bits placés à 1 et les LEDs des bits à 1 sont allumées. Les autres bits restent à 0 et la LED éteinte. |            <img src="Pictures/TODO.png" width="300" /> |
| 5     | Vous pouvez utiliser le connecteur +5V/GND pour alimenter des dispositifs extérieurs comme des circuits avec des relais ou des capteurs, indépendamment de l'état du port ou des interrupteurs en façade. |                                                        |

Remarque : Chaque port de sortie retient sa dernière donnée, donc pour éteindre les LEDs il faut explicitement écrire un 0.Vous pouvez utiliser le dipswitch pour espionner
n'importe quel numéro de port, par exemple les ports internes du Z-180 sur le SC126 ou les ports contrôlés par une ROM
avec BusRaider.
</TD></TR></TABLE>

## Ecran LCD : Interface I2C (SC126)<A id="a53"></A>

Sur un SC126, l'écran LCD est contrôlé par le bus I2C sur le port 0Ch. L'écran intègre généralement un adaptateur I2C, il n'y a
aucune carte supplémentaire à installer, et rien d'autre à effectuer que le branchement déjà décrit d'un câble 4 fils entre le connecteur I2C du SC126 et l'adaptateur I2C de l'écran.

L'adaptateur I2C de l'écran ne possède pas de sortie pour l'insérer dans une chaine I2C et doit donc être placé en dernier, aussi pour
utiliser d'autres dispositifs I2C vous devrez les placer en amont et vous assurer qu'ils possèdent une sortie pour reconduire le bus vers
un dispositif suivant. Reportez vous aux spécifications I2C et à celles de vos dispositifs I2C. Certains écrans ont un numéro d'identification I2C configurable.

Au lieu d'utiliser un écran I2C on peut installer un écran sans adaptateur I2C et le contrôler
par le biais d'un port parallèle ou d'une carte E/S. Le site de Stephen S. Cousins décrit diverses manières de controler un écran LCD
à partir d'un SC126 ou d'un RC2014. 

## Ecran LCD : Interface parallèle (SC126,RC2014 Pro)<A id="a54"></A>

Il n'existe pas de bus I2C dans le kit RC2014 Pro, aussi il faut passer par une interface parallèle, comme décrit dans [l'exemple
de Stephen S. Cousins](https://smallcomputercentral.wordpress.com/example-alphanumeric-lcd/) qui utilise soit un port de sortie 8-bits,
soit un Z-80 PIO, le tout vers 8 broches de l'écran LCD.

Avec le SC126, cette solution peut aussi être utilisée pour réserver le bus I2C à d'autres usages.

Sur le plan matériel, il faut installer une carte pour contrôler un port de sortie. Il existe au moins
trois kits qui permettent cela :

- la carte d'entrée-sortie 8 bits [SC129](https://smallcomputercentral.wordpress.com/sc129-digital-i-o-rc2014/) de Stephen S. Cousins
  (achat sur [Tindie](https://www.tindie.com/products/tindiescx/sc129-digital-io-module-kit-for-rc2014/)) : elle possède un connecteur de
  sortie sur lequel vous brancherez un câble 8 fils
- le [Digital Output Module](http://rc2014.co.uk/modules/digital-io/) de Spencer Owens (achat sur [Tindie](https://www.tindie.com/products/Semachthemonkey/digital-output-module-for-rc2014-z80-computer/)) :
  vous devrez configurer le numéro de base des ports comme indiqué dans la documentation de Spencer et utiliser les broches de l'un
  des 3 ports pour brancher un câble 8 fils
- la carte [SC103 Z-80 PIO](https://smallcomputercentral.wordpress.com/sc103-z80-pio-module-rc2014/) de Stephen S. Cousins :
  vous devrez configurer le numéro de base des ports comme indiqué dans la documentation de Stephen et utiliser les broches
  de l'un des 2 ports pour brancher un câble 8 fils

Tout le contrôle logiciel doit être effectué par programme qu'il s'agisse de I2C ou d'une carte ES parallèle car ni SCM ni RomWBW ne gèrent actuellement de périphérique écran LCD.
