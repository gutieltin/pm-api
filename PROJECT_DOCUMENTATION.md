# Project Management API - Complete Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [Technology Stack](#technology-stack)
3. [Project Structure](#project-structure)
4. [Core Models & Relationships](#core-models--relationships)
5. [API Controllers](#api-controllers)
6. [API Routes](#api-routes)
7. [Authentication & Authorization](#authentication--authorization)
8. [Events & Broadcasting](#events--broadcasting)
9. [Database Schema](#database-schema)
10. [Factories & Seeders](#factories--seeders)
11. [Testing](#testing)
12. [Key Features](#key-features)

---

## Project Overview

This is a **Laravel 12 REST API** for project management that allows teams to organize their work through workspaces, projects, tasks, and comments. The system supports role-based access control, real-time updates via WebSockets (Laravel Reverb), and API authentication via Sanctum tokens.

**Key Purpose**: Enable collaborative project management with fine-grained permissions and real-time communication.

---

## Technology Stack

### Backend
- **PHP**: 8.3.26
- **Laravel Framework**: v12
- **Laravel Sanctum**: v4 (API Authentication)
- **Laravel Reverb**: v1 (WebSocket Server)
- **Spatie Permission**: v7.1 (Role & Permission Management)

### Frontend Build
- **Tailwind CSS**: v4
- **Vite**: Module bundler
- **Laravel Echo**: v2 (Real-time Frontend)

### Development & Testing
- **PHPUnit**: v11 (Testing Framework)
- **Laravel Pint**: v1 (Code Formatting)
- **Laravel Pail**: v1 (Log Viewer)
- **Laravel Boost**: v2 (MCP Server)
- **Laravel Sail**: v1 (Docker Environment)

### Database
- **SQLite** / **MySQL** (Configurable via `.env`)

---

## Project Structure

```
project-management--api/
├── app/
│   ├── Events/                 # Real-time event broadcasting
│   │   ├── CommentPosted.php
│   │   ├── TaskCreated.php
│   │   ├── TaskDeleted.php
│   │   └── TaskUpdated.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/           # API Controllers (v1)
│   │   │       ├── AuthController.php
│   │   │       ├── ProjectController.php
│   │   │       ├── TaskController.php
│   │   │       ├── WorkspaceController.php
│   │   │       └── CommentController.php
│   │   └── Resources/         # API Response Resources
│   ├── Models/                # Eloquent Models
│   │   ├── User.php
│   │   ├── Workspace.php
│   │   ├── Project.php
│   │   ├── Task.php
│   │   └── Comment.php
│   ├── Policies/              # Authorization Policies
│   │   ├── UserPolicy.php
│   │   ├── WorkspacePolicy.php
│   │   ├── ProjectPolicy.php
│   │   └── TaskPolicy.php
│   └── Providers/
│       └── AppServiceProvider.php
├── bootstrap/
│   ├── app.php               # Application configuration (middleware, error handling)
│   └── providers.php         # Service provider registration
├── config/                   # Configuration files
├── database/
│   ├── migrations/           # Database schema migrations
│   ├── factories/            # Model factories for testing
│   └── seeders/              # Database seeders
├── public/
│   └── build/               # Vite compiled frontend assets
├── resources/
│   ├── css/
│   │   └── app.css          # Global styles
│   ├── js/                  # Frontend JavaScript
│   └── views/               # Blade templates (if applicable)
├── routes/
│   ├── api.php              # API v1 routes
│   ├── web.php              # Web routes
│   ├── channels.php         # Broadcasting channels
│   └── console.php          # Artisan commands
├── storage/                 # Application storage
├── tests/
│   ├── Feature/             # Feature tests
│   └── Unit/                # Unit tests
├── vendor/                  # Composer dependencies
├── composer.json            # PHP dependencies
├── package.json             # Node dependencies
├── phpunit.xml              # PHPUnit configuration
├── vite.config.js           # Vite configuration
└── artisan                  # Laravel CLI

```

---

## Core Models & Relationships

### User Model
**Location**: `app/Models/User.php`

**Responsibilities**:
- User authentication (extends `Authenticatable`)
- API token management (via Sanctum)
- Role assignments (via Spatie Permission)

**Traits**: `HasFactory`, `Notifiable`, `HasApiTokens`, `HasRoles`

**Key Relationships**:
- `workspaces()` - Belongs to many workspaces (pivot: `workspace_user` with role)
- `ownedWorkspaces()` - Has many workspaces as owner

**Key Methods**:
- `isAdmin()` - Check if user is administrator

**Attributes**:
```php
$fillable = ['name', 'email', 'password'];
$hidden = ['password', 'remember_token'];
```

---

### Workspace Model
**Location**: `app/Models/Workspace.php`

**Purpose**: Top-level container for collaboration. Teams manage projects within workspaces.

**Key Relationships**:
- `users()` - Belongs to many users (pivot: `workspace_user` with role)
- `projects()` - Has many projects
- `owner()` - Belongs to a user (owner)

**Status**: Supports soft deletes (`trashed`)

---

### Project Model
**Location**: `app/Models/Project.php`

**Purpose**: Container for tasks. Projects belong to workspaces.

**Key Relationships**:
- `workspace()` - Belongs to a workspace
- `owner()` - Belongs to a user (project owner)
- `tasks()` - Has many tasks

**Status**: Supports soft deletes

---

### Task Model
**Location**: `app/Models/Task.php`

**Purpose**: Individual work item with status tracking and assignment.

**Key Relationships**:
- `project()` - Belongs to a project
- `creator()` - Belongs to a user (who created it)
- `assignee()` - Belongs to a user (assigned to)
- `comments()` - Has many comments

**Attributes**:
- `status` - Enum: 'to_do', 'in_progress', 'done'
- `due_at` - Nullable datetime
- `description` - Task details

**Status**: Supports soft deletes

---

### Comment Model
**Location**: `app/Models/Comment.php`

**Purpose**: Threaded discussion on tasks.

**Key Relationships**:
- `task()` - Belongs to a task
- `user()` - Belongs to a user (author)

**Broadcasting**: Fires `CommentPosted` event for real-time updates

---

## API Controllers

### AuthController
**Endpoints**:
- `POST /api/v1/login` - User login (returns Sanctum token)
- `POST /api/v1/logout` - User logout
- `GET /api/v1/users` - List all users (admin only)
- `DELETE /api/v1/users/{user}` - Delete user (admin only)

**Key Methods**:
- `login(LoginRequest)` - Authenticate and return token
- `index()` - Get all users (with `viewAny` policy check)
- `destroy(User)` - Delete user (with `delete` policy check)

---

### WorkspaceController
**Endpoints**:
- `GET /api/v1/workspaces` - List user's workspaces
- `POST /api/v1/workspaces` - Create workspace
- `POST /api/v1/workspaces/{workspace}/members` - Add member to workspace
- `DELETE /api/v1/workspaces/{workspace}` - Delete workspace (soft delete)
- `GET /api/v1/workspaces/trashed` - List soft-deleted workspaces
- `POST /api/v1/workspaces/{id}/restore` - Restore deleted workspace

**Key Methods**:
- `index()` - Get authenticateduser's workspaces with project/member counts
- `store(StoreWorkspaceRequest)` - Create workspace and assign creator as admin
- `addMember(Request)` - Add user to workspace with specific role
- `destroy(Workspace)` - Soft delete workspace

---

### ProjectController
**Endpoints** (nested under workspace):
- `GET /api/v1/workspaces/{workspace}/projects` - List workspace projects
- `POST /api/v1/workspaces/{workspace}/projects` - Create project
- `PATCH /api/v1/workspaces/{workspace}/projects/{project}` - Update project
- `DELETE /api/v1/workspaces/{workspace}/projects/{project}` - Delete project
- `GET /api/v1/workspaces/{workspace}/projects/trashed` - List soft-deleted
- `POST /api/v1/workspaces/{workspace}/projects/{id}/restore` - Restore project

**Authorization**: Uses `ProjectPolicy` for create/update/delete

---

### TaskController
**Endpoints**:
- `GET /api/v1/projects/{project}/tasks` - List project tasks
- `POST /api/v1/projects/{project}/tasks` - Create task
- `GET /api/v1/tasks/{task}` - Get single task
- `PATCH /api/v1/tasks/{task}` - Update task
- `DELETE /api/v1/tasks/{task}` - Soft delete task
- `POST /api/v1/tasks/{id}/restore` - Restore task
- `DELETE /api/v1/tasks/{id}/force` - Permanent delete

**Key Events**: Fires `TaskCreated`, `TaskUpdated`, `TaskDeleted` for real-time updates

**Validation**: 
- Assignee must exist: `exists:users,id`
- Status must be valid enum value

---

### CommentController
**Endpoints**:
- `POST /api/v1/tasks/{task}/comments` - Create comment
- `GET /api/v1/tasks/{task}/comments` - List task comments

**Broadcasting**: Fires `CommentPosted` event for real-time updates

---

## API Routes

### Route Groups

**Public Routes** (Rate Limited: 5 per minute):
```php
POST /api/v1/login
```

**Protected Routes** (Require `auth:sanctum` token, Rate Limited: 60 per minute):
```
Workspaces:
  GET    /api/v1/workspaces
  POST   /api/v1/workspaces
  POST   /api/v1/workspaces/{workspace}/members
  DELETE /api/v1/workspaces/{workspace}
  GET    /api/v1/workspaces/trashed
  POST   /api/v1/workspaces/{id}/restore

Projects (workspace-prefixed):
  GET    /api/v1/workspaces/{workspace}/projects
  POST   /api/v1/workspaces/{workspace}/projects
  PATCH  /api/v1/workspaces/{workspace}/projects/{project}
  DELETE /api/v1/workspaces/{workspace}/projects/{project}
  GET    /api/v1/workspaces/{workspace}/projects/trashed
  POST   /api/v1/workspaces/{workspace}/projects/{id}/restore

Tasks:
  GET    /api/v1/projects/{project}/tasks
  POST   /api/v1/projects/{project}/tasks
  GET    /api/v1/tasks/{task}
  PATCH  /api/v1/tasks/{task}
  DELETE /api/v1/tasks/{task}
  POST   /api/v1/tasks/{id}/restore
  DELETE /api/v1/tasks/{id}/force

Comments:
  POST   /api/v1/tasks/{task}/comments
  GET    /api/v1/tasks/{task}/comments

User/Profile:
  GET    /api/v1/me (get authenticated user)
  GET    /api/v1/users (admin only)
  DELETE /api/v1/users/{user} (admin only)
  POST   /api/v1/logout
```

---

## Authentication & Authorization

### Authentication (Sanctum)

**Flow**:
1. User sends credentials to `POST /api/v1/login`
2. Server validates and returns API token
3. Client includes token in `Authorization: Bearer {token}` header
4. Server authenticates via `auth:sanctum` middleware

**Implementation**: 
- Tokens stored in `personal_access_tokens` table
- Token validity controlled by `.env` `SANCTUM_EXPIRATION`

---

### Authorization (Policies)

**Policies** protect resources:

#### UserPolicy
- `viewAny(User)` - Only admins can list all users
- `delete(User, User)` - Only admins can delete (not themselves)

#### WorkspacePolicy
- `view(User, Workspace)` - User must belong to workspace
- `update(User, Workspace)` - Only owner can update
- `delete(User, Workspace)` - Only owner can delete
- `addMember(User, Workspace)` - Only owner can add members

#### ProjectPolicy
- `create(User, Workspace)` - User must belong to workspace
- `update(User, Project)` - Only owner or admin can update
- `delete(User, Project)` - Only owner or admin can delete

#### TaskPolicy
- `create(User, Project)` - User must be in workspace
- `update(User, Task)` - Creator or assignee can update
- `delete(User, Task)` - Creator can delete

**Integration**: Policies are checked via `@can` blade directives or `authorize()` in controllers

---

## Events & Broadcasting

### Real-Time Events

Events enable real-time updates via WebSockets (Laravel Reverb):

#### TaskCreated
**Location**: `app/Events/TaskCreated.php`
- Fired when task is created
- Broadcasts to workspace channel
- Updates all connected clients

#### TaskUpdated
**Location**: `app/Events/TaskUpdated.php`
- Fired when task is modified
- Broadcasts task changes

#### TaskDeleted
**Location**: `app/Events/TaskDeleted.php`
- Fired when task is deleted
- Notifies team of removal

#### CommentPosted
**Location**: `app/Events/CommentPosted.php`
- Fired when comment is added
- Real-time comment delivery

### Broadcasting Channels

**Location**: `routes/channels.php`

- Private channels for workspaces
- Only members can listen

**Frontend Usage** (via Laravel Echo):
```javascript
Echo.private(`workspace.${workspaceId}`)
  .listen('TaskCreated', (event) => {
    // Update UI
  });
```

---

## Database Schema

### Tables

#### users
```
id (PK)
name
email (UNIQUE)
email_verified_at (nullable)
password
remember_token
role (added via migration 2026_02_22_202239)
must_reset_password (boolean, default: false)
created_at, updated_at
```

#### workspaces
```
id (PK)
name
description
owner_id (FK → users)
created_at, updated_at, deleted_at (soft deletes)
```

#### workspace_user (Pivot)
```
id (PK)
workspace_id (FK → workspaces)
user_id (FK → users)
role (enum: 'admin', 'manager', 'employee')
created_at, updated_at
```

#### projects
```
id (PK)
workspace_id (FK → workspaces)
owner_id (FK → users)
name
description
status
created_at, updated_at, deleted_at
```

#### tasks
```
id (PK)
project_id (FK → projects)
creator_id (FK → users, nullable)
assignee_id (FK → users, nullable)
title
description
status (enum: 'to_do', 'in_progress', 'done')
due_at (nullable datetime)
created_at, updated_at, deleted_at
```

#### comments
```
id (PK)
task_id (FK → tasks)
user_id (FK → users)
content
created_at, updated_at, deleted_at
```

#### personal_access_tokens
```
id (PK)
tokenable_type
tokenable_id
name
token (hashed)
abilities (JSON)
last_used_at (nullable)
created_at, updated_at
```

#### roles (Spatie Permission)
```
id (PK)
name (e.g., 'admin', 'editor')
guard_name
created_at, updated_at
```

#### permissions (Spatie Permission)
```
id (PK)
name (e.g., 'create posts')
guard_name
created_at, updated_at
```

#### model_has_roles (Spatie Permission Pivot)
#### role_has_permissions (Spatie Permission Pivot)
#### model_has_permissions (Spatie Permission Pivot)

---

## Factories & Seeders

### Factories

Located in `database/factories/`:

#### UserFactory
Generates test users with:
- Unique email
- Hashed password
- Random name

#### WorkspaceFactory
Generates workspaces with:
- Random name/description
- Owner assigned

#### ProjectFactory
Generates projects with:
- Workspace associations
- Owner assignments

#### TaskFactory
Generates tasks with:
- Random status
- Optional due dates
- Creator/assignee assignments

### Seeders

#### RoleSeeder
- Creates default roles: `admin`, `manager`, `employee`
- Defines permissions: `create projects`, `delete users`, etc.

#### AdminUserSeeder
- Creates admin user for development

#### CorporateSeeder
- Comprehensive seeder for demo data
- Creates workspaces, projects, tasks with realistic hierarchy

#### WorkshopProjectTaskSeeder
- Alternative seeder for workshop/training scenarios

---

## Testing

### Test Structure

```
tests/
├── Feature/
│   ├── TaskControllerTest.php
│   ├── ProjectControllerTest.php
│   ├── UserListingTest.php
│   └── [Other Feature Tests]
└── Unit/
    └── [Unit Tests]
```

### Running Tests

**All tests**:
```bash
php artisan test --compact
```

**Specific file**:
```bash
php artisan test --compact tests/Feature/TaskControllerTest.php
```

**Single test**:
```bash
php artisan test --compact --filter=testMethodName
```

### Test Patterns

- Inherit from `Tests\TestCase`
- Use `RefreshDatabase` trait for isolation
- Seed roles with `RoleSeeder`
- Create users with factories
- Assert HTTP responses and database state

**Example**:
```php
public function test_admin_can_list_users()
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/users');
    
    $response->assertStatus(200);
}
```

---

## Key Features

### 1. Multi-Tenant Workspaces
- Isolation between teams
- Hierarchical: Workspace > Projects > Tasks
- Role-based access within workspaces

### 2. Task Management
- Full CRUD operations
- Status tracking (To Do, In Progress, Done)
- Assignments and due dates
- Comment threads
- Event broadcasting for real-time updates

### 3. Real-Time Collaboration
- Laravel Reverb for WebSockets
- Event broadcasting (task/comment changes)
- Instant client updates via Laravel Echo
- Private channels per workspace

### 4. User Management
- Registration & authentication
- Sanctum API tokens
- Role-based access control (admin, manager, employee)
- Spatie Permission for fine-grained permissions

### 5. Soft Deletes
- Workspaces, projects, and tasks are soft-deleted
- Restoration endpoints available
- Force delete for permanent removal

### 6. API Rate Limiting
- Login: 5 requests per minute
- API: 60 requests per minute
- Configurable via middleware

### 7. Policy-Based Authorization
- Resource-level access control
- Blade integration for frontend checks
- middleware support for routes

---

## Configuration Files

### Bootstrap Configuration

**`bootstrap/app.php`**:
- Middleware registration (global, web, API)
- Exception handling (custom error responses)
- Routing file registration

**`bootstrap/providers.php`**:
- Service provider registration
- Auto-discovery of providers

### Key Config Files

**`config/auth.php`**:
- Guards: `web`, `sanctum`
- Providers: User model
- Password reset

**`config/broadcasting.php`**:
- Reverb driver for WebSockets
- Channel authorization

**`config/sanctum.php`**:
- Token expiration
- Stateful domains
- Middleware configuration

**`config/permission.php`**:
- Spatie Permission configuration
- Role/permission cache settings

---

## Common Development Tasks

### Create New API Resource
```bash
php artisan make:model NewResource --all
```

### Create Policy
```bash
php artisan make:policy NewResourcePolicy --model=NewResource
```

### Create Test
```bash
php artisan make:test NewResourceTest
php artisan make:test NewResourceTest --unit
```

### Format Code
```bash
vendor/bin/pint --dirty
```

### Run Migrations
```bash
php artisan migrate
php artisan migrate:fresh --seed
```

### Watch Frontend
```bash
npm run dev
```

---

## Environment Setup

### Required Environment Variables

```env
APP_NAME=ProjectManagementAPI
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DATABASE_CONNECTION=sqlite
DATABASE_URL=

SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000
SANCTUM_EXPIRATION=50

REVERB_HOST=127.0.0.1
REVERB_PORT=8080
```

### First-Time Setup

```bash
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
```

---

## Performance Considerations

1. **Eager Loading**: Controllers use `with()` to load relationships
2. **Pagination**: List endpoints can be paginated
3. **Rate Limiting**: Prevents API abuse
4. **Caching**: Roles/permissions are cached by Spatie
5. **Indexing**: Foreign keys are indexed for fast joins

---

## Security Notes

- Passwords are hashed with `bcrypt`
- CSRF protection on web routes
- API tokens are hashed in database
- Policies enforce authorization
- Input validation on all endpoints
- Rate limiting prevents brute force

---

## Troubleshooting

### "Undefined property: User::\$role"
- Ensure migration `2026_02_22_202239_add_role_to_users_table` has run
- Check that `$role` attribute is added to fillable if accessing via constructor

### WebSocket Connection Issues
- Verify Reverb is running: `php artisan reverb:start`
- Check `REVERB_HOST` and `REVERB_PORT` in `.env`
- Ensure firewall allows port 8080

### Token Expiration
- Check `SANCTUM_EXPIRATION` setting
- Clear expired tokens: `php artisan model:prune`

### 403 Forbidden on Protected Routes
- Verify user has required role via policy
- Check workspace membership
- Confirm Sanctum token is valid

---

## References

- [Laravel 12 Documentation](https://laravel.com/docs)
- [Sanctum Authentication](https://laravel.com/docs/sanctum)
- [Laravel Reverb WebSockets](https://laravel.com/docs/reverb)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
- [PHPUnit Testing](https://phpunit.de/)

---

**Last Updated**: February 26, 2026  
**Application Version**: 1.0.0  
**Laravel Version**: 12.0
