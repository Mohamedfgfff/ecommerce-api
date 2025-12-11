# استخدم صورة PHP الرسمية مع Apache
FROM php:8.2-apache

# تأكد من العمل في مجلد الويب
WORKDIR /var/www/html

# تعطيل أي MPM ممكن يكون مفعّل، وتفعيل mpm_prefork (مناسب مع mod_php)
# ثم تفعيل إعادة كتابة الروابط (rewrite)
RUN a2dismod mpm_event || true \
    && a2enmod mpm_prefork || true \
    && a2enmod rewrite

# نسخ الكود للسيرفر
COPY . /var/www/html

# تثبيت PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# ضبط صلاحيات المجلدات لتكون قابلة للكتابة من قبل Apache user
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# تشغيل Apache في foreground (الافتراضي في الصورة)
CMD ["apache2-foreground"]
