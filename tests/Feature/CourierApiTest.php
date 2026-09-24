<?php

namespace Tests\Feature;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierApiTest extends TestCase
{
    use RefreshDatabase;

    private function courier(array $overrides = []): Courier
    {
        return Courier::create(array_merge([
            'name' => 'Courier '.fake()->unique()->word(),
            'level' => 1,
            'email' => fake()->unique()->safeEmail(),
            'phone' => '081234567890',
            'is_active' => true,
        ], $overrides));
    }

    public function test_index_is_paginated_and_sorted_by_name_by_default(): void
    {
        $this->courier(['name' => 'Charlie Courier']);
        $this->courier(['name' => 'Alice Courier']);
        $this->courier(['name' => 'Bob Courier']);

        $response = $this->getJson('/api/couriers?per_page=2');

        $response->assertOk()->assertJsonPath('per_page', 2);
        $this->assertSame(['Alice Courier', 'Bob Courier'], collect($response->json('data'))->pluck('name')->all());
    }

    public function test_index_can_search_name_by_multiple_partial_terms(): void
    {
        $this->courier(['name' => 'Budiono Hadi Agung']);
        $this->courier(['name' => 'Budi Santoso']);
        $this->courier(['name' => 'Agung Prasetyo']);

        $response = $this->getJson('/api/couriers?search=budi+agung');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Budiono Hadi Agung');
    }

    public function test_index_can_filter_levels_two_and_three(): void
    {
        $this->courier(['name' => 'Level One', 'level' => 1]);
        $this->courier(['name' => 'Level Two', 'level' => 2]);
        $this->courier(['name' => 'Level Three', 'level' => 3]);
        $this->courier(['name' => 'Level Four', 'level' => 4]);

        $response = $this->getJson('/api/couriers?level=2,3');

        $response->assertOk();
        $this->assertSame([2, 3], collect($response->json('data'))->pluck('level')->sort()->values()->all());
    }

    public function test_created_at_sort_overrides_default_name_sort(): void
    {
        $older = $this->courier(['name' => 'Alpha']);
        $newer = $this->courier(['name' => 'Zulu']);
        Courier::whereKey($older->id)->update(['created_at' => now()->subDay()]);
        Courier::whereKey($newer->id)->update(['created_at' => now()]);

        $response = $this->getJson('/api/couriers?sort=created_at&direction=desc');

        $response->assertOk()->assertJsonPath('data.0.name', 'Zulu')->assertJsonPath('data.1.name', 'Alpha');
    }

    public function test_store_validates_and_persists_courier(): void
    {
        $payload = ['name' => 'Budi Agung', 'level' => 3, 'email' => 'budi@example.com', 'phone' => '0812000000'];

        $this->postJson('/api/couriers', $payload)
            ->assertCreated()
            ->assertJsonPath('name', 'Budi Agung')
            ->assertJsonPath('level', 3);

        $this->assertDatabaseHas('couriers', $payload);
    }

    public function test_store_rejects_invalid_level_and_email(): void
    {
        $this->postJson('/api/couriers', ['name' => 'Invalid', 'level' => 6, 'email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['level', 'email']);
    }

    public function test_show_returns_all_courier_data(): void
    {
        $courier = $this->courier(['name' => 'Detail Courier', 'level' => 4]);

        $this->getJson('/api/couriers/'.$courier->id)
            ->assertOk()
            ->assertJsonPath('id', $courier->id)
            ->assertJsonPath('name', 'Detail Courier')
            ->assertJsonPath('level', 4);
    }

    public function test_update_validates_and_persists_changes(): void
    {
        $courier = $this->courier(['name' => 'Before', 'level' => 2]);

        $this->putJson('/api/couriers/'.$courier->id, ['name' => 'After', 'level' => 5])
            ->assertOk()
            ->assertJsonPath('name', 'After')
            ->assertJsonPath('level', 5);

        $this->assertDatabaseHas('couriers', ['id' => $courier->id, 'name' => 'After', 'level' => 5]);
    }

    public function test_destroy_deletes_courier(): void
    {
        $courier = $this->courier();

        $this->deleteJson('/api/couriers/'.$courier->id)->assertNoContent();

        $this->assertDatabaseMissing('couriers', ['id' => $courier->id]);
    }
}
