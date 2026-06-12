# Clapstock API

Symfony API for the Clapstock SwiftUI app. It runs with FrankenPHP, MySQL, Mercure and a local S3-compatible service provided by SeaweedFS.

## Local Stack

```bash
docker compose up --build
docker compose exec php composer install
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

The API is exposed on `http://localhost:8000`.

Local services:

- MySQL: `localhost:3306`, database `clapstock`, user `clapstock`, password `clapstock`
- phpMyAdmin: `http://localhost:8080`, user `clapstock`, password `clapstock`
- SeaweedFS S3: `http://localhost:8333`, bucket `clapstock-local`
- S3 Manager UI: `http://localhost:8081`, instance `SeaweedFS`, credentials `test` / `test`
- Mercure hub: `http://localhost:8000/.well-known/mercure`

## API Contract

All JSON endpoints return structured errors:

```json
{
  "error": {
    "code": "validation_failed",
    "message": "name is required."
  }
}
```

### Create a project

```http
POST /api/projects
Content-Type: application/json

{
  "name": "Tournage A",
  "deviceId": "ios-device-id",
  "displayName": "Simon"
}
```

### Join a project

```http
POST /api/projects/join
Content-Type: application/json

{
  "code": "ABC123",
  "deviceId": "ios-device-id-2",
  "displayName": "Marie"
}
```

### Get project state

```http
GET /api/projects/{code}
```

### Create or update an item

```http
POST /api/projects/{code}/items
PATCH /api/projects/{code}/items/{itemId}
Content-Type: application/json

{
  "deviceId": "ios-device-id",
  "title": "Lampe",
  "description": "Lampe de bureau",
  "buyPrice": "12.50",
  "soldPrice": "20.00",
  "quantity": 1
}
```

`buyPrice` and `soldPrice` are required decimal amounts and are returned as strings.

### Delete an item

```http
DELETE /api/projects/{code}/items/{itemId}
```

### Upload a photo

Each item can have up to 3 photos.

```http
POST /api/projects/{code}/items/{itemId}/photos
Content-Type: multipart/form-data

photo=@/path/to/image.jpg
```

In Postman, open the `Upload photo` request, choose `Body > form-data`, then select a local image file for the `photo` field before sending the request.

### Delete a photo

```http
DELETE /api/projects/{code}/items/{itemId}/photos/{photoId}
```

### Generate the catalog PDF

```http
POST /api/projects/{code}/catalog.pdf
```

The response content type is `application/pdf`.

## Mercure

Subscribe to topic `project/{code}` from SwiftUI through an SSE client. Events include:

- `project.created`
- `participant.joined`
- `item.created`
- `item.updated`
- `item.deleted`
- `photo.created`
- `photo.deleted`

## Tests

```bash
php bin/console lint:container
php bin/console doctrine:schema:validate --skip-sync
php bin/phpunit
composer audit
```
