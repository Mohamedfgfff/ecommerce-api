# استخدم صورة PHP رسمية
FROM php:8.2-apache

# سمح بالوصول للملفات
RUN a2enmod rewrite

# نسخ الكود للسيرفر
COPY . /var/www/html

# تثبيت PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# تشغيل Apache
CMD ["apache2-foreground"]