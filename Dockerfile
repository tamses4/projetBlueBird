# 1. On part de l'image PHP avec Apache
FROM php:8.2-apache

# 2. On installe les outils système nécessaires (git et unzip sont requis pour Composer)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# 3. On installe les extensions PHP pour la base de données (MySQL)
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# 4. On active le module de réécriture d'URL (indispensable pour un Routeur comme Bramus)
RUN a2enmod rewrite

# 5. --- NOUVEAU --- Installation de COMPOSER
# On copie l'exécutable composer depuis l'image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. On définit le dossier de travail
WORKDIR /var/www/html/

# 7. On copie tous les fichiers du projet DANS le conteneur
COPY . .

# 8. --- NOUVEAU --- On lance l'installation des dépendances
# Cela va créer le dossier /vendor/ manquant
RUN composer install --no-dev --optimize-autoloader

# 9. On règle les permissions pour qu'Apache puisse lire les fichiers
RUN chown -R www-data:www-data /var/www/html

# 10. On expose le port 80
EXPOSE 80
