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
    docker-compose up -d
    ```
3. Go to the right directory:

    ```bash
    cd laravel
    ```
   
4. Install PHP dependencies:

    ```bash
    composer install
    ```
   
5. Copy the `.env.example` file to `.env`:

    ```bash
    cp .env.example .env
    ```
   
6. Generate an application key:

    ```bash
    php artisan key:generate
    ```
   
7. Run the migrations:

    ```bash
    php artisan migrate
    ```
8. Seed the database:

    ```bash
    php artisan db:seed
    ```

9. Create a JWT secret:

    ```bash
    php artisan jwt:secret -f
    ```
   
10. Start the development server:

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

2. Create your secrets file from the template:
    ```bash
    cp kubernetes/secrets.template.yml kubernetes/secrets.yml
    ```

3. Edit kubernetes/secrets.yml and fill in your base64-encoded values:
    ```bash
    # For each value you want to encode:
    echo -n "your-actual-value" | base64
    ```
    Or on windows you can encode the secrets on PowerShell via
    ```bash
    [convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes("your-actual-value"))
    ```

   For the dockerconfigjson secret, you can encode your Docker Hub credentials:
    ```bash
    echo -n '{"auths":{"ghcr.io":{"auth":"your-base64-encoded-credentials"}}}' | base64
    ```
   
4. Apply the Kubernetes configurations in the following order:

    a. Create namespace (if not exists):
    ```bash
    kubectl create namespace katze-backend
    ```

    b. Apply secrets first (they're needed by other resources):
    ```bash
    kubectl apply -f kubernetes/secrets.yml
    ```

    c. Apply storage resources:
    ```bash
    kubectl apply -f kubernetes/pvc.yml
    ```

    d. Apply Traefik configuration:
    ```bash
    kubectl apply -f kubernetes/traefik-config.yml
    ```

    e. Apply core services and deployments:
    ```bash
    kubectl apply -f kubernetes/mysql-deployment.yml
    kubectl apply -f kubernetes/redis-deployment.yml
    kubectl apply -f kubernetes/meilisearch-deployment.yml
    kubectl apply -f kubernetes/app-deployment.yml
    kubectl apply -f kubernetes/nginx-deployment.yml
    ```

    f. Apply Mailu deployment:
    ```bash
    kubectl apply -f kubernetes/mailu-deployment.yml
    ```

    g. Finally, apply ingress (after all services are running):
    ```bash
    kubectl apply -f kubernetes/ingress.yml
    ```

5. Check the status of your pods:
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
- Mailu (for Email)

Ensure these services are properly configured in your local and production environments.

## Troubleshooting

If you encounter any issues, please check the logs:

- For local development: `docker-compose logs`
- For K3s deployment: `kubectl logs -n katze-backend <pod-name>`
