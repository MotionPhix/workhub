# Verbs MCP Server Instructions

This guide walks you through installing, configuring, and using **Verbs** on your MCP server.

---

## 1. Requirements

Before starting, ensure your server meets the following requirements:

- **Laravel**: version 10 or later
- **PHP**: version 8.1 or later

---

## 2. Install Verbs

Install Verbs on your MCP server via composer:

```bash
composer require hirethunk/verbs
```

## 3. Publish and Run Migrations

Publish the Verbs migrations and run them to set up the required database tables:

```bash
php artisan vendor:publish --tag=verbs-migrations
php artisan migrate
```

## 4. Creating Events

Generate a new event using the Verbs artisan command:

```bash
php artisan verbs:event CustomerBeganTrial
```

This creates a new event file in app/Events.
Update the file to define the properties you need:

```php
class CustomerBeganTrial extends Event
{
    public int $customer_id;
}
```

Note: For real-world usage, use unique IDs instead of simple integers.

## 5. Working with States

States represent the evolving data of your entities, managed over time by events.

Generate a state:

```bash
php artisan verbs:state CustomerState
```

This creates a file in app/States. Example customization:

```php
class CustomerState extends State
{
    public ?Carbon $trial_started_at = null;
}
```


