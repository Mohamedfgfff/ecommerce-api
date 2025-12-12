# استخدم صورة PHP رسمية بدون Apache
FROM php:8.2-cli

# تثبيت pdo_mysql
RUN docker-php-ext-install pdo pdo_mysql

# نسخ الكود
WORKDIR /app
COPY . .

# تشغيل خادم PHP الداخلي على المنفذ المطلوب
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8000}"]