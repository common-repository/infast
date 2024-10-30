=== INFast pour WooCommerce ===
Contributors: intia
Tags: invoice, facture, infast, intia, woocommerce
Requires at least: 5.6
Tested up to: 6.6
Requires PHP: 7.0* 
Stable tag: 1.0.30
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html


Créez et envoyez par e-mail des factures conformes automatiquement à chaque commande passée sur votre e-boutique WooCommerce.

== Description ==
Cette extension vous permet de générer automatiquement des **factures conformes** lorsqu’une vente est effectuée sur votre e-boutique **WooCommerce**.  
  
INFast est un logiciel de facturation **100% français** qui vous fait gagner du temps en automatisant vos devis, acomptes, factures, avoirs.  
  
Grâce à une interface intuitive, vous gérez :  
- Les commandes manuelles
- Paiements hors boutique en ligne
- Fiches clients et articles
- Relances d’impayés
- Depuis n’importe quel support (tablette, ordinateur, smartphone)
- Service client par tchat 7/7 et [par téléphone sur rendez-vous](https://calendly.com/intia-devis-factures/renseignement-plugin-woocomerce)
  
INFast s’adresse aussi bien aux auto-entrepreneurs, qu’aux TPE et PME souhaitant se conformer aux exigences de la réglementation française, notamment en matière de facturation.  
    
= Fonctionnalités de l’extension =
- Création automatique d’une facture lors de chaque commande en ligne. Commande (finalisée et payée)
- Prise en compte du numéro de TVA Intracommunautaire (https://woocommerce.com/fr-fr/products/eu-vat-number/)
- Centralisation de vos documents de facturation, données clients et articles
- Création et mise à jour instantanées des clients WooCommerce vers INFast
- Création et mise à jour en temps réel des articles WooCommerce (et de leurs variantes) vers INFast
- Ajout automatique d’un nouvel article dans INFast lors de la création de facture. (si l’article n’existe pas déjà dans INFast)
- Synchronisation de tous les articles WooCommerce dans INFast
- Envoi automatique des factures par e-mail (paramétrable)
- Ajout d’un destinataire en copie lors des envois d’e-mails (paramétrable)
- Gestion des remboursements : 
  - Une commande qui a déjà été convertie en facture INFast ne sera pas modifiée en cas d’ajout de remboursements sur la commande. Il sera nécessaire de créer un avoir manuellement depuis INFast.
  - Une commande qui n’a pas encore été convertie en facture INFast prendra compte des remboursements lors du transfert vers INFast. Si la commande a été totalement annulée par les remboursements, la facture ne sera pas transférée dans INFast.

**Consulter l’exemple de facture disponible depuis un portail personnalisé pour voir la facturation INFast en action**
[Exemple de facture](https://inbox.intia.fr/ckto6edjy00f6j2uka1z4elyi)

= Les avantages à utiliser l’extension INFast pour WooCommerce =
En plus de gagner du temps avec l’automatisation de la facturation et de la synchronisation des données clients et articles, vous pouvez accéder à d’autres fonctionnalités directement depuis le logiciel devis factures INFast, comme : 

- le choix de la numérotation des factures
- la personnalisation de vos factures avec votre logo 
- vos factures au format pdf
- l’export de vos factures et bases de données client et article au format Excel
- le suivi automatique et l’historique de l’envoi de vos documents de facturation
- le suivi de votre chiffre d’affaires mois par mois
- la signature électronique de vos devis (illimité)
- le suivi de votre entreprise grace a des rapports
- le partage des données vers votre comptable

= Sécurité =
- INFast est conforme à la loi anti-fraude
- Vos données INFast sont sauvegardées et sécurisées sur des serveurs français
- INFast respecte le règlement général de protection des données personnelles (RGPD)


== Installation ==

= Pré-requis =
* PHP 7.2 ou ultérieur 
* MySQL 5.6 ou ultérieur
* WordPress 3.1 ou ultérieure
* WooCommerce 5.6 ou ultérieure
* Un compte [INFast](https://intia.fr/fr/infast/?utm_source=wordpress&utm_medium=web&utm_id=plugin_woocommerce) sur l’offre FURIOUS 🔥

= Installation =
Depuis l’administration de WordPress :  
- Rendez-vous dans la rubrique « extensions »
- Cliquez sur « Ajouter une extension »
- Recherchez « INFast »
- Cliquez sur « Installer maintenant »
- Activez l’extension

= Configuration =
Une fois le module activé, un nouveau sous-menu « INFast » apparaît dans le menu WooCommerce.  

**Identifiants**
Pour lier WooCommerce à INFast, renseignez le ClientID et ClientSecret de votre compte INFast.  
Ces identifiants sont accessibles depuis votre compte INFast : 
- allez dans le menu principal (en haut à droite) > « Paramètres » > « API »  

**E-mail à vos clients**   
Si vous souhaitez envoyer automatiquement les factures à vos clients, cochez la case « Envoyer les factures automatiquement par e-mail ? » 
Vous pouvez également recevoir une copie des e-mails en renseignant votre adresse mail.  

**Description des articles** 
Par défaut, la description courte de vos articles est utilisée dans INFast.
Si vous ne souhaitez pas qu’INFast affiche la description, vous pouvez cocher la case « Importer les articles WooCommerce sans leur description dans INFast »

**Synchronisation**
Si vous souhaitez forcer une synchronisation de vos articles WooCommerce dans INFast, cliquez sur « Lancer la synchronisation ».
Cette étape n’est normalement pas nécessaire. Vos articles et clients sont dans tous les cas synchronisés avec INFast lors de la création des factures.

**Délier les produits WooCommerce aux articles INFast**
Après la synchronisation d’un produit WooCommerce avec INFast, le produit WooCommerce est lié à l’article INFast.
Dans certains cas, il peut être nécessaire de supprimer cette liaison.
Attention : en cas de nouvelle synchronisation des produits, tous les produits créeront de nouveaux articles dans INFast. Vous risquez d’avoir des doublons dans INFast

**Sauvegarde de la configuration**
N’oubliez pas de sauvegarder ces changements.
    
  
== Frequently Asked Questions ==
= Est-ce que cette extension fonctionne sans WooCommerce ? =
Non, cette extension est dédiée à la synchronisation des commandes WooCommerce dans INFast.

= Est-ce que cette extension est gratuite ? =
Oui, cette extension est gratuite mais nécessite d’avoir un compte INFast actif.  
Vous devez également posséder un compte WooCommerce.  

= Est-ce qu’INFast est gratuit ? =
INFast est gratuit pendant 30 jours.
Vous devrez ensuite vous abonner à l’offre FURIOUS pour disposer des accès API permettant l’interconnexion avec WooCommerce.  

= Les articles sont-ils mis à jour automatiquement ? =
Oui.  
Dès qu’un article est créé ou modifié dans WooCommerce, il sera également créé ou modifié dans INFast.  
En revanche une mise à jour dans INFast n’entraîne pas de mise à jour dans WooCommerce.  

== Changelog ==
= Version 1.0.30 =
- Validation de l’extension avec la version 6.6 de WordPress 
- Amélioration des traductions

= Version 1.0.29 =
- Amélioration des traductions

= Version 1.0.28 =
- Amélioration interne

= Version 1.0.27 =
- Amélioration interne : Vérification que l’extension WooCommerce est activée

= Version 1.0.26 =
- Validation de l’extension avec la version 6.5.3 de WordPress 
- Traductions
- Amélioration interne

= Version 1.0.25 =
- Possibilité de choisir quels Statuts/Etats des commandes WooCommerce permettent le transfert en facture INFast.

= Version 1.0.24 =
- Gestion des remboursements : 
  - Une commande qui a déjà été convertie en facture INFast ne sera pas modifiée en cas d’ajout de remboursements sur la commande. Il sera nécessaire de créer un avoir manuellement depuis INFast.
  - Une commande qui n’a pas encore été convertie en facture INFast prendra compte des remboursements lors du transfert vers INFast. Si la commande a été totalement annulée par les remboursements, la facture ne sera pas transférée dans INFast.

= Version 1.0.23 =
- Délier les produits WooCommerce des articles INFast ne fonctionnait pas pour les variantes de produit

= Version 1.0.22 =
- Amélioration interne

= Version 1.0.21 =
- Testé avec la version 6.4 de WordPress
- Amélioration interne

= Version 1.0.20 =
- Corrections dans la gestion des arrondis
- Amélioration interne

= Version 1.0.19 =
- Corrections dans la gestion des arrondis
- Ajout de la référence dans les messages des notes des commandes
- Amélioration interne

= Version 1.0.18 =
- Corrections dans la gestion des arrondis
- Possibilité de régénérer une facture INFast à partir d’une commande WooCommerce qui a déjà précédemment été converti (nécessite un délai de 2 minutes)

= Version 1.0.17 =
- Corrections mineures

= Version 1.0.16 =
- Possibilité de délier les produits WooCommerce aux articles INFast
- Avec certaines configurations de WooCommerce on peut avoir dans certains cas un écart de 1 centime entre la facture INFast et la commande WooCommerce. Dans ce cas une ligne « Correction arrondi » de 1 centime est ajoutée à la facture INFast.

= Version 1.0.15 =
- La duplication d’article WooCommerce modifiait l’article original dans INFast
- Création d’une facture brouillon et vérification des montants avant validation, paiement et envoie par e-mail
- Dans certains cas 2 factures étaient générées pour la même commande
- Corrections mineures

= Version 1.0.14 =
- Dans certains cas 2 factures étaient générées pour la même commande

= Version 1.0.13 =
- Affiche le nom de l’entreprise et du client sur les factures INFast

= Version 1.0.12 =
- Utiliser la même dénomination du moyen de paiement dans la facture INFast

= Version 1.0.11 =
- N’affiche plus l’adresse de livraison si elle est identique à l’adresse de facturation
- Corrections mineures

= Version 1.0.10 =
- Ouverture des liens dans un nouvel onglet
- Corrections mineures

= Version 1.0.9 =
- Ajout du lien vers la facture dans les informations de facturation de la commande WooCommerce
- Ajout du lien vers la facture dans les notes de la commande WooCommerce
- Correction mineur dans les descriptions

= Version 1.0.8 =
- Possibilité de ne pas prendre en compte la description des produits (si c’est bien la terminologie) WooCommerce lors de la synchronisation des articles INFast
- Prise en charge de l’extension «N° de TVA Intracommunautaire» (https://woocommerce.com/fr-fr/products/eu-vat-number/)
- Ajout du lien vers la facture INFast dans le récapitulatif de la commande WooCommerce (vue administrateur)
- Ajout du lien client pour visualiser la facture INFast (lorsqu’elle a été envoyée par e-mail via INFast) dans le récapitulatif de la commande (vue client)

= Version 1.0.7 =
- Gestion des articles et clients supprimés dans INFast, dans ce cas un nouveau client ou article est créé
- Suppression des balises de style dans les descriptions d’articles

= Version 1.0.6 =
- Amélioration du test à la connexion à l’API d’INFast
- Limite la référence des articles importés
- Utilisation de la description longue au lieu de la description courte si elle existe

= Version 1.0.5 =
- Gestion des variantes de produits (chaque variante est considérée comme un article distinct dans INFast)

= Version 1.0.4 =
- Correction lors de la création d’une commande en tant qu’invité, le client INFast n’était pas créé
- Correction lors de la création d’une commande non payée. On crée la facture INFast seulement lorsque la commande est payée

= Version 1.0.3 =
- Correction lors de la saisie des clés d’API INFast API key. L’utilisateur devait renseigner ses clés 2 fois

= Version 1.0.2 =
- Mise à jour de la page de l’extension INFast pour WooCommerce

= Version 1.0.1 =
- Correction dans le fichier ReadMe
- Contenue de la GPLv3 dans le fichier Licence.txt

= Version 1.0.0 =
- Création des clients dans INFast dès la création dans WooCommerce
- Mise à jour des clients dans INFast dès la mise à jour dans WooCommerce
- Création des articles dans INFast dès la création dans WooCommerce
- Mise à jour des articles dans INFast dès la mise à jour dans WooCommerce
- Création des articles dans INFast lors de la création de facture si les articles ne sont pas déjà dans INFast
- Synchronisation de tous les articles WooCommerce dans INFast
- Possibilité d’activer ou non l’envoi d’e-mails
- Possibilité d’ajouter un destinataire en copie des envois d’e-mails

