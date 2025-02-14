<?php

declare(strict_types=1);


use App\Models\Article;
use App\Models\Author;

test('retrieves a list of articles', function (): void {
    $author = Author::factory()->create();
    Article::factory()->count(3)->hasAttached($author)->create();

    $response = $this->getJson('/api/articles');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => ['id', 'title', 'url', 'source', 'content', 'published_at']
                ]
            ],
            'message'
        ]);
});

test('filters articles by title', function (): void {
    Article::factory()->create(['title' => 'Laravel Testing']);
    Article::factory()->create(['title' => 'Pest Framework']);

    $response = $this->getJson('/api/articles?filter[title]=Laravel');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonFragment(['title' => 'Laravel Testing']);
});
