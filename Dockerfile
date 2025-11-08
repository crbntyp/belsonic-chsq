# Use PHP 8.1 with Apache
FROM php:8.1-cli

# Install Node.js for building assets
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install Node dependencies
RUN npm install

# Copy source files
COPY . .

# Build the project
RUN mkdir -p dist && \
    npm run build && \
    mkdir -p dist/img/uploads/venues dist/img/uploads/logos && \
    cp database/config.railway.php dist/includes/config.php && \
    cp -r database dist/ && \
    cp production-backup.sql dist/ 2>/dev/null || true

# Expose port
EXPOSE 8080

# Start PHP server
CMD php -S 0.0.0.0:${PORT:-8080} -t dist
