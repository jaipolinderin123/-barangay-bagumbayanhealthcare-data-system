# Start from a PHP image with Apache
FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install Python and pip
RUN apt-get update && apt-get install -y python3 python3-pip

# Set working directory
WORKDIR /var/www/html

# Copy PHP files
COPY ./php-app/ /var/www/html/

# Copy Python scripts
COPY ./python-scripts/ /python-scripts/

# Install Python dependencies
COPY requirements.txt /
RUN pip3 install -r /requirements.txt

# (Optional) Run clustering on container start
# CMD ["bash", "-c", "python3 /python-scripts/run_all.py && apache2-foreground"]

# Run Apache
CMD ["apache2-foreground"]
