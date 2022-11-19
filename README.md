# 🪞 Reflect REST API

Reflect is a framework for building REST APIs over HTTP and UNIX sockets in PHP. 
It handles authentication and request routing so you can put more attention towards building great endpoints.

> **Note**: This repo contains only the framework and no actual endpoints.

Check out the [Reflect wiki](https://github.com/VictorWesterlund/reflect/wiki) and the [Get Started guide](https://github.com/VictorWesterlund/reflect/wiki/Get-Started) for how to use this framework.

## 📥 Installation

To install Reflect you need the following prerequisites:

* MariaDB 15.1+
* PHP 8.1+ (preferably PHP-FPM) with the following modules:
   - `php-common`
   - `php-intl`
   - `php-mbstring`
   - `php-curl`
   - `php-mysqli`
   - `php-sqlite`
   
Assuming you have all that set up, here's how to get Reflect up and running on Debian-based systems:

1. **Clone this repo**

   ```
   $ git clone https://github.com/viwes/reflect
   ```
   
2. **Set environment variabes**
   
   Copy the `.env.example.ini` file to `.env.ini` and update values as needed.
   
3. **Import database**

   Download `db.sql` from the releases page and import it into a MariaDB database.
   
3. **That should be it**

   Follow the guides for HTTP and/or UNIX sockets depending on which (or both) you plan to set up.
   
---
   
### 🌐 Listen on HTTP

Reflect does not come with an executable to spin up a dev server quickly. So the following instruction will apply for your local development too.

Prerequisites specific for HTTP:
* A webserver (preferably NGINX 1.18+)

Then:

1. **Enable HTTP**

   Enable the HTTP endpoint by setting the `http` variable in `.env.ini` to the fully-qualified domain you wish to use.
   
   ```ini
   http = "https://localhost:44301/"
   ```
   
2. **Point webserver root**

   Point the root of a virtual host on your webserver to the `/public` folder in this repo.
  
   ```nginx
   root /path/to/reflect/public;
 
   location ~ /* {
      try_files /index.php =503;
 
      include snippets/fastcgi-php.local.conf; # Contains commented-out "try_files" as we override it here
      fastcgi_pass unix:/run/php/php8.1-fpm.sock;
   } 
   ```

---

### 🐧 Listen on UNIX socket

Reflect can respond to requests over `SOCK_STREAM` by implementing a HTTP-like server/client protocol.

Prerequisites specific for UNIX socket:

* An operating system with support for the `AF_UNIX` socket family.
* PHP 8.1+ CLI (preferrably via PHP-FPM)

Then:

1. **Enable UNIX socket**

   Enable the socket server by setting the `socket` variable in `.env.ini` to an absolute path on disk.
   
   ```ini
   socket = "/run/reflect/api.sock"
   ```
   
2. **Start the socket server**

   From the root of this repo, run:
   ```
   $ php server.php
   ```
   ![image](https://user-images.githubusercontent.com/35688133/201733771-1801be4f-de78-4b10-a819-71a5d4252b92.png)

---
