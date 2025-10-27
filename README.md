# cavavin_vintheque_electron_php

Nom de l'application : CAVAVIN  
Version : BETA 1.0*  
Date 11/10/2025  
Développeur : Sébastien Flouvat  

CAVAVIN est un gestionnaire de cave à vin prêt à l'emploi offrant des outils (inventaire, enregistrements, notation, journal de bord..)
L'application s'éxecute sur un environnement MACOS ou WINDOWS.
L'application est libre d'utilisation et gratuite, merci d'en faire un usage non commercial et non public.


<div style="display:flex;justify-content:space-between gap:10px;">
<img width="320" height="180" alt="img1" src="https://github.com/sebf830/cavavin_vintheque_electron_php/blob/master/screenshots/1.png">
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
<img width="320" height="180"alt="img1" src="https://github.com/sebf830/cavavin_vintheque_electron_php/blob/master/screenshots/2.png">
</div>



Version BETA :
Cete version Beta est une version test fonctionnelle. 
L'application est amenée à évoluer dans ses version ultérieures.
Cela n'affectera pas l'intégrité des données des utilisateurs.  

Stack technique : php . node . sqlite . alpineJS  


I - Installer les dependances back du projet
1. Se rendre à 'cd {chemin_vers_le_projet}/build/back' et executer les commandes : 
```
    composer install --no-dev --optimize-autoloader
    composer boot
```
Assurez vous d'avoir composer d'installé sur votre machine. (https://getcomposer.org/)

II - Installer les dépendances front du projet
1. se rendre à 'cd {chemin_vers_le_projet}/front' et executer la commande : 
``` 
    npm install --production
```



III - Packager l'application 
1. MAC : depuis un environnement, mac aller à 'cd {chemin_vers_le_projet}/front', executer : 
``` 
    npx electron-builder --mac
```
Un executable sera crée dans à /front/build/dist/Cavavin.dmg

2. WINDOWS : depuis un environnement windows, aller à 'cd {chemin_vers_le_projet}/front', executer : 
``` 
    npx electron-builder --win
```
Un executable sera crée dans à /front/build/dist/Cavavin.exe  



<div style="display:flex;justify-content:space-between gap:10px;">
<img width="320" height="180" alt="img1" src="https://github.com/sebf830/cavavin_vintheque_electron_php/blob/master/screenshots/3.png">
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
<img width="180" height="180"alt="img1" src="https://github.com/sebf830/cavavin_vintheque_electron_php/blob/master/screenshots/4.png">
</div>



IV. Lancer l'application :

WINDOWS: 
1. Double-cliquez sur le fichier cavavin.exe pour installer l'application sur windows.
2. Acceptez les avertissements si Windows bloque l'installation.
3. Choisissez un emplacement d'installation (l'emplacement par défaut est recommandé)

MAC:
1. Double-cliquez sur le fichier cavavin.dmg pour une installation sur mac.
2. Faites glissez l'icône CAVAVIN dans votre dossier Applications
3. Acceptez les avertissements liés à la sécurité. 
Vous serez peut être amené à "Ouvrir quand même" dans vos paramètres de sécurité.


V. RGPD et sécurité : 

L'application est OFFLINE et la stack est embarquée, vos données ne transitent jamais hors de l'application.


