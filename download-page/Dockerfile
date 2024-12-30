# Build stage
FROM node:18-alpine as build
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .

# Set environment variables for the build
ENV VITE_API_URL=https://katze.app/api
ENV VITE_WEBSOCKET_HOST=wss://soketi.katze.app

RUN npm run build

# Production stage
FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
COPY --from=build /app/public/android /usr/share/nginx/html/android

# Add nginx configuration
RUN echo 'server {\
    listen 80;\
    location / {\
        root /usr/share/nginx/html;\
        try_files $uri $uri/ /index.html;\
    }\
}' > /etc/nginx/conf.d/default.conf

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
