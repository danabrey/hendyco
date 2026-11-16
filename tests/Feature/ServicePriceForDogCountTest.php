<?php

namespace Tests\Feature;

use App\Models\Service;
use Tests\TestCase;

class ServicePriceForDogCountTest extends TestCase
{
    public function test_one_dog_is_charged_the_default_price(): void
    {
        $walk = Service::factory()->make(['default_price_pence' => 1650, 'extra_dog_price_pence' => 300]);

        $this->assertSame(1650, $walk->priceForDogCount(1));
    }

    public function test_each_dog_beyond_the_first_adds_the_extra_dog_price(): void
    {
        $walk = Service::factory()->make(['default_price_pence' => 1650, 'extra_dog_price_pence' => 300]);

        $this->assertSame(1950, $walk->priceForDogCount(2));
        $this->assertSame(2250, $walk->priceForDogCount(3));
    }

    public function test_no_dogs_is_charged_the_default_price(): void
    {
        $walk = Service::factory()->make(['default_price_pence' => 1650, 'extra_dog_price_pence' => 300]);

        $this->assertSame(1650, $walk->priceForDogCount(0));
    }
}
