# استخدم صورة PHP رسمية
FROM php:8.2-apache

# سمح بالوصول للملفات
RUN a2enmod rewrite

# نسخ الكود للسيرفر
COPY . /var/www/html

# تثبيت PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# ضبط صلاحيات المجلدات لتكون قابلة للكتابة من قبل Apache user
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# تشغيل Apache
CMD ["apache2-foreground"]