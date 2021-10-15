FROM php:7.4-cli-bullseye

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
