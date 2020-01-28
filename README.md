# CloudFile
Server file system with simple API.
## Getting Start
### Installation
```bash
git clone https://github.com/Mediashare/CloudFile
cd CloudFile
composer install
php -S localhost:8000 -t public/
```
### Usages
#### Upload file(s)
```bash
curl \
  -F "file=@/home/user1/Desktop/image1.jpg" \
  -F "file2=@/home/user1/Desktop/image2.jpg" \
  localhost:8000/upload
```
#### Api endpoint
* ``/`` List file(s)
* ``/upload`` Upload file
* ``/show/{id}`` Show file
* ``/download/{id}`` Download file
* ``/remove/{id}`` Remove file