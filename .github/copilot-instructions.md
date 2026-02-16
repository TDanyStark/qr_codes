Eres experto en SlimPHP v4, React + Vite, Tailwind v4 y MySQL. Prioriza la arquitectura limpia del backend y los patrones ya usados.

# Estructura y arquitectura
- Backend Slim v4 con clean architecture: src/Application, src/Domain, src/Infrastructure.
- Archivos clave de Slim: app/dependencies.php, app/middleware.php, app/repositories.php, app/routes.php, app/settings.php.
- Los repositorios de dominio se mapean a implementaciones en app/repositories.php (p. ej. persistencia DB vs memoria).

# Convenciones de acciones (backend)
- Para cada recurso, crea un Action base (ej: UserAction.php) y acciones especificas que heredan (CreateUserAction.php, ViewUserAction.php, ListUsersAction.php, UpdateUserAction.php, DeleteUserAction.php).
- Los Actions viven en src/Application/Actions/<Entidad>/ y se enlazan en app/routes.php.

# Base de datos
- El esquema MySQL esta en backend/database/qr_codes.sql.
- Tablas principales: users, qrcodes, scans. Respeta las FKs (qrcodes.owner_user_id -> users.id, scans.qrcode_id -> qrcodes.id).

# Frontend
- React + Vite en frontend/; estilos con Tailwind v4.
- Todas las vistas en dark mode, responsive y usando colores de variables en src/styles/global.css.
- Usa Shadcn UI; si falta un componente, instala con npx shadcn@latest add <componente>.

# Flujos y dev
- Backend: composer start (dev) o docker-compose up -d; tests con composer test (ver backend/README.md).
- Frontend: flujo de login en 2 pasos (dev) en src/pages/LoginEmail.tsx y src/pages/LoginCode.tsx; mock en src/lib/api.ts (ver frontend/README.md).
- Logs backend en backend/logs/.