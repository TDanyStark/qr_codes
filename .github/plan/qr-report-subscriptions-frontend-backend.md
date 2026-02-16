# Plan: QR Subscriptions UI + Backend Routes

## Overview
Add subscription selection to QR create/edit, expose backend routes for subscriptions and report settings, and build an admin settings view with multiple configs (one active).

## Decisions
- Subscriptions managed by both owner and admin.
- Subscription selection lives in the QR create/edit modal.
- Subscriber list includes all users.
- Report settings support multiple configs with one active.

## Steps
1. Add backend Actions for subscriptions and report settings under src/Application/Actions, following the QrCode Action pattern.
2. Wire new subscription and report settings routes in app/routes.php.
3. Extend QrCode create/edit actions to accept subscriber IDs and persist via QrSubscriptionRepository.
4. Extend frontend QR form data and UI to include a users multi-select in create/edit flows.
5. Add frontend API calls for users list, subscriptions, and report settings.
6. Build admin settings UI: list configs, create/edit, and activate one; add route and sidebar entry.
7. Verify backend and frontend flows end-to-end.

## Verification
- Backend: manual API checks for subscription and report settings endpoints.
- Frontend: confirm QR create/edit allows selecting subscribers.
- Admin: confirm settings CRUD and activation behavior.
- Reporting job: confirm active settings and subscriptions are respected.
