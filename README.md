Conia CMS/CMF Core
==================


Settings 

    'session.authcookie' => '<app>_auth', // Name of the auth cookie
    'session.expires' => 60 * 60 * 24,    // One day by default


Test database:

    CREATE DATABASE conia_db WITH TEMPLATE = template0 ENCODING = 'UTF8';
    CREATE USER conia_user PASSWORD 'conia_password';
    GRANT ALL PRIVILEGES ON DATABASE conia_db TO conia_user;
    ALTER DATABASE conia_db OWNER TO conia_user;

to allow recreation via command RecreateDb:

    ALTER USER conia_user SUPERUSER;
