FROM php:7.4-cli-bullseye

# Install dependencies
RUN apt-get update -y && apt-get upgrade -y
RUN apt-get install -y git unzip libzip-dev

# Install required PHP extensions
RUN docker-php-ext-install -j$(nproc) zip

# Install composer
COPY .docker/composer.sh .
RUN chmod +x ./composer.sh
RUN ./composer.sh
RUN rm ./composer.sh

# Copy application source code
COPY . /

# Install application dependencies
RUN ["php", "composer.phar", "install", "--no-progress", "--no-plugins", "--no-scripts"]

# Run
ENTRYPOINT ["php", "composer.phar", "run-script", "lookup"]
