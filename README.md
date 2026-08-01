# Personal Finance SaaS

A personal finance management SaaS application built with PHP 8.4 and MySQL.

## Features
- Multi-account management (Cash, Bank, E-Wallet, Investment)
- Transaction recording with categories and sub-categories
- Budget tracking with alerts
- Financial goals tracking
- Reports and analytics (Cash Flow, Net Worth)
- Multi-currency support
- Freemium model (Free/Pro tiers)

## Tech Stack
- PHP 8.4
- MySQL 8.0+
- Vanilla PHP (no framework dependency for simplicity)
- Composer for dependency management

## Setup

### Database Import
```bash
cd database
php import.php
```

### Development Server
```bash
cd public
php -S localhost:8000
```

## Project Structure
```
├── app/
│   ├── Config/          # Configuration files
│   ├── Core/            # Core classes (Database, Router, Auth, etc.)
│   ├── Models/          # Data models
│   ├── Controllers/     # Request handlers
│   ├── Views/           # Templates
│   ├── Services/        # Business logic
│   └── Middleware/      # Request middleware
├── database/
│   ├── schema.sql       # Database schema
│   ├── seeders/         # Seed data
│   └── import.php       # Database import script
├── public/
│   ├── index.php        # Entry point
│   ├── assets/          # CSS, JS, images
│   └── .htaccess        # Apache rewrite rules
├── tests/               # Unit/integration tests
├── composer.json
└── README.md
```