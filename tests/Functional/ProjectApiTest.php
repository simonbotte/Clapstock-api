<?php

namespace App\Tests\Functional;

use App\Entity\CatalogItem;
use App\Entity\ItemPhoto;

class ProjectApiTest extends ApiTestCase
{
    public function testCreateProjectRequiresName(): void
    {
        $this->jsonRequest('POST', '/api/projects', [
            'deviceId' => 'device-1',
            'displayName' => 'Simon',
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertSame('validation_failed', $this->jsonResponse()['error']['code']);
    }

    public function testCreateJoinAndShowProject(): void
    {
        $this->jsonRequest('POST', '/api/projects', [
            'name' => 'Tournage A',
            'deviceId' => 'device-1',
            'displayName' => 'Simon',
        ]);

        self::assertResponseStatusCodeSame(201);
        $created = $this->jsonResponse();
        self::assertSame('Tournage A', $created['name']);
        self::assertCount(1, $created['participants']);

        $this->jsonRequest('POST', '/api/projects/join', [
            'code' => $created['code'],
            'deviceId' => 'device-2',
            'displayName' => 'Marie',
        ]);

        self::assertResponseIsSuccessful();
        self::assertCount(2, $this->jsonResponse()['participants']);

        $this->client->request('GET', '/api/projects/'.$created['code']);
        self::assertResponseIsSuccessful();
        self::assertSame('Tournage A', $this->jsonResponse()['name']);
    }

    public function testListProjectsByParticipantDevice(): void
    {
        $this->createProject();
        $secondProject = $this->createProject();

        $this->jsonRequest('POST', '/api/projects/join', [
            'code' => $secondProject['code'],
            'deviceId' => 'device-2',
            'displayName' => 'Marie',
        ]);
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/projects', ['deviceId' => 'device-1']);
        self::assertResponseIsSuccessful();
        self::assertCount(2, $this->jsonResponse());

        $this->client->request('GET', '/api/projects', ['deviceId' => 'device-2']);
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->jsonResponse());
        self::assertSame($secondProject['code'], $this->jsonResponse()[0]['code']);
    }

    public function testCatalogItemCrudWithPrices(): void
    {
        $project = $this->createProject();

        $this->jsonRequest('POST', '/api/projects/'.$project['code'].'/items', [
            'deviceId' => 'device-1',
            'title' => 'Lampe',
            'description' => 'Lampe de bureau',
            'buyPrice' => '12.50',
            'soldPrice' => '20',
            'quantity' => 2,
        ]);

        self::assertResponseStatusCodeSame(201);
        $item = $this->jsonResponse();
        self::assertSame('12.50', $item['buyPrice']);
        self::assertSame('20.00', $item['soldPrice']);

        $this->jsonRequest('PATCH', '/api/projects/'.$project['code'].'/items/'.$item['id'], [
            'title' => 'Lampe vintage',
            'description' => 'Lampe de bureau verte',
            'buyPrice' => '13.00',
            'soldPrice' => '22.00',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('Lampe vintage', $this->jsonResponse()['title']);

        $this->client->request('DELETE', '/api/projects/'.$project['code'].'/items/'.$item['id']);
        self::assertResponseStatusCodeSame(204);
    }

    public function testPhotoLimitIsThree(): void
    {
        $project = $this->createProject();
        $item = $this->createItem($project['code']);
        $catalogItem = $this->entityManager->getRepository(CatalogItem::class)->find($item['id']);
        self::assertInstanceOf(CatalogItem::class, $catalogItem);

        for ($position = 1; $position <= 3; ++$position) {
            $photo = new ItemPhoto(
                $catalogItem,
                'fake-'.$position.'.jpg',
                'fake-'.$position.'-thumb.jpg',
                $position,
                'image/jpeg',
                'image/jpeg',
                100,
                50,
                1200,
                800,
                600,
                400,
            );
            $catalogItem->addPhoto($photo);
            $this->entityManager->persist($photo);
        }
        $this->entityManager->flush();

        $this->client->request('POST', '/api/projects/'.$project['code'].'/items/'.$item['id'].'/photos');

        self::assertResponseStatusCodeSame(400);
        self::assertSame('photo_limit_reached', $this->jsonResponse()['error']['code']);
    }

    public function testCatalogPdfIsGenerated(): void
    {
        $project = $this->createProject();
        $this->createItem($project['code']);

        $this->client->request('POST', '/api/projects/'.$project['code'].'/catalog.pdf');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
        self::assertStringStartsWith('%PDF', (string) $this->client->getResponse()->getContent());
    }

    /** @return array<string, mixed> */
    private function createProject(): array
    {
        $this->jsonRequest('POST', '/api/projects', [
            'name' => 'Tournage Test',
            'deviceId' => 'device-1',
            'displayName' => 'Simon',
        ]);

        self::assertResponseStatusCodeSame(201);

        return $this->jsonResponse();
    }

    /** @return array<string, mixed> */
    private function createItem(string $code): array
    {
        $this->jsonRequest('POST', '/api/projects/'.$code.'/items', [
            'deviceId' => 'device-1',
            'title' => 'Chaise',
            'description' => 'Chaise bois',
            'buyPrice' => '10.00',
            'soldPrice' => '18.00',
        ]);

        self::assertResponseStatusCodeSame(201);

        return $this->jsonResponse();
    }
}
