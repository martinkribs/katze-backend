# Katze Backend

This is the backend for the Katze application, built with Laravel and deployed on a K3s cluster.

## Prerequisites

- Docker
- Docker Compose
- kubectl (for K3s cluster management)
- PHP 8.3
- Composer

## Local Development Setup

1. Clone the repository:
    
    ```bash
   git clone https://github.com/ymartinkribs/katze-backend.git
   cd katze-backend
    ```
2. Start the Docker containers:

    ```bash
    docker-compose -f docker-compose.dev.yml up -d
    ```
   
3. Install PHP dependencies:

    ```bash
    cd laravel
    composer install
    ```
   
4. Copy the `.env.example` file to `.env`:

    ```bash
    cp .env.example .env
    ```
   
5. Generate an application key:

    ```bash
    php artisan key:generate
    ```
   
6. Run the migrations:

    ```bash
    php artisan migrate
    ```
7. Seed the database:

    ```bash
    php artisan db:seed
    ```

8. Start the development server:

    ```bash
    php artisan serve
    ```

The application should now be running at `http://localhost:8000`.

## Running Tests

To run the test suite:

```bash
php artisan test
```

## Building for Production

1. Build the Docker image:

    ```bash
    docker build -t katze-backend:latest .
    ```
## Deploying to K3s Cluster

1. Ensure your kubectl is configured to connect to your K3s cluster.

2. Apply the Kubernetes configurations:

    ```bash
    kubectl apply -f kubernetes/
    ```
3. Check the status of your pods:

    ```bash
    kubectl get pods -n katze-backend
    ```

## Environment Variables

Key environment variables are stored in the `environment` directory and are applied during the Docker build process. Ensure these are properly configured for your environment.

## Additional Services

This application relies on several services:

- MySQL
- Redis
- Meilisearch
- Soketi (for WebSockets)

Ensure these services are properly configured in your local and production environments.

## Troubleshooting

If you encounter any issues, please check the logs:

- For local development: `docker-compose logs`
- For K3s deployment: `kubectl logs -n katze-backend <pod-name>`