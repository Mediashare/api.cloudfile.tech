# CloudFile-API
**[DEPRECATED] Go to [Gitlab Project](https://gitlab.marquand.pro/MarquandT/cloudfile-api)**

CloudFile is a simple file storage solution that can be consumed via an API. Useful for all your projects, CloudFile allows the storage of files (images, videos, texts, applications...) with an public or private access.
## Features
* File Hosting
* Private or Public Volume
* Pretty render file with [ShowContent](https://packagist.org/packages/mediashare/show-content)
* Load balancing of files across multiple hard drives
* Backup with another CloudFile instance

## Getting Start
### Installation
```bash
git clone https://github.com/Mediashare/CloudFile-API
cd CloudFile-API
composer update
chmod -R 777 var
bin/console cloudfile:install \ 
  --username "Admin" \ 
  --password "Admin" \ 
  --disk-name "MobyDisk" \
  --disk-path "${PWD}/var/stockage" \ 
  --backup-host "" \ 
  --backup-apikey "" \ 
  --cloudfile-password "MyCloudFilePassword"
php -S localhost:8000 -t public/
```
To finish the installation go to [http://localhost:8000/install](http://localhost:8000/install)

### Create volume storage
```bash
curl \
  -X POST \
  -F "name=My first volume" \
  -F "size=5" \ # Gb
  -F "cloudfile_password=MyCloudFilePassword" \ # If not blank 
  http://localhost:8000/volume/new
```
## API Documentation
You can follow the [Documentation](https://mediashare.fr/post/api-cloudfile-documentation) for interaction with your CloudFile server.
