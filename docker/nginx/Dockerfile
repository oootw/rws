FROM node:22-alpine AS frontend

WORKDIR /build

COPY frontend/package.json frontend/package-lock.json ./
COPY frontend/scan/package.json ./scan/
COPY frontend/shared/package.json ./shared/

RUN npm ci --ignore-scripts

COPY frontend/ ./

ARG VITE_ALLOW_TENANT_QUERY=0
ENV VITE_ALLOW_TENANT_QUERY=${VITE_ALLOW_TENANT_QUERY}

RUN npm run build

FROM nginx:1.27-alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=frontend /dist/scan /var/www/frontend/scan
COPY backend/public /var/www/backend/public

EXPOSE 80
