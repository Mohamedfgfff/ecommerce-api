# استخدم صورة PHP الرسمية مع Apache
FROM php:8.2-apache

# تأكد من العمل في مجلد الويب
WORKDIR /var/www/html

# تعطيل أي MPM تعارض، وتفعيل mpm_prefork
# نحن نقوم بحذف ملفات الضبط يدوياً للتأكد من عدم تحميل أكثر من MPM
RUN rm -f /etc/apache2/mods-enabled/mpm_event.conf /etc/apache2/mods-enabled/mpm_event.load \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.conf /etc/apache2/mods-enabled/mpm_worker.load \
    && a2enmod mpm_prefork \
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
