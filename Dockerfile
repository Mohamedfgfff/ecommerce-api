# استخدم صورة PHP الرسمية مع Apache
FROM php:8.2-apache

# تأكد من العمل في مجلد الويب
WORKDIR /var/www/html

# تعطيل أي MPM تعارض، وتفعيل mpm_prefork
# نحن نقوم بحذف ملفات الضبط يدوياً للتأكد من عدم تحميل أكثر من MPM
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# نسخ الكود للسيرفر
COPY . /var/www/html

# تثبيت PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# ضبط صلاحيات المجلدات لتكون قابلة للكتابة من قبل Apache user
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# تحديث Apache للاستماع إلى المنفذ المحدد من قبل Railway (Environment Variable PORT)
# إذا لم يتم تحديد PORT، سيتم استخدام المنفذ 80 كاحتياطي
CMD sed -i "s/Listen 80/Listen ${PORT:-80}/g" /etc/apache2/ports.conf && sed -i "s/:80/:${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf && docker-php-entrypoint apache2-foreground
