# 📥 Installation

To install Reflect you need the following prerequisites:

* MariaDB 15.1+
* PHP 8.1+ (preferably PHP-FPM)
   
Assuming you have all that set up, here's how to get Reflect up and running:

1. **Clone this repo**

   ```
   git clone https://github.com/victorwesterlund/reflect
   ``` 

2. **Install composer dependencies**

   ```
   composer install
   ```
   
3. **Import database**

   Download `db.sql` from the releases page and import it into a MariaDB database.
   
4. **Set environment variabes**
   
   Make a copy of `.env.example.ini` and call it `.env.ini`.
   
   You can read more about each variable [on the Wiki](#TODO), but these following variables are required to get going
   
   ```ini
   ; Absolute path to the root folder of your Reflect endpoints.
   ; See https://github.com/victorwesterlund/reflect-template for an example
   endpoints = "/path/to/my/api/root"
   
   ; MySQL/MariaDB credentials
   mysql_host = ""
   mysql_user = ""
   mysql_pass = ""
   ; Name of the database you imported db.sql into in the previous step
   mysql_db = "name_of_reflect_database"
   ```
   
5. **HTTP and/or Socket**

   The following guides will explain how to accept requests from either HTTP or UNIX sockets.
   - [HTTP](#http)
   - [UNIX Socket](#unix-socket)
   
# Listen on...
   
## 🌐 HTTP

Reflect does not come with an executable to spin up a dev web server from PHP (yet). So you will need an actual web server to do local development too.

Prerequisites specific for HTTP:
* A webserver (preferably NGINX 1.18+)

Then:
   
1. **Point webserver root**

   Point the root of a virtual host on your webserver to the `/public` folder in this repo. HTTP is enabled by default so this is all you have to do.
  
   ```nginx
   root /path/to/reflect/public;
 
   location ~ /* {
      try_files /index.php =503;
 
      include snippets/fastcgi-php.local.conf; # Contains commented-out "try_files" as we override it here
      fastcgi_pass unix:/run/php/php8.1-fpm.sock;
   } 
   ```

## 🐧 UNIX socket

Reflect can respond to requests over `SOCK_STREAM`.

Prerequisites specific for UNIX socket:

* An operating system with support for the `AF_UNIX` socket family.
* PHP 8.1+ CLI (preferrably via PHP-FPM)

Then:

1. **Enable UNIX socket**

   Enable the socket server by setting the `socket` variable in `.env.ini` to an absolute path on disk.
   
   ```ini
   socket = "/run/reflect/api.sock"
   ```
   
   > **Note** **Your user** (not PHP's default user) needs permission to write files in the directory you specify.
   
2. **Start the socket server**

   From the root of this repo, run:
   ```
   $ php socket listen
   ```

   You should see the following screen when Reflect is ready to accept incoming connections:
   
   ![image](https://github.com/VictorWesterlund/reflect/assets/35688133/d74416ee-5d4c-443b-8db3-242614249fca)

---
