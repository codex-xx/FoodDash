# FoodDash Database Security and Backup Management

This document explains how FoodDash protects important data and recovers from failures.
The language is simple on purpose so first-year students can follow it.

## 1. Secure and Optimized Database Schema

FoodDash uses one central database for:
- mobile app users (customers and drivers)
- web app users (admins and restaurant partners)

### Main idea

We split data into focused tables so each table has one clear job.
This is called normalization. It reduces repeated data and keeps updates cleaner.

### Core tables

- `users`: admin and restaurant partner accounts
- `customers`: customer accounts
- `drivers`: driver accounts
- `orders`: order headers (who ordered, total, status)
- `order_items`: each item inside an order
- `delivery_records`: delivery progress for each order
- `payment_transactions`: payment results for each order
- `auth_tokens`: issued login tokens (for tracking/revocation)
- `login_activities`: login success/failure history
- `backup_runs`: backup history and status

### Relationships (simple view)

- One restaurant can have many orders.
- One customer can have many orders.
- One order can have many order items.
- One order has one delivery record.
- One order can have one or more payment transaction records (depending on retries).

### Performance optimization

Indexes are added to high-traffic fields like:
- `email_hash`, `phone_hash`
- token lookup fields (`jti`, `token_hash`)
- status/time fields for logs and backups

These indexes speed up search and filtering operations.

## 2. Encryption of Sensitive Data

### Password safety

Passwords are never stored as plain text.
They are hashed using strong algorithms (`Argon2id` when available, else `bcrypt`).

### Personal data protection

For email and phone fields we store:
- encrypted value (`*_encrypted`) for secure storage
- hash value (`*_hash`) for searching/indexing without exposing the real value

This means even if someone steals raw database files, reading sensitive data is much harder.

### Data in transit

All app-to-server traffic should use HTTPS.
HTTPS protects data while it travels over the internet.

## 3. Automated Backup System

FoodDash now supports automated SQL backups.

### CLI commands

- `php spark db:backup --label=daily`
- `php spark db:backups`

### Windows scheduler scripts

- `scripts/db_backup_daily.ps1`
- `scripts/db_restore.ps1`

Backups are stored in:
- `writable/db_backups`

Each run can be logged in `backup_runs` with:
- file name
- size
- checksum
- start/end timestamps
- status (success/failed)

## 4. Backup Restoration (Disaster Recovery)

If the system fails, admins can restore quickly.

### CLI restore

- `php spark db:restore --file=your_backup_file.sql`

### API restore (admin)

- `POST /api/admin/backups/restore`

Important practice:
- test restore in a staging/test environment first
- verify app login and order flow after restore

## 5. Integration Across Mobile and Web Systems

Both mobile and web use the same backend and central database.

### Why this matters

- one source of truth (no duplicate data per platform)
- same security rules for everyone
- same backup/restore strategy for all users

### Example

If a customer places an order in mobile app, admin and restaurant users can see the same order instantly in the web app because both read from the same centralized database.

## API endpoints for admin backup management

- `GET /api/admin/backups`
- `POST /api/admin/backups/run`
- `POST /api/admin/backups/restore`

These endpoints are protected by admin authorization filter (`apiadmin`).

## Summary

FoodDash now has:
- cleaner normalized data structure
- stronger protection for passwords and sensitive personal data
- automated backups
- disaster recovery restore flow
- centralized and consistent security for mobile and web
