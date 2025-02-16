<?php

declare(strict_types=1);


use App\Models\Article;
use App\Models\Author;
use App\Models\Category;

test('retrieves a list of articles', function (): void {
    $author = Author::factory()->create();
    $category = Category::factory()->create();
    Article::factory()->count(3)->hasAttached($author)->hasAttached($category)->create();

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

test('filters articles by author and categories', function (): void {
    $author = Author::factory()->create(['name' => 'Author 1']);
    $category = Category::factory()->create(['name' => 'Category 1']);
    Article::factory()->count(3)->hasAttached($author)->hasAttached($category)->create();

    $response = $this->getJson('/api/articles?filter[authors.name]=Author 1&filter[categories.name]=Category 1&include=authors,categories');

    $response->assertOk()
        ->assertJsonCount(3, 'data.data')
        ->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => ['id', 'title', 'url', 'source', 'content', 'published_at', 'authors', 'categories']
                ]
            ],
            'message'
        ])
        ->assertJsonFragment([
            'categories' => [['id' => 1, 'name' => 'Category 1']],
            'authors' => [['id' => 1, 'name' => 'Author 1']],
        ]);
});
