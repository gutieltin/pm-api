# API Documentation

This document provides an overview of the API routes and database schemas for the Laravel Project Management API.

## API Routes

The following routes are available in the API:

```
  GET|HEAD   / ...............................................................
  POST       api/v1/browser-logs .......................... boost.browser-logs
  POST       api/v1/login ........................... Api\AuthController@login
  POST       api/v1/logout ......................... Api\AuthController@logout
  GET|HEAD   api/v1/me .......................................................
  GET|HEAD   api/v1/projects/{project}/tasks ........ Api\TaskController@index
  POST       api/v1/projects/{project}/tasks ........ Api\TaskController@store
  GET|HEAD   api/v1/tasks/trashed ................. Api\TaskController@trashed
  DELETE     api/v1/tasks/{id}/force .......... Api\TaskController@forceDelete
  POST       api/v1/tasks/{id}/restore ............ Api\TaskController@restore
  PATCH      api/v1/tasks/{task} ................... Api\TaskController@update
  DELETE     api/v1/tasks/{task} .................. Api\TaskController@destroy
  GET|HEAD   api/v1/tasks/{task} ..................... Api\TaskController@show
  POST       api/v1/tasks/{task}/comments ........ Api\CommentController@store
  GET|HEAD   api/v1/tasks/{task}/comments ........ Api\CommentController@index
  GET|HEAD   api/v1/users ........................... Api\AuthController@index
  DELETE     api/v1/users/{user} .................. Api\AuthController@destroy
  GET|HEAD   api/v1/workspaces ................. Api\WorkspaceController@index
  POST       api/v1/workspaces ................. Api\WorkspaceController@store
  GET|HEAD   api/v1/workspaces/trashed ....... Api\WorkspaceController@trashed
  POST       api/v1/workspaces/{id}/restore .. Api\WorkspaceController@restore
  DELETE     api/v1/workspaces/{workspace} ... Api\WorkspaceController@destroy
  POST       api/v1/workspaces/{workspace}/members Api\WorkspaceController@addMember
  GET|HEAD   api/v1/workspaces/{workspace}/projects Api\ProjectController@index
  POST       api/v1/workspaces/{workspace}/projects Api\ProjectController@store
  GET|HEAD   api/v1/workspaces/{workspace}/projects/trashed Api\ProjectController@trashed
  POST       api/v1/workspaces/{workspace}/projects/{id}/restore Api\ProjectController@restore
  PATCH      api/v1/workspaces/{workspace}/projects/{project} Api\ProjectController@update
  DELETE     api/v1/workspaces/{workspace}/projects/{project} Api\ProjectController@destroy
  GET|POST|HEAD broadcasting/auth Illuminate\Broadcasting › BroadcastController@authenticate
  GET|HEAD   sanctum/csrf-cookie sanctum.csrf-cookie › Laravel\Sanctum › CsrfCookieController@show
  GET|HEAD   storage/{path} .................................... storage.local
  GET|HEAD   up ..............................................................
```

## Database Schemas

### Users Table
- id (bigint, primary key)
- name (varchar)
- email (varchar, unique)
- role (varchar, default 'member')
- email_verified_at (timestamp, nullable)
- password (varchar)
- must_reset_password (boolean, default false)
- remember_token (varchar, nullable)
- created_at (timestamp)
- updated_at (timestamp)

### Password Reset Tokens Table
- email (varchar, primary key)
- token (varchar)
- created_at (timestamp, nullable)

### Sessions Table
- id (varchar, primary key)
- user_id (bigint, nullable, foreign key to users)
- ip_address (varchar, nullable)
- user_agent (text, nullable)
- payload (longtext)
- last_activity (int)

### Workspaces Table
- id (bigint, primary key)
- name (varchar)
- slug (varchar, unique)
- owner_id (bigint, foreign key to users)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable) // soft deletes

### Workspace User Table (Pivot)
- id (bigint, primary key)
- workspace_id (bigint, foreign key to workspaces, cascade on delete)
- user_id (bigint, foreign key to users, cascade on delete)
- role (varchar, default 'member')
- created_at (timestamp)
- updated_at (timestamp)

### Projects Table
- id (bigint, primary key)
- workspace_id (bigint, foreign key to workspaces, cascade on delete)
- owner_id (bigint, foreign key to users)
- name (varchar)
- description (text, nullable)
- status (enum: 'active', 'archived', 'completed', default 'active')
- due_date (date, nullable)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable) // soft deletes

### Tasks Table
- id (bigint, primary key)
- project_id (bigint, foreign key to projects, cascade on delete)
- creator_id (bigint, nullable, foreign key to users, null on delete)
- assignee_id (bigint, nullable, foreign key to users, null on delete)
- title (varchar)
- description (text, nullable)
- priority (varchar, default 'medium')
- status (varchar, default 'pending')
- due_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable) // soft deletes

### Comments Table
- id (bigint, primary key)
- task_id (bigint, foreign key to tasks, cascade on delete)
- user_id (bigint, foreign key to users, cascade on delete)
- content (text)
- created_at (timestamp)
- updated_at (timestamp)

### Permissions Table (Spatie Laravel Permission)
- id (bigint, primary key)
- name (varchar)
- guard_name (varchar)
- created_at (timestamp)
- updated_at (timestamp)
- unique: name, guard_name

### Roles Table (Spatie Laravel Permission)
- id (bigint, primary key)
- team_foreign_key (bigint, nullable, if teams enabled)
- name (varchar)
- guard_name (varchar)
- created_at (timestamp)
- updated_at (timestamp)

### Model Has Permissions Table
- permission_id (bigint, foreign key to permissions)
- model_type (varchar)
- model_morph_key (bigint)
- team_foreign_key (bigint, if teams enabled)

### Model Has Roles Table
- role_id (bigint, foreign key to roles)
- model_type (varchar)
- model_morph_key (bigint)
- team_foreign_key (bigint, if teams enabled)

### Role Has Permissions Table
- permission_id (bigint, foreign key to permissions)
- role_id (bigint, foreign key to roles)

### Personal Access Tokens Table (Laravel Sanctum)
- id (bigint, primary key)
- tokenable_type (varchar)
- tokenable_id (bigint)
- name (text)
- token (varchar, 64, unique)
- abilities (text, nullable)
- last_used_at (timestamp, nullable)
- expires_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)