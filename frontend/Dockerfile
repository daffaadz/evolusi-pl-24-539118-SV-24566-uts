# ==============================================================================
# STAGE 1: BUILDER
# Build production assets using Node.js
# ==============================================================================
FROM node:22-alpine AS builder

WORKDIR /app

# Copy dependency definition files first for optimal layer caching
COPY package.json package-lock.json ./

RUN npm ci

# Copy all frontend source files
COPY . .

# Pass build arguments for environment variables (defaults to /api for reverse proxy)
ARG VITE_API_BASE_URL=/api
ENV VITE_API_BASE_URL=$VITE_API_BASE_URL

# Build Vite production bundle into /app/dist
RUN npm run build


# ==============================================================================
# STAGE 2: RUNTIME
# Lightweight Nginx web server serving static assets
# ==============================================================================
FROM nginx:1.27-alpine AS runtime

# Copy custom Nginx configuration for Vue SPA routing
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copy compiled static assets from builder stage
COPY --from=builder /app/dist /usr/share/nginx/html

EXPOSE 80

HEALTHCHECK --interval=15s --timeout=3s --retries=3 \
    CMD wget -q --spider http://127.0.0.1/ || exit 1

CMD ["nginx", "-g", "daemon off;"]

