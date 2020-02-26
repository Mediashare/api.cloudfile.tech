# CloudFile-API
CloudFile is a simple file storage solution that can be consumed via an API. Useful for all your projects, CloudFile allows the storage of files (images, videos, texts, applications...) in a public or private cloud. 
[Documentation](https://github.com/Mediashare/CloudFile-API/wiki)
## Getting Start
### Installation
```bash
git clone https://github.com/Mediashare/CloudFile-API
cd CloudFile
composer install
bin/console doctrine:schema:update --force
php -S localhost:8000 -t public/
```
### Api endpoint
* ``/api/`` List file(s)
* ``/api/upload`` Upload file(s)
* ``/api/info/{id}`` File informations
* ``/api/show/{id}`` Show file
* ``/api/download/{id}`` Download file
* ``/api/remove/{id}`` Remove file
### Usages
#### Use curl command line tool
```bash
curl \
  -F "file=@/home/user1/Desktop/image1.jpg" \
  -F "file2=@/home/user1/Desktop/image2.jpg" \
  localhost:8000/api/upload
```
#### Use ApiKey for private cloud
```bash
curl \
  -F "file=@/home/user1/Desktop/image1.jpg" \
  -H "ApiKey: xxxxxxx" \
  localhost:8000/api/upload
```
#### Add metadata to file(s)
You can add metadata to file(s) with GET & POST methods.
```bash
curl \
  -F "file=@/home/user1/Desktop/image1.jpg" \
  -F "category=image" \
  localhost:8000/api/upload?foo=bar
```