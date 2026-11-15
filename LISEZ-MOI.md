# Zayna Creation — Mise à jour du site 🌸

## Ce qui a changé

1. **Panier réel** — sur la page d'accueil, chaque produit a un bouton
   "Ajouter au panier" (avec choix de taille). L'icône 🛍️ dans le menu montre
   le nombre d'articles. La page `order.html` est maintenant la page
   **Panier + Commande** : le client peut ajuster les quantités, retirer un
   article, puis remplir ses coordonnées et valider — **sans créer de compte**.
2. **Gestion des produits pour l'admin** — nouvelle page `admin_products.php` :
   ajouter un article (photo, description, prix par taille ou prix unique),
   le modifier, le marquer **"Épuisé" / "Disponible"** en un clic, ou le
   supprimer. Dès qu'un produit est ajouté, il apparaît automatiquement sur
   la boutique, comme les autres.
3. **Espace admin protégé** — `admin.php` (commandes) et `admin_products.php`
   (produits) exigent toujours une connexion admin. Le client, lui, n'a
   plus besoin de compte pour rien : ni pour voir la boutique, ni pour
   commander.
4. **Notification par email** — à chaque commande, un email est envoyé
   automatiquement à l'adresse que vous configurez (voir plus bas).
5. **Site en français** — boutique, panier et espace admin sont traduits.

## Étapes d'installation (XAMPP)

1. Copiez tous les fichiers de ce dossier dans votre dossier du projet
   (ex. `C:\xampp\htdocs\zayna`), **en gardant votre dossier `images/`**
   existant (les photos golden-bloom.png, heart.png, etc. ne sont pas
   incluses ici, gardez celles que vous avez déjà).
2. Si ce n'est pas déjà fait, exécutez `setup_users.sql` dans phpMyAdmin
   (onglet SQL de la base `zayna`).
3. Exécutez ensuite **`setup_shop.sql`** (nouveau fichier) dans le même
   onglet SQL. Il crée les tables `products` et `order_items`, et
   ajoute automatiquement vos 7 produits actuels dans la nouvelle table
   `products` pour qu'ils apparaissent tout de suite sur le site.
4. Vérifiez que le dossier du site est accessible en écriture : quand
   l'admin ajoute une photo, le site crée automatiquement un dossier
   `uploads/products/` pour la stocker. Sous XAMPP en local, cela
   fonctionne normalement sans réglage supplémentaire.
5. Connectez-vous à l'espace admin via `login.html` avec le compte créé
   par `setup_users.sql` (email `admin@zayna.com`, mot de passe `password`
   — **changez-le tout de suite** une fois connecté, ou par une requête
   SQL comme indiqué en commentaire dans `setup_users.sql`).

## Configurer l'email de notification

Ouvrez `submit_order.php` et modifiez cette ligne tout en haut :

```php
define('ADMIN_EMAIL', 'ton-email@example.com');
```

Remplacez par votre vraie adresse email.

⚠️ **Important** : la fonction `mail()` de PHP ne fonctionne pas "toute
seule" sur un serveur local XAMPP — elle a besoin d'un serveur SMTP
configuré. Deux options :

- **En production** (hébergement mutualisé chez OVH, Hostinger, etc.) :
  `mail()` fonctionne généralement directement, aucune configuration
  supplémentaire n'est nécessaire.
- **En local (XAMPP) ou pour plus de fiabilité partout** : utilisez
  [PHPMailer](https://github.com/PHPMailer/PHPMailer) avec un compte
  Gmail (mot de passe d'application) ou tout autre fournisseur SMTP.
  Dites-le moi si vous voulez que je vous prépare cette version, je
  peux l'intégrer directement à `submit_order.php`.

Si l'email ne part pas, **la commande est quand même bien enregistrée**
dans la base de données et visible dans `admin.php` — l'email est un
"bonus", jamais un blocage.

## Comment ajouter un nouvel article (pour vous, l'admin)

1. Connectez-vous sur `login.html`.
2. Allez dans **Produits** (menu de gauche).
3. Cliquez **"+ Ajouter un produit"**.
4. Remplissez le nom, la description, choisissez une photo, une
   catégorie :
   - **Standard** → vous donnez un prix pour S, M, L (et éventuellement
     un prix pack S+M et S+M+L).
   - **Laptop / Autre** → un seul prix.
5. Cochez "Disponible en stock" (décochez si vous ne l'avez pas encore
   en stock).
6. Enregistrez → le produit apparaît immédiatement sur la boutique.

Pour marquer un article "épuisé" à tout moment (le bouton d'achat se
désactive automatiquement sur le site), cliquez juste sur
**"🚫 Marquer épuisé"** sur sa fiche dans `admin_products.php`.

## Fichiers de ce dossier

| Fichier | Rôle |
|---|---|
| `setup_shop.sql` | à exécuter une fois dans phpMyAdmin |
| `db.php` | connexion à la base (inchangé) |
| `products_api.php` | fournit la liste des produits à la boutique (JSON) |
| `admin_products.php` | gestion des produits (admin) |
| `admin.php` | gestion des commandes (admin) |
| `index.html` | boutique (page d'accueil) |
| `order.html` | panier + formulaire de commande |
| `cart.js` | logique du panier (partagée entre les pages) |
| `submit_order.php` | enregistre la commande + envoie l'email |
| `login.html` / `auth.php` | connexion admin |
| `setup_users.sql` | création du compte admin (déjà existant) |
| `pagedegarde.html` | page de garde (inchangée) |

**Remarque** : `admin_orders.php` (l'ancienne version simplifiée) n'est
plus nécessaire — `admin.php` fait tout, en mieux (recherche, filtres,
statuts, et maintenant les articles multiples par commande). Vous
pouvez le supprimer de votre serveur si vous le souhaitez.
