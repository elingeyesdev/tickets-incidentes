# GraphQL API Examples - Helpdesk System

## Endpoint
- **GraphQL API**: `http://localhost:8000/graphql`
- **GraphiQL Playground**: `http://localhost:8000/graphiql`

## Basic Health Check Queries

### 1. Ping Query
Simple ping-pong test to verify the API is working.

```graphql
{
  ping
}
```

**Response:**
```json
{
  "data": {
    "ping": "pong"
  }
}
```

### 2. Version Information
Get API version and system information.

```graphql
{
  version {
    version
    laravel
    environment
    timestamp
  }
}
```

**Response:**
```json
{
  "data": {
    "version": {
      "version": "v1.0.0",
      "laravel": "12.31.1",
      "environment": "local",
      "timestamp": "2025-09-29T15:00:12.380351Z"
    }
  }
}
```

### 3. Health Check
Check the health status of all system services.

```graphql
{
  health {
    service
    status
    details
  }
}
```

**Response:**
```json
{
  "data": {
    "health": [
      {
        "service": "PostgreSQL",
        "status": "healthy",
        "details": "Database connection successful"
      },
      {
        "service": "Redis",
        "status": "healthy",
        "details": "Redis connection successful"
      },
      {
        "service": "Laravel",
        "status": "healthy",
        "details": "Application is running"
      }
    ]
  }
}
```

## Introspection Query
For Apollo Studio / Sandbox schema discovery:

```graphql
{
  __schema {
    types {
      name
      description
    }
  }
}
```

## User Queries (Example from default schema)

### Single User
```graphql
{
  user(id: 1) {
    id
    name
    email
    created_at
  }
}
```

### Multiple Users
```graphql
{
  users(first: 10) {
    data {
      id
      name
      email
    }
    paginatorInfo {
      currentPage
      lastPage
      total
    }
  }
}
```

## Apollo Sandbox Configuration

1. Open Apollo Studio Sandbox: `https://studio.apollographql.com/sandbox`
2. Set endpoint to: `http://localhost:8000/graphql`
3. Enable introspection (should work automatically)
4. Start querying with the examples above

## Local GraphiQL Playground

Visit `http://localhost:8000/graphiql` for the built-in GraphQL playground with:
- Schema explorer
- Query builder
- Documentation
- Query history