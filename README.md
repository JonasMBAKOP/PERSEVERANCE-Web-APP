# LA PERSEVERANCE PLUS — Gestion scolaire

Application Laravel de gestion scolaire, adaptée depuis le projet COPTAN.

## Environnement local isolé

- Application Laravel : `http://127.0.0.1:8100`
- Vite (ressources front-end) : `http://127.0.0.1:5174`
- Base MySQL : `perseverance_db`

Ces ports sont volontairement différents de ceux du projet COPTAN afin de pouvoir exécuter les deux applications simultanément.

## Première installation

1. Démarrer MySQL dans WampServer puis créer la base :

```sql
CREATE DATABASE perseverance_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Vérifier `.env` : renseigner les paramètres MySQL si votre compte n'est pas `root` sans mot de passe, puis définir un compte administrateur unique :

```dotenv
INITIAL_ADMIN_EMAIL=admin@perseverance-plus.local
INITIAL_ADMIN_PASSWORD=choisissez-un-mot-de-passe-fort
```

3. Installer le schéma et les données de référence :

```powershell
php artisan config:clear
php artisan migrate --seed
```

4. Lancer le développement :

```powershell
composer run dev
```

Ouvrez ensuite `http://127.0.0.1:8100`.

## Données de l'établissement

Le seeding ne reprend ni l'adresse, ni les agréments, ni les coordonnées de COPTAN. Après la première connexion, complétez-les depuis **Paramètres** et chargez le logo officiel de LA PERSEVERANCE PLUS.

## À retenir

Ne lancez jamais `migrate:fresh` sur la base COPTAN : cette commande supprime les tables de la base configurée. Vérifiez toujours que `DB_DATABASE=perseverance_db` avant une migration destructive.