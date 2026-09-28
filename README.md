# Klantenproject

Een kleine PHP/MySQL-webapp voor een leerhandel. Je kunt de leervoorraad beheren, klanten registreren en bestellingen plaatsen.

## Wat kan het?

- **Bestellen**: klanten plaatsen een bestelling (product, kleur, hoeveelheid in kg)
- **Voorraad**: overzicht met zoeken en filteren; werknemers en admins kunnen producten toevoegen, bewerken en verwijderen
- **Gebruikers**: inloggen met rollen (`gebruiker`, `werknemer`, `admin`); admins beheren de gebruikers

## Techniek

PHP, MariaDB en phpMyAdmin, draaiend in Docker.

## Starten

1. `docker compose up -d`
2. Importeer `klanten_project.sql` via phpMyAdmin (http://localhost:8080) (login user root, password password)
3. Open http://localhost/login.php

## admin login
test1 tea@test test1234

## gebruiker login
test3 test@tea test1234
