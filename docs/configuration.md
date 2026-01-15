# Configuration Guide

eXtplorer 3 is highly configurable. This guide covers environment settings and detailed web server configurations.

## 1. Environment Variables (.env)

Copy the `env` file to `.env` to start customizing:
```bash
cp env .env
```

### Essential Settings
| Variable | Description | Recommended (Prod) |
| :--- | :--- | :--- |
| `CI_ENVIRONMENT` | Application mode. | `production` |
| `app.baseURL` | Full URL (with trailing slash). | `https://yourdomain.com/` |
| `app.forceGlobalSecureRequests` | Force HTTPS redirection. | `true` |

## 2. Web Server Configuration

### Option A: Apache 2.4+
Apache is supported out-of-the-box via the included `.htaccess` files.

#### Requirements
*   `mod_rewrite` enabled (`a2enmod rewrite`).
*   `AllowOverride All` set for the directory.

#### Virtual Host Example
```apache
<VirtualHost *:80>
    ServerName files.example.com
    DocumentRoot /var/www/html/extplorer3/public
    
    # Redirect HTTP to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName files.example.com
    DocumentRoot /var/www/html/extplorer3/public
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem

    # IMPORTANT: Point DocumentRoot to 'public/' for security
    <Directory /var/www/html/extplorer3/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/extplorer-error.log
    CustomLog ${APACHE_LOG_DIR}/extplorer-access.log combined
</VirtualHost>
```

#### Troubleshooting Apache
*   **404 on API calls:** Likely `mod_rewrite` is not active or `AllowOverride` is set to `None`.
*   **Permission Denied:** Check file ownership (`chown www-data:www-data`).

### Option B: Nginx + PHP-FPM
Nginx requires manual configuration as it does not read `.htaccess` files.

#### 1. PHP-FPM Pool Configuration
Ensure your PHP-FPM pool is configured correctly.
*   **File:** `/etc/php/8.1/fpm/pool.d/www.conf` (path varies by OS/Version)
*   **Settings:**
    ```ini
    user = www-data
    group = www-data
    listen = /run/php/php8.1-fpm.sock
    listen.owner = www-data
    listen.group = www-data
    pm = dynamic
    pm.max_children = 10
    ```

#### 2. Nginx Server Block
This configuration assumes you have set the root to the `public/` directory, which is the most secure method.

```nginx
server {
    listen 80;
    server_name files.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name files.example.com;
    
    # SSL Configuration (Adjust paths)
    ssl_certificate /etc/ssl/certs/your_cert.crt;
    ssl_certificate_key /etc/ssl/private/your_key.key;
    ssl_protocols TLSv1.2 TLSv1.3;

    # ROOT DIRECTORY: Point to 'public' folder
    root /var/www/html/extplorer3/public;
    index index.php index.html;

    # Access Logs
    access_log /var/log/nginx/extplorer_access.log;
    error_log /var/log/nginx/extplorer_error.log;

    # 1. Main Application Handling
    location / {
        # Tries to serve file directly, fallback to index.php
        try_files $uri $uri/ /index.php?$args;
    }

    # 2. PHP Handling
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        
        # Adjust socket path for your PHP version
        fastcgi_pass unix:/run/php/php8.1-fpm.sock; 
        
        # FastCGI Params
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Timeouts (increase for large file operations/archives)
        fastcgi_read_timeout 300; 
    }

    # 3. Security Hardening
    
    # Deny access to hidden files (e.g., .env, .git)
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Deny access to critical directories if root is misconfigured
    # (Redundant if root is set to /public, but good for safety)
    location ~ ^/(app|writable|vendor|tests|spark|composer\.(json|lock)) {
        deny all;
        return 404;
    }
    
    # 4. Large File Uploads
    # Essential for File Manager functionality
    client_max_body_size 100M; 
}
```

#### Troubleshooting Nginx
*   **"File not found" (404) on PHP files:** Check `fastcgi_param SCRIPT_FILENAME`. It must correctly resolve to the file path on disk.
*   **413 Request Entity Too Large:** Increase `client_max_body_size` in Nginx and `upload_max_filesize` / `post_max_size` in `php.ini`.
*   **504 Gateway Timeout:** Increase `fastcgi_read_timeout` for long operations like creating large ZIP archives.

## 3. PHP Configuration (`php.ini`)

For a File Manager, default PHP settings are often too restrictive.

| Directive | Recommended | Reason |
| :--- | :--- | :--- |
| `memory_limit` | `256M` or `512M` | Handling large images/archives. |
| `upload_max_filesize` | `100M`+ | Allow uploading large files. |
| `post_max_size` | `100M`+ | Must be >= `upload_max_filesize`. |
| `max_execution_time` | `60` or `120` | Prevent timeouts during operations. |
| `max_input_vars` | `3000` | Handling large folder listings in POST. |

After changing these, restart PHP-FPM:
```bash
sudo systemctl restart php8.1-fpm
```
