# Kanban DnD — Setup & Run (Angular + Symfony)

This guide explains how to install and run the **Angular frontend** and the **Symfony API backend** for a simple Kanban with drag & drop and persistence.

---

## Prerequisites
- **Node.js** (v18+ recommended) and **npm**
- **Angular CLI**: `npm i -g @angular/cli`
- **PHP 8.1+**, **Composer**
- **Database**: MySQL (via XAMPP) **or** SQLite

> If you use MySQL, make sure the MySQL server is started. If you use SQLite, no extra service is needed.

---

## 1) Backend — Symfony API

### 1.1 Create project
```bash
symfony new kanban-api --version=6.4
cd kanban-api
composer require symfony/maker-bundle --dev
composer require symfony/serializer-pack symfony/validator
composer require symfony/orm-pack doctrine/annotations
```

### 1.2 Database configuration
Choose **one** option.

**Option A — MySQL (XAMPP)**
```bash
# .env.local
echo "DATABASE_URL=\"mysql://root:@127.0.0.1:3306/kanban?charset=utf8mb4\"" > .env.local

# create database (if not exists)
php bin/console doctrine:database:create
```

**Option B — SQLite (zero-config)**
```bash
# .env.local
echo "DATABASE_URL=\"sqlite:///%kernel.project_dir%/var/data.db\"" > .env.local
```

### 1.3 Entity & migrations
```bash
php bin/console make:entity Task
# Fields:
# - title: string(255)
# - status: string(10)
# - sortOrder: integer
# - createdAt: datetime_immutable

php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### 1.4 CORS (simple, no Nelmio)
Create `src/EventSubscriber/CorsSubscriber.php`:
```php
<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [ KernelEvents::RESPONSE => 'onResponse' ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $resp = $event->getResponse();
        $resp->headers->set('Access-Control-Allow-Origin', 'http://localhost:4200');
        $resp->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $resp->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
```

Optional preflight route (helps when Angular sends `OPTIONS`):
Create `src/Controller/OptionsController.php`:
```php
<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OptionsController
{
    #[Route('/{path}', name: 'cors_options', requirements: ['path' => '.*'], methods: ['OPTIONS'])]
    public function options(): Response
    {
        return new Response('', 204);
    }
}
```

### 1.5 API Controller
Create `src/Controller/Api/TaskController.php` with CRUD & reorder.
> If you used the version from our chat, you’re good. Remember the DQL fix: compute `MAX(sortOrder)` then add `+1` in PHP.

**Endpoints**
- `GET    /api/tasks` — list tasks
- `POST   /api/tasks` — create task `{ title, status }`
- `PUT    /api/tasks/{id}` — update fields `{ title?, status?, sort_order? }`
- `DELETE /api/tasks/{id}` — delete
- `PATCH  /api/tasks/reorder` — body `{ status, orderedIds: number[] }`

### 1.6 Run backend
```bash
# from kanban-api/
symfony server:start -d
# or
php -S 127.0.0.1:8000 -t public
```

Test quickly:
```bash
curl http://127.0.0.1:8000/api/tasks
```

---

## 2) Frontend — Angular

### 2.1 Create project
```bash
ng new kanban-dnd --style=scss --routing
cd kanban-dnd
npm i @angular/cdk bootstrap
```

### 2.2 Add Bootstrap
Edit `angular.json` and add to `styles`:
```json
"styles": [
  "node_modules/bootstrap/dist/css/bootstrap.min.css",
  "src/styles.scss"
]
```

### 2.3 Standalone bootstrap (HttpClient)
In `src/main.ts`:
```ts
import { bootstrapApplication } from '@angular/platform-browser';
import { provideHttpClient } from '@angular/common/http';
import { AppComponent } from './app/app';

bootstrapApplication(AppComponent, {
  providers: [provideHttpClient()]
});
```

### 2.4 Components (standalone)
- `AppComponent` standalone imports: `CommonModule`, `FormsModule`, `DragDropModule`, and your `BoardComponent`.
- `BoardComponent` standalone imports: `CommonModule`, `FormsModule`, `DragDropModule`.
- Use Angular CDK drag & drop (`CdkDragDrop`, `cdkDropList`, `cdkDrag`, etc.).

### 2.5 Environment
Either call Symfony directly (CORS configured) **or** use Angular proxy.

**Call Symfony directly**
```ts
// src/environments/environment.ts
export const environment = { apiUrl: 'http://127.0.0.1:8000/api' };
```

**Use Angular proxy (avoid CORS)**
Create `proxy.conf.json` at the project root:
```json
{
  "/api": {
    "target": "http://127.0.0.1:8000",
    "secure": false,
    "changeOrigin": true
  }
}
```
Then:
```ts
// src/environments/environment.ts
export const environment = { apiUrl: '/api' };
```
And start dev server with:
```bash
ng serve --proxy-config proxy.conf.json
```

### 2.6 Start frontend
```bash
npm start
# or
ng serve
```

Open: `http://localhost:4200`

---

## 3) Seeding sample data

Run the SQL script provided with this README (see `seed_kanban.sql` in the same folder).  
It contains inserts for the **Doctrine table** `task`. If your table is named `tasks` (legacy PHP backend), an alternate block is included (commented).

**MySQL example**:
```bash
# from your MySQL console
SOURCE /path/to/seed_kanban.sql;
```

**SQLite example**:
```bash
# if using SQLite file var/data.db, use the sqlite3 CLI
sqlite3 var/data.db < /path/to/seed_kanban.sql
```

---

## 4) Troubleshooting

- **CORS blocked**: Use the `CorsSubscriber` above or the Angular proxy.
- **No provider for _HttpClient**: Provide HttpClient in `main.ts` using `provideHttpClient()`.
- **500 on POST /api/tasks** with DQL parse error: remove `+1` from the DQL and do `((int)$max)+1` in PHP.
- **Table/columns errors**: run `make:migration` + `doctrine:migrations:migrate` and keep entity fields: `title`, `status`, `sortOrder`, `createdAt`.

---

## 5) Minimal Task interface (front)
```ts
export interface Task {
  id: number;
  title: string;
  status: 'todo' | 'doing' | 'done';
  sort_order: number;
  created_at?: string;
}
```

Done! You can now drag tasks between columns, persist order, add/remove tasks, and style with Bootstrap.
