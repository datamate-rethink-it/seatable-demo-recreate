# Use the official PHP image
FROM php:8.2-cli

# Install dependencies required for Composer
RUN apt-get update && apt-get install -y \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory inside the container
WORKDIR /app

# Copy your PHP script and composer.json into the container
COPY . .

# Install Composer dependencies
RUN composer install

# Run your PHP script
CMD ["php", "createOrgsTemplatesPlugins.php"]
