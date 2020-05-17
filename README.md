# CloudFile-API
CloudFile is a simple file storage solution that can be consumed via an API. Useful for all your projects, CloudFile allows the storage of files (images, videos, texts, applications...) with an public or private access.
## Getting Start
### Installation
```bash
git clone https://github.com/Mediashare/CloudFile-API
cd CloudFile-API
composer update
bin/console doctrine:schema:update --force
chmod -R 777 var
```
You can edit files stockage directory in .env file

```bash
stockage=%kernel.project_dir%/var/stockage
cloudfile_password="MyCloudFilePassword" 
```
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
You can follow the [Documentation](http://doc.cloudfile.tech) for interaction with your CloudFile server.