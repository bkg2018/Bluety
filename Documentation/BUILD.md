# Montage Bluety

Bluety est un ensemble de panneaux pour un boitier en métal bleu qui mesure 25 cm de large par 11 cm de hauteur, et 19 cm de profondeur. Ce boitier accueille parfaitement l'ordinateur SC126 avec une carte d'extension SC130Tiny, offrant 5 slots d'extension et une extensibilité vers l'extérieur.

Les éléments composant Bluety sont :

* le boitier, qui peut être acheté sur Banggood.
* une **façade avant** avec des ouvertures pour des interrupteurs, des LEDs, un écran LCD, un bouton marche-arrêt et des connecteurs d'entrée-sortie 
* une **façade arrière** avec des ouvertures pour un bouton reset, des interrupteurs, et des prises HDMI et USB
* **Blink'n Switch**, un circuit imprimé qui se fixe sur la façade avant et qui possède des interrupteurs et connecteurs pour deux ports d'entrées sortie 8-bits ainsi que pour les 8 LED de status d'un SC126 (port 0x0D en sortie)
* **Bus Daughter**, une carte d'interfaçage qui se connecte sur un slot 40 broches à la norme RC2014 pour relier l'ordinateur (SC126 par exemple) au panneau Blink'n Switch

Le montage présente quelques aspects non conventionnels en raison de l'espacement entre le circuit imprimé Blink'n Switch et la façade avant, induit par les interrupteurs des ports d'entrée sortie.


## Blink'n Switch

Blink's Switch est le circuit imprimé des deux ports d'entrée-sortie et du port de status SC126 (port 0x0D en sortie). Il sera dénommé BnS dans la suite de ce manuel.

La soudure des composants de Blink'n Switch (BnS) nécessite des assemblages provisoires avec la façade avant afin que les composants soient bien alignés sur la façade une fois soudés. Vous devez préparer les éléments suivants :

* deux supports d'assemblage M3*8 avec vis et écrou


## Bus Daughter

## Façade avant

## Façade arrière