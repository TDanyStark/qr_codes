# Plan: QR Report Subscriptions

## Overview
Create a subscription system per QR, admin-configurable reporting schedule, and a cron-friendly job that emails periodic QR stats with a CSV attachment and a global Looker Studio link.

## Decisions
- Subscriptions: new table (many users per QR).
- Email: summary + CSV attachment + Looker Studio link.
- Looker link: global, managed by admin.
- Schedule: configurable in DB via admin UI (no code change when switching monthly/weekly).

## Steps
1. Update DB schema: add subscription and report settings tables in backend/database/qr_codes.sql and document in backend/README.md.
2. Add Domain entities and repositories for subscriptions and settings in backend/src/Domain, with PDO implementations in backend/src/Infrastructure/Persistence; wire in backend/app/repositories.php.
3. Build a reporting service that uses ScanRepository + MailerInterface to generate the summary and CSV attachment (store in public/tmp/reports).
4. Add a CLI entrypoint in backend/tools to run from cron, compute the date range from settings, and dispatch emails.
5. Add backend actions and routes to manage subscriptions and report settings (admin-protected) in backend/app/routes.php and backend/src/Application/Actions.
6. Build admin UI for settings and QR subscriptions in frontend/src/pages, wire in frontend/src/AppRoutes.tsx and frontend/src/lib/api.ts.
7. Update .github/copilot-instructions.md with the new feature conventions.

## Verification
- Run the CLI job manually and confirm log output + email delivery.
- Validate admin endpoints for subscriptions and settings.
- Confirm email content: summary counts, attached CSV, and Looker link.
- Verify date range logic for monthly and weekly schedules.
